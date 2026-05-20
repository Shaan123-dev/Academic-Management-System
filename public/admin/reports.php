<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

// Get filter parameters
$student_filter = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$export = isset($_GET['export']) && $_GET['export'] === 'csv';

// Build WHERE clause for filtering
$whereClause = "";
$params = [];

if ($student_filter > 0) {
    $whereClause = " WHERE r.student_id = ? ";
    $params[] = $student_filter;
}

if ($export) {
    // Export CSV with filter
    if ($student_filter > 0) {
        $stmt = $pdo->prepare('
            SELECT 
                u.role_code,
                u.name AS student_name,
                u.email,
                u.contact,
                u.department,
                s.subject_name,
                r.assignment_marks,
                r.internal_marks,
                r.exam_marks,
                r.final_total,
                r.final_grade,
                r.gpa
            FROM results r
            JOIN users u ON u.id = r.student_id
            JOIN subjects s ON s.id = r.subject_id
            WHERE r.student_id = ?
            ORDER BY u.name, s.subject_name
        ');
        $stmt->execute([$student_filter]);
    } else {
        $stmt = $pdo->prepare('
            SELECT 
                u.role_code,
                u.name AS student_name,
                u.email,
                u.contact,
                u.department,
                s.subject_name,
                r.assignment_marks,
                r.internal_marks,
                r.exam_marks,
                r.final_total,
                r.final_grade,
                r.gpa
            FROM results r
            JOIN users u ON u.id = r.student_id
            JOIN subjects s ON s.id = r.subject_id
            ORDER BY u.name, s.subject_name
        ');
        $stmt->execute();
    }

    $rows = $stmt->fetchAll();

    // Set filename based on filter
    $filename = $student_filter > 0 ? 'student_report_' . $student_filter . '.csv' : 'all_students_report.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Code', 'Student', 'Email', 'Contact', 'Department', 'Subject', 'Assignment (30)', 'Internal (20)', 'Exam (50)', 'Total (100)', 'Grade', 'GPA']);

    foreach ($rows as $row) {
        fputcsv($output, [
            $row['role_code'],
            $row['student_name'],
            $row['email'],
            $row['contact'],
            $row['department'],
            $row['subject_name'],
            $row['assignment_marks'],
            $row['internal_marks'],
            $row['exam_marks'],
            $row['final_total'],
            $row['final_grade'],
            $row['gpa'],
        ]);
    }

    fclose($output);
    exit;
}

