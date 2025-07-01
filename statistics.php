<?php
// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'config.php'; // Ensure your PDO connection is set up correctly
require_once 'url_helper.php';

// Redirect if user is not logged in.
// IMPORTANT: For this file, if it's strictly loaded via AJAX into a modal,
// you might *not* want a full page redirect here. Instead, you might want
// to return a specific error message or an empty div if the session is not set.
// However, sticking to your original logic for now.
if (!isset($_SESSION['username'])) {
    // For AJAX requests, return an error message instead of redirecting
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo '<div class="error-message">Session expired. Please <a href="' . url('login.php') . '">login</a> again.</div>';
        exit();
    } else {
        redirect('login.php');
    }
}

// Define the ordered list of stages for display and processing
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

/* ---------------------------
    Retrieve Projects for Statistics
------------------------------ */
// Fetch all projects along with their 'Notice to Proceed' status
// and their *first* unsubmitted stage. This is crucial for determining current stage.
$sql = "SELECT p.*,
        (SELECT isSubmitted FROM tblproject_stages WHERE projectID = p.projectID AND stageName = 'Notice to Proceed') AS notice_to_proceed_submitted,
        (SELECT s.stageName FROM tblproject_stages s WHERE s.projectID = p.projectID AND s.isSubmitted = 0
            ORDER BY FIELD(s.stageName, 'Purchase Request','RFQ 1','RFQ 2','RFQ 3','Abstract of Quotation','Purchase Order','Notice of Award','Notice to Proceed') ASC
            LIMIT 1) AS first_unsubmitted_stage
        FROM tblproject p";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$projects = $stmt->fetchAll();

// --- Calculate PR Status Counts and Percentages ---
$totalProjects = count($projects);
$finishedProjects = 0;
$ongoingProjects = 0;

// Initialize stage counts
$stageCounts = [];
foreach ($stagesOrder as $stage) {
    $stageCounts[$stage] = 0;
}
$stageCounts['Finished'] = 0;

// This will store the breakdown data for the new nested grid
$ongoingBreakdownData = [];

foreach ($projects as $project) {
    if ($project['notice_to_proceed_submitted'] == 1) {
        $finishedProjects++;
        $stageCounts['Finished']++;
    } else {
        $ongoingProjects++;
        if (!empty($project['first_unsubmitted_stage'])) {
            $stageCounts[$project['first_unsubmitted_stage']]++;
        }
    }
}

// Populate ongoingBreakdownData for the nested grid
foreach ($stagesOrder as $stage) {
    if (!empty($stageCounts[$stage]) && $stageCounts[$stage] > 0) {
        $shortForm = '';
        switch ($stage) {
            case 'Purchase Request': $shortForm = 'PR'; break;
            case 'RFQ 1': $shortForm = 'RFQ1'; break;
            case 'RFQ 2': $shortForm = 'RFQ2'; break;
            case 'RFQ 3': $shortForm = 'RFQ3'; break;
            case 'Abstract of Quotation': $shortForm = 'AoQ'; break;
            case 'Purchase Order': $shortForm = 'PO'; break;
            case 'Notice of Award': $shortForm = 'NoA'; break;
            case 'Notice to Proceed': $shortForm = 'NtP'; break;
            default: $shortForm = $stage; break;
        }
        $ongoingBreakdownData[] = [
            'name' => $shortForm,
            'count' => $stageCounts[$stage]
        ];
    }
}

$percentageDone = ($totalProjects > 0) ? round(($finishedProjects / $totalProjects) * 100, 2) : 0;
$percentageOngoing = ($totalProjects > 0) ? round(($ongoingProjects / $totalProjects) * 100, 2) : 0;


include 'view/statistics_content.php';
?>
