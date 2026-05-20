<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    // Get form data
    $course_name = trim($_POST['course_name']);
    $year_label = trim($_POST['year_label']);
    $semester = trim($_POST['semester']);

    if ($action === 'create') {
        // Check for duplicate course (same name, year, semester)
        $checkStmt = $pdo->prepare('
            SELECT id FROM courses 
            WHERE course_name = ? AND year_label = ? AND semester = ?
        ');
        $checkStmt->execute([$course_name, $year_label, $semester]);

        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate course! A course with same name, year, and semester already exists.');
            redirect_to(BASE_URL . '/admin/courses.php');
        }

        // Insert new course
        $stmt = $pdo->prepare('INSERT INTO courses (course_name, year_label, semester) VALUES (?, ?, ?)');
        $stmt->execute([$course_name, $year_label, $semester]);
        flash('success', 'Course added successfully.');
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];

        // Check for duplicate course (excluding current course)
        $checkStmt = $pdo->prepare('
            SELECT id FROM courses 
            WHERE course_name = ? AND year_label = ? AND semester = ? AND id != ?
        ');
        $checkStmt->execute([$course_name, $year_label, $semester, $id]);

        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate course! Another course with same name, year, and semester already exists.');
            redirect_to(BASE_URL . '/admin/courses.php');
        }

        // Update course
        $stmt = $pdo->prepare('UPDATE courses SET course_name=?, year_label=?, semester=? WHERE id=?');
        $stmt->execute([$course_name, $year_label, $semester, $id]);
        flash('success', 'Course updated successfully.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM courses WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Course deleted successfully.');
    }

    redirect_to(BASE_URL . '/admin/courses.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$courses = $pdo->query('SELECT * FROM courses ORDER BY course_name, year_label, semester')->fetchAll();

$pageTitle = 'Courses | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Course Management</h1>
                <p>Add year, semester, and maintain course records. Duplicate courses (same name + year + semester) are not allowed.</p>
            </div>
        </div>

        <div class="section-stack">
            <div class="form-card">
                <?php display_flash(); ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                    <?= $edit ? '<input type="hidden" name="id" value="' . (int)$edit['id'] . '">' : '' ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Course Name <span class="required">*</span></label>
                            <input type="text" name="course_name" required value="<?= e($edit['course_name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Year <span class="required">*</span></label>
                            <input type="text" name="year_label" required value="<?= e($edit['year_label'] ?? '') ?>" placeholder="e.g., Year 1">
                        </div>

                        <div class="form-group">
                            <label>Semester <span class="required">*</span></label>
                            <input type="text" name="semester" required value="<?= e($edit['semester'] ?? '') ?>" placeholder="e.g., Semester 1">
                        </div>
                    </div>

                    <small style="color: #6c757d; display: block; margin-bottom: 15px;">
                        <strong>Note:</strong> Same course name can exist with different Year or Semester.
                        Example: "BSc Computing - Year 1 - Semester 1" and "BSc Computing - Year 1 - Semester 2" are both allowed.
                    </small>

                    <button class="btn btn-primary"><?= $edit ? 'Update Course' : 'Add Course' ?></button>
                    <?php if ($edit): ?>
                        <a class="btn btn-secondary" href="courses.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search courses" data-table-search="courseTable">
                </div>

                <div class="table-wrap">
                    <table id="courseTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= (int)$course['id'] ?></td>
                                    <td><?= e($course['course_name']) ?></td>
                                    <td><?= e($course['year_label']) ?></td>
                                    <td><?= e($course['semester']) ?></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" title="Edit" href="?edit=<?= (int)$course['id'] ?>">✎</a>

                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$course['id'] ?>">
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
        </div>
    </main>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>