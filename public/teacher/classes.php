<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];

$stmt = $pdo->prepare('
    SELECT cs.*, c.course_name, s.subject_name
    FROM class_schedules cs
    JOIN courses c ON c.id = cs.course_id
    JOIN subjects s ON s.id = cs.subject_id
    WHERE cs.teacher_id = ?
    ORDER BY cs.day_name, cs.start_time
');
$stmt->execute([$teacherId]);
$classes = $stmt->fetchAll();

$pageTitle = 'Assigned Classes | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Assigned Classes</h1>
                <p>All classes assigned to you through the class schedule.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="search-row">
                <input type="text" placeholder="Search classes by subject, course, day or room" data-table-search="teacherClassesTable">
            </div>

            <div class="table-wrap">
                <table id="teacherClassesTable">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Subject</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Room</th>
                            <th>Year</th>
                            <th>Semester</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $row): ?>
                            <tr>
                                <td><?= e($row['course_name']) ?></td>
                                <td><?= e($row['subject_name']) ?></td>
                                <td><?= e($row['day_name']) ?></td>
                                <td><?= e(substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5)) ?></td>
                                <td><?= e($row['classroom']) ?></td>
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