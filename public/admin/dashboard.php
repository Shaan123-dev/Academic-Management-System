<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);
$stats = stats($pdo);
$announcements = $pdo->query('SELECT title, body, posted_at FROM announcements ORDER BY posted_at DESC LIMIT 5')->fetchAll();
$pageTitle = 'Admin Dashboard | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
<?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
<main class="main-panel">
  <div class="dashboard-top">
    <div class="dashboard-title">
      <h1>Admin Dashboard</h1>
      <p><?= e(current_datetime()) ?> • Welcome, <?= e(user()['name']) ?></p>
    </div>
    <div class="user-chip">👑 Admin Panel</div>
  </div>

  <div class="metric-grid">
    <div class="metric-card"><div class="label">Students</div><div class="value"><?= (int)$stats['students'] ?></div><div class="subtext">Registered student accounts</div></div>
    <div class="metric-card"><div class="label">Teachers</div><div class="value"><?= (int)$stats['teachers'] ?></div><div class="subtext">Active teaching staff</div></div>
    <div class="metric-card"><div class="label">Assignments</div><div class="value"><?= (int)$stats['assignments'] ?></div><div class="subtext">Uploaded academic tasks</div></div>
    <div class="metric-card"><div class="label">Announcements</div><div class="value"><?= (int)$stats['announcements'] ?></div><div class="subtext">Published notices</div></div>
  </div>

  <div class="dashboard-grid">

    <div class="panel-card">
        <h3>Quick Actions</h3>

        <div class="quick-actions">
            <a href="<?= BASE_URL ?>/admin/students.php">
                <span class="qa-emoji">🎓</span>
                <span>Students</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/teachers.php">
                <span class="qa-emoji">👨‍🏫</span>
                <span>Teachers</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/courses.php">
                <span class="qa-emoji">📚</span>
                <span>Courses</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/subjects.php">
                <span class="qa-emoji">📝</span>
                <span>Subjects</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/announcements.php">
                <span class="qa-emoji">📢</span>
                <span>Announcements</span>
            </a>

            <a href="<?= BASE_URL ?>/admin/reports.php">
                <span class="qa-emoji">📊</span>
                <span>Reports</span>
            </a>
        </div>
    </div>
    <div class="panel-card">
      <h3>Recent Announcements</h3>
      <div class="list-clean">
        <?php foreach ($announcements as $item): ?>
          <div class="list-item">
            <strong><?= e($item['title']) ?></strong>
            <span><?= e(mb_strimwidth($item['body'], 0, 90, '...')) ?> — <?= e(date('d M Y', strtotime($item['posted_at']))) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
