<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare('
                INSERT INTO enrollments (student_id, course_id, year_label, semester, status)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                (int)$_POST['student_id'],
                (int)$_POST['course_id'],
                trim($_POST['year_label']),
                trim($_POST['semester']),
                trim($_POST['status'])
            ]);
            flash('success', 'Student assigned to course successfully.');
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare('
                UPDATE enrollments
                SET student_id = ?, course_id = ?, year_label = ?, semester = ?, status = ?
                WHERE id = ?
            ');
            $stmt->execute([
                (int)$_POST['student_id'],
                (int)$_POST['course_id'],
                trim($_POST['year_label']),
                trim($_POST['semester']),
                trim($_POST['status']),
                (int)$_POST['id']
            ]);
            flash('success', 'Enrollment updated successfully.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM enrollments WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            flash('success', 'Enrollment deleted.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect_to(BASE_URL . '/admin/enrollments.php');
}

$students = $pdo->query('SELECT id, role_code, name FROM users WHERE role = "student" ORDER BY name')->fetchAll();
$courses = $pdo->query('SELECT id, course_name, year_label, semester FROM courses ORDER BY course_name, year_label, semester')->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM enrollments WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$list = $pdo->query('
    SELECT e.*, u.name AS student_name, u.role_code, c.course_name
    FROM enrollments e
    JOIN users u ON u.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    ORDER BY e.id DESC
')->fetchAll();

$pageTitle = 'Enrollments | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Assign Student to Course</h1>
                <p>Manage student enrollments by course, year, and semester.</p>
            </div>
        </div>

        <div class="section-stack">
            <div class="form-card">
                <?php display_flash(); ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                    <?php if ($edit): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= (int)$student['id'] ?>" <?= ((int)($edit['student_id'] ?? 0) === (int)$student['id']) ? 'selected' : '' ?>>
                                        <?= e($student['role_code'] . ' - ' . $student['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Course</label>
                            <select name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= (int)$course['id'] ?>" <?= ((int)($edit['course_id'] ?? 0) === (int)$course['id']) ? 'selected' : '' ?>>
                                        <?= e($course['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Year</label>
                            <input type="text" name="year_label" required value="<?= e($edit['year_label'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Semester</label>
                            <input type="text" name="semester" required value="<?= e($edit['semester'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" <?= (($edit['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (($edit['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="inline-actions">
                        <button class="btn btn-primary"><?= $edit ? 'Update Enrollment' : 'Assign Course' ?></button>
                        <?php if ($edit): ?>
                            <a class="btn btn-secondary" href="enrollments.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search enrollment by student, code, course, semester" data-table-search="enrollmentTable">
                </div>

                <div class="table-wrap">
                    <table id="enrollmentTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Code</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($list as $row): ?>
                                <tr>
                                    <td><?= e($row['student_name']) ?></td>
                                    <td><?= e($row['role_code']) ?></td>
                                    <td><?= e($row['course_name']) ?></td>
                                    <td><?= e($row['year_label']) ?></td>
                                    <td><?= e($row['semester']) ?></td>
                                    <td><?= e($row['status']) ?></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" href="?edit=<?= (int)$row['id'] ?>">✎</a>
                                            <form method="post" onsubmit="return confirm('Delete this enrollment?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                                <button class="icon-btn danger" type="submit">🗑</button>
                                            </form>
                                        </div>
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