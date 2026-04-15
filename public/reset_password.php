<?php
require_once dirname(__DIR__) . '/includes/auth.php';

if (is_post()) {
    verify_csrf();

    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $passwordErrors = validate_password_strength($password);
    if ($passwordErrors) {
        foreach ($passwordErrors as $err) {
            flash('danger', $err);
        }
    redirect_to(BASE_URL . '/reset_password.php');
}

    if ($token === '' || $password === '' || $confirmPassword === '') {
        flash('danger', 'Please fill all fields.');
        redirect_to(BASE_URL . '/reset_password.php');
    }

    if ($password !== $confirmPassword) {
        flash('danger', 'Passwords do not match.');
        redirect_to(BASE_URL . '/reset_password.php');
    }

    $stmt = $pdo->prepare('
        SELECT id 
        FROM users 
        WHERE reset_token = ? 
          AND reset_expires_at IS NOT NULL 
          AND reset_expires_at >= NOW()
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        flash('danger', 'Invalid or expired reset token.');
        redirect_to(BASE_URL . '/reset_password.php');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $update = $pdo->prepare('
        UPDATE users
        SET password = ?, reset_token = NULL, reset_expires_at = NULL
        WHERE id = ?
    ');
    $update->execute([$hashedPassword, (int)$user['id']]);

    flash('success', 'Password updated successfully. Please login.');
    redirect_to(BASE_URL . '/login.php');
}

$pageTitle = 'Reset Password | ' . APP_NAME;
$bodyClass = 'auth-page auth-split-page';
$hideFooter = true;

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="auth-split-wrap">

    <div class="auth-left-panel">
        <div class="auth-left-inner">
            <div class="auth-left-logo">
                <img src="<?= BASE_URL ?>/../assets/images/logo.png" alt="Logo">
            </div>

            <h1>Reset Password</h1>

            <p>
                Enter the reset token from the previous page and set your new password securely.
            </p>
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
                    <p>Enter your reset token and create a new password.</p>
                </div>
            </div>

            <?php display_flash(); ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="auth-group">
                    <label>Reset Token <span class="required">*</span></label>
                    <input type="text" name="token" required>
                </div>

                <div class="auth-group">
                    <label>New Password <span class="required">*</span></label>
                    <input type="password" name="password" required>
                </div>

                <div class="auth-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="auth-submit-btn">Update Password</button>

                <div class="auth-extra-links">
                    <a href="<?= BASE_URL ?>/forgot_password.php">Back to Forgot Password</a>
                    <a href="<?= BASE_URL ?>/index.php">Home</a>
                </div>
            </form>
        </div>
    </div>

</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>