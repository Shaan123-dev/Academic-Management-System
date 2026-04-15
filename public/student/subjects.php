<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);
$studentId = (int)user()['id'];

$stmt = $pdo->prepare('
    SELECT DISTINCT
        s.subject_code,
        s.subject_name,
        s.year_label,
        s.semester,
        c.course_name,
        t.name AS teacher_name
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    JOIN subjects s ON s.course_id = c.id
    JOIN users t ON t.id = s.teacher_id
    WHERE e.student_id = ? AND e.status = "active"
    ORDER BY s.subject_name
');
$stmt->execute([$studentId]);
$subjects = $stmt->fetchAll();

$pageTitle = 'My Subjects | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>My Subjects</h1>
                <p>Subjects available from your active course enrollments.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="search-row">
                <input type="text" placeholder="Search my subjects" data-table-search="studentSubjectsTable">
            </div>

            <div class="table-wrap">
                <table id="studentSubjectsTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject</th>
                            <th>Course</th>
                            <th>Teacher</th>
                            <th>Year</th>
                            <th>Semester</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $row): ?>
                            <tr>
                                <td><?= e($row['subject_code']) ?></td>
                                <td><?= e($row['subject_name']) ?></td>
                                <td><?= e($row['course_name']) ?></td>
                                <td><?= e($row['teacher_name']) ?></td>
                                <td><?= e($row['year_label']) ?></td>
                                <td><?= e($row['semester']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>