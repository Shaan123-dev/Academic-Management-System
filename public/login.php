<?php
require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $result = login_user($email, $password);

        if ($result['success']) {
            if ($result['role'] === 'Admin') {
                header('Location: /AMS/public/admin/dashboard.php');
                exit;
            } elseif ($result['role'] === 'Teacher') {
                header('Location: /AMS/public/teacher/dashboard.php');
                exit;
            } elseif ($result['role'] === 'Student') {
                header('Location: /AMS/public/student/dashboard.php');
                exit;
            } else {
                $error = 'Unknown role detected.';
            }
        } else {
            $error = $result['message'];
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2>Login Page</h2>

<?php if ($error !== ''): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>