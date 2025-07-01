<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Project - DepEd BAC Tracking System</title>
    <link rel="stylesheet" href="assets/css/edit_project.css">
    <link rel="stylesheet" href="assets/css/background.css">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const stageForms = document.querySelectorAll('.stage-form');

        // Highlight the current active stage for better visibility
        const highlightActiveStage = function() {
            const firstUnsubmittedStageName = <?php echo json_encode($firstUnsubmittedStageName); ?>;
            if (firstUnsubmittedStageName) {
                document.querySelectorAll(`tr[data-stage="${firstUnsubmittedStageName}"]`).forEach(row => {
                    row.style.backgroundColor = '#f8f9fa';
                    row.style.boxShadow = '0 0 5px rgba(0,0,0,0.1)';
                });

                document.querySelectorAll(`.stage-card h4`).forEach(heading => {
                    if (heading.textContent === firstUnsubmittedStageName) {
                        heading.closest('.stage-card').style.backgroundColor = '#f8f9fa';
                        heading.closest('.stage-card').style.boxShadow = '0 0 8px rgba(0,0,0,0.15)';
                    }
                });
            }
        };

        highlightActiveStage();

        // Improved form validation
        stageForms.forEach(form => {
            form.addEventListener('submit', function(event) {
                const stageNameInput = form.querySelector('input[name="stageName"]');
                const stageName = stageNameInput ? stageNameInput.value : '';
                const safeStage = stageName.replace(/ /g, '_');

                const createdField = form.querySelector(`input[name="created_${safeStage}"]`);
                const approvedAtField = form.querySelector('input[name="approvedAt"]');
                const remarkField = form.querySelector('input[name="remark"]');
                const submitButton = form.querySelector('button[name="submit_stage"]');

                const buttonText = submitButton ? submitButton.textContent.trim() : '';

                if (buttonText === 'Submit' && !submitButton.disabled) {
                    let isValid = true;
                    let errorMessages = [];

                    // Validate 'Approved' field
                    if (!approvedAtField || !approvedAtField.value) {
                        isValid = false;
                        errorMessages.push("Approved Date/Time is required");
                    }

                    // Validate 'Remark' field
                    if (!remarkField || !remarkField.value.trim()) {
                        isValid = false;
                        errorMessages.push("Remarks are required");
                    }

                    // Validate 'Created' field for admins and non-PR stages
                    const isAdmin = <?php echo json_encode($isAdmin); ?>;
                    const isPurchaseRequest = (stageName === 'Purchase Request');
                    const currentCreatedValue = createdField ? createdField.value : '';

                    if (isAdmin && !isPurchaseRequest && !currentCreatedValue) {
                        isValid = false;
                        errorMessages.push("Created Date/Time is required for this stage");
                    }

                    if (!isValid) {
                        event.preventDefault();
                        alert("Please fix the following errors:\n• " + errorMessages.join("\n• "));
                    }
                }
            });
        });

        // Add tooltips for better usability
        const addTooltip = function(element, text) {
            element.title = text;
            element.style.cursor = 'help';
        };

        document.querySelectorAll('th').forEach(th => {
            if (th.textContent === 'Created') {
                addTooltip(th, 'When the document was created');
            } else if (th.textContent === 'Approved') {
                addTooltip(th, 'When the document was approved');
            } else if (th.textContent === 'Office') {
                addTooltip(th, 'Office responsible for this stage');
            }
        });
    });
    </script>
