<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php'; require_role(['student']); $studentId=(int)user()['id'];
$stmt=$pdo->prepare('SELECT COUNT(*) FROM assignments'); $stmt->execute(); $assignments=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare('SELECT COUNT(*) FROM announcements WHERE visibility_role IN ("all","student")'); $stmt->execute(); $announcementsCount=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare('SELECT COUNT(*) FROM submissions WHERE student_id=?'); $stmt->execute([$studentId]); $submitted=(int)$stmt->fetchColumn();
$announcements=$pdo->query('SELECT title, body, posted_at FROM announcements WHERE visibility_role IN ("all","student") ORDER BY posted_at DESC LIMIT 4')->fetchAll();
$pageTitle='Student Dashboard | '.APP_NAME; include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel"><div class="dashboard-top"><div class="dashboard-title"><h1>Student Dashboard</h1><p><?= e(current_datetime()) ?> • Welcome, <?= e(user()['name']) ?></p></div><div class="user-chip">🎓 Student Panel</div></div>
<div class="metric-grid"><div class="metric-card"><div class="label">Assignments</div><div class="value"><?= $assignments ?></div><div class="subtext">Available tasks</div></div><div class="metric-card"><div class="label">Submitted</div><div class="value"><?= $submitted ?></div><div class="subtext">Completed uploads</div></div><div class="metric-card"><div class="label">Announcements</div><div class="value"><?= $announcementsCount ?></div><div class="subtext">Visible notices</div></div><div class="metric-card"><div class="label">Today</div><div class="value"><?= date('d') ?></div><div class="subtext"><?= date('l') ?></div></div></div>
<div class="dashboard-grid"><div class="panel-card">
    <h3>Quick Actions</h3>

    <div class="quick-actions">
        <a href="<?= BASE_URL ?>/student/attendance.php">
            <span class="qa-emoji">📋</span>
            <span>Attendance</span>
        </a>

        <a href="<?= BASE_URL ?>/student/courses.php">
            <span class="qa-emoji">📚</span>
            <span>Courses</span>
        </a>

        <a href="<?= BASE_URL ?>/student/subjects.php">
            <span class="qa-emoji">📖</span>
            <span>Subjects</span>
        </a>

        <a href="<?= BASE_URL ?>/student/assignments.php">
            <span class="qa-emoji">📝</span>
            <span>Assignments</span>
        </a>

        <a href="<?= BASE_URL ?>/student/materials.php">
            <span class="qa-emoji">📂</span>
            <span>Materials</span>
        </a>

        <a href="<?= BASE_URL ?>/student/results.php">
            <span class="qa-emoji">📊</span>
            <span>Results</span>
        </a>
    </div>
</div><div class="panel-card"><h3>Recent Announcements</h3><div class="list-clean"><?php foreach($announcements as $a): ?><div class="list-item"><strong><?= e($a['title']) ?></strong><span><?= e(mb_strimwidth($a['body'],0,90,'...')) ?></span></div><?php endforeach; ?></div></div></div>
</main></div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
