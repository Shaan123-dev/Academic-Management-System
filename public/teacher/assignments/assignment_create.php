<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Teacher Assignment Create Page
|--------------------------------------------------------------------------
| Teacher can:
| - Create assignment
| - Upload optional file
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

require_role('teacher');

$pageTitle = 'Create Assignment';
$pageDescription = 'Create a new assignment.';

$error = '';
$user = current_user();

/*
|--------------------------------------------------------------------------
| Get teacher ID
|--------------------------------------------------------------------------
*/
$teacherStmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ? LIMIT 1");
$teacherStmt->execute([$user['id']]);
$teacher = $teacherStmt->fetch();

$teacherId = (int)($teacher['id'] ?? 0);

if ($teacherId <= 0) {
    die('Teacher profile not found.');
}

$formData = [
    'title' => '',
    'description' => '',
    'due_date' => '',
    'status' => 'published'
];

if (is_post()) {
    verify_csrf();

    $title = clean_input($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $dueDate = clean_input($_POST['due_date'] ?? '');
    $status = clean_input($_POST['status'] ?? 'published');

    $allowedStatuses = ['draft', 'published', 'closed'];

    $formData = [
        'title' => $title,
        'description' => $description,
        'due_date' => $dueDate,
        'status' => $status
    ];

    if ($title === '' || mb_strlen($title) < 3) {
        $error = 'Title must be at least 3 characters.';
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = 'Invalid status selected.';
    } else {
        try {
            $uploadedFileName = null;
            $uploadedFilePath = null;

            /*
            |--------------------------------------------------------------------------
            | Optional file upload
            |--------------------------------------------------------------------------
            */
            if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = upload_file(
                    $_FILES['assignment_file'],
                    ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip'],
                    [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'application/zip',
                        'application/x-zip-compressed'
                    ],
                    __DIR__ . '/../../uploads',
                    10 * 1024 * 1024
                );

                if (!$upload) {
                    throw new Exception('Invalid assignment file. Allowed: PDF, DOC, DOCX, PPT, PPTX, ZIP. Max 10MB.');
                }

                $uploadedFileName = $upload['original_name'];
                $uploadedFilePath = 'uploads/' . $upload['saved_name'];
            }

            $insertStmt = $pdo->prepare("
                INSERT INTO assignments (
                    subject_id, class_id, teacher_id, title, description, due_date, status, file_name, file_path
                )
                VALUES (NULL, NULL, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $teacherId,
                $title,
                $description !== '' ? $description : null,
                $dueDate !== '' ? $dueDate : null,
                $status,
                $uploadedFileName,
                $uploadedFilePath
            ]);

            $assignmentId = (int)$pdo->lastInsertId();

            log_audit($pdo, (int) current_user()['id'], 'create', 'assignments', $assignmentId);

            set_flash('success', 'Assignment created successfully.');
            redirect(BASE_URL . '/public/teacher/assignments/assignment_index.php');
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<section class="content-section">
    <div class="container sidebar-layout">
        <?php require_once __DIR__ . '/../../../includes/sidebar.php'; ?>

        <div class="content-area">
            <div class="page-header">
                <h2>Create Assignment</h2>
                <p>Create a new assignment and upload an optional file.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?= csrf_input() ?>

                    <div class="form-group">
                        <label for="title">Assignment Title</label>
                        <input type="text" id="title" name="title" value="<?= e($formData['title']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?= e($formData['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <input type="date" id="due_date" name="due_date" value="<?= e($formData['due_date']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="published" <?= $formData['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $formData['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="closed" <?= $formData['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="assignment_file">Assignment File (Optional)</label>
                        <input type="file" id="assignment_file" name="assignment_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip">
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Create Assignment</button>
                        <a class="btn btn-outline" href="<?= BASE_URL ?>/public/teacher/assignments/assignment_index.php">Back to List</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>