<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO subjects (course_id, teacher_id, subject_code, subject_name, year_label, semester) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            (int)$_POST['course_id'],
            (int)$_POST['teacher_id'],
            trim($_POST['subject_code']),
            trim($_POST['subject_name']),
            trim($_POST['year_label']),
            trim($_POST['semester'])
        ]);
        flash('success', 'Subject added.');
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE subjects SET course_id=?, teacher_id=?, subject_code=?, subject_name=?, year_label=?, semester=? WHERE id=?');
        $stmt->execute([
            (int)$_POST['course_id'],
            (int)$_POST['teacher_id'],
            trim($_POST['subject_code']),
            trim($_POST['subject_name']),
            trim($_POST['year_label']),
            trim($_POST['semester']),
            (int)$_POST['id']
        ]);
        flash('success', 'Subject updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM subjects WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Subject deleted.');
    }

    redirect_to(BASE_URL . '/admin/subjects.php');
}

$courses = $pdo->query('SELECT id, course_name FROM courses ORDER BY course_name')->fetchAll();
$teachers = $pdo->query('SELECT id, name FROM users WHERE role="teacher" ORDER BY name')->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM subjects WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$subjects = $pdo->query('
    SELECT s.*, c.course_name, u.name AS teacher_name
    FROM subjects s
    JOIN courses c ON c.id=s.course_id
    JOIN users u ON u.id=s.teacher_id
    ORDER BY s.id DESC
')->fetchAll();

$pageTitle = 'Subjects | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Subject Management</h1>
                <p>Add subject code, year, semester, and teacher assignment.</p>
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
                            <label>Course <span class="required">*</span></label>
                            <select name="course_id">
                                <?php foreach($courses as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($edit['course_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                        <?= e($c['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Teacher <span class="required">*</span></label>
                            <select name="teacher_id">
                                <?php foreach($teachers as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" <?= ((int)($edit['teacher_id'] ?? 0) === (int)$t['id']) ? 'selected' : '' ?>>
                                        <?= e($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Subject Code <span class="required">*</span></label>
                            <input type="text" name="subject_code" required value="<?= e($edit['subject_code'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Subject Name <span class="required">*</span></label>
                            <input type="text" name="subject_name" required value="<?= e($edit['subject_name'] ?? '') ?>">
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

                    <button class="btn btn-primary"><?= $edit ? 'Update Subject' : 'Add Subject' ?></button>
                    <?php if($edit): ?>
                        <a class="btn btn-secondary" href="subjects.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search subjects" data-table-search="subjectTable">
                </div>

                <div class="table-wrap">
                    <table id="subjectTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Subject</th>
                                <th>Course</th>
                                <th>Teacher</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($subjects as $s): ?>
                                <tr>
                                    <td><?= e($s['subject_code']) ?></td>
                                    <td><?= e($s['subject_name']) ?></td>
                                    <td><?= e($s['course_name']) ?></td>
                                    <td><?= e($s['teacher_name']) ?></td>
                                    <td><?= e($s['year_label']) ?></td>
                                    <td><?= e($s['semester']) ?></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" title="Edit" href="?edit=<?= (int)$s['id'] ?>">✎</a>

                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
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