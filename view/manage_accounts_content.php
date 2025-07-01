<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Accounts - DepEd BAC Tracking System</title>
    <link rel="stylesheet" href="assets/css/manage_accoun.css">
    <link rel="stylesheet" href="assets/css/background.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
    // Include your header.php file here.
    // This will insert the header HTML, its inline styles, and its inline JavaScript.
    include 'header.php';
    ?>

    <div class="accounts-container">
        <a href="<?php echo url('index.php'); ?>" class="back-btn">&#8592;</a>
        <h2 class="page-title">Manage User Accounts</h2>
        <?php
            if ($deleteSuccess != "") { echo "<p class='msg success'>" . htmlspecialchars($deleteSuccess) . "</p>"; }
            if ($editSuccess != "") { echo "<p class='msg success'>" . htmlspecialchars($editSuccess) . "</p>"; }
            // Changed class to 'error' for consistency
            if ($error != "") { echo "<p class='msg error'>" . htmlspecialchars($error) . "</p>"; }
        ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Office ID & Name</th>
                        <th>Position</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td data-label="User ID"><?php echo htmlspecialchars($account['userID']); ?></td>
                            <td data-label="Name"><?php echo htmlspecialchars($account['firstname'] . " " . $account['middlename'] . " " . $account['lastname']); ?></td>
                            <td data-label="Username"><?php echo htmlspecialchars($account['username']); ?></td>
                            <td data-label="Role"><?php echo ($account['admin'] == 1) ? "Admin" : "User"; ?></td>
                            <td data-label="Office"><?php echo htmlspecialchars($account['officeID'] . ' - ' . ($account['officename'] ?? "")); ?></td>
                            <td data-label="Position"><?php echo htmlspecialchars($account['position'] ?? "N/A"); ?></td>
                            <td data-label="Actions">
                                <div class="action-buttons">
                                    <button class="edit-btn icon-btn" data-id="<?php echo $account['userID']; ?>">
                                        <img src="assets/images/Edit_icon.png" alt="Edit" class="action-icon">
                                    </button>
                                    <button class="account-delete-btn icon-btn" data-id="<?php echo $account['userID']; ?>">
                                        <img src="assets/images/delete.png" alt="Delete" class="action-icon">
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" id="editClose">&times;</span>
            <h2>Edit Account</h2>
            <form id="editAccountForm" action="manage_accounts.php" method="post">
                <input type="hidden" name="editUserID" id="editUserID">
                
                <div class="form-group">
                    <label for="editFirstname">First Name<span class="required">*</span></label>
                    <input type="text" name="firstname" id="editFirstname" required>
                </div>

                <div class="form-group">
                    <label for="editMiddlename">Middle Name</label>
                    <input type="text" name="middlename" id="editMiddlename">
                </div>

                <div class="form-group">
                    <label for="editLastname">Last Name<span class="required">*</span></label>
                    <input type="text" name="lastname" id="editLastname" required>
                </div>

                <div class="form-group">
                    <label for="editPosition">Position</label>
                    <input type="text" name="position" id="editPosition">
                </div>

                <div class="form-group">
                    <label for="editUsername">Username<span class="required">*</span></label>
                    <input type="text" name="username" id="editUsername" required>
                </div>

                <div class="form-group">
                    <label for="editPassword">Password (leave blank to keep unchanged)</label>
                    <input type="password" name="password" id="editPassword">
                </div>

                <div class="form-group">
                    <label for="editOffice">Office Name<span class="required">*</span></label>
                    <select name="office" id="editOffice" required>
                        <?php foreach ($officeList as $officeID => $officeName): ?>
                            <option value="<?php echo htmlspecialchars($officeName); ?>"><?php echo htmlspecialchars($officeName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" name="admin" id="editAdmin">
                    <label for="editAdmin">Admin User</label>
                </div>

                <button type="submit" name="editAccount" class="submit-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <span class="close" id="deleteClose">&times;</span>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete this account? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button id="confirmDeleteBtn" class="delete-btn">Yes, Delete</button>
                <button id="cancelDeleteBtn" class="cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit button functionality
            document.querySelectorAll('.edit-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const userID = row.querySelector('[data-label="User ID"]').textContent.trim();
                    const fullName = row.querySelector('[data-label="Name"]').textContent.trim();
                    let nameParts = fullName.split(" ");
                    const firstname = nameParts[0] || "";
                    const lastname = (nameParts.length > 1) ? nameParts[nameParts.length - 1] : "";
                    // Handle middle name correctly, in case it's multi-word or absent
                    let middlename = "";
                    if (nameParts.length > 2) {
                        // All parts between first and last are middle name
                        middlename = nameParts.slice(1, nameParts.length - 1).join(" ");
                    }

                    const username = row.querySelector('[data-label="Username"]').textContent.trim();
                    const role = row.querySelector('[data-label="Role"]').textContent.trim();
                    const office = row.querySelector('[data-label="Office"]').textContent.trim();
                    const position = row.querySelector('[data-label="Position"]').textContent.trim();
                    
                    // Populate the form fields
                    document.getElementById('editUserID').value = userID;
                    document.getElementById('editFirstname').value = firstname;
                    document.getElementById('editMiddlename').value = middlename;
                    document.getElementById('editLastname').value = lastname;
                    document.getElementById('editUsername').value = username;
                    document.getElementById('editPassword').value = ""; // Always clear password field for security
                    document.getElementById('editAdmin').checked = (role === "Admin");
                    document.getElementById('editPosition').value = position;
                    
                    // Set the selected option for the office dropdown
                    const editOfficeSelect = document.getElementById('editOffice');
                    let foundOffice = false;
                    for (let i = 0; i < editOfficeSelect.options.length; i++) {
                        if (editOfficeSelect.options[i].value === office) {
                            editOfficeSelect.selectedIndex = i;
                            foundOffice = true;
                            break;
                        }
                    }
                    if (!foundOffice && office) {
                        // If the office from the table row isn't in the dropdown, add it as a new option
                        // This handles cases where office data in table might not perfectly match dropdown options
                        let newOption = new Option(office, office, true, true);
                        editOfficeSelect.add(newOption);
                    }
                    
                    // Display the modal
                    document.getElementById('editModal').style.display = 'flex'; // Use 'flex' to show the flex container
                });
            });
            
            // Delete button functionality
            let currentDeleteUserID = null;
            document.querySelectorAll('.account-delete-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    currentDeleteUserID = this.dataset.id;
                    document.getElementById('deleteConfirmModal').style.display = 'flex'; // Use 'flex' to show the flex container
                });
            });
            
            // Confirm delete button
            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                if (currentDeleteUserID) {
                    window.location.href = `<?php echo url('manage_accounts.php'); ?>?delete=${currentDeleteUserID}`;
                }
                document.getElementById('deleteConfirmModal').style.display = 'none';
            });
            
            // Cancel delete button
            document.getElementById('cancelDeleteBtn').addEventListener('click', function() {
                document.getElementById('deleteConfirmModal').style.display = 'none';
                currentDeleteUserID = null;
            });
            
            // Close buttons
            document.getElementById('editClose').addEventListener('click', function() {
                document.getElementById('editModal').style.display = 'none';
            });
            
            document.getElementById('deleteClose').addEventListener('click', function() {
                document.getElementById('deleteConfirmModal').style.display = 'none';
                currentDeleteUserID = null;
            });
            
            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                const editModal = document.getElementById('editModal');
                const deleteConfirmModal = document.getElementById('deleteConfirmModal');
                
                if (event.target === editModal) {
                    editModal.style.display = 'none';
                }
                if (event.target === deleteConfirmModal) {
                    deleteConfirmModal.style.display = 'none';
                    currentDeleteUserID = null;
                }
            });
        });
    </script>
</body>
</html>