<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);

$studentId = (int) user()['id'];

// ============================================================
// SECURITY: Only fetch results for the logged-in student
// Prevents students from viewing others' results via URL manipulation
// ============================================================
$rows = $pdo->prepare('
    SELECT r.*, s.subject_name, s.subject_code
    FROM results r
    JOIN subjects s ON s.id = r.subject_id
    WHERE r.student_id = ?
    ORDER BY s.subject_name
');
$rows->execute([$studentId]);
$rows = $rows->fetchAll();

$totalGpa = 0;
foreach ($rows as $r) {
    $totalGpa += (float)$r['gpa'];
}

$overall = count($rows) ? round($totalGpa / count($rows), 2) : 0;
$status = $overall >= 2 ? 'Good Standing' : 'Needs Improvement';

$pageTitle = 'Results | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>My Results</h1>
                <p>Full result details with subject GPA and overall GPA summary.</p>
            </div>
        </div>

        <div class="section-stack">
            <div class="grid-3">
                <div class="feature-card">
                    <h3>Subjects</h3>
                    <p><?= count($rows) ?></p>
                    <span class="subtle">Total enrolled subjects</span>
                </div>

                <div class="feature-card">
                    <h3>Overall GPA</h3>
                    <p><?= e($overall) ?></p>
                    <span class="subtle">Average academic performance</span>
                </div>

                <div class="feature-card">
                    <h3>Status</h3>
                    <p><?= e($status) ?></p>
                    <span class="subtle">Current academic standing</span>
                </div>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search results" data-table-search="studentResultTable">
                </div>

                <div class="table-wrap">
                    <table id="studentResultTable">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Code</th>
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
                                    <td><?= e($r['subject_name']) ?></td>
                                    <td><?= e($r['subject_code']) ?></td>
                                    <td><?= e($r['assignment_marks']) ?></td>
                                    <td><?= e($r['internal_marks']) ?></td>
                                    <td><?= e($r['exam_marks']) ?></td>
                                    <td><?= e($r['final_total']) ?></td>
                                    <td><span class="kpi"><?= e($r['final_grade']) ?></span></td>
                                    <td><?= e($r['gpa']) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="8" class="empty">No result records found.</td>
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