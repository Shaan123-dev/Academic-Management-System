<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];
$assignmentId = (int)($_GET['assignment'] ?? 0);

$assignmentStmt = $pdo->prepare('
    SELECT a.*, s.subject_name 
    FROM assignments a 
    JOIN subjects s ON s.id = a.subject_id 
    WHERE a.id = ? AND a.teacher_id = ? LIMIT 1
');
$assignmentStmt->execute([$assignmentId, $teacherId]);
$assignment = $assignmentStmt->fetch();

if (!$assignment) {
    flash('danger', 'Assignment not found.');
    redirect_to(BASE_URL . '/teacher/assignments.php');
}

$listStmt = $pdo->prepare('
    SELECT sub.*, u.name AS student_name, u.email
    FROM submissions sub
    JOIN users u ON u.id = sub.student_id
    WHERE sub.assignment_id = ? ORDER BY sub.submitted_at DESC
');
$listStmt->execute([$assignmentId]);
$rows = $listStmt->fetchAll();

$pageTitle = 'Submissions | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="assignment-hero">
            <h1>Submissions</h1>
            <p><strong><?= e($assignment['title']) ?></strong> • <?= e($assignment['subject_name']) ?> • Due <?= e(date('Y-m-d', strtotime($assignment['deadline']))) ?></p>
        </div>

        <div class="back-row">
            <a class="back-link" href="<?= BASE_URL ?>/teacher/assignments.php">← Back to assignments</a>
        </div>

        <div class="assignment-table">
            <h2 style="margin-top:0;">Student Submissions</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Comment</th>
                            <th>Submitted At</th>
                            <th>File</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= e($row['student_name']) ?></strong></td>
                                <td><?= e($row['email']) ?></td>
                                <td><?= e($row['comment'] ?? '-') ?></td>
                                <td><?= e(date('d M Y, h:i A', strtotime($row['submitted_at']))) ?></td>
                                <td>
                                    <?php if ($row['file_name']): ?>
                                        <a class="file-link" href="<?= BASE_URL ?>/open_file.php?type=submission&file=<?= urlencode($row['file_name']) ?>" target="_blank">📄 View File</a>
                                    <?php else: ?>
                                        <span class="subtle">No file</span>
                                    <?php endif; ?>
            </div>
            <td>
                <div class="inline-actions">
                    <?php if ($row['file_name']): ?>
                        <a class="btn btn-secondary" href="<?= BASE_URL . '/../uploads/submissions/' . e($row['file_name']) ?>" download style="text-decoration:none;">⬇ Download</a>
                    <?php endif; ?>
                </div>
        </div>
        </tr>
    <?php endforeach; ?>

    <?php if (empty($rows)): ?>
        <tr>
            <td colspan="6" class="empty" style="text-align: center; padding: 40px;">
                <span style="font-size: 48px;">📭</span>
                <h3>No Submissions Yet</h3>
                <p>No students have submitted this assignment yet.</p>
</div>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</main>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>