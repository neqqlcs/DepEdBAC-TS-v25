<?php
// project_tracker.php

// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'config.php'; // Ensure your PDO connection is set up correctly
require_once 'url_helper.php';

// Redirect if user is not logged in.
if (!isset($_SESSION['username'])) {
    redirect('login.php');
}

// Define the ordered list of stages for display and processing
// This is crucial for the SQL query to determine the 'first_unsubmitted_stage' correctly
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


$filterStatus = $_GET['status'] ?? ''; // Get the 'status' parameter from the URL

// Base SQL query to fetch all projects, their 'Notice to Proceed' status,
// and the *first* unsubmitted stage (if any).
$sql = "SELECT p.*,
        (SELECT isSubmitted FROM tblproject_stages WHERE projectID = p.projectID AND stageName = 'Notice to Proceed') AS notice_to_proceed_submitted,
        (SELECT s.stageName FROM tblproject_stages s WHERE s.projectID = p.projectID AND s.isSubmitted = 0
            ORDER BY FIELD(s.stageName, 'Purchase Request','RFQ 1','RFQ 2','RFQ 3','Abstract of Quotation','Purchase Order','Notice of Award','Notice to Proceed') ASC
            LIMIT 1) AS first_unsubmitted_stage
        FROM tblproject p";

$conditions = [];
$params = [];

// Add conditions based on the filter status
if ($filterStatus === 'done') {
    $conditions[] = " (SELECT isSubmitted FROM tblproject_stages WHERE projectID = p.projectID AND stageName = 'Notice to Proceed') = 1";
} elseif ($filterStatus === 'ongoing') {
    $conditions[] = " (SELECT isSubmitted FROM tblproject_stages WHERE projectID = p.projectID AND stageName = 'Notice to Proceed') = 0";
}

// Append conditions to the SQL query if any exist
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Prepare and execute the SQL query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array

// Get admin status from session
$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] == 1;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Tracker</title>
    <!-- Link to your project tracker specific CSS -->
    <link rel="stylesheet" href="assets/css/your_project_tracker_styles.css">
    <style>
        /* Basic styling for the project tracker table */
        body {
            font-family: 'Inter', sans-serif;
            margin: 8vw 10vh;
            background-color: #fdf0d3; /* Changed background color to #fefefe */
            color: #333;

        }
        h1 {
            color:rgb(0, 0, 0);
            text-align: center;
            margin-bottom: 20px;
        }
        .project-list {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow-x: auto; /* For responsive table on small screens */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #eaf6ff;
        }
        .status-done {
            color: #28a745; /* Green for done */
            font-weight: bold;
        }
        .status-ongoing {
            color: #ffc107; /* Orange for ongoing */
            font-weight: bold;
        }
        .no-projects {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .filter-info {
            text-align: center;
            margin-bottom: 15px;
            font-size: 36px; /* Increased font size for prominence */
            font-weight: bold; /* Made it bold */
            color:rgb(0, 0, 0);
            padding: 10px;
            border-radius: 5px;
        }
        .back-button-container {
            margin-bottom: 20px; /* Space between button and filter info/table */
            text-align: left; /* Align button to the left */
        }
        /* Updated back-button styling to match .back-btn provided */
        .back-button {
            display: inline-block;
            background-color: #0d47a1; /* Changed to match .back-btn */
            color: #fff;
            padding: 8px 12px; /* Changed to match .back-btn */
            text-decoration: none;
            border-radius: 4px; /* Changed to match .back-btn */
            /* margin: 10px; Removed from here, but container has margin-bottom */
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #0056b3;
        }

        /* Action Button Styles - New styles provided by user */
        .edit-project-btn, .delete-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px; /* Not explicitly needed for image buttons, but good for consistency */
            text-decoration: none;
            color: inherit;
            background-color: transparent; /* Default transparent to let background-color override */
        }
        .edit-project-btn { background-color: #0D47A1; color: white; }
        .delete-btn { background-color: #C62828; color: white; }

        /* Styling for the images inside the action buttons */
        .edit-project-btn img, .delete-btn img {
            width: 24px; /* Explicitly set as per request */
            height: 24px; /* Explicitly set as per request */
            /* Removed margin from here, as margin is on the anchor tags now */
            vertical-align: middle; /* Align icons nicely */
        }
        /* Removed .action-icons img rule as it's superseded by more specific rules above */
    </style>
</head>
<body>
    <?php
    include 'header.php'
    ?>

    <?php if ($filterStatus === 'ongoing'): ?>
        <p class="filter-info">Ongoing Projects</p>
    <?php elseif ($filterStatus === 'done'): ?>
        <p class="filter-info">Finished Projects</p>
    <?php endif; ?>

    <div class="back-button-container">
        <!-- Changed link to index.php and added show_stats parameter -->
        <a href="<?php echo url('index.php', ['show_stats' => 'true']); ?>" class="back-button">&larr; Back to Dashboard</a>
    </div>
    <div class="project-list">
        <?php if (!empty($projects)): ?>
            <table>
                <thead>
                    <tr>
                        <th>PR Number</th>
                        <th>Project Details</th>
                        <th>Remarks</th>
                        <th>Current Stage</th>
                        <th>Status</th>
                        <th>Actions</th> <!-- New Actions column header -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($project['prNumber'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($project['projectDetails'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($project['remarks'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                if (($project['notice_to_proceed_submitted'] ?? 0) == 1) {
                                    echo "<span class='status-done'>Finished</span>";
                                } else {
                                    echo htmlspecialchars($project['first_unsubmitted_stage'] ?? 'No Stage Defined');
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (($project['notice_to_proceed_submitted'] ?? 0) == 1) {
                                    echo "<span class='status-done'>Done</span>";
                                } else {
                                    echo "<span class='status-ongoing'>Ongoing</span>";
                                }
                                ?>
                            </td>
                            <td class="action-icons">
                                <!-- Edit Icon -->
                                <a href="<?php echo url('edit_project.php', ['projectID' => $project['projectID']]); ?>" class="edit-project-btn" title="Edit Project" style="margin-right: 5px;">
                                    <img src="assets/images/Edit_icon.png" alt="Edit Project" style="width:24px;height:24px;">
                                </a>
                                <!-- Delete Icon - Only show if user is an admin -->
                                <?php if ($isAdmin): ?>
                                <a href="<?php echo url('index.php', ['deleteProject' => $project['projectID']]); ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this project and all its stages?');" title="Delete Project">
                                    <img src="assets/images/delete.png" alt="Delete Project" style="width:24px;height:24px;">
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: /* Corrected syntax: added colon */ ?>
            <p class="no-projects">No projects found.</p>
        <?php endif; ?>
    </div>
</body>
</html>