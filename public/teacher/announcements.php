<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);

$teacherId = (int) user()['id'];

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
            $teacherId
        ]);
        flash('success', 'Announcement posted.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM announcements WHERE id = ? AND created_by = ?');
        $stmt->execute([(int)$_POST['id'], $teacherId]);
        flash('success', 'Announcement deleted.');
    }

    redirect_to(BASE_URL . '/teacher/announcements.php');
}

$subjects = $pdo->prepare('
    SELECT id, subject_name
    FROM subjects
    WHERE teacher_id = ?
    ORDER BY subject_name
');
$subjects->execute([$teacherId]);
$subjects = $subjects->fetchAll();

$rows = $pdo->prepare('
    SELECT
        a.*,
        COALESCE(s.subject_name, "General") AS subject_name
    FROM announcements a
    LEFT JOIN subjects s ON s.id = a.subject_id
    WHERE a.created_by = ? OR a.visibility_role IN ("all", "teacher")
    ORDER BY a.posted_at DESC
');
$rows->execute([$teacherId]);
$rows = $rows->fetchAll();

$pageTitle = 'Teacher Announcements | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Announcements</h1>
                <p>Post announcements with target audience and search.</p>
            </div>
        </div>

        <div class="section-stack">
            <div class="form-card">
                <?php display_flash(); ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Title <span class="required">*</span></label>
                            <input type="text" name="title" required>
                        </div>

                        <div class="form-group">
                            <label>Visibility <span class="required">*</span></label>
                            <select name="visibility_role" required>
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                                <option value="all">All</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Target Audience <span class="required">*</span></label>
                            <input type="text" name="target_audience" required>
                        </div>

                        <div class="form-group">
                            <label>Subject</label>
                            <select name="subject_id">
                                <option value="">General</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>"><?= e($s['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Message <span class="required">*</span></label>
                        <textarea name="body" required></textarea>
                    </div>

                    <button class="btn btn-primary">Post Announcement</button>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search announcements" data-table-search="teacherAnnTable">
                </div>

                <div class="table-wrap">
                    <table id="teacherAnnTable">
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
                                        <?php if ((int)$r['created_by'] === $teacherId): ?>
                                            <div class="inline-actions">
                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                    <button class="icon-btn danger" title="Delete" type="submit">🗑</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="subtle">View only</span>
                                        <?php endif; ?>
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