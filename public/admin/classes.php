<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $stmt = $pdo->prepare('
            INSERT INTO class_schedules
            (course_id, subject_id, teacher_id, day_name, start_time, end_time, classroom, year_label, semester)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            (int)$_POST['course_id'],
            (int)$_POST['subject_id'],
            (int)$_POST['teacher_id'],
            trim($_POST['day_name']),
            $_POST['start_time'],
            $_POST['end_time'],
            trim($_POST['classroom']),
            trim($_POST['year_label']),
            trim($_POST['semester'])
        ]);
        flash('success', 'Class added.');
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('
            UPDATE class_schedules
            SET course_id=?, subject_id=?, teacher_id=?, day_name=?, start_time=?, end_time=?, classroom=?, year_label=?, semester=?
            WHERE id=?
        ');
        $stmt->execute([
            (int)$_POST['course_id'],
            (int)$_POST['subject_id'],
            (int)$_POST['teacher_id'],
            trim($_POST['day_name']),
            $_POST['start_time'],
            $_POST['end_time'],
            trim($_POST['classroom']),
            trim($_POST['year_label']),
            trim($_POST['semester']),
            (int)$_POST['id']
        ]);
        flash('success', 'Class updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM class_schedules WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Class deleted.');
    }

    redirect_to(BASE_URL . '/admin/classes.php');
}

$courses = $pdo->query('SELECT id, course_name FROM courses ORDER BY course_name')->fetchAll();
$subjects = $pdo->query('SELECT id, subject_name FROM subjects ORDER BY subject_name')->fetchAll();
$teachers = $pdo->query('SELECT id, name FROM users WHERE role="teacher" ORDER BY name')->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM class_schedules WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$classes = $pdo->query('
    SELECT
        cs.*,
        c.course_name,
        s.subject_name,
        u.name AS teacher_name
    FROM class_schedules cs
    JOIN courses c ON c.id = cs.course_id
    JOIN subjects s ON s.id = cs.subject_id
    JOIN users u ON u.id = cs.teacher_id
    ORDER BY FIELD(day_name, "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"), start_time
')->fetchAll();

$pageTitle = 'Classes | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Class Management</h1>
                <p>Manage class schedule with cleaner spacing, year, teacher, subject, and room details.</p>
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
                            <select name="course_id" required>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($edit['course_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                        <?= e($c['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <select name="subject_id" required>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['subject_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                                        <?= e($s['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Teacher <span class="required">*</span></label>
                            <select name="teacher_id" required>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" <?= ((int)($edit['teacher_id'] ?? 0) === (int)$t['id']) ? 'selected' : '' ?>>
                                        <?= e($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Day <span class="required">*</span></label>
                            <select name="day_name" required>
                                <?php foreach (day_options() as $day): ?>
                                    <option value="<?= e($day) ?>" <?= (($edit['day_name'] ?? '') === $day) ? 'selected' : '' ?>>
                                        <?= e($day) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Start Time <span class="required">*</span></label>
                            <input type="time" name="start_time" required value="<?= e($edit['start_time'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>End Time <span class="required">*</span></label>
                            <input type="time" name="end_time" required value="<?= e($edit['end_time'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Classroom <span class="required">*</span></label>
                            <input type="text" name="classroom" required value="<?= e($edit['classroom'] ?? '') ?>">
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

                    <button class="btn btn-primary"><?= $edit ? 'Update Class' : 'Add Class' ?></button>
                    <?php if ($edit): ?>
                        <a class="btn btn-secondary" href="classes.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search class schedule" data-table-search="classTable">
                </div>

                <div class="table-wrap">
                    <table id="classTable">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Course</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Room</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $row): ?>
                                <tr>
                                    <td><?= e($row['day_name']) ?></td>
                                    <td><?= e(substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5)) ?></td>
                                    <td><?= e($row['course_name']) ?></td>
                                    <td><?= e($row['subject_name']) ?></td>
                                    <td><?= e($row['teacher_name']) ?></td>
                                    <td><?= e($row['classroom']) ?></td>
                                    <td><?= e($row['year_label']) ?></td>
                                    <td><?= e($row['semester']) ?></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" title="Edit" href="?edit=<?= (int)$row['id'] ?>">✎</a>

                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                                <button class="icon-btn danger" title="Delete" type="submit">🗑</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$classes): ?>
                                <tr>
                                    <td colspan="9" class="empty">No classes found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>