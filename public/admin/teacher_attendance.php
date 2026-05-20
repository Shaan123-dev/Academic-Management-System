<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

$selectedTeacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $teacher_id = (int)$_POST['teacher_id'];
        $subject_id = (int)$_POST['subject_id'];
        $attendance_date = $_POST['attendance_date'];
        $status = trim($_POST['status']);

        // Duplicate check (teacher + subject + date)
        $checkStmt = $pdo->prepare('
            SELECT id FROM teacher_attendance 
            WHERE teacher_id = ? AND subject_id = ? AND attendance_date = ?
        ');
        $checkStmt->execute([$teacher_id, $subject_id, $attendance_date]);
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate attendance! This teacher already has attendance for this subject on this date.');
            redirect_to(BASE_URL . '/admin/teacher_attendance.php');
        }

        $stmt = $pdo->prepare('INSERT INTO teacher_attendance (teacher_id, subject_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$teacher_id, $subject_id, $attendance_date, $status, (int)user()['id']]);
        flash('success', 'Teacher attendance marked.');
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $teacher_id = (int)$_POST['teacher_id'];
        $subject_id = (int)$_POST['subject_id'];
        $attendance_date = $_POST['attendance_date'];
        $status = trim($_POST['status']);

        $checkStmt = $pdo->prepare('
            SELECT id FROM teacher_attendance 
            WHERE teacher_id = ? AND subject_id = ? AND attendance_date = ? AND id != ?
        ');
        $checkStmt->execute([$teacher_id, $subject_id, $attendance_date, $id]);
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate attendance! Another record exists for this teacher, subject, and date.');
            redirect_to(BASE_URL . '/admin/teacher_attendance.php');
        }

        $stmt = $pdo->prepare('UPDATE teacher_attendance SET teacher_id=?, subject_id=?, attendance_date=?, status=? WHERE id=?');
        $stmt->execute([$teacher_id, $subject_id, $attendance_date, $status, $id]);
        flash('success', 'Teacher attendance updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM teacher_attendance WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Record deleted.');
    }
    redirect_to(BASE_URL . '/admin/teacher_attendance.php');
}

$teachers = $pdo->query('SELECT id, name, department FROM users WHERE role="teacher" ORDER BY name')->fetchAll();

$filteredSubjects = [];
if ($selectedTeacherId > 0) {
    $subjStmt = $pdo->prepare('SELECT id, subject_name FROM subjects WHERE teacher_id = ? ORDER BY subject_name');
    $subjStmt->execute([$selectedTeacherId]);
    $filteredSubjects = $subjStmt->fetchAll();
}

$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM teacher_attendance WHERE id=?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
    if ($edit) $selectedTeacherId = $edit['teacher_id'];
}

$rows = $pdo->query('
    SELECT ta.*, u.name, u.department, s.subject_name
    FROM teacher_attendance ta
    JOIN users u ON u.id = ta.teacher_id
    LEFT JOIN subjects s ON s.id = ta.subject_id
    ORDER BY attendance_date DESC
')->fetchAll();

$pageTitle = 'Teacher Attendance | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Teacher Attendance</h1>
                <p>Mark attendance per subject. Select a teacher first to see available subjects. No duplicates allowed for the same teacher, subject, and date.</p>
            </div>
        </div>
        <div class="section-stack">
            <div class="form-card">
                <?php display_flash(); ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                    <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Teacher <span class="required">*</span></label>
                            <select name="teacher_id" id="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" <?= ($selectedTeacherId == $t['id']) ? 'selected' : '' ?> <?= ((int)($edit['teacher_id'] ?? 0) == (int)$t['id']) ? 'selected' : '' ?>>
                                        <?= e($t['name']) ?> (<?= e($t['department'] ?: 'No Dept') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <select name="subject_id" id="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php if ($selectedTeacherId > 0): ?>
                                    <?php foreach ($filteredSubjects as $sub): ?>
                                        <option value="<?= (int)$sub['id'] ?>" <?= ((int)($edit['subject_id'] ?? 0) == (int)$sub['id']) ? 'selected' : '' ?>>
                                            <?= e($sub['subject_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Select a teacher first</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date <span class="required">*</span></label>
                            <input type="date" name="attendance_date" required value="<?= e($edit['attendance_date'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="status">
                                <option <?= (($edit['status'] ?? '') === 'Present') ? 'selected' : '' ?>>Present</option>
                                <option <?= (($edit['status'] ?? '') === 'Absent') ? 'selected' : '' ?>>Absent</option>
                                <option <?= (($edit['status'] ?? '') === 'Late') ? 'selected' : '' ?>>Late</option>
                            </select>
                        </div>
                    </div>
                    <div class="inline-actions">
                        <button class="btn btn-primary"><?= $edit ? 'Update Attendance' : 'Save Attendance' ?></button>
                        <?php if ($edit): ?>
                            <a class="btn btn-secondary" href="teacher_attendance.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search teacher attendance" data-table-search="teacherAttendTable">
                    <select data-filter-target="teacherAttendTable">
                        <option value="">All Status</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table id="teacherAttendTable">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Department</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= e($r['name']) ?></td>
                                    <td><?= e($r['department'] ?: 'N/A') ?></td>
                                    <td><?= e($r['subject_name'] ?: 'No subject') ?></td>
                                    <td><?= e($r['attendance_date']) ?></td>
                                    <td><span class="kpi"><?= e($r['status']) ?></span></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" title="Edit" href="?edit=<?= (int)$r['id'] ?>">✎</a>
                                            <form method="post">
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
        </div>
    </main>
</div>

<script>
    document.getElementById('teacher_id').addEventListener('change', function() {
        var teacherId = this.value;
        if (teacherId) {
            window.location.href = '<?= BASE_URL ?>/admin/teacher_attendance.php?teacher_id=' + teacherId;
        } else {
            window.location.href = '<?= BASE_URL ?>/admin/teacher_attendance.php';
        }
    });
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>