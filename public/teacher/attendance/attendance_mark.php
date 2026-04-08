<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Teacher Mark Attendance Page
|--------------------------------------------------------------------------
| This page allows teacher to:
| - Mark attendance for all students on a selected date
| - Save or update attendance
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

require_role('teacher');

$pageTitle = 'Mark Attendance';
$pageDescription = 'Teacher attendance marking page.';

$error = '';
$success = '';

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
| Load students
|--------------------------------------------------------------------------
*/
$studentsStmt = $pdo->query("
    SELECT s.id, s.student_code, u.full_name
    FROM students s
    INNER JOIN users u ON u.id = s.user_id
    ORDER BY u.full_name ASC
");
$students = $studentsStmt->fetchAll();

$selectedDate = clean_input($_POST['attendance_date'] ?? date('Y-m-d'));

/*
|--------------------------------------------------------------------------
| Save attendance
|--------------------------------------------------------------------------
*/
if (is_post()) {
    verify_csrf();

    $selectedDate = clean_input($_POST['attendance_date'] ?? '');
    $remarks = $_POST['remarks'] ?? [];
    $statuses = $_POST['status'] ?? [];

    if ($selectedDate === '') {
        $error = 'Attendance date is required.';
    } elseif (!$statuses) {
        $error = 'Please mark attendance for students.';
    } else {
        try {
            $pdo->beginTransaction();

            foreach ($statuses as $studentId => $status) {
                $studentId = (int)$studentId;
                $status = clean_input((string)$status);
                $remark = trim((string)($remarks[$studentId] ?? ''));

                if (!in_array($status, ['present', 'absent', 'late', 'leave'], true)) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Insert or update attendance
                |--------------------------------------------------------------------------
                */
                $stmt = $pdo->prepare("
                    INSERT INTO attendance (student_id, teacher_id, attendance_date, status, remarks)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        teacher_id = VALUES(teacher_id),
                        status = VALUES(status),
                        remarks = VALUES(remarks)
                ");
                $stmt->execute([
                    $studentId,
                    $teacherId,
                    $selectedDate,
                    $status,
                    $remark !== '' ? $remark : null
                ]);
            }

            log_audit($pdo, (int) current_user()['id'], 'create/update', 'attendance', null);

            $pdo->commit();

            $success = 'Attendance saved successfully.';
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Failed to save attendance.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Load existing attendance for selected date
|--------------------------------------------------------------------------
*/
$existingAttendance = [];

if ($selectedDate !== '') {
    $existingStmt = $pdo->prepare("
        SELECT student_id, status, remarks
        FROM attendance
        WHERE teacher_id = ? AND attendance_date = ?
    ");
    $existingStmt->execute([$teacherId, $selectedDate]);

    foreach ($existingStmt->fetchAll() as $row) {
        $existingAttendance[(int)$row['student_id']] = [
            'status' => $row['status'],
            'remarks' => $row['remarks'] ?? ''
        ];
    }
}

require_once __DIR__ . '/../../../includes/header.php';
?>

<section class="content-section">
    <div class="container sidebar-layout">
        <?php require_once __DIR__ . '/../../../includes/sidebar.php'; ?>

        <div class="content-area">
            <div class="page-header">
                <h2>Mark Attendance</h2>
                <p>Mark or update attendance for students by date.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="actions">
                <a class="btn btn-outline" href="<?= BASE_URL ?>/public/teacher/attendance/attendance_index.php">Back to Attendance Records</a>
            </div>

            <div class="form-card">
                <form method="POST" action="">
                    <?= csrf_input() ?>

                    <div class="form-group">
                        <label for="attendance_date">Attendance Date</label>
                        <input type="date" id="attendance_date" name="attendance_date" value="<?= e($selectedDate) ?>" required>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student Code</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students): ?>
                                    <?php foreach ($students as $student): ?>
                                        <?php
                                            $studentId = (int)$student['id'];
                                            $savedStatus = $existingAttendance[$studentId]['status'] ?? 'present';
                                            $savedRemarks = $existingAttendance[$studentId]['remarks'] ?? '';
                                        ?>
                                        <tr>
                                            <td><?= e($student['full_name']) ?></td>
                                            <td><?= e($student['student_code']) ?></td>
                                            <td>
                                                <select name="status[<?= e((string)$studentId) ?>]">
                                                    <option value="present" <?= $savedStatus === 'present' ? 'selected' : '' ?>>Present</option>
                                                    <option value="absent" <?= $savedStatus === 'absent' ? 'selected' : '' ?>>Absent</option>
                                                    <option value="late" <?= $savedStatus === 'late' ? 'selected' : '' ?>>Late</option>
                                                    <option value="leave" <?= $savedStatus === 'leave' ? 'selected' : '' ?>>Leave</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="remarks[<?= e((string)$studentId) ?>]" value="<?= e($savedRemarks) ?>" placeholder="Optional remark">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="actions" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>