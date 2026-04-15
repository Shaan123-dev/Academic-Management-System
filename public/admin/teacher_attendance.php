<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO teacher_attendance (teacher_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([(int)$_POST['teacher_id'], $_POST['attendance_date'], trim($_POST['status']), (int)user()['id']]);
        flash('success', 'Teacher attendance marked.');
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE teacher_attendance SET teacher_id=?, attendance_date=?, status=? WHERE id=?');
        $stmt->execute([(int)$_POST['teacher_id'], $_POST['attendance_date'], trim($_POST['status']), (int)$_POST['id']]);
        flash('success', 'Teacher attendance updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM teacher_attendance WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Record deleted.');
    }
    redirect_to(BASE_URL . '/admin/teacher_attendance.php');
}

$teachers = $pdo->query('SELECT id, name, department FROM users WHERE role="teacher" ORDER BY name')->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM teacher_attendance WHERE id=?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch();
}
$rows = $pdo->query('SELECT ta.*, u.name, u.department FROM teacher_attendance ta JOIN users u ON u.id=ta.teacher_id ORDER BY attendance_date DESC')->fetchAll();
$pageTitle = 'Teacher Attendance | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel">
<div class="dashboard-top"><div class="dashboard-title"><h1>Teacher Attendance</h1><p>Mark, edit, search, and review teacher attendance records.</p></div></div>
<div class="section-stack">
<div class="form-card"><?php display_flash(); ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
<?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
<div class="form-grid">
<div class="form-group"><label>Teacher <span class="required">*</span></label><select name="teacher_id" required><?php foreach($teachers as $t): ?><option value="<?= (int)$t['id'] ?>" <?= ((int)($edit['teacher_id'] ?? 0)===(int)$t['id'])?'selected':'' ?>><?= e($t['name']) ?> - <?= e($t['department']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Date <span class="required">*</span></label><input type="date" name="attendance_date" required value="<?= e($edit['attendance_date'] ?? date('Y-m-d')) ?>"></div>
<div class="form-group"><label>Status <span class="required">*</span></label><select name="status"><option <?= (($edit['status'] ?? '')==='Present')?'selected':'' ?>>Present</option><option <?= (($edit['status'] ?? '')==='Absent')?'selected':'' ?>>Absent</option><option <?= (($edit['status'] ?? '')==='Late')?'selected':'' ?>>Late</option></select></div>
</div>
<div class="inline-actions"><button class="btn btn-primary"><?= $edit ? 'Update Attendance' : 'Save Attendance' ?></button><?php if ($edit): ?><a class="btn btn-secondary" href="teacher_attendance.php">Cancel</a><?php endif; ?></div>
</form></div>
<div class="table-card">
<div class="search-row"><input type="text" placeholder="Search teacher attendance" data-table-search="teacherAttendTable"><select data-filter-target="teacherAttendTable"><option value="">All Status</option><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option></select></div>
<div class="table-wrap"><table id="teacherAttendTable"><thead><tr><th>Teacher</th><th>Department</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['department']) ?></td><td><?= e($r['attendance_date']) ?></td><td><span class="kpi"><?= e($r['status']) ?></span></td><td><div class="inline-actions"><a class="icon-btn" title="Edit" href="?edit=<?= (int)$r['id'] ?>">✎</a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="icon-btn danger" title="Delete">🗑</button></form></div></td></tr><?php endforeach; ?></tbody></table></div>
</div>
</div>
</main></div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