</head>
<body class="<?php echo $isAdmin ? 'admin-view' : 'user-view'; ?>">
    <?php
    include 'header.php'; // Uncomment if you have a header.php file
    ?>

    <div class="dashboard-container">
        <a href="<?php echo url('index.php'); ?>" class="back-btn">&larr; Back to Dashboard</a>

        <h1>Edit Project</h1>

        <?php
            if (isset($errorHeader)) { echo "<p style='color:red;'>$errorHeader</p>"; }
            if (isset($successHeader)) { echo "<p style='color:green;'>$successHeader</p>"; }
            if (isset($stageError)) { echo "<p style='color:red;'>$stageError</p>"; }
        ?>

        <div class="project-header">
            <label for="prNumber">PR Number:</label>
            <?php if ($isAdmin): ?>
                <form action="edit_project.php?projectID=<?php echo $projectID; ?>" method="post" style="margin-bottom:10px;">
                    <input type="text" name="prNumber" id="prNumber" value="<?php echo htmlspecialchars($project['prNumber']); ?>" required>
            <?php else: ?>
                <div class="readonly-field"><?php echo htmlspecialchars($project['prNumber']); ?></div>
            <?php endif; ?>

            <label for="projectDetails">Project Details:</label>
            <?php if ($isAdmin): ?>
                <textarea name="projectDetails" id="projectDetails" rows="3" required><?php echo htmlspecialchars($project['projectDetails']); ?></textarea>
            <?php else: ?>
                <div class="readonly-field"><?php echo htmlspecialchars($project['projectDetails']); ?></div>
            <?php endif; ?>
            
            <label>Mode of Procurement:</label> <p>
                <?php echo htmlspecialchars($project['MoPDescription'] ?? 'N/A'); ?> </p>

            <label>Program Owner:</label>
            <p>
                <?php
                    echo htmlspecialchars($project['programOwner'] ?? 'N/A');
                    if (!empty($project['programOffice'])) {
                        echo " | Office: " . htmlspecialchars($project['programOffice']);
                    }
                ?>
            </p>

            <label for="totalABC">Total ABC:</label>
            <?php if ($isAdmin): ?>
                <input type="number" name="totalABC" id="totalABC"
                    value="<?php echo htmlspecialchars($project['totalABC']); ?>"
                    required min="0" step="1">
            <?php else: ?>
                <div class="readonly-field">
                    <?php echo isset($project['totalABC']) ? number_format($project['totalABC']) : 'N/A'; ?>
                </div>
            <?php endif; ?>
            
            <label>Created By:</label>
            <p><?php echo htmlspecialchars($project['creator_firstname'] . " " . $project['creator_lastname'] . " | Office: " . ($project['officename'] ?? 'N/A')); ?></p>

            <label>Date Created:</label>
            <p><?php echo date("m-d-Y h:i A", strtotime($project['createdAt'])); ?></p>

            <label>Last Updated:</label> <p>
                <?php
                $lastUpdatedInfo = "Not Available";
                $mostRecentTimestamp = null;
                $mostRecentUserId = null;

                $editedTs = !empty($project['editedAt']) ? strtotime($project['editedAt']) : 0;
                $lastAccessedTs = !empty($project['lastAccessedAt']) ? strtotime($project['lastAccessedAt']) : 0;

                if ($editedTs > 0 && ($editedTs >= $lastAccessedTs || $lastAccessedTs === 0)) {
                    $mostRecentTimestamp = $editedTs;
                    $mostRecentUserId = $project['editedBy'];
                } else if ($lastAccessedTs > 0) {
                    $mostRecentTimestamp = $lastAccessedTs;
                    $mostRecentUserId = $project['lastAccessedBy'];
                }

                $lastUpdatedUserFullName = "N/A";
                if (!empty($mostRecentUserId)) {
                    $stmtUser = $pdo->prepare("SELECT firstname, lastname FROM tbluser WHERE userID = ?");
                    $stmtUser->execute([$mostRecentUserId]);
                    $user = $stmtUser->fetch();
                    if ($user) {
                        $lastUpdatedUserFullName = htmlspecialchars($user['firstname'] . " " . $user['lastname']);
                    }
                }

                if ($lastUpdatedUserFullName !== "N/A" && $mostRecentTimestamp) {
                    $lastUpdatedInfo = $lastUpdatedUserFullName . ", on " . date("m-d-Y h:i A", $mostRecentTimestamp);
                }
                echo $lastUpdatedInfo;
                ?>
            </p>
            <?php if ($isAdmin): ?>
                <button type="submit" name="update_project_header" class="update-project-details-btn">
                    <span>Update Project Details</span>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <h3>Project Stages</h3>
        <?php
            $projectStatusClass = $noticeToProceedSubmitted ? 'finished' : 'in-progress';
            $projectStatusText = 'Status: ' . ($noticeToProceedSubmitted ? 'Finished' : 'In Progress');
            echo '<div class="project-status ' . $projectStatusClass . '">' . $projectStatusText . '</div>';
        ?>
        <?php if (isset($stageSuccess)) { echo "<p style='color:green;'>$stageSuccess</p>"; } ?>
        <div class="table-wrapper">
            <table id="stagesTable">
                <thead>
                    <tr>
                        <th style="width: 15%;">Stage</th>
                        <th style="width: 20%;">Created</th>
                        <th style="width: 20%;">Approved</th>
                        <th style="width: 15%;">Office</th>
                        <th style="width: 15%;">Remark</th>
                        <th style="width: 15%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Using $firstUnsubmittedStageName already determined above
                    foreach ($stagesOrder as $index => $stage):
                        $safeStage = str_replace(' ', '_', $stage);
                        $currentStageData = $stagesMap[$stage] ?? null;

                        $currentSubmitted = ($currentStageData && $currentStageData['isSubmitted'] == 1);

                        $value_created = ($currentStageData && !empty($currentStageData['createdAt']))
                                                 ? date("Y-m-d\TH:i", strtotime($currentStageData['createdAt'])) : "";
                        $value_approved = ($currentStageData && !empty($currentStageData['approvedAt']))
                                                  ? date("Y-m-d\TH:i", strtotime($currentStageData['approvedAt'])) : "";

                        // Get the office ID stored for THIS specific stage from the database
                        $value_office_id_for_stage = ($currentStageData && isset($currentStageData['officeID']))
                                                ? (int)$currentStageData['officeID'] : null;

                        $value_remark = ($currentStageData && !empty($currentStageData['remarks']))
                                                ? htmlspecialchars($currentStageData['remarks']) : "";

                        $isLastProcessedStage = ($stage === $lastSubmittedStageName);

                        $disableFields = true;
                        $disableCreatedField = true;

                        if ($stage === $firstUnsubmittedStageName) {
                            $disableFields = false;
                            if ($isAdmin && $stage !== 'Purchase Request') {
                                $disableCreatedField = false;
                            }
                            if ($stage === 'Purchase Request' && !empty($value_created)) {
                                $disableCreatedField = true;
                            }
                        }

                        if ($currentSubmitted) {
                            $disableFields = true;
                            $disableCreatedField = true;
                        }
                        if ($stage !== $firstUnsubmittedStageName && !$currentSubmitted) {
                             $disableFields = true;
                             $disableCreatedField = true;
                        }

                        // Determine the office name to display
                        $displayOfficeName = "N/A"; // Default display if no office is found or applicable

                        // Case 1: Stage has an officeID already saved in the database
                        if ($value_office_id_for_stage !== null && isset($officeList[$value_office_id_for_stage])) {
                            $displayOfficeName = htmlspecialchars($value_office_id_for_stage . ' - ' . $officeList[$value_office_id_for_stage]);
                        }
                        // Case 2: Stage is the 'firstUnsubmittedStageName' (the current active stage)
                        // AND it does not yet have an officeID saved in the database.
                        // In this case, it should show the logged-in user's office.
                        elseif ($stage === $firstUnsubmittedStageName && $value_office_id_for_stage === null && $loggedInUserOfficeName !== "N/A") {
                            // Already formatted with ID in $loggedInUserOfficeName
                            $displayOfficeName = $loggedInUserOfficeName;
                        }
                    ?>
                    <form action="<?php echo url('edit_project.php', ['projectID' => $projectID]); ?>" method="post" class="stage-form">
                        <tr data-stage="<?php echo htmlspecialchars($stage); ?>">
                            <td><?php echo htmlspecialchars($stage); ?></td>
                            <td>
                                <input type="datetime-local" name="created_<?php echo $safeStage; ?>"
                                        value="<?php echo $value_created; ?>"
                                        <?php if ($disableCreatedField) echo "disabled"; ?>
                                        <?php if (!$disableCreatedField && $stage !== 'Purchase Request') echo "required"; ?>>
                            </td>
                            <td>
                                <input type="datetime-local" name="approvedAt"
                                        value="<?php echo $value_approved; ?>"
                                        <?php if ($disableFields) echo "disabled"; ?>
                                        <?php if (!$disableFields) echo "required"; ?>>
                            </td>
                            <td>
                                <div class="readonly-office-field">
                                    <?php echo $displayOfficeName; ?>
                                </div>
                                </td>
                            <td>
                                <input type="text" name="remark"
                                        value="<?php echo $value_remark; ?>"
                                        <?php if ($disableFields) echo "disabled"; ?>
                                        <?php if (!$disableFields) echo "required"; ?>>
                            </td>
                            <td>
                                <input type="hidden" name="stageName" value="<?php echo htmlspecialchars($stage); ?>">
                                <div style="margin-top:10px;">
                                    <?php
                                    if ($currentSubmitted) {
                                        if ($isAdmin && $isLastProcessedStage) {
                                            echo '<button type="submit" name="submit_stage" class="submit-stage-btn unsubmit-btn">Unsubmit</button>';
                                        } else {
                                            echo '<button type="button" class="submit-stage-btn" disabled>Finished</button>';
                                        }
                                    } else {
                                        if ($stage === $firstUnsubmittedStageName) {
                                            echo '<button type="submit" name="submit_stage" class="submit-stage-btn">Submit</button>';
                                        } else {
                                            echo '<button type="button" class="submit-stage-btn" disabled>Pending</button>';
                                        }
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                    </form>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-view">
            <?php foreach ($stagesOrder as $index => $stage):
                $safeStage = str_replace(' ', '_', $stage);
                $currentStageData = $stagesMap[$stage] ?? null;
                $currentSubmitted = ($currentStageData && $currentStageData['isSubmitted'] == 1);

                $value_created = ($currentStageData && !empty($currentStageData['createdAt'])) ? date("Y-m-d\TH:i", strtotime($currentStageData['createdAt'])) : "";
                $value_approved = ($currentStageData && !empty($currentStageData['approvedAt'])) ? date("Y-m-d\TH:i", strtotime($currentStageData['approvedAt'])) : "";

                $value_office_id_for_stage = ($currentStageData && isset($currentStageData['officeID']))
                                        ? (int)$currentStageData['officeID'] : null;

                $value_remark = ($currentStageData && !empty($currentStageData['remarks'])) ? htmlspecialchars($currentStageData['remarks']) : "";

                $isLastProcessedStage = ($stage === $lastSubmittedStageName);

                $disableFields = true;
                $disableCreatedField = true;

                if ($stage === $firstUnsubmittedStageName) {
                    $disableFields = false;
                    if ($isAdmin && $stage !== 'Purchase Request') {
                        $disableCreatedField = false;
                    }
                    if ($stage === 'Purchase Request' && !empty($value_created)) {
                        $disableCreatedField = true;
                    }
                }

                if ($currentSubmitted) {
                    $disableFields = true;
                    $disableCreatedField = true;
                }
                if ($stage !== $firstUnsubmittedStageName && !$currentSubmitted) {
                    $disableFields = true;
                    $disableCreatedField = true;
                }

                $displayOfficeName = "N/A";
                if ($value_office_id_for_stage !== null && isset($officeList[$value_office_id_for_stage])) {
                    $displayOfficeName = htmlspecialchars($value_office_id_for_stage . ' - ' . $officeList[$value_office_id_for_stage]);
                } elseif (!$disableFields && $value_office_id_for_stage === null && $loggedInUserOfficeName !== "N/A") {
                    // Already formatted with ID in $loggedInUserOfficeName
                    $displayOfficeName = $loggedInUserOfficeName;
                }
            ?>
            <form action="<?php echo url('edit_project.php', ['projectID' => $projectID]); ?>" method="post" class="stage-form">
                <div class="stage-card">
                    <h4><?php echo htmlspecialchars($stage); ?></h4>

                    <label>Created At:</label>
                    <input type="datetime-local" name="created_<?php echo $safeStage; ?>" value="<?php echo $value_created; ?>"
                        <?php if ($disableCreatedField) echo "disabled"; ?>
                        <?php if (!$disableCreatedField && $stage !== 'Purchase Request') echo "required"; ?>>

                    <label>Approved At:</label>
                    <input type="datetime-local" name="approvedAt" value="<?php echo $value_approved; ?>"
                        <?php if ($disableFields) echo "disabled"; ?>
                        <?php if (!$disableFields) echo "required"; ?>>

                    <label>Office:</label>
                    <div class="readonly-office-field">
                        <?php echo $displayOfficeName; ?>
                    </div>

                    <label>Remark:</label>
                    <input type="text" name="remark" value="<?php echo $value_remark; ?>"
                        <?php if ($disableFields) echo "disabled"; ?>
                        <?php if (!$disableFields) echo "required"; ?>>

                    <input type="hidden" name="stageName" value="<?php echo htmlspecialchars($stage); ?>">
                    <div style="margin-top:10px;">
                        <?php
                        if ($currentSubmitted) {
                            if ($isAdmin && $isLastProcessedStage) {
                                echo '<button type="submit" name="submit_stage" class="submit-stage-btn unsubmit-btn">Unsubmit</button>';
                            } else {
                                echo '<button type="button" class="submit-stage-btn" disabled>Finished</button>';
                            }
                        } else {
                            if ($stage === $firstUnsubmittedStageName) {
                                echo '<button type="submit" name="submit_stage" class="submit-stage-btn">Submit</button>';
                            } else {
                                echo '<button type="button" class="submit-stage-btn" disabled>Pending</button>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </form>
            <?php endforeach; ?>
        </div>

        <?php if ($noticeToProceedSubmitted): ?>
            <div class="completion-message">
                <p>All stages are completed! This project is now finished.</p>
            </div>
        <?php endif; ?>


    </div>
</body>
</html>