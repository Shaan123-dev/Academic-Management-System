<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);

$studentId = user()['id'];

// First, get the student's enrolled course IDs (most common scenario)
$courseStmt = $pdo->prepare("SELECT DISTINCT course_id FROM enrollments WHERE student_id = ?");
$courseStmt->execute([$studentId]);
$enrolledCourses = $courseStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($enrolledCourses)) {
    // No enrollments – show empty table
    $rows = [];
} else {
    $placeholders = implode(',', array_fill(0, count($enrolledCourses), '?'));
    $stmt = $pdo->prepare("
        SELECT cs.*, c.course_name, s.subject_name, s.subject_code, u.name AS teacher_name
        FROM class_schedules cs
        JOIN courses c ON c.id = cs.course_id
        JOIN subjects s ON s.id = cs.subject_id
        JOIN users u ON u.id = cs.teacher_id
        WHERE cs.course_id IN ($placeholders)
        ORDER BY FIELD(cs.day_name, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), cs.start_time
    ");
    $stmt->execute($enrolledCourses);
    $rows = $stmt->fetchAll();
}

$pageTitle = 'Schedule | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Schedule</h1>
                <p>Your class timetable based on enrolled subjects.</p>
            </div>
        </div>
        <div class="table-card">
            <div class="search-row">
                <input type="text" placeholder="Search by day, subject, teacher" data-table-search="studentScheduleTable">
            </div>
            <div class="table-wrap">
                <table id="studentScheduleTable">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Code</th>
                            <th>Teacher</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Classroom</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= e($r['day_name']) ?></td>
                                <td><?= e(substr($r['start_time'], 0, 5) . ' - ' . substr($r['end_time'], 0, 5)) ?></td>
                                <td><?= e($r['subject_name']) ?></td>
                                <td><?= e($r['subject_code']) ?></td>
                                <td><?= e($r['teacher_name']) ?></td>
                                <td><?= e($r['year_label']) ?></td>
                                <td><?= e($r['semester']) ?></td>
                                <td><?= e($r['classroom']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="8" class="empty">No schedule found for your enrolled subjects.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>