<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Academic Management Portal';
$pageDescription = $pageDescription ?? 'Academic Management Portal System for Admin, Teacher, and Student management.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#0f3d91">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a href="<?= BASE_URL ?>/public/index.php" class="brand">
            <img src="<?= BASE_URL ?>/assets/images/logo.jpeg" alt="Marks Mafias Logo" class="brand-logo">
            <div class="brand-text">
                <h1>Academic Management Portal</h1>
                <p>Marks Mafias</p>
            </div>
        </a>

        <nav class="main-nav" aria-label="Main Navigation">
            <a href="<?= BASE_URL ?>/public/index.php">Home</a>

            <?php if (is_logged_in()): ?>
                <a href="<?= e(dashboard_url_by_role(current_user()['role_name'])) ?>">Dashboard</a>
                <a href="<?= BASE_URL ?>/public/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/public/login.php">Login</a>
                <a href="<?= BASE_URL ?>/public/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>