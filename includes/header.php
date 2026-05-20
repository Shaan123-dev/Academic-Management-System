<?php if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
} ?>
<?php
$path = $_SERVER['PHP_SELF'] ?? '';
$autoStyles = [BASE_URL . '/../assets/css/style.css'];

if (
    str_contains($path, '/admin/') ||
    str_contains($path, '/teacher/') ||
    str_contains($path, '/student/')
) {
    $autoStyles[] = BASE_URL . '/../assets/css/dashboard.css';
}

if (in_array(basename($path), ['login.php', 'forgot_password.php', 'reset_password.php'], true)) {
    $autoStyles[] = BASE_URL . '/../assets/css/auth.css';
}

$canonical =
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://'
    . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . ($path ?: '');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Academic Management Portal System for managing academic data, attendance, assignments, results, schedules, and announcements.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="A complete academic management portal for admin, teacher, and student users.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= BASE_URL ?>/../assets/images/logo.png">

    <?php foreach ($autoStyles as $css): ?>
        <link rel="stylesheet" href="<?= e($css) ?>">
    <?php endforeach; ?>

    <script defer src="<?= BASE_URL ?>/../assets/js/main.js"></script>
</head>

<body class="<?= isset($bodyClass) ? e($bodyClass) : '' ?>">