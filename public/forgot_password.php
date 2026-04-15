<?php
require_once dirname(__DIR__) . '/includes/auth.php';

if (is_post()) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $found = $stmt->fetch();

    if ($found) {
        $token = bin2hex(random_bytes(16));

        $update = $pdo->prepare('
            UPDATE users
            SET reset_token = ?, reset_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR)
            WHERE id = ?
        ');
        $update->execute([$token, (int)$found['id']]);

        $_SESSION['generated_reset_token'] = $token;
        flash('success', 'Reset token generated successfully.');
    } else {
        unset($_SESSION['generated_reset_token']);
        flash('danger', 'No account found for that email.');
    }

    redirect_to(BASE_URL . '/forgot_password.php');
}

$generatedToken = $_SESSION['generated_reset_token'] ?? null;

$pageTitle = 'Forgot Password | ' . APP_NAME;
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

            <h1>Simple Password Reset</h1>

            <p>
                Enter your email and the system will generate a reset token.
                Paste that token on the reset page.
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
                    <h2>Forgot Password</h2>
                    <p>Generate your reset token here.</p>
                </div>
            </div>

            <?php display_flash(); ?>

            <?php if ($generatedToken): ?>
                <div class="auth-group">
                    <label>Reset Token</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input
                            type="text"
                            id="resetToken"
                            value="<?= e($generatedToken) ?>"
                            readonly
                        >
                        <button type="button" class="btn btn-secondary" onclick="copyResetToken()">Copy</button>
                    </div>
                    <small id="copyMsg" style="display:none; color:green;">Copied!</small>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="auth-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required>
                </div>

                <button type="submit" class="auth-submit-btn">Generate Reset Token</button>

                <div class="auth-extra-links">
                    <a href="<?= BASE_URL ?>/reset_password.php">Go to Reset Password</a>
                    <a href="<?= BASE_URL ?>/index.php">Home</a>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function copyResetToken() {
    const input = document.getElementById('resetToken');
    const msg = document.getElementById('copyMsg');

    navigator.clipboard.writeText(input.value).then(function () {
        msg.style.display = 'inline';
        setTimeout(function () {
            msg.style.display = 'none';
        }, 1500);
    });
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>