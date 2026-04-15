<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php'; require_role(['admin']);
$pageTitle='Admin Profile | '.APP_NAME; include dirname(dirname(__DIR__)) . '/includes/header.php'; $u=user(); ?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel">
<div class="dashboard-top"><div class="dashboard-title"><h1>Admin Profile</h1><p>Administrator biodata overview.</p></div></div>
<div class="content-card">
  <div class="grid-2">
    <div><p><strong>Name:</strong> <?= e($u['name']) ?></p><p><strong>Email:</strong> <?= e($u['email']) ?></p><p><strong>Role:</strong> <?= e(ucfirst($u['role'])) ?></p><p><strong>Contact:</strong> <?= e($u['contact']) ?></p></div>
    <div><p><strong>Department:</strong> <?= e($u['department']) ?></p><p><strong>Qualification:</strong> <?= e($u['qualification']) ?></p><p><strong>Address:</strong> <?= e($u['address']) ?></p><p><strong>Date of Birth:</strong> <?= e($u['dob']) ?></p></div>
  </div>
</div>
</main></div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
