<?php
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = 'Forgot Password | ' . APP_NAME;
$bodyClass = 'auth-page auth-split-page';
$hideFooter = true;

if (is_post()) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    
    // ============================================================
    // STEP 1: Check if it's a Gmail address
    // ============================================================
    if (!validate_gmail_email($email)) {
        flash('danger', 'Only Gmail addresses are allowed for password reset. Please use a @gmail.com email.');
        redirect_to(BASE_URL . '/forgot_password.php');
    }
    
    // ============================================================
    // STEP 2: Check if email exists in database
    // ============================================================
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $found = $stmt->fetch();
    
    // ============================================================
    // If email NOT registered, show error message
    // ============================================================
    if (!$found) {
        flash('danger', 'Email not registered in our system. Please contact admin or use a registered email.');
        redirect_to(BASE_URL . '/forgot_password.php');
    }
    
    // ============================================================
    // Email is registered - proceed with OTP
    // ============================================================
    
    // Rate limiting check
    if (!check_otp_rate_limit($pdo, $email)) {
        flash('danger', 'Too many requests. Please wait a few minutes before trying again.');
        redirect_to(BASE_URL . '/forgot_password.php');
    }
    
    // Generate secure OTP
    $otp = generate_secure_otp();
    $hashedOtp = hash_otp($otp);
    $expiryTime = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store hashed OTP in database
    $update = $pdo->prepare('
        UPDATE users 
        SET otp_code = ?, 
            otp_expires_at = ?, 
            otp_attempts = 0,
            last_otp_request = NOW(),
            reset_token = NULL,
            reset_expires_at = NULL
        WHERE id = ?
    ');
    $update->execute([$hashedOtp, $expiryTime, (int)$found['id']]);
    
    // Send OTP via email
    $emailSent = send_otp_email($email, $otp);
    
    // For debugging - log to file
    $logFile = ROOT_PATH . '/logs/otp_log.txt';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $logEntry = date('Y-m-d H:i:s') . " - Forgot Password - To: $email - OTP: $otp - EmailSent: " . ($emailSent ? 'Yes' : 'No') . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Success - redirect to reset password page
    flash('success', 'OTP sent to your email. Please check your inbox or spam folder.');
    redirect_to(BASE_URL . '/reset_password.php?email=' . urlencode($email));
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
            <p>Enter your registered Gmail address. We'll send you a 6-digit OTP to reset your password.</p>
        </div>
    </div>
    <div class="auth-right-panel">
        <div class="auth-top-links">
            <a href="<?= BASE_URL ?>/login.php">Back to Login</a>
        </div>
        <div class="auth-form-card">
            <div class="auth-header-row">
                <div class="auth-header-main">
                    <h2>Forgot Password</h2>
                    <p>Enter your registered Gmail address to receive OTP.</p>
                </div>
            </div>
            <?php display_flash(); ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="auth-group">
                    <label>Gmail Address <span class="required">*</span></label>
                    <input type="email" name="email" required placeholder="yourname@gmail.com">
                    <small style="color: #6c757d;">Only registered @gmail.com addresses will receive OTP.</small>
                </div>
                <button type="submit" class="auth-submit-btn">Send OTP</button>
                <div class="auth-extra-links">
                    <a href="<?= BASE_URL ?>/reset_password.php">Already have OTP? Reset Password</a>
                    <a href="<?= BASE_URL ?>/index.php">Home</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>