<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(dashboard_url_by_role(current_user()['role_name']));
}

$error = '';

if (is_post()) {
    verify_csrf();

    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $error = 'Password is required.';
    } else {
        if (login_user($pdo, $email, $password)) {
            redirect(dashboard_url_by_role(current_user()['role_name']));
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
$pageDescription = 'Secure login for Academic Management Portal.';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="login-page">
    <div class="login-card">
        <div class="logo-wrap">
            <img src="<?= BASE_URL ?>/assets/images/logo.jpeg" alt="Marks Mafias Logo">
        </div>

        <h2>Login</h2>
        <p>Sign in with your Admin, Teacher, or Student account.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($msg = get_flash('success')): ?>
            <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_input() ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>