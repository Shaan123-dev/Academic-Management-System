<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);
$studentId = (int)user()['id'];

$stmt = $pdo->prepare('
    SELECT
        m.*,
        s.subject_name,
        s.subject_code,
        t.name AS teacher_name
    FROM enrollments e
    JOIN subjects s ON s.course_id = e.course_id
    JOIN study_materials m ON m.subject_id = s.id
    JOIN users t ON t.id = m.teacher_id
    WHERE e.student_id = ? AND e.status = "active"
    ORDER BY m.id DESC
');
$stmt->execute([$studentId]);
$materials = $stmt->fetchAll();

$pageTitle = 'Study Materials | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Study Materials</h1>
                <p>Download notes, files, and learning resources uploaded by your teachers.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="search-row">
                <input type="text" placeholder="Search materials by title, subject or teacher" data-table-search="studentMaterialsTable">
            </div>

            <div class="table-wrap">
                <table id="studentMaterialsTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Description</th>
                            <th>File</th>
                            <th>Uploaded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $row): ?>
                            <tr>
                                <td><?= e($row['title']) ?></td>
                                <td><?= e($row['subject_code'] . ' - ' . $row['subject_name']) ?></td>
                                <td><?= e($row['teacher_name']) ?></td>
                                <td><?= e($row['description']) ?></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="file-link" href="<?= BASE_URL ?>/open_file.php?type=material&file=<?= urlencode($row['file_name']) ?>" target="_blank">📄 View</a>
                                        <a class="file-link" href="<?= BASE_URL . '/../uploads/materials/' . e($row['file_name']) ?>" download>⬇ Download</a>
                                    </div>
                                </td>
                                <td><?= e(format_dt($row['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>