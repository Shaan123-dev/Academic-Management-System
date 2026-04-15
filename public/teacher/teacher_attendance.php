<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);

$teacherId = (int) user()['id'];

$rowsStmt = $pdo->prepare('
    SELECT *
    FROM teacher_attendance
    WHERE teacher_id = ?
    ORDER BY attendance_date DESC
');
$rowsStmt->execute([$teacherId]);
$rows = $rowsStmt->fetchAll();

$present = 0;
foreach ($rows as $r) {
    if (strtolower($r['status']) === 'present') {
        $present++;
    }
}

$total = count($rows);
$percent = attendance_percent($present, $total);
$score = round($percent, 0);

$pageTitle = 'My Attendance | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>My Attendance</h1>
                <p>Attendance percentage, score out of 100, plus searchable attendance history.</p>
            </div>
        </div>

        <div class="section-stack">
            <div class="metric-grid metric-grid-3">
                <div class="metric-card">
                    <div class="label">Present Days</div>
                    <div class="value"><?= (int)$present ?></div>
                    <div class="subtle">Days marked present</div>
                </div>

                <div class="metric-card">
                    <div class="label">Total Records</div>
                    <div class="value"><?= (int)$total ?></div>
                    <div class="subtle">All attendance entries</div>
                </div>

                <div class="metric-card">
                    <div class="label">Attendance Score</div>
                    <div class="value"><?= (int)$score ?>/100</div>
                    <div class="subtle"><?= e($percent) ?>% overall attendance</div>
                </div>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search my attendance" data-table-search="teacherSelfAttendance">

                    <select data-filter-target="teacherSelfAttendance">
                        <option value="">All Status</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="late">Late</option>
                    </select>
                </div>

                <div class="table-wrap">
                    <table id="teacherSelfAttendance">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= e($r['attendance_date']) ?></td>
                                    <td><span class="kpi"><?= e($r['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="2" class="empty">No attendance records found.</td>
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