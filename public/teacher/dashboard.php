<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php'; require_role(['teacher']);
$teacherId=(int)user()['id'];
$subjectCount=(int)$pdo->prepare('SELECT COUNT(*) FROM subjects WHERE teacher_id=?')->execute([$teacherId]) or 0;
$stmt=$pdo->prepare('SELECT COUNT(*) FROM subjects WHERE teacher_id=?'); $stmt->execute([$teacherId]); $subjectCount=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare('SELECT COUNT(*) FROM assignments WHERE teacher_id=?'); $stmt->execute([$teacherId]); $assignmentCount=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare('SELECT COUNT(*) FROM results WHERE teacher_id=?'); $stmt->execute([$teacherId]); $resultCount=(int)$stmt->fetchColumn();
$announcements=$pdo->prepare('SELECT title, body, posted_at FROM announcements WHERE visibility_role IN ("all","teacher") ORDER BY posted_at DESC LIMIT 4'); $announcements->execute(); $announcements=$announcements->fetchAll();
$pageTitle='Teacher Dashboard | '.APP_NAME; include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel">
<div class="dashboard-top"><div class="dashboard-title"><h1>Teacher Dashboard</h1><p><?= e(current_datetime()) ?> • Welcome, <?= e(user()['name']) ?></p></div><div class="user-chip">🧑‍🏫 Teacher Panel</div></div>
<div class="metric-grid">
<div class="metric-card"><div class="label">Subjects</div><div class="value"><?= $subjectCount ?></div><div class="subtext">Assigned subjects</div></div>
<div class="metric-card"><div class="label">Assignments</div><div class="value"><?= $assignmentCount ?></div><div class="subtext">Created by you</div></div>
<div class="metric-card"><div class="label">Results</div><div class="value"><?= $resultCount ?></div><div class="subtext">Published records</div></div>
<div class="metric-card"><div class="label">Date</div><div class="value"><?= date('d') ?></div><div class="subtext"><?= date('F Y') ?></div></div>
</div>
<div class="dashboard-grid">
<div class="panel-card">
    <h3>Quick Actions</h3>

    <div class="quick-actions">
        <a href="<?= BASE_URL ?>/teacher/attendance.php">
            <span class="qa-emoji">📋</span>
            <span>Attendance</span>
        </a>

        <a href="<?= BASE_URL ?>/teacher/classes.php">
            <span class="qa-emoji">🏫</span>
            <span>Classes</span>
        </a>

        <a href="<?= BASE_URL ?>/teacher/students.php">
            <span class="qa-emoji">👨‍🎓</span>
            <span>Students</span>
        </a>

        <a href="<?= BASE_URL ?>/teacher/assignments.php">
            <span class="qa-emoji">📝</span>
            <span>Assignments</span>
        </a>

        <a href="<?= BASE_URL ?>/teacher/materials.php">
            <span class="qa-emoji">📂</span>
            <span>Materials</span>
        </a>

        <a href="<?= BASE_URL ?>/teacher/results.php">
            <span class="qa-emoji">📊</span>
            <span>Results</span>
        </a>
    </div>
</div>
<div class="panel-card"><h3>Recent Announcements</h3><div class="list-clean"><?php foreach($announcements as $a): ?><div class="list-item"><strong><?= e($a['title']) ?></strong><span><?= e(mb_strimwidth($a['body'],0,90,'...')) ?></span></div><?php endforeach; ?></div></div>
</div></main></div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
