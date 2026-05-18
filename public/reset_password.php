<?php
require_once dirname(__DIR__) . '/includes/auth.php';

// Get email from URL if present
$prefilledEmail = isset($_GET['email']) ? urldecode($_GET['email']) : '';

$pageTitle = 'Reset Password | ' . APP_NAME;
$bodyClass = 'auth-page auth-split-page';
$hideFooter = true;

if (is_post()) {
    verify_csrf();
    
    $otp = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate password strength
    $passwordErrors = validate_password_strength($password);
    if ($passwordErrors) {
        foreach ($passwordErrors as $err) {
            flash('danger', $err);
        }
        redirect_to(BASE_URL . '/reset_password.php');
    }
    
    if ($otp === '' || $password === '' || $confirmPassword === '') {
        flash('danger', 'Please fill all fields.');
        redirect_to(BASE_URL . '/reset_password.php');
    }
    
    if ($password !== $confirmPassword) {
        flash('danger', 'Passwords do not match.');
        redirect_to(BASE_URL . '/reset_password.php');
    }
    
    // Find user by valid OTP
    $stmt = $pdo->prepare('
        SELECT id, otp_code, otp_attempts 
        FROM users 
        WHERE otp_code IS NOT NULL 
          AND otp_expires_at IS NOT NULL 
          AND otp_expires_at > NOW()
    ');
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    $user = null;
    foreach ($users as $u) {
        if (verify_otp($otp, $u['otp_code'])) {
            $user = $u;
            break;
        }
    }
    
    if (!$user) {
        flash('danger', 'Invalid or expired OTP. Please request a new one.');
        redirect_to(BASE_URL . '/forgot_password.php');
    }
    
    // Check if too many attempts
    if ($user['otp_attempts'] >= 3) {
        flash('danger', 'Too many invalid attempts. Please request a new OTP.');
        redirect_to(BASE_URL . '/forgot_password.php');
    }
    
    // Update password and clear OTP
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $update = $pdo->prepare('
        UPDATE users
        SET password = ?, 
            otp_code = NULL, 
            otp_expires_at = NULL,
            otp_attempts = 0,
            reset_token = NULL,
            reset_expires_at = NULL
        WHERE id = ?
    ');
    $update->execute([$hashedPassword, (int)$user['id']]);
    
    flash('success', 'Password updated successfully. Please login with your new password.');
    redirect_to(BASE_URL . '/login.php');
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="auth-split-wrap">
    <div class="auth-left-panel">
        <div class="auth-left-inner">
            <div class="auth-left-logo">
                <img src="<?= BASE_URL ?>/../assets/images/logo.png" alt="Logo">
            </div>
            <h1>Reset Password</h1>
            <p>Enter the OTP sent to your Gmail address and create a new password.</p>
        </div>
    </div>
    <div class="auth-right-panel">
        <div class="auth-top-links">
            <a href="<?= BASE_URL ?>/login.php">Back to Login</a>
        </div>
        <div class="auth-form-card">
            <div class="auth-header-row">
                <div class="auth-header-main">
                    <h2>Reset Password</h2>
                    <p>Enter your OTP and new password.</p>
                </div>
            </div>
            <?php display_flash(); ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="auth-group">
                    <label>OTP Code <span class="required">*</span></label>
                    <input type="text" name="otp" required placeholder="6-digit code" maxlength="6" autofocus>
                </div>
                <div class="auth-group">
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
                    <small>Minimum 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character.</small>
                </div>
                <div class="auth-group">
                    <label>Confirm Password <span class="required">*</span></label>
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
                <button type="submit" class="auth-submit-btn">Update Password</button>
                <div class="auth-extra-links">
                    <a href="<?= BASE_URL ?>/forgot_password.php">Request New OTP</a>
                    <a href="<?= BASE_URL ?>/index.php">Home</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>