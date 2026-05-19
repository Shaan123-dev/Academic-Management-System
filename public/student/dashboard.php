<?php
/**
 * Student Dashboard - Main control panel for students
 * Same dropdown-based analytics as admin/teacher with student-specific data
 * Trends: Subject Marks, Attendance, Pass vs Fail
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['student']);

$studentId = (int) user()['id'];

// ============================================================
// STUDENT STATISTICS - For metric cards
// ============================================================

// Count total assignments available for this student
$stmt = $pdo->prepare('
    SELECT COUNT(DISTINCT a.id) 
    FROM assignments a
    JOIN subjects s ON s.id = a.subject_id
    JOIN enrollments e ON e.course_id = s.course_id
    WHERE e.student_id = ? AND e.status = "active"
');
$stmt->execute([$studentId]);
$totalAssignments = (int) $stmt->fetchColumn();

// Count submitted assignments
$stmt = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE student_id = ?');
$stmt->execute([$studentId]);
$submittedCount = (int) $stmt->fetchColumn();

// Count total announcements for students
$stmt = $pdo->query('SELECT COUNT(*) FROM announcements WHERE visibility_role IN ("all", "student")');
$announcementsCount = (int) $stmt->fetchColumn();

// Fetch latest 5 announcements
$announcements = $pdo->query('
    SELECT title, body, posted_at 
    FROM announcements 
    WHERE visibility_role IN ("all", "student") 
    ORDER BY posted_at DESC 
    LIMIT 5
')->fetchAll();

// ============================================================
// CHART DATA - For Dashboard Analytics
// ============================================================

// 1. SUBJECT MARKS - Student's marks by subject
$marksData = $pdo->prepare('
    SELECT 
        s.subject_name,
        r.final_total as marks
    FROM results r
    JOIN subjects s ON s.id = r.subject_id
    WHERE r.student_id = ?
    ORDER BY s.subject_name
');
$marksData->execute([$studentId]);
$marksData = $marksData->fetchAll();

// Prepare marks data (fallback if no data)
$subjectNames = array_column($marksData, 'subject_name') ?: ['Web Dev', 'Database', 'Network', 'Security', 'Programming'];
$studentMarks = array_column($marksData, 'marks') ?: [85, 72, 78, 68, 90];

// 2. ATTENDANCE TREND - Last 6 months attendance percentage
$attendanceTrend = $pdo->prepare('
    SELECT 
        DATE_FORMAT(attendance_date, "%b") as month,
        ROUND(AVG(CASE WHEN status = "Present" THEN 1 ELSE 0 END) * 100, 1) as rate
    FROM student_attendance
    WHERE student_id = ?
        AND attendance_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(attendance_date), MONTH(attendance_date)
    ORDER BY attendance_date
');
$attendanceTrend->execute([$studentId]);
$attendanceTrend = $attendanceTrend->fetchAll();

// Prepare attendance data (fallback if no data)
$attendanceMonths = array_column($attendanceTrend, 'month') ?: ['MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT'];
$attendanceRates = array_column($attendanceTrend, 'rate') ?: [85, 88, 82, 90, 87, 92];

// 3. PASS VS FAIL - Count passed vs failed subjects for this student
$passFailStats = $pdo->prepare('
    SELECT 
        SUM(CASE WHEN r.final_grade IN ("A+", "A", "B+", "B", "C+", "C") THEN 1 ELSE 0 END) as passed,
        SUM(CASE WHEN r.final_grade IN ("D", "F") THEN 1 ELSE 0 END) as failed
    FROM results r
    WHERE r.student_id = ?
');
$passFailStats->execute([$studentId]);
$passFailStats = $passFailStats->fetch();

$passedCount = (int)($passFailStats['passed'] ?? 4);
$failedCount = (int)($passFailStats['failed'] ?? 1);

$pageTitle = 'Student Dashboard | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
<?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
<main class="main-panel">
  <div class="dashboard-top">
    <div class="dashboard-title">
      <h1>Student Dashboard</h1>
      <p><?= e(current_datetime()) ?> • Welcome, <?= e(user()['name']) ?></p>
    </div>
    <div class="user-chip">🎓 Student Panel</div>
  </div>

  <!-- Metric Cards - Student Statistics -->
  <div class="metric-grid">
    <div class="metric-card"><div class="label">Assignments</div><div class="value"><?= $totalAssignments ?></div><div class="subtext">Available tasks</div></div>
    <div class="metric-card"><div class="label">Submitted</div><div class="value"><?= $submittedCount ?></div><div class="subtext">Completed uploads</div></div>
    <div class="metric-card"><div class="label">Announcements</div><div class="value"><?= $announcementsCount ?></div><div class="subtext">Visible notices</div></div>
    <div class="metric-card"><div class="label">Today</div><div class="value"><?= date('d') ?></div><div class="subtext"><?= date('l') ?></div></div>
  </div>

  <div class="dashboard-grid">

    <!-- Quick Actions Panel -->
    <div class="panel-card">
        <h3>Quick Actions</h3>
        <div class="quick-actions">
            <a href="<?= BASE_URL ?>/student/attendance.php"><span class="qa-emoji">📋</span><span>Attendance</span></a>
            <a href="<?= BASE_URL ?>/student/courses.php"><span class="qa-emoji">📚</span><span>Courses</span></a>
            <a href="<?= BASE_URL ?>/student/subjects.php"><span class="qa-emoji">📖</span><span>Subjects</span></a>
            <a href="<?= BASE_URL ?>/student/assignments.php"><span class="qa-emoji">📝</span><span>Assignments</span></a>
            <a href="<?= BASE_URL ?>/student/materials.php"><span class="qa-emoji">📂</span><span>Materials</span></a>
            <a href="<?= BASE_URL ?>/student/results.php"><span class="qa-emoji">📊</span><span>Results</span></a>
        </div>
    </div>

    <!-- Recent Announcements Panel -->
    <div class="panel-card">
      <h3>Recent Announcements</h3>
      <div class="list-clean">
        <?php foreach ($announcements as $item): ?>
          <div class="list-item">
            <strong><?= e($item['title']) ?></strong>
            <span><?= e(mb_strimwidth($item['body'], 0, 90, '...')) ?> — <?= e(date('d M Y', strtotime($item['posted_at']))) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- DASHBOARD ANALYTICS SECTION - Same as Admin with Dropdowns   -->
  <!-- ============================================================ -->
  <div class="dashboard-analytics">
    
    <!-- Header with title and download button -->
    <div class="analytics-header">
      <h2>📊 My Performance Analytics</h2>
      <button class="download-analytics-btn">⬇ Download Chart</button>
    </div>
    
    <!-- Two dropdowns: Trend selection and Chart type selection -->
    <div class="analytics-controls">
      <div class="analytics-control-group">
        <label for="trendSelect">📈 Select Trend</label>
        <select id="trendSelect">
          <option value="marks">📊 Subject Marks</option>
          <option value="attendance">📋 My Attendance (Last 6 Months)</option>
          <option value="passfail">🎯 Pass vs Fail (Results)</option>
        </select>
      </div>
      
      <div class="analytics-control-group">
        <label for="chartTypeSelect">📊 Chart Type</label>
        <select id="chartTypeSelect">
          <option value="bar">Bar Chart</option>
          <option value="line">Line Chart</option>
          <option value="doughnut">Doughnut Chart</option>
        </select>
      </div>
    </div>
    
    <!-- Canvas where chart will be drawn -->
    <div class="analytics-chart-container">
      <canvas id="analyticsChart"></canvas>
    </div>
    
    <!-- Dynamic note that changes based on selected trend -->
    <div class="analytics-footer">
      <p class="chart-note" id="chartNote">
        <span class="note-badge">ℹ️</span> 
        Select a trend and chart type above to visualize your academic performance.
      </p>
    </div>
  </div>
  <!-- END OF CHARTS SECTION -->

</main>
</div>

<!-- Include Chart.js and html2canvas libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<!-- Include student charts module -->
<script src="/Academic-Management-System/assets/js/student-charts.js"></script>

<!-- Pass data from PHP to JavaScript and initialize charts -->
<script>
// Prepare data object for student charts
const studentChartData = {
    // Data for Subject Marks
    subjectNames: <?= json_encode($subjectNames) ?>,
    studentMarks: <?= json_encode($studentMarks) ?>,
    // Data for Attendance
    attendanceMonths: <?= json_encode($attendanceMonths) ?>,
    attendanceRates: <?= json_encode($attendanceRates) ?>,
    // Data for Pass vs Fail
    passedCount: <?= json_encode($passedCount) ?>,
    failedCount: <?= json_encode($failedCount) ?>
};

// Initialize dashboard analytics when page loads
document.addEventListener('DOMContentLoaded', function() {
    initStudentDashboardAnalytics(studentChartData);
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>