<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];
$rows = $pdo->prepare('SELECT cs.*, c.course_name, s.subject_name FROM class_schedules cs JOIN courses c ON c.id=cs.course_id JOIN subjects s ON s.id=cs.subject_id WHERE cs.teacher_id=? ORDER BY FIELD(day_name,"Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"), start_time');
$rows->execute([$teacherId]);
$rows = $rows->fetchAll();
$pageTitle = 'Schedule | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Schedule</h1>
                <p>Admin-managed class schedule with filter by semester and day.</p>
            </div>
        </div>
        <div class="table-card">
            <div class="search-row"><input type="text" placeholder="Search by semester, day, subject" data-table-search="teacherScheduleTable"></div>
            <div class="table-wrap">
                <table id="teacherScheduleTable">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Course</th>
                            <th>Subject</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Classroom</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($rows as $r): ?><tr>
                                <td><?= e($r['day_name']) ?></td>
                                <td><?= e(substr($r['start_time'], 0, 5) . ' - ' . substr($r['end_time'], 0, 5)) ?></td>
                                <td><?= e($r['course_name']) ?></td>
                                <td><?= e($r['subject_name']) ?></td>
                                <td><?= e($r['year_label']) ?></td>
                                <td><?= e($r['semester']) ?></td>
                                <td><?= e($r['classroom']) ?></td>
                            </tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </main>
</div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>