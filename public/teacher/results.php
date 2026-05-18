<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];

// Handle CSV export for all students or a single student
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
    
    if ($studentId > 0) {
        $stmt = $pdo->prepare('
            SELECT r.*, u.name AS student_name, u.role_code, s.subject_name
            FROM results r
            JOIN users u ON u.id = r.student_id
            JOIN subjects s ON s.id = r.subject_id
            WHERE r.teacher_id = ? AND r.student_id = ?
            ORDER BY s.subject_name
        ');
        $stmt->execute([$teacherId, $studentId]);
        $rows = $stmt->fetchAll();
        $filename = 'student_' . $studentId . '_results.csv';
    } else {
        $stmt = $pdo->prepare('
            SELECT r.*, u.name AS student_name, u.role_code, s.subject_name
            FROM results r
            JOIN users u ON u.id = r.student_id
            JOIN subjects s ON s.id = r.subject_id
            WHERE r.teacher_id = ?
            ORDER BY r.id DESC
        ');
        $stmt->execute([$teacherId]);
        $rows = $stmt->fetchAll();
        $filename = 'teacher_results.csv';
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student', 'Code', 'Subject', 'Assignment (30)', 'Internal (20)', 'Exam (50)', 'Total (100)', 'Grade', 'GPA']);

    foreach ($rows as $row) {
        fputcsv($output, [
            $row['student_name'],
            $row['role_code'],
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

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = null;

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';
    $student_id = (int)$_POST['student_id'];
    $subject_id = (int)$_POST['subject_id'];
    
    $verifySubject = $pdo->prepare('SELECT id FROM subjects WHERE id = ? AND teacher_id = ?');
    $verifySubject->execute([$subject_id, $teacherId]);
    if (!$verifySubject->fetch()) {
        flash('danger', 'You are not authorized to enter results for this subject.');
        redirect_to(BASE_URL . '/teacher/results.php');
    }
    
    $verifyStudent = $pdo->prepare('
        SELECT e.id FROM enrollments e
        JOIN subjects s ON s.course_id = e.course_id
        WHERE s.teacher_id = ? AND e.student_id = ? AND s.id = ?
        LIMIT 1
    ');
    $verifyStudent->execute([$teacherId, $student_id, $subject_id]);
    if (!$verifyStudent->fetch()) {
        flash('danger', 'You are not authorized to enter results for this student.');
        redirect_to(BASE_URL . '/teacher/results.php');
    }
    
    $assignment = (float)$_POST['assignment_marks'];
    $internal = (float)$_POST['internal_marks'];
    $exam = (float)$_POST['exam_marks'];
    
    $errors = [];
    if ($assignment < 0 || $assignment > 30) $errors[] = 'Assignment marks must be between 0 and 30.';
    if ($internal < 0 || $internal > 20) $errors[] = 'Internal marks must be between 0 and 20.';
    if ($exam < 0 || $exam > 50) $errors[] = 'Exam marks must be between 0 and 50.';
    
    if (!empty($errors)) {
        foreach ($errors as $error) flash('danger', $error);
        redirect_to(BASE_URL . '/teacher/results.php');
    }
    
    $total = $assignment + $internal + $exam;
    $grade = grade_from_total($total);
    $gpa = gpa_from_total($total);

    if ($action === 'create') {
        $checkStmt = $pdo->prepare('SELECT id FROM results WHERE student_id = ? AND subject_id = ?');
        $checkStmt->execute([$student_id, $subject_id]);
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate result! A result for this student and subject already exists.');
            redirect_to(BASE_URL . '/teacher/results.php');
        }
        $stmt = $pdo->prepare('INSERT INTO results (student_id, subject_id, teacher_id, assignment_marks, internal_marks, exam_marks, final_total, final_grade, gpa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$student_id, $subject_id, $teacherId, $assignment, $internal, $exam, $total, $grade, $gpa]);
        flash('success', 'Result saved successfully.');
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $checkStmt = $pdo->prepare('SELECT id FROM results WHERE student_id = ? AND subject_id = ? AND id != ?');
        $checkStmt->execute([$student_id, $subject_id, $id]);
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate result! Another result for this student and subject already exists.');
            redirect_to(BASE_URL . '/teacher/results.php');
        }
        $stmt = $pdo->prepare('UPDATE results SET student_id=?, subject_id=?, assignment_marks=?, internal_marks=?, exam_marks=?, final_total=?, final_grade=?, gpa=? WHERE id=? AND teacher_id=?');
        $stmt->execute([$student_id, $subject_id, $assignment, $internal, $exam, $total, $grade, $gpa, $id, $teacherId]);
        flash('success', 'Result updated successfully.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM results WHERE id=? AND teacher_id=?');
        $stmt->execute([$id, $teacherId]);
        flash('success', 'Result deleted successfully.');
    }
    redirect_to(BASE_URL . '/teacher/results.php');
}

if ($editId) {
    $stmt = $pdo->prepare('SELECT r.* FROM results r WHERE r.id = ? AND r.teacher_id = ?');
    $stmt->execute([$editId, $teacherId]);
    $edit = $stmt->fetch();
}

$subjects = $pdo->prepare('SELECT * FROM subjects WHERE teacher_id = ? ORDER BY subject_name');
$subjects->execute([$teacherId]);
$subjects = $subjects->fetchAll();

$students = $pdo->prepare('
    SELECT DISTINCT u.id, u.name, u.role_code
    FROM users u
    JOIN enrollments e ON e.student_id = u.id
    JOIN courses c ON c.id = e.course_id
    JOIN subjects s ON s.course_id = c.id
    WHERE s.teacher_id = ? AND u.role = "student"
    ORDER BY u.name
');
$students->execute([$teacherId]);
$students = $students->fetchAll();

$list = $pdo->prepare('
    SELECT r.*, u.name AS student_name, u.role_code, s.subject_name 
    FROM results r 
    JOIN users u ON u.id = r.student_id 
    JOIN subjects s ON s.id = r.subject_id 
    WHERE r.teacher_id = ? 
    ORDER BY r.id DESC
');
$list->execute([$teacherId]);
$list = $list->fetchAll();

$pageTitle = 'Results | ' . APP_NAME;
$bodyClass = 'teacher-results-page';
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Results</h1>
                <p>UK Grading System: A (70-100), B (60-69), C (50-59), D (40-49), F (below 40)</p>
            </div>
            <div class="inline-actions">
                <a class="btn btn-primary" href="<?= BASE_URL ?>/teacher/results.php?export=csv">⬇ Download All CSV</a>
            </div>
        </div>
        
        <div class="section-stack">
            <div class="form-card">
                <?php display_flash(); ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                    <?php if($edit): ?>
                        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Student <span class="required">*</span></label>
                            <select name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach($students as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= (($edit['student_id'] ?? 0) == $s['id']) ? 'selected' : '' ?>>
                                        <?= e($s['role_code'] . ' - ' . $s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <select name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach($subjects as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= (($edit['subject_id'] ?? 0) == $s['id']) ? 'selected' : '' ?>>
                                        <?= e($s['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Assignment Marks (0-30) <span class="required">*</span></label>
                            <input type="number" name="assignment_marks" max="30" min="0" step="0.01" required value="<?= e($edit['assignment_marks'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Internal Marks (0-20) <span class="required">*</span></label>
                            <input type="number" name="internal_marks" max="20" min="0" step="0.01" required value="<?= e($edit['internal_marks'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Exam Marks (0-50) <span class="required">*</span></label>
                            <input type="number" name="exam_marks" max="50" min="0" step="0.01" required value="<?= e($edit['exam_marks'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <small style="color: #6c757d; display: block; margin-bottom: 15px;">
                        <strong>Note:</strong> Total marks = 100 (Assignment 30 + Internal 20 + Exam 50)<br>
                    </small>
                    
                    <div class="inline-actions">
                        <button class="btn btn-primary"><?= $edit ? 'Update Result' : 'Save Result' ?></button>
                        <?php if($edit): ?>
                            <a class="btn btn-secondary" href="results.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search results" data-table-search="teacherResultTable">
                </div>

                <div class="table-wrap">
                    <table id="teacherResultTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Code</th>
                                <th>Subject</th>
                                <th>Assignment (30)</th>
                                <th>Internal (20)</th>
                                <th>Exam (50)</th>
                                <th>Total (100)</th>
                                <th>Grade</th>
                                <th>GPA</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($list as $r): ?>
                                <tr>
                                    <td><?= e($r['student_name']) ?></td>
                                    <td><?= e($r['role_code']) ?></td>
                                    <td><?= e($r['subject_name']) ?></td>
                                    <td><?= e($r['assignment_marks']) ?> / 30</td>
                                    <td><?= e($r['internal_marks']) ?> / 20</td>
                                    <td><?= e($r['exam_marks']) ?> / 50</td>
                                    <td><strong><?= e($r['final_total']) ?> / 100</strong></td>
                                    <td><span class="kpi"><?= e($r['final_grade']) ?></span></td>
                                    <td><?= e($r['gpa']) ?></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" href="?edit=<?= (int)$r['id'] ?>" title="Edit">✎</a>
                                            <a class="icon-btn" href="<?= BASE_URL ?>/teacher/results.php?export=csv&student_id=<?= (int)$r['student_id'] ?>" title="Download CSV for this student">⬇</a>
                                            <form method="post" onsubmit="return confirm('Delete this result?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                                <button class="icon-btn danger" title="Delete">🗑</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<style>
html, body {
    min-height: 100%;
    margin: 0;
    padding: 0;
}

body.teacher-results-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

body.teacher-results-page .dashboard-shell {
    flex: 1;
}

body.teacher-results-page footer {
    margin-top: auto;
}
</style>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>