<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);

$studentId = (int) user()['id'];

if (is_post()) {
    verify_csrf();
    try {
        $assignment_id = (int)$_POST['assignment_id'];

        $checkStmt = $pdo->prepare('
            SELECT a.id, a.deadline, a.title, a.file_name as assignment_file
            FROM assignments a
            JOIN subjects s ON s.id = a.subject_id
            JOIN enrollments e ON e.course_id = s.course_id
            WHERE a.id = ? AND e.student_id = ? AND e.status = "active"
            AND a.deadline > NOW()
            LIMIT 1
        ');
        $checkStmt->execute([$assignment_id, $studentId]);
        $assignment = $checkStmt->fetch();

        if (!$assignment) {
            flash('danger', 'Assignment not found or submission deadline has passed.');
            redirect_to(BASE_URL . '/student/assignments.php');
        }

        $file = upload_file('submission_file', ['pdf', 'doc', 'docx'], 5 * 1024 * 1024, ROOT_PATH . '/uploads/submissions');
        $comment = trim($_POST['comment'] ?? '');

        if (!$file) {
            flash('danger', 'Please select a file to upload.');
            redirect_to(BASE_URL . '/student/assignments.php');
        }

        $existing = $pdo->prepare('SELECT id FROM submissions WHERE assignment_id=? AND student_id=?');
        $existing->execute([$assignment_id, $studentId]);
        $old = $existing->fetch();

        if ($old) {
            $oldFileStmt = $pdo->prepare('SELECT file_name FROM submissions WHERE id=?');
            $oldFileStmt->execute([$old['id']]);
            $oldFile = $oldFileStmt->fetch();

            if ($oldFile && $oldFile['file_name']) {
                delete_secure_file($oldFile['file_name'], ROOT_PATH . '/uploads/submissions');
            }

            $stmt = $pdo->prepare('UPDATE submissions SET file_name=?, comment=?, submitted_at=NOW() WHERE id=?');
            $stmt->execute([$file, $comment, (int)$old['id']]);
            flash('success', 'Assignment resubmitted successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO submissions (assignment_id, student_id, file_name, comment, submitted_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$assignment_id, $studentId, $file, $comment]);
            flash('success', 'Assignment submitted successfully.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect_to(BASE_URL . '/student/assignments.php');
}

$now = date('Y-m-d H:i:s');

$activeStmt = $pdo->prepare('
    SELECT 
        a.*, 
        s.subject_name, 
        u.name AS teacher_name,
        sub.id AS submission_id, 
        sub.file_name AS submitted_file, 
        sub.comment, 
        sub.submitted_at
    FROM assignments a
    JOIN subjects s ON s.id = a.subject_id
    JOIN users u ON u.id = a.teacher_id
    JOIN enrollments e ON e.course_id = s.course_id
    LEFT JOIN submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
    WHERE e.student_id = ? 
        AND e.status = "active"
        AND a.deadline > ?
    ORDER BY a.deadline ASC
');
$activeStmt->execute([$studentId, $studentId, $now]);
$activeAssignments = $activeStmt->fetchAll();

$pastStmt = $pdo->prepare('
    SELECT 
        a.*, 
        s.subject_name, 
        u.name AS teacher_name,
        sub.id AS submission_id, 
        sub.file_name AS submitted_file, 
        sub.comment, 
        sub.submitted_at
    FROM assignments a
    JOIN subjects s ON s.id = a.subject_id
    JOIN users u ON u.id = a.teacher_id
    JOIN enrollments e ON e.course_id = s.course_id
    LEFT JOIN submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
    WHERE e.student_id = ? 
        AND e.status = "active"
        AND a.deadline <= ?
    ORDER BY a.deadline DESC
');
$pastStmt->execute([$studentId, $studentId, $now]);
$pastAssignments = $pastStmt->fetchAll();

$pageTitle = 'Assignments | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="assignment-hero">
            <h1>Assignments</h1>
            <p>Submit before due date. Past assignments cannot be submitted after deadline.</p>
        </div>

        <?php display_flash(); ?>

        <div class="assignment-table">
            <h2 style="margin-top:0; color: #148f3c;">📝 Active Assignments</h2>
            <p style="margin-bottom: 15px; color: #666;">Submit these assignments before the deadline.</p>

            <div class="table-wrap">
                <table id="activeAssignmentTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Module</th>
                            <th>Due Date</th>
                            <th>Teacher</th>
                            <th>Assignment File</th>
                            <th>Status</th>
                            <th>Submit</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($activeAssignments) > 0): ?>
                            <?php foreach ($activeAssignments as $a): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($a['title']) ?></strong>
                                        <div class="subtle"><?= e($a['instructions']) ?></div>
                                    </td>

                                    <td><?= e($a['subject_name']) ?></td>

                                    <td>
                                        <?= e(date('d M Y, h:i A', strtotime($a['deadline']))) ?>
                                        <?php if (strtotime($a['deadline']) - time() < 86400): ?>
                                            <span style="color: #e74c3c; display: block; font-size: 0.75rem;">⚠️ Due soon!</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= e($a['teacher_name']) ?></td>

                                    <td>
                                        <?php if ($a['file_name']): ?>
                                            <div class="inline-actions">
                                                <a class="file-link" href="<?= BASE_URL ?>/open_file.php?type=assignment&file=<?= urlencode($a['file_name']) ?>" target="_blank">📄 View Assignment</a>
                                                <a class="file-link" href="<?= BASE_URL . '/../uploads/assignments/' . rawurlencode(basename($a['file_name'])) ?>" download>⬇ Download</a>
                                            </div>
                                        <?php else: ?>
                                            <span class="subtle">No file</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($a['submission_id']): ?>
                                            <span class="status-pill">✅ Submitted</span>
                                            <div class="subtle" style="margin-top:5px;">On: <?= e(date('d M Y, h:i A', strtotime($a['submitted_at']))) ?></div>
                                        <?php else: ?>
                                            <span class="status-pill pending">⏳ Pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($a['submission_id']): ?>
                                            <form method="post" enctype="multipart/form-data" class="submit-stack">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">
                                                <input type="file" name="submission_file" required accept=".pdf,.doc,.docx" style="font-size: 0.85rem;">
                                                <input type="text" name="comment" placeholder="Comment (optional)" value="<?= e($a['comment'] ?? '') ?>" style="font-size: 0.85rem;">

                                                <div class="submit-actions">
                                                    <button class="btn btn-blue" type="submit">🔄 Resubmit</button>

                                                    <?php if ($a['submitted_file']): ?>
                                                        <a class="btn btn-secondary" href="<?= BASE_URL ?>/open_file.php?type=submission&file=<?= urlencode($a['submitted_file']) ?>" target="_blank">📄 View My Submission</a>
                                                        <a class="btn btn-secondary" href="<?= BASE_URL . '/../uploads/submissions/' . rawurlencode(basename($a['submitted_file'])) ?>" download>⬇ Download</a>
                                                    <?php endif; ?>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" enctype="multipart/form-data" class="submit-stack">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">
                                                <input type="file" name="submission_file" required accept=".pdf,.doc,.docx">
                                                <input type="text" name="comment" placeholder="Comment (optional)">
                                                <button class="btn btn-primary" type="submit">📤 Submit Assignment</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty" style="text-align: center; padding: 40px;">
                                    <span style="font-size: 48px;">📭</span>
                                    <h3>No Active Assignments</h3>
                                    <p>You have no pending assignments at the moment.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (count($pastAssignments) > 0): ?>
            <div class="assignment-table" style="margin-top: 30px;">
                <h2 style="margin-top:0; color: #888;">📚 Past Assignments</h2>
                <p style="margin-bottom: 15px; color: #666;">These assignments are past the deadline. Submission is no longer available.</p>

                <div class="table-wrap">
                    <table id="pastAssignmentTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Module</th>
                                <th>Due Date</th>
                                <th>Teacher</th>
                                <th>Assignment File</th>
                                <th>Your Submission</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($pastAssignments as $a): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($a['title']) ?></strong>
                                        <div class="subtle"><?= e($a['instructions']) ?></div>
                                    </td>

                                    <td><?= e($a['subject_name']) ?></td>

                                    <td>
                                        <?= e(date('d M Y, h:i A', strtotime($a['deadline']))) ?>
                                        <span style="color: #e74c3c; display: block; font-size: 0.7rem;">📅 Passed</span>
                                    </td>

                                    <td><?= e($a['teacher_name']) ?></td>

                                    <td>
                                        <?php if ($a['file_name']): ?>
                                            <div class="inline-actions">
                                                <a class="file-link" href="<?= BASE_URL ?>/open_file.php?type=assignment&file=<?= urlencode($a['file_name']) ?>" target="_blank">📄 View Assignment</a>
                                                <a class="file-link" href="<?= BASE_URL . '/../uploads/assignments/' . rawurlencode(basename($a['file_name'])) ?>" download>⬇ Download</a>
                                            </div>
                                        <?php else: ?>
                                            <span class="subtle">No file</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($a['submitted_file']): ?>
                                            <div class="inline-actions">
                                                <a class="file-link" href="<?= BASE_URL ?>/open_file.php?type=submission&file=<?= urlencode($a['submitted_file']) ?>" target="_blank">📄 View Your Submission</a>
                                                <a class="file-link" href="<?= BASE_URL . '/../uploads/submissions/' . rawurlencode(basename($a['submitted_file'])) ?>" download>⬇ Download</a>
                                            </div>
                                            <div class="subtle">Submitted: <?= e(date('d M Y, h:i A', strtotime($a['submitted_at']))) ?></div>
                                        <?php else: ?>
                                            <span class="subtle" style="color: #e74c3c;">❌ Not submitted</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($a['submission_id']): ?>
                                            <span class="status-pill">✅ Submitted</span>
                                        <?php else: ?>
                                            <span class="status-pill pending">❌ Missed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>