// Fetch all students for the filter dropdown
$students = $pdo->query('
    SELECT id, role_code, name, email 
    FROM users 
    WHERE role = "student" 
    ORDER BY name
')->fetchAll();

// Build the main query with filter
if ($student_filter > 0) {
    $stmt = $pdo->prepare('
        SELECT r.*, u.name AS student_name, u.role_code, u.department, u.contact, u.email, s.subject_name
        FROM results r
        JOIN users u ON u.id = r.student_id
        JOIN subjects s ON s.id = r.subject_id
        WHERE r.student_id = ?
        ORDER BY u.name, s.subject_name
    ');
    $stmt->execute([$student_filter]);
} else {
    $stmt = $pdo->prepare('
        SELECT r.*, u.name AS student_name, u.role_code, u.department, u.contact, u.email, s.subject_name
        FROM results r
        JOIN users u ON u.id = r.student_id
        JOIN subjects s ON s.id = r.subject_id
        ORDER BY u.name, s.subject_name
    ');
    $stmt->execute();
}
$rows = $stmt->fetchAll();

// Get list of all students with their result status for better display
$allStudents = $pdo->query('
    SELECT 
        u.id, 
        u.role_code, 
        u.name, 
        u.email,
        COUNT(r.id) as result_count
    FROM users u
    LEFT JOIN results r ON r.student_id = u.id
    WHERE u.role = "student"
    GROUP BY u.id
    ORDER BY u.name
')->fetchAll();

$pageTitle = 'Reports | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Student Result Reports</h1>
                <p>View and export student results. Use filter to see specific student or download CSV.</p>
            </div>
            <div class="inline-actions">
                <?php if ($student_filter > 0): ?>
                    <a class="btn btn-primary" href="?export=csv&student_id=<?= $student_filter ?>">⬇ Download CSV</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="?export=csv">⬇ Download All CSV</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="form-card" style="margin-bottom: 20px;">
            <h3>Filter by Student</h3>
            <form method="get" class="filter-form">
                <div class="form-row" style="display: flex; gap: 15px; align-items: flex-end;">
                    <div class="form-group" style="flex: 1;">
                        <label>Select Student</label>
                        <select name="student_id" class="student-filter-select" onchange="this.form.submit()">
                            <option value="">-- All Students --</option>
                            <?php foreach ($allStudents as $student): ?>
                                <option value="<?= $student['id'] ?>" <?= ($student_filter == $student['id']) ? 'selected' : '' ?>>
                                    <?= e($student['role_code'] . ' - ' . $student['name']) ?>
                                    <?php if ($student['result_count'] == 0): ?>
                                        (No results yet)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Section when filter is applied -->
        <?php if ($student_filter > 0 && !empty($rows)): ?>
            <?php
            $firstRow = $rows[0];
            $totalMarks = 0;
            $totalSubjects = count($rows);
            foreach ($rows as $r) {
                $totalMarks += $r['final_total'];
            }
            $avgPercentage = $totalSubjects > 0 ? round($totalMarks / $totalSubjects, 1) : 0;
            $overallGrade = grade_from_total($avgPercentage);
            ?>
            <div class="metric-grid" style="margin-bottom: 20px;">
                <div class="metric-card">
                    <div class="label">Student Name</div>
                    <div class="value" style="font-size: 1.2rem;"><?= e($firstRow['student_name']) ?></div>
                    <div class="subtext"><?= e($firstRow['role_code']) ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Department</div>
                    <div class="value" style="font-size: 1.2rem;"><?= e($firstRow['department'] ?: 'Not Assigned') ?></div>
                    <div class="subtext"><?= e($firstRow['email']) ?></div>
                </div>
                <div class="metric-card">
                    <div class="label">Subjects Completed</div>
                    <div class="value"><?= $totalSubjects ?></div>
                    <div class="subtext">Total subjects</div>
                </div>
                <div class="metric-card">
                    <div class="label">Average Score</div>
                    <div class="value"><?= $avgPercentage ?>%</div>
                    <div class="subtext">Overall Grade: <?= $overallGrade ?></div>
                </div>
            </div>
        <?php elseif ($student_filter > 0 && empty($rows)): ?>
            <?php
            // Get the student name for better message
            $studentInfo = $pdo->prepare('SELECT name, role_code FROM users WHERE id = ?');
            $studentInfo->execute([$student_filter]);
            $student = $studentInfo->fetch();
            ?>
            <div class="alert alert-info" style="background: #e3f2fd; border: 1px solid #2196f3; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 20px;">
                <span style="font-size: 48px;">📭</span>
                <h3 style="margin: 10px 0 5px;">No Results Found</h3>
                <p style="margin: 0;">Student <strong><?= e($student['name'] ?? 'Selected') ?></strong> (<?= e($student['role_code'] ?? '') ?>) does not have any results yet.</p>
                <p style="margin-top: 10px; font-size: 0.9rem;">Results will appear here once teachers enter marks for this student.</p>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <div class="search-row">
                <input type="text" placeholder="Search reports by student, code, subject" data-table-search="reportTable">
            </div>

            <div class="table-wrap">
                <table id="reportTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Code</th>
                            <th>Contact</th>
                            <th>Subject</th>
                            <th>Assignment (30)</th>
                            <th>Internal (20)</th>
                            <th>Exam (50)</th>
                            <th>Total (100)</th>
                            <th>Grade</th>
                            <th>GPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) > 0): ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($r['student_name']) ?></strong>
                                        <div class="subtle"><?= e($r['department'] ?: 'No department') ?><br><?= e($r['email']) ?></div>
                                    </td>
                                    <td><?= e($r['role_code']) ?></td>
                                    <td><?= e($r['contact'] ?: 'N/A') ?></td>
                                    <td><?= e($r['subject_name']) ?></td>
                                    <td><?= e($r['assignment_marks']) ?> / 30</td>
                                    <td><?= e($r['internal_marks']) ?> / 20</td>
                                    <td><?= e($r['exam_marks']) ?> / 50</td>
                                    <td><strong><?= e($r['final_total']) ?> / 100</strong></td>
                                    <td><span class="kpi"><?= e($r['final_grade']) ?></span></td>
                                    <td><?= e($r['gpa']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="empty" style="text-align: center; padding: 40px;">
                                    <span style="font-size: 48px; display: block;">📊</span>
                                    <strong style="font-size: 1.2rem;">No Result Records Found</strong><br>
                                    <span style="color: #6c757d;"><?= $student_filter > 0 ? 'This student has no results yet.' : 'No students have results recorded yet.' ?></span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<style>
    .filter-form {
        margin: 0;
    }

    .student-filter-select {
        min-width: 300px;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .student-filter-select:focus {
        outline: none;
        border-color: #148f3c;
        box-shadow: 0 0 0 2px rgba(20, 143, 60, 0.2);
    }

    /* Alert styling */
    .alert-info {
        background: #e3f2fd;
        border: 1px solid #2196f3;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        margin-bottom: 20px;
    }

    .alert-info h3 {
        margin: 10px 0 5px;
        color: #0d47a1;
    }

    .alert-info p {
        margin: 0;
        color: #555;
    }

    /* Empty state styling */
    .empty {
        text-align: center;
        padding: 40px !important;
    }

    .empty span {
        font-size: 48px;
        display: block;
        margin-bottom: 10px;
    }

    .empty strong {
        font-size: 1.2rem;
        display: block;
        margin-bottom: 5px;
    }

    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
        }

        .student-filter-select {
            width: 100%;
            min-width: auto;
        }
    }
</style>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>