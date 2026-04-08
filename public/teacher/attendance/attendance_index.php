<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Teacher Attendance List Page
|--------------------------------------------------------------------------
| This page allows teacher to:
| - View attendance records marked by them
| - Filter by date, student, status
| - Go to mark attendance page
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

require_role('teacher');

$pageTitle = 'Attendance Records';
$pageDescription = 'Teacher attendance records page.';

$user = current_user();

/*
|--------------------------------------------------------------------------
| Get teacher ID from logged-in user
|--------------------------------------------------------------------------
*/
$teacherStmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ? LIMIT 1");
$teacherStmt->execute([$user['id']]);
$teacher = $teacherStmt->fetch();

$teacherId = (int)($teacher['id'] ?? 0);

if ($teacherId <= 0) {
    die('Teacher profile not found.');
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/
$filterDate = clean_input($_GET['attendance_date'] ?? '');
$filterStudent = clean_input($_GET['student_id'] ?? '');
$filterStatus = clean_input($_GET['status'] ?? '');

$where = ["a.teacher_id = ?"];
$params = [$teacherId];

if ($filterDate !== '') {
    $where[] = "a.attendance_date = ?";
    $params[] = $filterDate;
}

if ($filterStudent !== '' && ctype_digit($filterStudent)) {
    $where[] = "a.student_id = ?";
    $params[] = (int)$filterStudent;
}

if ($filterStatus !== '' && in_array($filterStatus, ['present', 'absent', 'late', 'leave'], true)) {
    $where[] = "a.status = ?";
    $params[] = $filterStatus;
}

$sql = "
    SELECT
        a.id,
        a.attendance_date,
        a.status,
        a.remarks,
        a.marked_at,
        u.full_name AS student_name,
        s.student_code
    FROM attendance a
    INNER JOIN students s ON s.id = a.student_id
    INNER JOIN users u ON u.id = s.user_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY a.attendance_date DESC, a.id DESC
";

$listStmt = $pdo->prepare($sql);
$listStmt->execute($params);
$attendanceRecords = $listStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Load students for filter dropdown
|--------------------------------------------------------------------------
*/
$studentsStmt = $pdo->query("
    SELECT s.id, s.student_code, u.full_name
    FROM students s
    INNER JOIN users u ON u.id = s.user_id
    ORDER BY u.full_name ASC
");
$students = $studentsStmt->fetchAll();

require_once __DIR__ . '/../../../includes/header.php';
?>

<section class="content-section">
    <div class="container sidebar-layout">
        <?php require_once __DIR__ . '/../../../includes/sidebar.php'; ?>

        <div class="content-area">
            <div class="page-header">
                <h2>Attendance Records</h2>
                <p>View attendance marked by you.</p>
            </div>

            <?php if ($msg = get_flash('success')): ?>
                <div class="alert alert-success"><?= e($msg) ?></div>
            <?php endif; ?>

            <div class="actions">
                <a class="btn btn-primary" href="<?= BASE_URL ?>/public/teacher/attendance/attendance_mark.php">Mark Attendance</a>
            </div>

            <div class="form-card" style="margin-bottom: 20px;">
                <h3>Filter Attendance</h3>

                <form method="GET" action="">
                    <div class="search-bar">
                        <input type="date" name="attendance_date" value="<?= e($filterDate) ?>">

                        <select name="student_id">
                            <option value="">All Students</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= e((string)$student['id']) ?>" <?= ((string)$filterStudent === (string)$student['id']) ? 'selected' : '' ?>>
                                    <?= e($student['full_name']) ?> (<?= e($student['student_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="status">
                            <option value="">All Status</option>
                            <option value="present" <?= $filterStatus === 'present' ? 'selected' : '' ?>>Present</option>
                            <option value="absent" <?= $filterStatus === 'absent' ? 'selected' : '' ?>>Absent</option>
                            <option value="late" <?= $filterStatus === 'late' ? 'selected' : '' ?>>Late</option>
                            <option value="leave" <?= $filterStatus === 'leave' ? 'selected' : '' ?>>Leave</option>
                        </select>

                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a class="btn btn-outline" href="<?= BASE_URL ?>/public/teacher/attendance/attendance_index.php">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <h3>Attendance History</h3>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Student Code</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Marked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($attendanceRecords): ?>
                                <?php foreach ($attendanceRecords as $row): ?>
                                    <tr>
                                        <td><?= e($row['attendance_date']) ?></td>
                                        <td><?= e($row['student_name']) ?></td>
                                        <td><?= e($row['student_code']) ?></td>
                                        <td>
                                            <span class="badge 
                                                <?= $row['status'] === 'present' ? 'badge-success' : 
                                                    ($row['status'] === 'absent' ? 'badge-danger' : 'badge-warning') ?>">
                                                <?= e($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= e($row['remarks'] ?? '') ?></td>
                                        <td><?= e($row['marked_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No attendance records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>