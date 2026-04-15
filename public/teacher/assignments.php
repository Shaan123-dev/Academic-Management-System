<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    try {
        if ($action === 'create') {
            $file = upload_file('assignment_file', ['pdf','doc','docx'], 5 * 1024 * 1024, ROOT_PATH . '/uploads/assignments');

            $stmt = $pdo->prepare('
                INSERT INTO assignments (subject_id, teacher_id, title, instructions, file_name, deadline, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmt->execute([
                (int)$_POST['subject_id'],
                $teacherId,
                trim($_POST['title']),
                trim($_POST['instructions']),
                $file,
                $_POST['deadline']
            ]);

            flash('success', 'Assignment created.');
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];

            $current = $pdo->prepare('SELECT file_name FROM assignments WHERE id = ? AND teacher_id = ?');
            $current->execute([$id, $teacherId]);
            $old = $current->fetch();

            $file = upload_file('assignment_file', ['pdf','doc','docx'], 5 * 1024 * 1024, ROOT_PATH . '/uploads/assignments') ?: ($old['file_name'] ?? null);

            $stmt = $pdo->prepare('
                UPDATE assignments
                SET subject_id = ?, title = ?, instructions = ?, file_name = ?, deadline = ?
                WHERE id = ? AND teacher_id = ?
            ');
            $stmt->execute([
                (int)$_POST['subject_id'],
                trim($_POST['title']),
                trim($_POST['instructions']),
                $file,
                $_POST['deadline'],
                $id,
                $teacherId
            ]);

            flash('success', 'Assignment updated.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM assignments WHERE id = ? AND teacher_id = ?');
            $stmt->execute([(int)$_POST['id'], $teacherId]);
            flash('success', 'Assignment deleted.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect_to(BASE_URL . '/teacher/assignments.php');
}

$subjectsStmt = $pdo->prepare('SELECT * FROM subjects WHERE teacher_id = ? ORDER BY subject_name');
$subjectsStmt->execute([$teacherId]);
$subjects = $subjectsStmt->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM assignments WHERE id = ? AND teacher_id = ?');
    $stmt->execute([(int)$_GET['edit'], $teacherId]);
    $edit = $stmt->fetch();
}

$rowsStmt = $pdo->prepare('
    SELECT a.*, s.subject_name, u.name AS teacher_name,
    (SELECT COUNT(*) FROM submissions sub WHERE sub.assignment_id = a.id) AS submission_count
    FROM assignments a
    JOIN subjects s ON s.id = a.subject_id
    JOIN users u ON u.id = a.teacher_id
    WHERE a.teacher_id = ?
    ORDER BY a.created_at DESC
');
$rowsStmt->execute([$teacherId]);
$rows = $rowsStmt->fetchAll();

$pageTitle = 'Assignments | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="assignment-hero">
            <h1>Assignment List</h1>
            <p>Create, edit, and manage assignments with submission tracking.</p>
        </div>

        <?php display_flash(); ?>

        <div class="form-card" style="margin-bottom:18px;">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Module</label>
                        <select name="subject_id" required>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['subject_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                                    <?= e($s['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Due</label>
                        <input type="date" name="deadline" required value="<?= !empty($edit['deadline']) ? e(date('Y-m-d', strtotime($edit['deadline']))) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>File</label>
                        <input type="file" name="assignment_file" accept=".pdf,.doc,.docx">
                    </div>
                </div>

                <div class="form-group">
                    <label>Instructions</label>
                    <textarea name="instructions"><?= e($edit['instructions'] ?? '') ?></textarea>
                </div>

                <div class="inline-actions">
                    <button class="btn btn-primary"><?= $edit ? 'Update Assignment' : 'Create Assignment' ?></button>
                    <?php if ($edit): ?>
                        <a class="btn btn-secondary" href="assignments.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="assignment-table">
            <div class="table-wrap">
                <table id="teacherAssignmentTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Module</th>
                            <th>Due</th>
                            <th>Teacher</th>
                            <th>File</th>
                            <th>Submissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= (int)$r['id'] ?></td>
                                <td><?= e($r['title']) ?></td>
                                <td><?= e($r['subject_name']) ?></td>
                                <td><?= e(date('Y-m-d', strtotime($r['deadline']))) ?></td>
                                <td><?= e($r['teacher_name']) ?></td>
                                <td>
                                    <?php if ($r['file_name']): ?>
                                        <a class="file-link" href="<?= BASE_URL . '/../uploads/assignments/' . e($r['file_name']) ?>" target="_blank">Open</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-secondary" href="<?= BASE_URL ?>/teacher/submissions.php?assignment=<?= (int)$r['id'] ?>">
                                        View (<?= (int)$r['submission_count'] ?>)
                                    </a>
                                </td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="icon-btn" href="?edit=<?= (int)$r['id'] ?>">✎</a>
                                        <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this assignment?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button class="icon-btn danger" title="Delete">🗑</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>