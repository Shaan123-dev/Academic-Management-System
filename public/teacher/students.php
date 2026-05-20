<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);

$teacherId = (int) user()['id'];

// ============================================================
// SECURITY: Only fetch students from teacher's assigned subjects
// This prevents teachers from seeing students from other courses
// ============================================================
$stmt = $pdo->prepare('
    SELECT DISTINCT
        u.id,
        u.role_code,
        u.name,
        u.email,
        u.contact,
        c.course_name,
        e.year_label,
        e.semester,
        e.status
    FROM enrollments e
    JOIN users u ON u.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    JOIN subjects s ON s.course_id = c.id
    WHERE s.teacher_id = ?
    ORDER BY u.name
');
$stmt->execute([$teacherId]);
$students = $stmt->fetchAll();

$pageTitle = 'Class Students | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Students in My Classes</h1>
                <p>Students enrolled in the courses attached to your subjects.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="search-row">
                <input type="text" placeholder="Search student by code, name, course or semester" data-table-search="teacherStudentsTable">
            </div>

            <div class="table-wrap">
                <table id="teacherStudentsTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td><?= e($row['role_code']) ?></td>
                                <td><?= e($row['name']) ?></td>
                                <td><?= e($row['email']) ?></td>
                                <td><?= e($row['contact']) ?></td>
                                <td><?= e($row['course_name']) ?></td>
                                <td><?= e($row['year_label']) ?></td>
                                <td><?= e($row['semester']) ?></td>
                                <td><?= e($row['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>