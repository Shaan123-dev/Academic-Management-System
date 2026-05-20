<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$isLoggedIn = logged_in();
$role = $_SESSION['role'] ?? null;
?>

<div class="topbar topbar-slim">
  <div class="container topbar-inner slim-inner">
    <div class="brand slim-brand">
      <img src="<?= BASE_URL ?>/../assets/images/logo.png" alt="Marks Mafias Logo">
      <div class="brand-text slim-brand-text">
        <div class="brand-main">Marks Mafias</div>
        <div class="brand-sub">Academic Management Portal</div>
      </div>
    </div>

    <nav class="nav-links slim-nav">
      <a href="<?= BASE_URL ?>/index.php" class="active">Home</a>
      <a href="#gallery">Explore Gallery</a>

      <?php if ($isLoggedIn && $role): ?>
        <a href="<?= dashboard_path($role) ?>">Dashboard</a>
        <a href="<?= BASE_URL ?>/logout.php">Logout</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</div>