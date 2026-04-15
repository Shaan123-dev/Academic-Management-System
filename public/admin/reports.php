<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $pdo->query('
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
    ')->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=student_reports.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Code', 'Student', 'Email', 'Contact', 'Department', 'Subject', 'Assignment', 'Internal', 'Exam', 'Total', 'Grade', 'GPA']);

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

$rows = $pdo->query('
    SELECT r.*, u.name AS student_name, u.role_code, u.department, u.contact, u.email, s.subject_name
    FROM results r
    JOIN users u ON u.id = r.student_id
    JOIN subjects s ON s.id = r.subject_id
    ORDER BY u.name, s.subject_name
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
                <p>Printable result reports with export support.</p>
            </div>
            <div class="inline-actions">
                <a class="btn btn-primary" href="<?= BASE_URL ?>/admin/reports.php?export=csv">Download CSV</a>
            </div>
        </div>

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
                            <th>Assignment</th>
                            <th>Internal</th>
                            <th>Exam</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>GPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['student_name']) ?></strong>
                                    <div class="subtle"><?= e($r['department']) ?><br><?= e($r['email']) ?></div>
                                </td>
                                <td><?= e($r['role_code']) ?></td>
                                <td><?= e($r['contact']) ?></td>
                                <td><?= e($r['subject_name']) ?></td>
                                <td><?= e($r['assignment_marks']) ?></td>
                                <td><?= e($r['internal_marks']) ?></td>
                                <td><?= e($r['exam_marks']) ?></td>
                                <td><?= e($r['final_total']) ?></td>
                                <td><span class="kpi"><?= e($r['final_grade']) ?></span></td>
                                <td><?= e($r['gpa']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>