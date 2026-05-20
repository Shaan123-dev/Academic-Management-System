<?php
require_once dirname(__DIR__) . '/includes/auth.php';

// Redirect if already logged in
if (logged_in()) {
    redirect_to(dashboard_path(user()['role'] ?? null));
}

if (is_post()) {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = validate_required([
        'email' => 'Email',
        'password' => 'Password',
    ], $_POST);

    if (!$errors) {
        // Check for rate limiting
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM login_attempts 
            WHERE (email = ? OR ip_address = ?) 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ');
        $stmt->execute([$email, $ip]);
        $attempts = (int)$stmt->fetchColumn();

        if ($attempts >= 5) {
            flash('danger', 'Too many failed attempts. Please try again later.');
            redirect_to(BASE_URL . '/login.php');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
        $stmt->execute([$email]);
        $found = $stmt->fetch();

        if ($found && password_verify($password, $found['password'])) {
            $_SESSION['user'] = $found;
            regenerate_session();

            $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = ?');
            $stmt->execute([$email]);

            flash('success', 'Login successful.');
            redirect_to(dashboard_path($found['role']));
        }

        $stmt = $pdo->prepare('
            INSERT INTO login_attempts (email, ip_address, attempt_time) 
            VALUES (?, ?, NOW())
        ');
        $stmt->execute([$email, $ip]);

        $errors[] = 'Invalid email or password.';
    }

    foreach ($errors as $error) {
        flash('danger', $error);
    }

    redirect_to(BASE_URL . '/login.php');
}

$pageTitle = 'Login | ' . APP_NAME;
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
            <h1>Welcome Back</h1>
            <p>Sign in to continue to your academic dashboard and manage schedules, attendance, assignments, results, and announcements.</p>
        </div>
    </div>
    <div class="auth-right-panel">
        <div class="auth-top-links">
            <a href="<?= BASE_URL ?>/index.php">Home</a>
        </div>
        <div class="auth-form-card">
            <div class="auth-header-row">
                <div class="auth-header-main">
                    <h2>Sign In</h2>
                    <p>Enter your account details to continue.</p>
                </div>
            </div>
            <?php display_flash(); ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="auth-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required>
                </div>
                <div class="auth-group">
                    <label>Password <span class="required">*</span></label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="login_password" required style="padding-right: 45px; width: 100%;">
                        <button type="button" onclick="togglePassword('login_password')"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                    background: none; border: none; cursor: pointer; font-size: 18px; 
                                    padding: 0; margin: 0; color: #888;">
                            🔒
                        </button>
                    </div>
                </div>
                <button type="submit" class="auth-submit-btn">Sign In</button>
                <div class="auth-bottom-row">
                    <a href="<?= BASE_URL ?>/forgot_password.php">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>