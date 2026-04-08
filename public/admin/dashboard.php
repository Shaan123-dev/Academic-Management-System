<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Only admin can access this page
require_role('admin');

$pageTitle = 'Admin Dashboard';
$pageDescription = 'Admin dashboard for Academic Management Portal.';

/*
|--------------------------------------------------------------------------
| Small helper function to count rows from allowed tables
|--------------------------------------------------------------------------
*/
function get_count(PDO $pdo, string $table): int
{
    $allowed = ['users', 'students', 'teachers', 'courses', 'subjects', 'classes', 'assignments', 'announcements', 'results'];

    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM {$table}");
    $row = $stmt->fetch();

    return (int)($row['total'] ?? 0);
}

// Dashboard statistics
$totalStudents = get_count($pdo, 'students');
$totalTeachers = get_count($pdo, 'teachers');
$totalCourses = get_count($pdo, 'courses');
$totalSubjects = get_count($pdo, 'subjects');
$totalAssignments = get_count($pdo, 'assignments');
$totalAnnouncements = get_count($pdo, 'announcements');
$totalResults = get_count($pdo, 'results');
$totalClasses = get_count($pdo, 'classes');

// Recent announcements for dashboard table
$recentAnnouncementsStmt = $pdo->query("
    SELECT a.title, a.target_role, a.created_at, u.full_name
    FROM announcements a
    INNER JOIN users u ON u.id = a.created_by_user_id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$recentAnnouncements = $recentAnnouncementsStmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<section class="content-section">
    <div class="container sidebar-layout">
        <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

        <div class="content-area">
            <div class="page-header">
                <h2>Admin Dashboard</h2>
                <p>Welcome, <?= e(current_user()['full_name']) ?>. Here is the overall system summary.</p>
            </div>

            <div class="dashboard-cards">
                <div class="stat-card">
                    <h4>Total Students</h4>
                    <p><?= e((string)$totalStudents) ?></p>
                </div>

                <div class="stat-card">
                    <h4>Total Teachers</h4>
                    <p><?= e((string)$totalTeachers) ?></p>
                </div>

                <div class="stat-card">
                    <h4>Total Courses</h4>
                    <p><?= e((string)$totalCourses) ?></p>
                </div>

                <div class="stat-card">
                    <h4>Total Subjects</h4>
                    <p><?= e((string)$totalSubjects) ?></p>
                </div>

                <div class="stat-card">
                    <h4>Total Classes</h4>
                    <p><?= e((string)$totalClasses) ?></p>
                </div>

                <div class="stat-card">
                    <h4>Total Assignments</h4>
                    <p><?= e((string)$totalAssignments) ?></p>
                </div>

                <div class="stat-card">
                    <h4>Total Results</h4>
                    <p><?= e((string)$totalResults) ?></p>
                </div>

                <div class="stat-card">
                    <h4>Announcements</h4>
                    <p><?= e((string)$totalAnnouncements) ?></p>
                </div>
            </div>

            <div class="table-card">
                <h3>Recent Announcements</h3>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Target</th>
                                <th>Created By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentAnnouncements): ?>
                                <?php foreach ($recentAnnouncements as $item): ?>
                                    <tr>
                                        <td><?= e($item['title']) ?></td>
                                        <td><span class="badge badge-primary"><?= e($item['target_role']) ?></span></td>
                                        <td><?= e($item['full_name']) ?></td>
                                        <td><?= e($item['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No announcements found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>