<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $stmt = $pdo->prepare('
            INSERT INTO announcements
            (title, body, visibility_role, target_audience, subject_id, created_by, posted_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            trim($_POST['title']),
            trim($_POST['body']),
            trim($_POST['visibility_role']),
            trim($_POST['target_audience']),
            $_POST['subject_id'] ? (int)$_POST['subject_id'] : null,
            (int)user()['id']
        ]);
        flash('success', 'Announcement posted.');
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('
            UPDATE announcements
            SET title=?, body=?, visibility_role=?, target_audience=?, subject_id=?
            WHERE id=?
        ');
        $stmt->execute([
            trim($_POST['title']),
            trim($_POST['body']),
            trim($_POST['visibility_role']),
            trim($_POST['target_audience']),
            $_POST['subject_id'] ? (int)$_POST['subject_id'] : null,
            (int)$_POST['id']
        ]);
        flash('success', 'Announcement updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM announcements WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Announcement deleted.');
    }

    redirect_to(BASE_URL . '/admin/announcements.php');
}

$subjects = $pdo->query('SELECT id, subject_name FROM subjects ORDER BY subject_name')->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM announcements WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$rows = $pdo->query('
    SELECT
        a.*,
        COALESCE(s.subject_name, "General") AS subject_name,
        u.name AS creator
    FROM announcements a
    LEFT JOIN subjects s ON s.id = a.subject_id
    LEFT JOIN users u ON u.id = a.created_by
    ORDER BY a.posted_at DESC
')->fetchAll();

$pageTitle = 'Announcements | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Announcements</h1>
                <p>Create global or targeted announcements with CRUD support.</p>
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
                            <label>Title <span class="required">*</span></label>
                            <input type="text" name="title" required value="<?= e($edit['title'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Visibility <span class="required">*</span></label>
                            <select name="visibility_role" required>
                                <option value="all" <?= (($edit['visibility_role'] ?? '') === 'all') ? 'selected' : '' ?>>All</option>
                                <option value="teacher" <?= (($edit['visibility_role'] ?? '') === 'teacher') ? 'selected' : '' ?>>Teacher</option>
                                <option value="student" <?= (($edit['visibility_role'] ?? '') === 'student') ? 'selected' : '' ?>>Student</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Audience</label>
                            <input type="text" name="target_audience" value="<?= e($edit['target_audience'] ?? '') ?>" placeholder="Example: Semester 1 Students">
                        </div>

                        <div class="form-group">
                            <label>Subject</label>
                            <select name="subject_id">
                                <option value="">General</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= ((int)($edit['subject_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                                        <?= e($s['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Message <span class="required">*</span></label>
                        <textarea name="body" required><?= e($edit['body'] ?? '') ?></textarea>
                    </div>

                    <button class="btn btn-primary"><?= $edit ? 'Update' : 'Publish' ?></button>
                    <?php if ($edit): ?>
                        <a class="btn btn-secondary" href="announcements.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search announcements" data-table-search="annTable">
                </div>

                <div class="table-wrap">
                    <table id="annTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Audience</th>
                                <th>Visibility</th>
                                <th>Subject</th>
                                <th>Posted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($r['title']) ?></strong>
                                        <div class="subtle"><?= e(mb_strimwidth($r['body'], 0, 90, '...')) ?></div>
                                    </td>
                                    <td><?= e($r['target_audience'] ?: 'General') ?></td>
                                    <td><?= e($r['visibility_role']) ?></td>
                                    <td><?= e($r['subject_name']) ?></td>
                                    <td><?= e(date('d M Y', strtotime($r['posted_at']))) ?></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" title="Edit" href="?edit=<?= (int)$r['id'] ?>">✎</a>

                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <button class="icon-btn danger" title="Delete" type="submit">🗑</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="6" class="empty">No announcements found.</td>
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