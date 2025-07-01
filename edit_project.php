<?php
// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php'; // Ensure this file properly connects to your database using PDO
require_once 'url_helper.php';

// Check that the user is logged in.
if (!isset($_SESSION['username'])) {
    redirect('login.php');
}

// Get the projectID from GET parameters.
$projectID = isset($_GET['projectID']) ? intval($_GET['projectID']) : 0;
if ($projectID <= 0) {
    die("Invalid Project ID");
}

// Define the ordered list of stages. This should be consistent across index.php and edit_project.php
$stagesOrder = [
    'Purchase Request',
    'RFQ 1',
    'RFQ 2',
    'RFQ 3',
    'Abstract of Quotation',
    'Purchase Order',
    'Notice of Award',
    'Notice to Proceed'
];

// Permission Variables - define early for consistent use
$isAdmin = ($_SESSION['admin'] == 1);
$isProjectCreator = false; // Initialize, will be set after fetching project details


// --- Define the Office List (fetched dynamically) ---
$officeList = [];
try {
    $stmtOffice = $pdo->query("SELECT officeID, officename FROM officeid ORDER BY officename");
    while ($row = $stmtOffice->fetch(PDO::FETCH_ASSOC)) {
        $officeList[$row['officeID']] = $row['officename'];
    }
} catch (PDOException $e) {
    error_log("Error fetching office list: " . $e->getMessage());
    die("Could not retrieve office list. Please try again later.");
}

// --- Get the logged-in user's office details ---
$loggedInUserOfficeID = null;
$loggedInUserOfficeName = "N/A";
if (isset($_SESSION['userID'])) {
    try {
        $stmtUserOffice = $pdo->prepare("SELECT u.officeID, o.officename FROM tbluser u LEFT JOIN officeid o ON u.officeID = o.officeID WHERE u.userID = ?");
        $stmtUserOffice->execute([$_SESSION['userID']]);
        $userOfficeData = $stmtUserOffice->fetch(PDO::FETCH_ASSOC);
        if ($userOfficeData) {
            $loggedInUserOfficeID = $userOfficeData['officeID'];
            $loggedInUserOfficeName = htmlspecialchars($userOfficeData['officeID'] . ' - ' . ($userOfficeData['officename'] ?? 'N/A'));
        }
    } catch (PDOException $e) {
        error_log("Error fetching logged-in user office details: " . $e->getMessage());
        // Gracefully handle if user's office cannot be fetched
    }
}


// --- Function to fetch project details ---
function fetchProjectDetails($pdo, $projectID) {
    $sql = "
        SELECT
            p.*,
            u.firstname AS creator_firstname,
            u.lastname AS creator_lastname,
            o.officename,
            mop.MoPDescription  -- Add this line to select MoPDescription
        FROM tblproject p
        LEFT JOIN tbluser u ON p.userID = u.userID
        LEFT JOIN officeid o ON u.officeID = o.officeID
        LEFT JOIN mode_of_procurement mop ON p.MoPID = mop.MoPID -- Add this line for the JOIN
        WHERE p.projectID = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$projectID]);
    return $stmt->fetch();
}

