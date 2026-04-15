<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO student_attendance (student_id, subject_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([(int)$_POST['student_id'], (int)$_POST['subject_id'], $_POST['attendance_date'], trim($_POST['status']), $teacherId]);
        flash('success', 'Attendance saved.');
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE student_attendance SET student_id=?, subject_id=?, attendance_date=?, status=? WHERE id=? AND marked_by=?');
        $stmt->execute([(int)$_POST['student_id'], (int)$_POST['subject_id'], $_POST['attendance_date'], trim($_POST['status']), (int)$_POST['id'], $teacherId]);
        flash('success', 'Attendance updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM student_attendance WHERE id=? AND marked_by=?');
        $stmt->execute([(int)$_POST['id'], $teacherId]);
        flash('success', 'Attendance deleted.');
    }
    redirect_to(BASE_URL . '/teacher/attendance.php');
}
$subjectsStmt = $pdo->prepare('SELECT * FROM subjects WHERE teacher_id=? ORDER BY subject_name');
$subjectsStmt->execute([$teacherId]);
$subjects = $subjectsStmt->fetchAll();
$students = $pdo->query('SELECT id, name, role_code FROM users WHERE role="student" ORDER BY name')->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM student_attendance WHERE id=? AND marked_by=?');
    $stmt->execute([$editId, $teacherId]);
    $edit = $stmt->fetch();
}
$rowsStmt = $pdo->prepare('SELECT sa.*, u.name AS student_name, u.role_code, s.subject_name FROM student_attendance sa JOIN users u ON u.id=sa.student_id JOIN subjects s ON s.id=sa.subject_id WHERE sa.marked_by=? ORDER BY attendance_date DESC');
$rowsStmt->execute([$teacherId]);
$rows = $rowsStmt->fetchAll();
$pageTitle = 'Student Attendance | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel"><div class="dashboard-top"><div class="dashboard-title"><h1>Student Attendance</h1><p>Mark and review attendance with search, subject filter, and cleaner spacing.</p></div></div>
<div class="section-stack">
<div class="form-card"><?php display_flash(); ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>"><?php if($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?><div class="form-grid"><div class="form-group"><label>Student</label><select name="student_id"><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['student_id'] ?? 0)===(int)$s['id'])?'selected':'' ?>><?= e($s['role_code'].' - '.$s['name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Subject</label><select name="subject_id"><?php foreach($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['subject_id'] ?? 0)===(int)$s['id'])?'selected':'' ?>><?= e($s['subject_name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Date</label><input type="date" name="attendance_date" value="<?= e($edit['attendance_date'] ?? date('Y-m-d')) ?>" required></div><div class="form-group"><label>Status</label><select name="status"><option <?= (($edit['status'] ?? '')==='Present')?'selected':'' ?>>Present</option><option <?= (($edit['status'] ?? '')==='Absent')?'selected':'' ?>>Absent</option><option <?= (($edit['status'] ?? '')==='Late')?'selected':'' ?>>Late</option></select></div></div><div class="inline-actions"><button class="btn btn-primary"><?= $edit ? 'Update Attendance' : 'Save Attendance' ?></button><?php if($edit): ?><a class="btn btn-secondary" href="attendance.php">Cancel</a><?php endif; ?></div></form></div>
<div class="table-card"><div class="search-row"><input type="text" placeholder="Search attendance by student or subject" data-table-search="teacherStudentAttendance"><select data-filter-target="teacherStudentAttendance"><option value="">All Subjects</option><?php foreach($subjects as $s): ?><option value="<?= e(strtolower($s['subject_name'])) ?>"><?= e($s['subject_name']) ?></option><?php endforeach; ?></select><select data-filter-target="teacherStudentAttendance"><option value="">All Status</option><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option></select></div><div class="table-wrap"><table id="teacherStudentAttendance"><thead><tr><th>Student</th><th>Code</th><th>Subject</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= e($r['student_name']) ?></td><td><?= e($r['role_code']) ?></td><td><?= e($r['subject_name']) ?></td><td><?= e($r['attendance_date']) ?></td><td><span class="kpi"><?= e($r['status']) ?></span></td><td><div class="inline-actions"><a class="icon-btn" title="Edit" href="?edit=<?= (int)$r['id'] ?>">✎</a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="icon-btn danger" title="Delete">🗑</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></div>
</div></main></div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
