<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

// Get selected student from URL
$selectedStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
        // Duplicate check (already present)
        $checkStmt = $pdo->prepare('
            SELECT id FROM student_attendance 
            WHERE student_id = ? AND subject_id = ? AND attendance_date = ?
        ');
        $checkStmt->execute([(int)$_POST['student_id'], (int)$_POST['subject_id'], $_POST['attendance_date']]);

        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate attendance! Attendance for this student on this date already exists.');
            redirect_to(BASE_URL . '/admin/student_attendance.php');
        }
        $stmt = $pdo->prepare('INSERT INTO student_attendance (student_id, subject_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([(int)$_POST['student_id'], (int)$_POST['subject_id'], $_POST['attendance_date'], trim($_POST['status']), (int)user()['id']]);
        flash('success', 'Student attendance marked.');
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $checkStmt = $pdo->prepare('
            SELECT id FROM student_attendance 
            WHERE student_id = ? AND subject_id = ? AND attendance_date = ? AND id != ?
        ');
        $checkStmt->execute([(int)$_POST['student_id'], (int)$_POST['subject_id'], $_POST['attendance_date'], $id]);
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate attendance! Another record already exists for this student on this date.');
            redirect_to(BASE_URL . '/admin/student_attendance.php');
        }
        $stmt = $pdo->prepare('UPDATE student_attendance SET student_id=?, subject_id=?, attendance_date=?, status=? WHERE id=?');
        $stmt->execute([(int)$_POST['student_id'], (int)$_POST['subject_id'], $_POST['attendance_date'], trim($_POST['status']), (int)$_POST['id']]);
        flash('success', 'Student attendance updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM student_attendance WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Record deleted.');
    }
    redirect_to(BASE_URL . '/admin/student_attendance.php');
}

// All students
$students = $pdo->query('SELECT id, name, role_code FROM users WHERE role="student" ORDER BY name')->fetchAll();

// All subjects (unfiltered – will be filtered by JavaScript or by selected student in PHP)
$allSubjects = $pdo->query('SELECT id, subject_name FROM subjects ORDER BY subject_name')->fetchAll();

// If a student is selected, get the subjects they are eligible for (via enrollments)
$filteredSubjects = [];
if ($selectedStudentId > 0) {
    $courseStmt = $pdo->prepare('SELECT DISTINCT course_id FROM enrollments WHERE student_id = ? AND status = "active"');
    $courseStmt->execute([$selectedStudentId]);
    $studentCourses = $courseStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($studentCourses)) {
        $placeholders = implode(',', array_fill(0, count($studentCourses), '?'));
        $subjectStmt = $pdo->prepare("
            SELECT id, subject_name FROM subjects 
            WHERE course_id IN ($placeholders) 
            ORDER BY subject_name
        ");
        $subjectStmt->execute($studentCourses);
        $filteredSubjects = $subjectStmt->fetchAll();
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM student_attendance WHERE id=?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
    if ($edit) $selectedStudentId = $edit['student_id'];
}

$rows = $pdo->query('
    SELECT sa.*, u.name AS student_name, u.role_code, s.subject_name 
    FROM student_attendance sa 
    JOIN users u ON u.id = sa.student_id 
    JOIN subjects s ON s.id = sa.subject_id 
    ORDER BY attendance_date DESC
')->fetchAll();

$pageTitle = 'Student Attendance | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Student Attendance</h1>
                <p>Admin overview with CRUD, search, and subject‑wise attendance details. Select a student first to filter subjects.</p>
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
                            <label>Student <span class="required">*</span></label>
                            <select name="student_id" id="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= ($selectedStudentId == $s['id']) ? 'selected' : '' ?> <?= ((int)($edit['student_id'] ?? 0) == (int)$s['id']) ? 'selected' : '' ?>>
                                        <?= e($s['role_code'] . ' - ' . $s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <select name="subject_id" id="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php if ($selectedStudentId > 0): ?>
                                    <?php foreach ($filteredSubjects as $sub): ?>
                                        <option value="<?= (int)$sub['id'] ?>" <?= ((int)($edit['subject_id'] ?? 0) == (int)$sub['id']) ? 'selected' : '' ?>>
                                            <?= e($sub['subject_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Select a student first</option>
                                <?php endif; ?>
                            </select>
                            <?php if ($selectedStudentId == 0): ?>
                                <small>Please select a student first to populate eligible subjects.</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Date <span class="required">*</span></label>
                            <input type="date" name="attendance_date" value="<?= e($edit['attendance_date'] ?? date('Y-m-d')) ?>" required>
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
                            <a class="btn btn-secondary" href="student_attendance.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search student attendance" data-table-search="studentAttendanceTable">
                    <select data-filter-target="studentAttendanceTable">
                        <option value="">All Status</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                    </select>
                    <select data-filter-target="studentAttendanceTable">
                        <option value="">All Subjects</option><?php foreach ($allSubjects as $s): ?><option value="<?= e(strtolower($s['subject_name'])) ?>"><?= e($s['subject_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="table-wrap">
                    <table id="studentAttendanceTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Code</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= e($r['student_name']) ?></td>
                                    <td><?= e($r['role_code']) ?></td>
                                    <td><?= e($r['subject_name']) ?></td>
                                    <td><?= e($r['attendance_date']) ?></td>
                                    <td><span class="kpi"><?= e($r['status']) ?></span></td>
                                    <td>
                                        <div class="inline-actions"><a class="icon-btn" title="Edit" href="?edit=<?= (int)$r['id'] ?>">✎</a>
                                            <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="icon-btn danger" title="Delete">🗑</button></form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                </div>
            </div>
        </div>
</div>
</main>
</div>

<script>
    // Reload page when student selection changes
    document.getElementById('student_id').addEventListener('change', function() {
        var studentId = this.value;
        if (studentId) {
            window.location.href = '<?= BASE_URL ?>/admin/student_attendance.php?student_id=' + studentId;
        } else {
            window.location.href = '<?= BASE_URL ?>/admin/student_attendance.php';
        }
    });
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>