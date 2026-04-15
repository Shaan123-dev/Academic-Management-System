<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);

$studentId = (int) user()['id'];

$rows = $pdo->prepare('
    SELECT sa.*, s.subject_name
    FROM student_attendance sa
    JOIN subjects s ON s.id = sa.subject_id
    WHERE student_id = ?
    ORDER BY attendance_date DESC
');
$rows->execute([$studentId]);
$rows = $rows->fetchAll();

$total = count($rows);
$present = 0;
$absent = 0;

foreach ($rows as $r) {
    if (strtolower($r['status']) === 'present') {
        $present++;
    }
    if (strtolower($r['status']) === 'absent') {
        $absent++;
    }
}

$percent = attendance_percent($present, $total);

$pageTitle = 'My Attendance | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>My Attendance</h1>
                <p>View total present, absent, and attendance percentage.</p>
            </div>
        </div>

        <div class="section-stack">
            <div class="grid-3">
                <div class="feature-card">
                    <h3>Total Present</h3>
                    <p><?= (int)$present ?></p>
                    <span class="subtle">All present records</span>
                </div>

                <div class="feature-card">
                    <h3>Total Absent</h3>
                    <p><?= (int)$absent ?></p>
                    <span class="subtle">All absent records</span>
                </div>

                <div class="feature-card">
                    <h3>Percentage</h3>
                    <p><?= e($percent) ?>%</p>
                    <span class="subtle">Overall attendance percentage</span>
                </div>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search attendance" data-table-search="studentAttendanceTable">
                </div>

                <div class="table-wrap">
                    <table id="studentAttendanceTable">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= e($r['subject_name']) ?></td>
                                    <td><?= e($r['attendance_date']) ?></td>
                                    <td><span class="kpi"><?= e($r['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="3" class="empty">No attendance records found.</td>
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