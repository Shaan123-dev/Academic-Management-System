<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php'; require_role(['teacher']);
$u=user(); $code=$u['role_code'] ?: digital_code('TCH', (int)$u['id']); $expiry=date('d M Y', strtotime('+1 year'));
$pageTitle='Digital ID | '.APP_NAME; include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel"><h1 class="page-title">Teacher Digital ID</h1>
<div class="id-wrap"><div class="id-card">
<div class="id-head"><div style="display:flex;align-items:center;gap:14px;"><img class="logo" src="<?= BASE_URL ?>/../assets/images/logo.png" alt="Logo"><div><strong style="font-size:1.15rem;">Marks Mafias</strong><div>Academic Management System</div></div></div><div class="id-highlight"><?= e($code) ?></div></div>
<div class="id-body">
<img class="avatar" src="<?= photo_path($u['photo'] ?? null) ?>" alt="Teacher Photo">
<div class="id-details"><div class="id-highlight">Official Teacher ID</div><h2><?= e($u['name']) ?></h2><p>Role: Teacher</p><p>Course: <?= e($u['department']) ?></p><p>Subject: <?= e($u['qualification']) ?></p><p>Email: <?= e($u['email']) ?></p><p>Contact: <?= e($u['contact']) ?></p><p>Expiry: <?= e($expiry) ?></p></div>
<img class="qr" src="<?= e(qr_url($code . '|' . $u['email'])) ?>" alt="QR Code">
</div>
<div class="print-btn"><button class="btn btn-secondary" onclick="window.print()">Print ID</button></div>
</div></div>
</main></div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
