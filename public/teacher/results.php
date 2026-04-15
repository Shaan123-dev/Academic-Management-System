<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);
$teacherId = (int)user()['id'];
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
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

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=teacher_results.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student', 'Code', 'Subject', 'Assignment', 'Internal', 'Exam', 'Total', 'Grade', 'GPA']);

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
    $assignment = (float)$_POST['assignment_marks'];
    $internal = (float)$_POST['internal_marks'];
    $exam = (float)$_POST['exam_marks'];
    $total = $assignment + $internal + $exam;
    $grade = grade_from_total($total);
    $gpa = gpa_from_total($total);

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO results (student_id, subject_id, teacher_id, assignment_marks, internal_marks, exam_marks, final_total, final_grade, gpa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([(int)$_POST['student_id'], (int)$_POST['subject_id'], $teacherId, $assignment, $internal, $exam, $total, $grade, $gpa]);
        flash('success','Result saved.');
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE results SET student_id=?, subject_id=?, assignment_marks=?, internal_marks=?, exam_marks=?, final_total=?, final_grade=?, gpa=? WHERE id=? AND teacher_id=?');
        $stmt->execute([(int)$_POST['student_id'], (int)$_POST['subject_id'], $assignment, $internal, $exam, $total, $grade, $gpa, (int)$_POST['id'], $teacherId]);
        flash('success','Result updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM results WHERE id=? AND teacher_id=?');
        $stmt->execute([(int)$_POST['id'], $teacherId]);
        flash('success','Result deleted.');
    }
    redirect_to(BASE_URL.'/teacher/results.php');
}

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM results WHERE id=? AND teacher_id=?');
    $stmt->execute([$editId, $teacherId]);
    $edit = $stmt->fetch();
}
$subjects = $pdo->prepare('SELECT * FROM subjects WHERE teacher_id=?'); $subjects->execute([$teacherId]); $subjects = $subjects->fetchAll();
$students = $pdo->query('SELECT id, name, role_code FROM users WHERE role="student" ORDER BY name')->fetchAll();
$list = $pdo->prepare('SELECT r.*, u.name AS student_name, u.role_code, s.subject_name FROM results r JOIN users u ON u.id=r.student_id JOIN subjects s ON s.id=r.subject_id WHERE r.teacher_id=? ORDER BY r.id DESC'); $list->execute([$teacherId]); $list = $list->fetchAll();
$pageTitle='Results | '.APP_NAME; include dirname(dirname(__DIR__)) . '/includes/header.php'; ?>
<div class="dashboard-shell"><?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?><main class="main-panel"><div class="dashboard-top"><div class="dashboard-title"><h1>Results</h1><p>Final total, grade, and GPA are calculated automatically from marks. Teachers can add, edit, and remove records.</p></div>
<div class="inline-actions">
        <a class="btn btn-primary" href="<?= BASE_URL ?>/teacher/results.php?export=csv">
    ⬇ Download CSV
</a>
    </div>
</div>
<div class="section-stack">
<div class="form-card"><?php display_flash(); ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>"><?php if($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?><div class="form-grid"><div class="form-group"><label>Student</label><select name="student_id"><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (($edit['student_id'] ?? 0)==$s['id'])?'selected':'' ?>><?= e($s['role_code'].' - '.$s['name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Subject</label><select name="subject_id"><?php foreach($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (($edit['subject_id'] ?? 0)==$s['id'])?'selected':'' ?>><?= e($s['subject_name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Assignment Marks</label><input type="number" name="assignment_marks" max="20" min="0" step="0.01" required value="<?= e($edit['assignment_marks'] ?? '') ?>"></div><div class="form-group"><label>Internal Marks</label><input type="number" name="internal_marks" max="20" min="0" step="0.01" required value="<?= e($edit['internal_marks'] ?? '') ?>"></div><div class="form-group"><label>Exam Marks</label><input type="number" name="exam_marks" max="60" min="0" step="0.01" required value="<?= e($edit['exam_marks'] ?? '') ?>"></div></div><div class="inline-actions"><button class="btn btn-primary"><?= $edit ? 'Update Result' : 'Save Result' ?></button><?php if($edit): ?><a class="btn btn-secondary" href="results.php">Cancel</a><?php endif; ?></div></form></div>
<div class="table-card"><div class="search-row"><input type="text" placeholder="Search results" data-table-search="teacherResultTable"></div><div class="table-wrap"><table id="teacherResultTable"><thead><tr><th>Student</th><th>Code</th><th>Subject</th><th>Assignment</th><th>Internal</th><th>Exam</th><th>Total</th><th>Grade</th><th>GPA</th><th>Action</th></tr></thead><tbody><?php foreach($list as $r): ?><tr><td><?= e($r['student_name']) ?></td><td><?= e($r['role_code']) ?></td><td><?= e($r['subject_name']) ?></td><td><?= e($r['assignment_marks']) ?></td><td><?= e($r['internal_marks']) ?></td><td><?= e($r['exam_marks']) ?></td><td><?= e($r['final_total']) ?></td><td><span class="kpi"><?= e($r['final_grade']) ?></span></td><td><?= e($r['gpa']) ?></td><td><div class="inline-actions"><a class="btn btn-linkish btn-icon" href="?edit=<?= (int)$r['id'] ?>" title="Edit">✎</a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="icon-btn danger" title="Delete">🗑</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></div>
</div>
</main></div><?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