// --- Function to fetch project stages ---
function fetchProjectStages($pdo, $projectID, $stagesOrder) {
    // This query now expects officeID to be present in tblproject_stages
    $stmt2 = $pdo->prepare("SELECT * FROM tblproject_stages
                             WHERE projectID = ?
                             ORDER BY FIELD(stageName, 'Purchase Request','RFQ 1','RFQ 2','RFQ 3','Abstract of Quotation','Purchase Order','Notice of Award','Notice to Proceed')");
    $stmt2->execute([$projectID]);
    $stages = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // If no stages exist, create records for every stage.
    if (empty($stages)) {
        foreach ($stagesOrder as $stageName) {
            $insertCreatedAt = null;
            if ($stageName === 'Purchase Request') {
                $insertCreatedAt = date("Y-m-d H:i:s");
            }
            // Initialize officeID as NULL when creating new stages
            $stmtInsert = $pdo->prepare("INSERT INTO tblproject_stages (projectID, stageName, officeID, createdAt) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$projectID, $stageName, null, $insertCreatedAt]);
        }
        // Re-fetch stages after creation
        $stmt2->execute([$projectID]);
        $stages = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    return $stages;
}

// --- Initial Data Fetch ---
$project = fetchProjectDetails($pdo, $projectID);
if (!$project) {
    die("Project not found");
}
$isProjectCreator = ($project['userID'] == $_SESSION['userID']);

$stages = fetchProjectStages($pdo, $projectID, $stagesOrder);

// Map stages by stageName for easy access and find the last submitted stage.
$stagesMap = [];
$noticeToProceedSubmitted = false;
$lastSubmittedStageIndex = -1;

foreach ($stages as $index => $s) {
    $stagesMap[$s['stageName']] = $s;
    if ($s['isSubmitted'] == 1) {
        $stageIndexInOrder = array_search($s['stageName'], $stagesOrder);
        if ($stageIndexInOrder !== false && $stageIndexInOrder > $lastSubmittedStageIndex) {
            $lastSubmittedStageIndex = $stageIndexInOrder;
        }
    }
    if ($s['stageName'] === 'Notice to Proceed' && $s['isSubmitted'] == 1) {
        $noticeToProceedSubmitted = true;
    }
}
$lastSubmittedStageName = ($lastSubmittedStageIndex !== -1) ? $stagesOrder[$lastSubmittedStageIndex] : null;


// Process Project Header update (available ONLY for admins).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project_header'])) {
    if ($isAdmin) {
        $prNumber = trim($_POST['prNumber']);
        $projectDetails = trim($_POST['projectDetails']);
        if (empty($prNumber) || empty($projectDetails)) {
            $errorHeader = "PR Number and Project Details are required.";
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE tblproject
                                             SET prNumber = ?, projectDetails = ?, editedAt = CURRENT_TIMESTAMP, editedBy = ?, lastAccessedAt = CURRENT_TIMESTAMP, lastAccessedBy = ?
                                             WHERE projectID = ?");
            $stmtUpdate->execute([$prNumber, $projectDetails, $_SESSION['userID'], $_SESSION['userID'], $projectID]);

            $successHeader = "Project details updated successfully.";
            $project = fetchProjectDetails($pdo, $projectID);
            $stages = fetchProjectStages($pdo, $projectID, $stagesOrder);
        }
    } else {
        $errorHeader = "You do not have permission to update project details.";
    }
}

// Process individual stage submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_stage'])) {
    $stageName = $_POST['stageName'];
    $safeStage = str_replace(' ', '_', $stageName);

    $currentStageDataForPost = $stagesMap[$stageName] ?? null;
    $currentIsSubmittedInDB = ($currentStageDataForPost && $currentStageDataForPost['isSubmitted'] == 1);

    $formCreated = isset($_POST["created_$safeStage"]) && !empty($_POST["created_$safeStage"]) ? $_POST["created_$safeStage"] : null;
    $approvedAt = isset($_POST['approvedAt']) && !empty($_POST['approvedAt']) ? $_POST['approvedAt'] : null;
    $remark = isset($_POST['remark']) ? trim($_POST['remark']) : "";

    // Determine if this is a "Submit" or "Unsubmit" action
    $isSubmittedVal = 1; // Default to submit
    if ($isAdmin && $currentIsSubmittedInDB && $stageName === $lastSubmittedStageName) {
        // If admin is unsubmitting, and it's the last submitted stage, set isSubmitted to 0
        $isSubmittedVal = 0;
    }

    // --- Validation Logic ---
    $validationFailed = false;
    if ($isSubmittedVal == 1) { // Only validate on "Submit" action
        // 'Approved' and 'Remark' are always required for submission
        if (empty($approvedAt)) {
            $validationFailed = true;
        }
        // 'Created' is required for admin submission (except PR)
        if ($isAdmin && $stageName !== 'Purchase Request' && empty($formCreated)) {
            $validationFailed = true;
        }
        // No validation needed for 'Office' as it's automatically filled by user's officeID on backend
    }

    if ($validationFailed) {
        $stageError = "All required fields (Approved and Remark" . ($isAdmin && $stageName !== 'Purchase Request' ? ", Created" : "") . ") must be filled for stage '$stageName' to be submitted.";
    } else {
        // Determine the office ID to save: it's the logged-in user's office ID for submission
        // This is the core change: It will always use the submitting user's office ID.
        $officeIDToSave = $loggedInUserOfficeID;

        // Prepare createdAt for update:
        $currentCreatedAtInDB = $currentStageDataForPost['createdAt'] ?? null;
        $actualCreatedAt = $currentCreatedAtInDB;

        if ($isAdmin) {
            if ($stageName !== 'Purchase Request' && !empty($formCreated)) {
                $actualCreatedAt = $formCreated;
            } else if ($isSubmittedVal == 1 && empty($currentCreatedAtInDB) && $stageName !== 'Purchase Request') {
                $actualCreatedAt = date("Y-m-d H:i:s");
            }
        } else {
            if ($isSubmittedVal == 1 && empty($currentCreatedAtInDB)) {
                $actualCreatedAt = date("Y-m-d H:i:s");
            }
        }

        // Convert datetime-local values ("Y-m-d\TH:i") to MySQL datetime ("Y-m-d H:i:s").
        $created_dt = $actualCreatedAt ? date("Y-m-d H:i:s", strtotime($actualCreatedAt)) : null;

        // If unsubmitting, clear approvedAt, officeID, and remarks
        if ($isSubmittedVal == 0) {
            $approved_dt = null;
            $officeIDToSave = null; // Set officeID to null on unsubmit
            $remark = "";
        } else {
            $approved_dt = $approvedAt ? date("Y-m-d H:i:s", strtotime($approvedAt)) : null;
        }

        // Updated SQL to use officeID
        $stmtStageUpdate = $pdo->prepare("UPDATE tblproject_stages
                                               SET createdAt = ?, approvedAt = ?, officeID = ?, remarks = ?, isSubmitted = ?
                                               WHERE projectID = ? AND stageName = ?");
        $stmtStageUpdate->execute([$created_dt, $approved_dt, $officeIDToSave, $remark, $isSubmittedVal, $projectID, $stageName]);
        $stageSuccess = "Stage '$stageName' updated successfully.";

        // If this is a "Submit" action, auto-update the next stage's createdAt if empty.
        if ($isSubmittedVal == 1) {
            $index = array_search($stageName, $stagesOrder);
            if ($index !== false && $index < count($stagesOrder) - 1) {
                $nextStageName = $stagesOrder[$index + 1];
                if (!(isset($stagesMap[$nextStageName]) && !empty($stagesMap[$nextStageName]['createdAt']))) {
                    $now = date("Y-m-d H:i:s");
                    $stmtNext = $pdo->prepare("UPDATE tblproject_stages SET createdAt = ? WHERE projectID = ? AND stageName = ?");
                    $stmtNext->execute([$now, $projectID, $nextStageName]);
                }
            }
        }

        // Update editedAt/editedBy for tblproject
        $pdo->prepare("UPDATE tblproject SET editedAt = CURRENT_TIMESTAMP, editedBy = ?, lastAccessedAt = CURRENT_TIMESTAMP, lastAccessedBy = ? WHERE projectID = ?")
            ->execute([$_SESSION['userID'], $_SESSION['userID'], $projectID]);

        // Re-fetch ALL data immediately after stage update/submission
        $project = fetchProjectDetails($pdo, $projectID);
        $stages = fetchProjectStages($pdo, $projectID, $stagesOrder);

        // Re-map stages after re-fetching to ensure latest status is used for rendering
        $stagesMap = [];
        $noticeToProceedSubmitted = false;
        $lastSubmittedStageIndex = -1;
        foreach ($stages as $index => $s) {
            $stagesMap[$s['stageName']] = $s;
            if ($s['isSubmitted'] == 1) {
                $stageIndexInOrder = array_search($s['stageName'], $stagesOrder);
                if ($stageIndexInOrder !== false && $stageIndexInOrder > $lastSubmittedStageIndex) {
                    $lastSubmittedStageIndex = $stageIndexInOrder;
                }
            }
            if ($s['stageName'] === 'Notice to Proceed' && $s['isSubmitted'] == 1) {
                $noticeToProceedSubmitted = true;
            }
        }
        $lastSubmittedStageName = ($lastSubmittedStageIndex !== -1) ? $stagesOrder[$lastSubmittedStageIndex] : null;
    }
}

// --- Pre-fetch names for display: Edited By ---
$editedByName = "N/A";
if (!empty($project['editedBy'])) {
    $stmtUser = $pdo->prepare("SELECT firstname, lastname FROM tbluser WHERE userID = ?");
    $stmtUser->execute([$project['editedBy']]);
    $user = $stmtUser->fetch();
    if ($user) {
        $editedByName = htmlspecialchars($user['firstname'] . " " . $user['lastname']);
    }
}

// --- Determine the "Next Unsubmitted Stage" for strict sequential access ---
// This is used throughout the page for determining which stage can be submitted
$firstUnsubmittedStageName = null;
foreach ($stagesOrder as $stage) {
    if (isset($stagesMap[$stage]) && $stagesMap[$stage]['isSubmitted'] == 0) {
        $firstUnsubmittedStageName = $stage;
        break;
    }
}
include 'view/edit_project_content.php';
?>