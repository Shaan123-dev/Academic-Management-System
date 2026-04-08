<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Teacher Submission List Page
|--------------------------------------------------------------------------
| Teacher can:
| - View submissions for their assignments
| - Download submitted files
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

require_role('teacher');

$pageTitle = 'Assignment Submissions';
$pageDescription = 'Teacher submission overview page.';

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

$listStmt = $pdo->prepare("
    SELECT
        sub.id,
        sub.submitted_file_name,
        sub.submitted_file_path,
        sub.remarks,
        sub.submitted_at,
        a.title AS assignment_title,
        u.full_name AS student_name,
        s.student_code
    FROM assignment_submissions sub
    INNER JOIN assignments a ON a.id = sub.assignment_id
    INNER JOIN students s ON s.id = sub.student_id
    INNER JOIN users u ON u.id = s.user_id
    WHERE a.teacher_id = ?
    ORDER BY sub.submitted_at DESC
");
$listStmt->execute([$teacherId]);
$submissions = $listStmt->fetchAll();

require_once __DIR__ . '/../../../includes/header.php';
?>

<section class="content-section">
    <div class="container sidebar-layout">
        <?php require_once __DIR__ . '/../../../includes/sidebar.php'; ?>

        <div class="content-area">
            <div class="page-header">
                <h2>Assignment Submissions</h2>
                <p>View and download student submissions for your assignments.</p>
            </div>

            <div class="table-card">
                <h3>Submission List</h3>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Student</th>
                                <th>Student Code</th>
                                <th>File</th>
                                <th>Remarks</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($submissions): ?>
                                <?php foreach ($submissions as $row): ?>
                                    <tr>
                                        <td><?= e($row['assignment_title']) ?></td>
                                        <td><?= e($row['student_name']) ?></td>
                                        <td><?= e($row['student_code']) ?></td>
                                        <td>
                                            <a href="<?= BASE_URL . '/public/' . e($row['submitted_file_path']) ?>" target="_blank">
                                                <?= e($row['submitted_file_name']) ?>
                                            </a>
                                        </td>
                                        <td><?= e((string)($row['remarks'] ?? '')) ?></td>
                                        <td><?= e($row['submitted_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No submissions found.</td>
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