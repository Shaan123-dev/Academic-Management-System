<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);
$studentId = (int)user()['id'];

if (is_post()) {
    verify_csrf();
    try {
        $file = upload_file('submission_file', ['pdf','doc','docx'], 5 * 1024 * 1024, ROOT_PATH . '/uploads/submissions');
        $comment = trim($_POST['comment'] ?? '');
        $existing = $pdo->prepare('SELECT id FROM submissions WHERE assignment_id=? AND student_id=?');
        $existing->execute([(int)$_POST['assignment_id'], $studentId]);
        $old = $existing->fetch();

        if ($old) {
            $stmt = $pdo->prepare('UPDATE submissions SET file_name=?, comment=?, submitted_at=NOW() WHERE id=?');
            $stmt->execute([$file, $comment, (int)$old['id']]);
            flash('success', 'Assignment resubmitted successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO submissions (assignment_id, student_id, file_name, comment, submitted_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([(int)$_POST['assignment_id'], $studentId, $file, $comment]);
            flash('success', 'Assignment submitted successfully.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }
    redirect_to(BASE_URL . '/student/assignments.php');
}

$sql = 'SELECT a.*, s.subject_name, u.name AS teacher_name,
        sub.id AS submission_id, sub.file_name AS submitted_file, sub.comment, sub.submitted_at
        FROM assignments a
        JOIN subjects s ON s.id = a.subject_id
        JOIN users u ON u.id = a.teacher_id
        LEFT JOIN submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
        ORDER BY a.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$studentId]);
$assignments = $stmt->fetchAll();

$pageTitle='Assignments | '.APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel">
  <div class="assignment-hero">
    <h1>Assignments</h1>
    <p>Submit before due date. You can re-submit to update your submission.</p>
  </div>
  <?php display_flash(); ?>
  <div class="assignment-table">
    <div class="table-wrap">
      <table id="studentAssignmentTable">
        <thead>
          <tr>
            <th>Title</th>
            <th>Module</th>
            <th>Due</th>
            <th>Teacher</th>
            <th>File</th>
            <th>Status</th>
            <th>Submit</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($assignments as $a): ?>
          <tr>
            <td>
              <strong><?= e($a['title']) ?></strong>
              <div class="subtle"><?= e($a['instructions']) ?></div>
            </td>
            <td><?= e($a['subject_name']) ?></td>
            <td><?= e(date('Y-m-d', strtotime($a['deadline']))) ?></td>
            <td><?= e($a['teacher_name']) ?></td>
            <td>
              <?php if($a['file_name']): ?>
                <a class="file-link" href="<?= BASE_URL . '/../uploads/assignments/' . e($a['file_name']) ?>" target="_blank">Open</a>
              <?php else: ?>-
              <?php endif; ?>
            </td>
            <td>
              <?php if($a['submission_id']): ?>
                <span class="status-pill">Submitted</span>
                <div class="subtle" style="margin-top:8px;">On: <?= e($a['submitted_at']) ?></div>
              <?php else: ?>
                <span class="status-pill pending">Pending</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" enctype="multipart/form-data" class="submit-stack">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">
                <input type="file" name="submission_file" required accept=".pdf,.doc,.docx">
                <input type="text" name="comment" placeholder="Comment (optional)" value="<?= e($a['comment'] ?? '') ?>">
                <div class="submit-actions">
                  <button class="btn btn-blue" type="submit"><?= $a['submission_id'] ? 'Update Submission' : 'Submit Assignment' ?></button>
                  <?php if($a['submitted_file']): ?>
                    <a class="btn btn-secondary" href="<?= BASE_URL . '/../uploads/submissions/' . e($a['submitted_file']) ?>" target="_blank">My File</a>
                  <?php endif; ?>
                </div>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main></div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
