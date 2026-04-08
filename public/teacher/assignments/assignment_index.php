<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Teacher Assignment List Page
|--------------------------------------------------------------------------
| Teacher can:
| - View own assignments
| - Delete own assignment
| - Go to create/edit pages
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

require_role('teacher');

$pageTitle = 'My Assignments';
$pageDescription = 'Teacher assignment list page.';

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

/*
|--------------------------------------------------------------------------
| Delete assignment
|--------------------------------------------------------------------------
*/
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $assignmentId = (int) $_GET['delete'];

    try {
        $deleteStmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND teacher_id = ?");
        $deleteStmt->execute([$assignmentId, $teacherId]);

        log_audit($pdo, (int) current_user()['id'], 'delete', 'assignments', $assignmentId);

        set_flash('success', 'Assignment deleted successfully.');
        redirect(BASE_URL . '/public/teacher/assignments/assignment_index.php');
    } catch (Throwable $e) {
        $error = 'Failed to delete assignment.';
    }
}

/*
|--------------------------------------------------------------------------
| Fetch teacher assignments
|--------------------------------------------------------------------------
*/
$listStmt = $pdo->prepare("
    SELECT id, title, description, due_date, status, file_name, file_path, created_at
    FROM assignments
    WHERE teacher_id = ?
    ORDER BY id DESC
");
$listStmt->execute([$teacherId]);
$assignments = $listStmt->fetchAll();

require_once __DIR__ . '/../../../includes/header.php';
?>

<section class="content-section">
    <div class="container sidebar-layout">
        <?php require_once __DIR__ . '/../../../includes/sidebar.php'; ?>

        <div class="content-area">
            <div class="page-header">
                <h2>My Assignments</h2>
                <p>Create, edit, and manage your assignments.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($msg = get_flash('success')): ?>
                <div class="alert alert-success"><?= e($msg) ?></div>
            <?php endif; ?>

            <div class="actions">
                <a class="btn btn-primary" href="<?= BASE_URL ?>/public/teacher/assignments/assignment_create.php">Create Assignment</a>
            </div>

            <div class="table-card">
                <h3>Assignment List</h3>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($assignments): ?>
                                <?php foreach ($assignments as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($row['title']) ?></strong><br>
                                            <small><?= e(mb_strimwidth((string)$row['description'], 0, 90, '...')) ?></small>
                                        </td>
                                        <td><?= e($row['due_date'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge <?= $row['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                                <?= e($row['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['file_path'])): ?>
                                                <a href="<?= BASE_URL . '/public/' . e($row['file_path']) ?>" target="_blank">Download</a>
                                            <?php else: ?>
                                                <span class="badge badge-warning">No File</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($row['created_at']) ?></td>
                                        <td>
                                            <div class="actions">
                                                <a class="btn btn-outline" href="<?= BASE_URL ?>/public/teacher/assignments/assignment_edit.php?id=<?= e((string)$row['id']) ?>">Edit</a>
                                                <a class="btn btn-danger"
                                                   href="<?= BASE_URL ?>/public/teacher/assignments/assignment_index.php?delete=<?= e((string)$row['id']) ?>"
                                                   onclick="return confirm('Are you sure you want to delete this assignment?');">
                                                   Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No assignments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>