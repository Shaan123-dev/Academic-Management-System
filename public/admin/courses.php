<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO courses (course_name, year_label, semester) VALUES (?, ?, ?)');
        $stmt->execute([trim($_POST['course_name']), trim($_POST['year_label']), trim($_POST['semester'])]);
        flash('success', 'Course added.');
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE courses SET course_name=?, year_label=?, semester=? WHERE id=?');
        $stmt->execute([trim($_POST['course_name']), trim($_POST['year_label']), trim($_POST['semester']), (int)$_POST['id']]);
        flash('success', 'Course updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM courses WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Course deleted.');
    }

    redirect_to(BASE_URL . '/admin/courses.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$courses = $pdo->query('SELECT * FROM courses ORDER BY id DESC')->fetchAll();

$pageTitle = 'Courses | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Course Management</h1>
                <p>Add year, semester, and maintain course records.</p>
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
                            <input type="text" name="year_label" required value="<?= e($edit['year_label'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Semester <span class="required">*</span></label>
                            <input type="text" name="semester" required value="<?= e($edit['semester'] ?? '') ?>">
                        </div>
                    </div>

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