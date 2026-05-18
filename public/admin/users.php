<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

// Handle actions (reset password, delete, update status)
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'reset_password') {
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
            flash('success', 'Password reset successfully.');

        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role != "admin"');
            $stmt->execute([(int)$_POST['id']]);
            flash('success', 'User deleted successfully.');
            
        } elseif ($action === 'update_status') {
            $userId = (int)$_POST['id'];
            $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
            $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ? AND role != "admin"');
            $stmt->execute([$status, $userId]);
            flash('success', 'User status updated successfully.');
        }
    } catch (Throwable $e) {
        flash('danger', $e->getMessage());
    }

    redirect_to(BASE_URL . '/admin/users.php');
}

// Fetch all non-admin users
$users = $pdo->query('
    SELECT * FROM users 
    WHERE role != "admin" 
    ORDER BY role, name
')->fetchAll();

// Group users by role for better display
$teachers = array_filter($users, function($u) { return $u['role'] === 'teacher'; });
$students = array_filter($users, function($u) { return $u['role'] === 'student'; });

$pageTitle = 'Manage Users | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Manage Users</h1>
                <p>View, manage passwords, and update status for all teachers and students.</p>
            </div>
            <div class="user-chip">
                👥 Total: <?= count($users) ?> Users
            </div>
        </div>

        <?php display_flash(); ?>

        <!-- Info Box -->
        <div class="alert alert-info" style="background: #e8f3ec; border-left: 4px solid #148f3c; margin-bottom: 20px;">
            <strong>💡 Note:</strong> To add new students or teachers, please use the 
            <a href="<?= BASE_URL ?>/admin/students.php" style="color: #148f3c; font-weight: bold;">Manage Students</a> and 
            <a href="<?= BASE_URL ?>/admin/teachers.php" style="color: #148f3c; font-weight: bold;">Manage Teachers</a> pages.
        </div>

        <!-- Teachers Section -->
        <?php if (count($teachers) > 0): ?>
        <div class="table-card" style="margin-bottom: 24px;">
            <h3 style="margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #eef2f0;">
                👨‍🏫 Teachers (<?= count($teachers) ?>)
            </h3>
            <div class="table-wrap">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $item): ?>
                            <tr>
                                <td><?= e($item['role_code']) ?></td>
                                <td><strong><?= e($item['name']) ?></strong></td>
                                <td><?= e($item['email']) ?></td>
                                <td><?= e($item['department'] ?: '-') ?></td>
                                <td><?= e($item['contact'] ?: '-') ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 5px 10px; border-radius: 20px; border: 1px solid #ddd; background: <?= $item['status'] === 'active' ? '#e8f3ec' : '#fde8e8' ?>; color: <?= $item['status'] === 'active' ? '#148f3c' : '#c0392b' ?>; font-weight: bold;">
                                            <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>🟢 Active</option>
                                            <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>🔴 Inactive</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="inline-actions">
                                        <!-- Reset Password Modal Trigger -->
                                        <button class="icon-btn" onclick="showResetModal(<?= (int)$item['id'] ?>, '<?= e($item['name']) ?>')" title="Reset Password" style="cursor: pointer;">🔑</button>
                                        
                                        <!-- Delete Button -->
                                        <form method="post" onsubmit="return confirm('Delete this teacher? This action cannot be undone.');" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
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
        <?php endif; ?>

        <!-- Students Section -->
        <?php if (count($students) > 0): ?>
        <div class="table-card">
            <h3 style="margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #eef2f0;">
                🎓 Students (<?= count($students) ?>)
            </h3>
            <div class="table-wrap">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $item): ?>
                            <tr>
                                <td><?= e($item['role_code']) ?></td>
                                <td><strong><?= e($item['name']) ?></strong></td>
                                <td><?= e($item['email']) ?></td>
                                <td><?= e($item['department'] ?: '-') ?></td>
                                <td><?= e($item['contact'] ?: '-') ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 5px 10px; border-radius: 20px; border: 1px solid #ddd; background: <?= $item['status'] === 'active' ? '#e8f3ec' : '#fde8e8' ?>; color: <?= $item['status'] === 'active' ? '#148f3c' : '#c0392b' ?>; font-weight: bold;">
                                            <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>🟢 Active</option>
                                            <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>🔴 Inactive</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="inline-actions">
                                        <!-- Reset Password Modal Trigger -->
                                        <button class="icon-btn" onclick="showResetModal(<?= (int)$item['id'] ?>, '<?= e($item['name']) ?>')" title="Reset Password" style="cursor: pointer;">🔑</button>
                                        
                                        <!-- Delete Button -->
                                        <form method="post" onsubmit="return confirm('Delete this student? This action cannot be undone.');" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
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
        <?php endif; ?>

        <?php if (count($users) === 0): ?>
            <div class="table-card">
                <div class="empty" style="text-align: center; padding: 60px;">
                    <span style="font-size: 48px;">👥</span>
                    <h3>No Users Found</h3>
                    <p>No teachers or students have been added yet.</p>
                    <div style="margin-top: 20px;">
                        <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-primary">➕ Add Student</a>
                        <a href="<?= BASE_URL ?>/admin/teachers.php" class="btn btn-secondary">➕ Add Teacher</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; border-radius: 16px; padding: 30px; width: 400px; max-width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0;">Reset Password</h3>
        <p id="resetUserName" style="color: #666;">Loading...</p>
        <form method="post" id="resetForm">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" id="resetUserId">
            <div class="form-group">
                <label>New Password <span class="required">*</span></label>
                <input type="password" name="new_password" id="resetPassword" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                <small style="color: #6c757d; display: block; margin-top: 5px;">
                    <strong>Password Requirements:</strong> 8+ chars, 1 uppercase, 1 lowercase, 1 number, 1 special (!@#$%^&*)
                </small>
            </div>
            <div class="form-group">
                <label>Confirm Password <span class="required">*</span></label>
                <input type="password" id="confirmPassword" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                <small id="passwordMatchMsg" style="color: #e74c3c; display: none;">Passwords do not match</small>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitReset">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<style>
.users-table {
    width: 100%;
    border-collapse: collapse;
}
.users-table th, 
.users-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eef2f0;
    vertical-align: middle;
}
.users-table th {
    background: #f8f9fa;
    font-weight: 700;
    color: #173221;
}
.users-table tr:hover {
    background: #f8fdf8;
}
.inline-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}
.icon-btn {
    background: #eef2f0;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
}
.icon-btn.danger {
    background: #fde8e8;
    color: #c0392b;
}
.icon-btn:hover {
    opacity: 0.8;
}
.alert a {
    text-decoration: none;
}
.alert a:hover {
    text-decoration: underline;
}
@media (max-width: 768px) {
    .users-table th, .users-table td {
        padding: 8px 10px;
        font-size: 0.85rem;
    }
    .inline-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Reset Password Modal Functions
function showResetModal(userId, userName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserName').innerHTML = 'User: <strong>' + userName + '</strong>';
    document.getElementById('resetModal').style.display = 'flex';
    document.getElementById('resetPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    document.getElementById('passwordMatchMsg').style.display = 'none';
}

function closeModal() {
    document.getElementById('resetModal').style.display = 'none';
}

// Password match validation
document.getElementById('confirmPassword').addEventListener('input', function() {
    var password = document.getElementById('resetPassword').value;
    var confirm = this.value;
    var msg = document.getElementById('passwordMatchMsg');
    var submitBtn = document.getElementById('submitReset');
    
    if (password === confirm && password !== '') {
        msg.style.display = 'none';
        submitBtn.disabled = false;
    } else if (password !== confirm && confirm !== '') {
        msg.style.display = 'block';
        submitBtn.disabled = true;
    } else {
        msg.style.display = 'none';
        submitBtn.disabled = false;
    }
});

// Close modal when clicking outside
document.getElementById('resetModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>