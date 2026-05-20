<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

$id = (int) user()['id'];

// Handle password change
if (is_post()) {
  verify_csrf();

  $currentPassword = $_POST['current_password'] ?? '';
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';

  // Verify current password
  $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
  $stmt->execute([$id]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($currentPassword, $user['password'])) {
    flash('danger', 'Current password is incorrect.');
    redirect_to(BASE_URL . '/admin/profile.php');
  }

  // Validate new password strength
  $passwordErrors = validate_password_strength($password);
  if ($passwordErrors) {
    foreach ($passwordErrors as $error) {
      flash('danger', $error);
    }
    redirect_to(BASE_URL . '/admin/profile.php');
  }

  if ($password !== $confirmPassword) {
    flash('danger', 'New passwords do not match.');
    redirect_to(BASE_URL . '/admin/profile.php');
  }

  // Update password
  $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
  $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
  flash('success', 'Password updated successfully. Please login again with your new password.');

  // Logout user to force login with new password
  destroy_session();
  session_start();
  redirect_to(BASE_URL . '/login.php');
}

$u = user();
$pageTitle = 'Admin Profile | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
  <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
  <main class="main-panel">
    <div class="dashboard-top">
      <div class="dashboard-title">
        <h1>Admin Profile</h1>
        <p>View your profile information and change your password.</p>
      </div>
      <div class="user-chip">👑 Admin Panel</div>
    </div>

    <div class="section-stack">
      <!-- Profile Information Card -->
      <div class="content-card">
        <h3>Profile Information</h3>
        <div class="grid-2">
          <div>
            <p><strong>Name:</strong> <?= e($u['name']) ?></p>
            <p><strong>Email:</strong> <?= e($u['email']) ?></p>
            <p><strong>Role:</strong> <?= e(ucfirst($u['role'])) ?></p>
            <p><strong>Contact:</strong> <?= e($u['contact'] ?? 'N/A') ?></p>
          </div>
          <div>
            <p><strong>Department:</strong> <?= e($u['department'] ?? 'Administration') ?></p>
            <p><strong>Qualification:</strong> <?= e($u['qualification'] ?? 'N/A') ?></p>
            <p><strong>Address:</strong> <?= e($u['address'] ?? 'N/A') ?></p>
            <p><strong>Date of Birth:</strong> <?= e($u['dob'] ?? 'N/A') ?></p>
          </div>
        </div>
      </div>

      <!-- Change Password Card -->
      <div class="form-card">
        <h3>Change Password</h3>
        <?php display_flash(); ?>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

          <div class="form-group">
            <label>Current Password <span class="required">*</span></label>
            <div style="position: relative;">
              <input type="password" name="current_password" id="current_password" required style="padding-right: 45px; width: 100%;">
              <button type="button" onclick="togglePassword('current_password')"
                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                           background: none; border: none; cursor: pointer; font-size: 18px; 
                                           padding: 0; margin: 0; color: #888;">
                🔒
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>New Password <span class="required">*</span></label>
            <div style="position: relative;">
              <input type="password" name="password" id="new_password" required style="padding-right: 45px; width: 100%;">
              <button type="button" onclick="togglePassword('new_password')"
                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                           background: none; border: none; cursor: pointer; font-size: 18px; 
                                       padding: 0; margin: 0; color: #888;">
                🔒
              </button>
            </div>
            <small style="color: #6c757d; display: block; margin-top: 5px;">
              <strong>Password Requirements:</strong> Minimum 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character (!@#$%^&*)
            </small>
          </div>

          <div class="form-group">
            <label>Confirm New Password <span class="required">*</span></label>
            <div style="position: relative;">
              <input type="password" name="confirm_password" id="confirm_password" required style="padding-right: 45px; width: 100%;">
              <button type="button" onclick="togglePassword('confirm_password')"
                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                           background: none; border: none; cursor: pointer; font-size: 18px; 
                                           padding: 0; margin: 0; color: #888;">
                🔒
              </button>
            </div>
          </div>

          <div class="inline-actions">
            <button class="btn btn-primary">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<!-- The following styles are already present in dashboard.css; kept only for grid layout -->
<style>
  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }

  .content-card {
    background: rgba(255, 255, 255, 0.96);
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  }

  @media (max-width: 700px) {
    .grid-2 {
      grid-template-columns: 1fr;
      gap: 10px;
    }
  }
</style>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>