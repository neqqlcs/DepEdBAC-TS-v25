<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Account - DepEd BAC Tracking System</title>
  <link rel="stylesheet" href="assets/css/edit_account.css" />
  <link rel="stylesheet" href="assets/css/background.css" />

</head>
<body>

    <?php
    include 'header.php';
    ?>


  <div class="modal">
    <div class="modal-content">
      <span class="close" onclick="window.location.href='<?php echo url('index.php'); ?>'">&times;</span>

      <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>

      <?php if ($success): ?>
        <h3>Password Updated Successfully!</h3>
        <button onclick="window.location.href='<?php echo url('index.php'); ?>'">Return to Dashboard</button>
      <?php else: ?>
      <div class="card-container">
        <!-- User Info Card -->
        <div class="info-card">
          <h3>Account Information</h3>
          <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value"><?= htmlspecialchars($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Position:</span>
            <span class="info-value"><?= htmlspecialchars($user['position']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Username:</span>
            <span class="info-value"><?= htmlspecialchars($user['username']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Office:</span>
            <span class="info-value"><?= htmlspecialchars($user['officeID'] . ' - ' . $user['officename']) ?></span>
          </div>
        </div>
        
        <!-- Password Change Card -->
        <div class="password-card">
          <h3>Change Password</h3>
          <form method="post">
            <div class="form-group">
              <label for="old_password">Old Password*</label>
              <input type="password" id="old_password" name="old_password" required />
            </div>

            <div class="form-group">
              <label for="new_password">New Password*</label>
              <input type="password" id="new_password" name="new_password" required />
            </div>

            <div class="form-group">
              <label for="confirm_new_password">Confirm New Password*</label>
              <input type="password" id="confirm_new_password" name="confirm_new_password" required />
            </div>

            <button type="submit">Update Password</button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>