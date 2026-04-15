<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    try {
        if ($action === 'create') {
            $file = upload_file('material_file', ['pdf','doc','docx','ppt','pptx'], 10 * 1024 * 1024, ROOT_PATH . '/uploads/materials');

            $stmt = $pdo->prepare('
                INSERT INTO study_materials (subject_id, teacher_id, title, description, file_name)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                (int)$_POST['subject_id'],
                $teacherId,
                trim($_POST['title']),
                trim($_POST['description']),
                $file
            ]);

            flash('success', 'Study material uploaded successfully.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM study_materials WHERE id = ? AND teacher_id = ?');
            $stmt->execute([(int)$_POST['id'], $teacherId]);
            flash('success', 'Study material deleted.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect_to(BASE_URL . '/teacher/materials.php');
}

$subjectsStmt = $pdo->prepare('SELECT id, subject_name FROM subjects WHERE teacher_id = ? ORDER BY subject_name');
$subjectsStmt->execute([$teacherId]);
$subjects = $subjectsStmt->fetchAll();

$materialsStmt = $pdo->prepare('
    SELECT m.*, s.subject_name
    FROM study_materials m
    JOIN subjects s ON s.id = m.subject_id
    WHERE m.teacher_id = ?
    ORDER BY m.id DESC
');
$materialsStmt->execute([$teacherId]);
$materials = $materialsStmt->fetchAll();

$pageTitle = 'Study Materials | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Study Materials</h1>
                <p>Upload and manage learning materials for your subjects.</p>
            </div>
        </div>

        <div class="section-stack">
            <div class="form-card">
                <?php display_flash(); ?>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Subject</label>
                            <select name="subject_id" required>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= (int)$subject['id'] ?>"><?= e($subject['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" required>
                        </div>

                        <div class="form-group">
                            <label>File</label>
                            <input type="file" name="material_file" required accept=".pdf,.doc,.docx,.ppt,.pptx">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description"></textarea>
                    </div>

                    <button class="btn btn-primary">Upload Material</button>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search materials" data-table-search="materialsTable">
                </div>

                <div class="table-wrap">
                    <table id="materialsTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Subject</th>
                                <th>Description</th>
                                <th>File</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td><?= e($material['title']) ?></td>
                                    <td><?= e($material['subject_name']) ?></td>
                                    <td><?= e($material['description']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL . '/../uploads/materials/' . e($material['file_name']) ?>" target="_blank">Download</a>
                                    </td>
                                    <td><?= e(format_dt($material['created_at'])) ?></td>
                                    <td>
                                        <form method="post" onsubmit="return confirm('Delete this material?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$material['id'] ?>">
                                            <button class="icon-btn danger">🗑</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>