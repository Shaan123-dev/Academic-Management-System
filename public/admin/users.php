<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    try {
        if ($action === 'create') {
            $errors = validate_required([
                'role' => 'Role',
                'name' => 'Full Name',
                'email' => 'Email',
                'password' => 'Password',
            ], $_POST);

            $errors = array_merge($errors, validate_password_strength($_POST['password'] ?? ''));

            if ($errors) {
                foreach ($errors as $error) flash('danger', $error);
                redirect_to(BASE_URL . '/admin/users.php');
            }

            $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('
                INSERT INTO users (role, role_code, name, email, password, dob, address, contact, guardian, qualification, department, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ');

            $role = trim($_POST['role']);
            $stmt->execute([
                $role,
                next_role_code($pdo, $role),
                trim($_POST['name']),
                trim($_POST['email']),
                $passwordHash,
                $_POST['dob'] ?: null,
                trim($_POST['address']),
                trim($_POST['contact']),
                trim($_POST['guardian']),
                trim($_POST['qualification']),
                trim($_POST['department']),
                'active'
            ]);

            flash('success', 'User added successfully.');
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role != "admin"');
            $stmt->execute([(int)$_POST['id']]);
            flash('success', 'User deleted.');
        } elseif ($action === 'reset_password') {
            $userId = (int)$_POST['id'];
            $newPassword = $_POST['new_password'] ?? '';

            $errors = validate_password_strength($newPassword);
            if ($newPassword === '') {
                $errors[] = 'New password is required.';
            }

            if ($errors) {
                foreach ($errors as $error) flash('danger', $error);
                redirect_to(BASE_URL . '/admin/users.php');
            }

            $stmt = $pdo->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ? AND role != "admin"');
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

            flash('success', 'User password reset successfully.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect_to(BASE_URL . '/admin/users.php');
}

$users = $pdo->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
$pageTitle = 'Manage Users | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Manage Users</h1>
                <p>Admin can create users, delete users, and reset passwords.</p>
            </div>
            <div class="user-chip">Admin</div>
        </div>

        <div class="form-card">
            <h3 style="margin-bottom:14px;">Add New User</h3>
            <?php display_flash(); ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role">
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob">
                    </div>

                    <div class="form-group">
                        <label>Contact</label>
                        <input type="text" name="contact">
                    </div>

                    <div class="form-group">
                        <label>Guardian</label>
                        <input type="text" name="guardian">
                    </div>

                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification">
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department">
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"></textarea>
                </div>

                <button class="btn btn-primary">Add User</button>
            </form>
        </div>

        <div class="table-card">
            <div class="search-row">
                <input type="text" placeholder="Search users by role, name, email or department" data-table-search="usersTable">
            </div>

            <div class="table-wrap">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Reset Password</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $item): ?>
                            <tr>
                                <td><?= (int)$item['id'] ?></td>
                                <td><?= e(ucfirst($item['role'])) ?></td>
                                <td><?= e($item['name']) ?></td>
                                <td><?= e($item['email']) ?></td>
                                <td><?= e($item['department']) ?></td>
                                <td><?= e($item['contact']) ?></td>
                                <td><span class="kpi"><?= e($item['status']) ?></span></td>
                                <td>
                                    <?php if ($item['role'] !== 'admin'): ?>
                                        <form method="post" class="inline-actions">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <input type="password" name="new_password" placeholder="New password" required>
                                            <button class="btn btn-secondary" type="submit">Reset</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['role'] !== 'admin'): ?>
                                        <form method="post" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <button class="btn btn-primary" style="min-height:38px;">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>