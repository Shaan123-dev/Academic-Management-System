<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);
$courses = $pdo->query('SELECT DISTINCT course_name FROM courses ORDER BY course_name')->fetchAll(PDO::FETCH_COLUMN);
$classes = $pdo->query('SELECT DISTINCT CONCAT(year_label, " - ", semester) AS class_name FROM class_schedules ORDER BY year_label, semester')->fetchAll(PDO::FETCH_COLUMN);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';
    try {
        if ($action === 'create') {
            $photo = upload_file('photo', ['jpg','jpeg','png','webp'], 3 * 1024 * 1024, ROOT_PATH . '/uploads/photos');
            $roleCode = trim($_POST['role_code']) ?: next_role_code($pdo, 'student');
            $stmt = $pdo->prepare('INSERT INTO users (role, role_code, name, email, password, photo, dob, address, contact, guardian, qualification, department, status) VALUES ("student",?,?,?,?,?,?,?,?,?,?,?,"active")');
            $stmt->execute([
                $roleCode,
                trim($_POST['name']),
                trim($_POST['email']),
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $photo,
                $_POST['dob'] ?: null,
                trim($_POST['address']),
                trim($_POST['contact']),
                trim($_POST['guardian']),
                trim($_POST['qualification']),
                trim($_POST['department'])
            ]);
            flash('success', 'Student added successfully.');
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $current = $pdo->prepare('SELECT photo FROM users WHERE id = ? AND role="student"'); $current->execute([$id]); $old = $current->fetch();
            $photo = upload_file('photo', ['jpg','jpeg','png','webp'], 3 * 1024 * 1024, ROOT_PATH . '/uploads/photos') ?: ($old['photo'] ?? null);
            $stmt = $pdo->prepare('UPDATE users SET role_code=?, name=?, email=?, photo=?, dob=?, address=?, contact=?, guardian=?, qualification=?, department=?, status=? WHERE id=? AND role="student"');
            $stmt->execute([trim($_POST['role_code']), trim($_POST['name']), trim($_POST['email']), $photo, $_POST['dob'] ?: null, trim($_POST['address']), trim($_POST['contact']), trim($_POST['guardian']), trim($_POST['qualification']), trim($_POST['department']), trim($_POST['status']), $id]);
            flash('success', 'Student updated successfully.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role="student"');
            $stmt->execute([(int)$_POST['id']]);
            flash('success', 'Student deleted.');
        }
    } catch (Throwable $e) { flash('danger', $e->getMessage()); }
    redirect_to(BASE_URL . '/admin/students.php');
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = null;
if ($editId) { $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "student"'); $stmt->execute([$editId]); $edit = $stmt->fetch(); }
$students = $pdo->query('SELECT * FROM users WHERE role="student" ORDER BY id DESC')->fetchAll();
$nextCode = next_role_code($pdo, 'student');
$pageTitle = 'Manage Students | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel">
<div class="dashboard-top"><div class="dashboard-title"><h1>Manage Students</h1><p>Keep student details complete with course, class, contact, guardian details, and profile photo.</p></div><div class="user-chip">Student Records</div></div>
<div class="section-stack">
<div class="form-card">
  <h3 style="margin-top:0;"><?= $edit ? 'Update Student' : 'Add Student' ?></h3>
  <?php display_flash(); ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div class="form-group"><label>Student Code <span class="required">*</span></label><input type="text" name="role_code" required readonly value="<?= e($edit['role_code'] ?? $nextCode) ?>"></div>
      <div class="form-group"><label>Full Name <span class="required">*</span></label><input type="text" name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="email" required value="<?= e($edit['email'] ?? '') ?>"></div>
      <div class="form-group"><label>Password <?= $edit ? '' : '<span class="required">*</span>' ?></label><input type="password" name="password" <?= $edit ? '' : 'required' ?>></div>
      <div class="form-group"><label>Date of Birth <span class="required">*</span></label><input type="date" name="dob" required value="<?= e($edit['dob'] ?? '') ?>"></div>
      <div class="form-group"><label>Contact <span class="required">*</span></label><input type="text" name="contact" required value="<?= e($edit['contact'] ?? '') ?>"></div>
      <div class="form-group"><label>Guardian <span class="required">*</span></label><input type="text" name="guardian" required value="<?= e($edit['guardian'] ?? '') ?>"></div>
      <div class="form-group"><label>Qualification <span class="required">*</span></label><input type="text" name="qualification" required value="<?= e($edit['qualification'] ?? '') ?>"></div>
      <div class="form-group"><label>Course <span class="required">*</span></label><select name="department" required><option value="">Select Course</option><?php foreach($courses as $course): ?><option value="<?= e($course) ?>" <?= (($edit['department'] ?? '') === $course) ? 'selected' : '' ?>><?= e($course) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Class <span class="required">*</span></label><select name="class_filter"><option value="">Select Class</option><?php foreach($classes as $class): ?><option value="<?= e($class) ?>"><?= e($class) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Photo</label><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"></div>
      <?php if ($edit): ?><div class="form-group"><label>Status</label><select name="status"><option value="active" <?= (($edit['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option><option value="inactive" <?= (($edit['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option></select></div><?php endif; ?>
    </div>
    <div class="form-group"><label>Address <span class="required">*</span></label><textarea name="address" required><?= e($edit['address'] ?? '') ?></textarea></div>
    <div class="inline-actions"><button class="btn btn-primary"><?= $edit ? 'Update Student' : 'Add Student' ?></button><?php if ($edit): ?><a class="btn btn-secondary" href="students.php">Cancel</a><?php endif; ?></div>
  </form>
</div>

<div class="table-card">
  <div class="search-row">
    <input type="text" placeholder="Search student by code, name, email, or course" data-table-search="studentsTable">
    <select data-filter-target="studentsTable"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
    <select data-filter-target="studentsTable"><option value="">Filter by Course / Class</option><?php foreach($courses as $course): ?><option value="<?= e(strtolower($course)) ?>"><?= e($course) ?></option><?php endforeach; ?><?php foreach($classes as $class): ?><option value="<?= e(strtolower($class)) ?>"><?= e($class) ?></option><?php endforeach; ?></select>
  </div>
  <div class="table-wrap">
    <table id="studentsTable">
      <thead><tr><th>Photo</th><th>Code</th><th>Name</th><th>Email</th><th>Course</th><th>Contact</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($students as $item): ?>
        <tr>
          <td><img src="<?= photo_path($item['photo'] ?? null) ?>" class="user-photo"></td>
          <td><?= e($item['role_code']) ?></td>
          <td><?= e($item['name']) ?></td>
          <td><?= e($item['email']) ?></td>
          <td><?= e($item['department']) ?></td>
          <td><?= e($item['contact']) ?></td>
          <td><span class="kpi"><?= e($item['status']) ?></span></td>
          <td><div class="inline-actions"><a class="icon-btn" href="?edit=<?= (int)$item['id'] ?>">✎</a><form method="post" onsubmit="return confirm('Delete this student?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="icon-btn danger" title="Delete">🗑</button></form></div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
</main></div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
