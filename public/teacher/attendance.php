<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';
    
    if ($action === 'create') {
        $student_id = (int)$_POST['student_id'];
        $subject_id = (int)$_POST['subject_id'];
        $attendance_date = $_POST['attendance_date'];
        $status = trim($_POST['status']);
        
        // Verify this student belongs to teacher's course
        $verifyStmt = $pdo->prepare('
            SELECT s.id FROM subjects s
            JOIN enrollments e ON e.course_id = s.course_id
            WHERE s.teacher_id = ? AND s.id = ? AND e.student_id = ?
            LIMIT 1
        ');
        $verifyStmt->execute([$teacherId, $subject_id, $student_id]);
        
        if (!$verifyStmt->fetch()) {
            flash('danger', 'You are not authorized to mark attendance for this student.');
            redirect_to(BASE_URL . '/teacher/attendance.php');
        }
        
        // Check for duplicate attendance
        $checkStmt = $pdo->prepare('
            SELECT id FROM student_attendance 
            WHERE student_id = ? AND subject_id = ? AND attendance_date = ?
        ');
        $checkStmt->execute([$student_id, $subject_id, $attendance_date]);
        
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate attendance! Attendance for this student on this date already exists.');
            redirect_to(BASE_URL . '/teacher/attendance.php');
        }
        
        $stmt = $pdo->prepare('INSERT INTO student_attendance (student_id, subject_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$student_id, $subject_id, $attendance_date, $status, $teacherId]);
        flash('success', 'Attendance saved successfully.');
        
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $student_id = (int)$_POST['student_id'];
        $subject_id = (int)$_POST['subject_id'];
        $attendance_date = $_POST['attendance_date'];
        $status = trim($_POST['status']);
        
        // Verify this record belongs to the teacher
        $verifyStmt = $pdo->prepare('
            SELECT sa.id FROM student_attendance sa
            JOIN subjects s ON s.id = sa.subject_id
            WHERE sa.id = ? AND s.teacher_id = ?
            LIMIT 1
        ');
        $verifyStmt->execute([$id, $teacherId]);
        
        if (!$verifyStmt->fetch()) {
            flash('danger', 'You are not authorized to edit this attendance record.');
            redirect_to(BASE_URL . '/teacher/attendance.php');
        }
        
        $checkStmt = $pdo->prepare('
            SELECT id FROM student_attendance 
            WHERE student_id = ? AND subject_id = ? AND attendance_date = ? AND id != ?
        ');
        $checkStmt->execute([$student_id, $subject_id, $attendance_date, $id]);
        
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate attendance! Another record already exists for this student on this date.');
            redirect_to(BASE_URL . '/teacher/attendance.php');
        }
        
        $stmt = $pdo->prepare('UPDATE student_attendance SET student_id=?, subject_id=?, attendance_date=?, status=? WHERE id=? AND marked_by=?');
        $stmt->execute([$student_id, $subject_id, $attendance_date, $status, $id, $teacherId]);
        flash('success', 'Attendance updated successfully.');
        
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Verify this record belongs to the teacher
        $verifyStmt = $pdo->prepare('
            SELECT sa.id FROM student_attendance sa
            JOIN subjects s ON s.id = sa.subject_id
            WHERE sa.id = ? AND s.teacher_id = ?
            LIMIT 1
        ');
        $verifyStmt->execute([$id, $teacherId]);
        
        if (!$verifyStmt->fetch()) {
            flash('danger', 'You are not authorized to delete this attendance record.');
            redirect_to(BASE_URL . '/teacher/attendance.php');
        }
        
        $stmt = $pdo->prepare('DELETE FROM student_attendance WHERE id=? AND marked_by=?');
        $stmt->execute([$id, $teacherId]);
        flash('success', 'Attendance deleted successfully.');
    }
    redirect_to(BASE_URL . '/teacher/attendance.php');
}

// Get only subjects assigned to this teacher
$subjectsStmt = $pdo->prepare('
    SELECT * FROM subjects WHERE teacher_id = ? ORDER BY subject_name
');
$subjectsStmt->execute([$teacherId]);
$subjects = $subjectsStmt->fetchAll();

// Get only students enrolled in courses that match teacher's subjects
$studentsStmt = $pdo->prepare('
    SELECT DISTINCT u.id, u.name, u.role_code
    FROM users u
    JOIN enrollments e ON e.student_id = u.id
    JOIN courses c ON c.id = e.course_id
    JOIN subjects s ON s.course_id = c.id
    WHERE s.teacher_id = ? AND u.role = "student"
    ORDER BY u.name
');
$studentsStmt->execute([$teacherId]);
$students = $studentsStmt->fetchAll();

$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('
        SELECT sa.* FROM student_attendance sa
        JOIN subjects s ON s.id = sa.subject_id
        WHERE sa.id = ? AND s.teacher_id = ?
        LIMIT 1
    ');
    $stmt->execute([$editId, $teacherId]);
    $edit = $stmt->fetch();
}

// Get only attendance records for teacher's subjects
$rowsStmt = $pdo->prepare('
    SELECT sa.*, u.name AS student_name, u.role_code, s.subject_name 
    FROM student_attendance sa 
    JOIN users u ON u.id = sa.student_id 
    JOIN subjects s ON s.id = sa.subject_id 
    WHERE s.teacher_id = ? 
    ORDER BY attendance_date DESC
');
$rowsStmt->execute([$teacherId]);
$rows = $rowsStmt->fetchAll();

$pageTitle = 'Student Attendance | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Student Attendance</h1>
                <p>Mark and review attendance for your assigned students only.</p>
            </div>
        </div>
        <div class="section-stack">
            <div class="form-card">
                <?php display_flash(); ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                    <?php if($edit): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Student <span class="required">*</span></label>
                            <select name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach($students as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['student_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                                        <?= e($s['role_code'] . ' - ' . $s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <select name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach($subjects as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['subject_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                                        <?= e($s['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                        <?php if($edit): ?>
                            <a class="btn btn-secondary" href="attendance.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search attendance by student or subject" data-table-search="teacherStudentAttendance">
                    <select data-filter-target="teacherStudentAttendance">
                        <option value="">All Subjects</option>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?= e(strtolower($s['subject_name'])) ?>"><?= e($s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select data-filter-target="teacherStudentAttendance">
                        <option value="">All Status</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                    </select>
                </div>
                <div class="table-wrap">
                    <table id="teacherStudentAttendance">
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
                            <?php foreach($rows as $r): ?>
                                <tr>
                                    <td><?= e($r['student_name']) ?></td>
                                    <td><?= e($r['role_code']) ?></td>
                                    <td><?= e($r['subject_name']) ?></td>
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
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>