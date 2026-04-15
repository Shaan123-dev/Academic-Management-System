<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);
$studentId = (int)user()['id'];

$stmt = $pdo->prepare('
    SELECT e.*, c.course_name
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    WHERE e.student_id = ?
    ORDER BY e.id DESC
');
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll();

$pageTitle = 'My Courses | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>
<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>My Enrolled Courses</h1>
                <p>Courses assigned to your student profile.</p>
            </div>
        </div>

        <div class="table-card">
            <div class="search-row">
                <input type="text" placeholder="Search my courses" data-table-search="studentCoursesTable">
            </div>

            <div class="table-wrap">
                <table id="studentCoursesTable">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Semester</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $row): ?>
                            <tr>
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