<?php

/**
 * Teacher Dashboard - Main control panel for teachers
 * Same dropdown-based analytics as admin dashboard with teacher-specific data
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['teacher']);

$teacherId = (int) user()['id'];

// ============================================================
// TEACHER STATISTICS - For metric cards
// ============================================================

// Count subjects assigned to this teacher
$stmt = $pdo->prepare('SELECT COUNT(*) FROM subjects WHERE teacher_id = ?');
$stmt->execute([$teacherId]);
$subjectCount = (int) $stmt->fetchColumn();

// Count assignments created by this teacher
$stmt = $pdo->prepare('SELECT COUNT(*) FROM assignments WHERE teacher_id = ?');
$stmt->execute([$teacherId]);
$assignmentCount = (int) $stmt->fetchColumn();

// Count results entered by this teacher
$stmt = $pdo->prepare('SELECT COUNT(*) FROM results WHERE teacher_id = ?');
$stmt->execute([$teacherId]);
$resultCount = (int) $stmt->fetchColumn();

// Fetch latest 5 announcements
$announcements = $pdo->prepare('
    SELECT title, body, posted_at 
    FROM announcements 
    WHERE visibility_role IN ("all", "teacher") 
    ORDER BY posted_at DESC 
    LIMIT 5
');
$announcements->execute();
$announcements = $announcements->fetchAll();

// ============================================================
// CHART DATA - For Dashboard Analytics
// ============================================================

// 1. MARKS TREND - Student marks by subject
$marksData = $pdo->prepare('
    SELECT 
        s.subject_name,
        ROUND(AVG(r.final_total), 1) as avg_marks
    FROM results r
    JOIN subjects s ON s.id = r.subject_id
    WHERE s.teacher_id = ?
    GROUP BY s.id
    ORDER BY avg_marks DESC
    LIMIT 6
');
$marksData->execute([$teacherId]);
$marksData = $marksData->fetchAll();

// Prepare marks data (fallback if no data)
$subjectNames = array_column($marksData, 'subject_name') ?: ['Web Dev', 'Database', 'Network', 'Security', 'Programming', 'Math'];
$avgMarks = array_column($marksData, 'avg_marks') ?: [78, 72, 81, 68, 85, 74];

// 2. PASS VS FAIL - Count students who passed vs failed
$passFailStats = $pdo->prepare('
    SELECT 
        SUM(CASE WHEN r.final_grade IN ("A+", "A", "B+", "B", "C+", "C") THEN 1 ELSE 0 END) as passed,
        SUM(CASE WHEN r.final_grade IN ("D", "F") THEN 1 ELSE 0 END) as failed
    FROM results r
    JOIN subjects s ON s.id = r.subject_id
    WHERE s.teacher_id = ?
');
$passFailStats->execute([$teacherId]);
$passFailStats = $passFailStats->fetch();

$passedCount = (int)($passFailStats['passed'] ?? 45);
$failedCount = (int)($passFailStats['failed'] ?? 15);

// 3. ATTENDANCE TREND - Last 6 months attendance percentage
$attendanceTrend = $pdo->prepare('
    SELECT 
        DATE_FORMAT(sa.attendance_date, "%b") as month,
        ROUND(AVG(CASE WHEN sa.status = "Present" THEN 1 ELSE 0 END) * 100, 1) as rate
    FROM student_attendance sa
    JOIN subjects s ON s.id = sa.subject_id
    WHERE s.teacher_id = ?
        AND sa.attendance_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(sa.attendance_date), MONTH(sa.attendance_date)
    ORDER BY sa.attendance_date
');
$attendanceTrend->execute([$teacherId]);
$attendanceTrend = $attendanceTrend->fetchAll();

// Prepare attendance data (fallback if no data)
$attendanceMonths = array_column($attendanceTrend, 'month') ?: ['MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT'];
$attendanceRates = array_column($attendanceTrend, 'rate') ?: [82, 85, 79, 88, 86, 90];

$pageTitle = 'Teacher Dashboard | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
  <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>
  <main class="main-panel">
    <div class="dashboard-top">
      <div class="dashboard-title">
        <h1>Teacher Dashboard</h1>
        <p><?= e(current_datetime()) ?> • Welcome, <?= e(user()['name']) ?></p>
      </div>
      <div class="user-chip">🧑‍🏫 Teacher Panel</div>
    </div>

    <!-- Metric Cards - Teacher Statistics -->
    <div class="metric-grid">
      <div class="metric-card">
        <div class="label">Subjects</div>
        <div class="value"><?= $subjectCount ?></div>
        <div class="subtext">Assigned subjects</div>
      </div>
      <div class="metric-card">
        <div class="label">Assignments</div>
        <div class="value"><?= $assignmentCount ?></div>
        <div class="subtext">Created by you</div>
      </div>
      <div class="metric-card">
        <div class="label">Results</div>
        <div class="value"><?= $resultCount ?></div>
        <div class="subtext">Published records</div>
      </div>
      <div class="metric-card">
        <div class="label">Date</div>
        <div class="value"><?= date('d') ?></div>
        <div class="subtext"><?= date('F Y') ?></div>
      </div>
    </div>

    <div class="dashboard-grid">

      <!-- Quick Actions Panel -->
      <div class="panel-card">
        <h3>Quick Actions</h3>
        <div class="quick-actions">
          <a href="<?= BASE_URL ?>/teacher/attendance.php"><span class="qa-emoji">📋</span><span>Attendance</span></a>
          <a href="<?= BASE_URL ?>/teacher/classes.php"><span class="qa-emoji">🏫</span><span>Classes</span></a>
          <a href="<?= BASE_URL ?>/teacher/students.php"><span class="qa-emoji">👨‍🎓</span><span>Students</span></a>
          <a href="<?= BASE_URL ?>/teacher/assignments.php"><span class="qa-emoji">📝</span><span>Assignments</span></a>
          <a href="<?= BASE_URL ?>/teacher/materials.php"><span class="qa-emoji">📂</span><span>Materials</span></a>
          <a href="<?= BASE_URL ?>/teacher/results.php"><span class="qa-emoji">📊</span><span>Results</span></a>
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
        <h2>📊 Teaching Analytics</h2>
        <button class="download-analytics-btn">⬇ Download Chart</button>
      </div>

      <!-- Two dropdowns: Trend selection and Chart type selection -->
      <div class="analytics-controls">
        <div class="analytics-control-group">
          <label for="trendSelect">📈 Select Trend</label>
          <select id="trendSelect">
            <option value="attendance">📋 Class Attendance (Last 6 Months)</option>
            <option value="marks">📊 Student Marks by Subject</option>
            <option value="passfail">🎯 Pass vs Fail Ratio</option>
          </select>
        </div>

        <div class="analytics-control-group">
          <label for="chartTypeSelect">📊 Chart Type</label>
          <select id="chartTypeSelect">
            <option value="line">Line Chart</option>
            <option value="bar">Bar Chart</option>
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
          Select a trend and chart type above to visualize teaching data.
        </p>
      </div>
    </div>
    <!-- END OF CHARTS SECTION -->

  </main>
</div>

<!-- Include Chart.js and html2canvas libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<!-- Include teacher charts module -->
<script src="/Academic-Management-System/assets/js/teacher-charts.js"></script>

<!-- Pass data from PHP to JavaScript and initialize charts -->
<script>
  // Prepare data object for teacher charts
  const teacherChartData = {
    // Data for Attendance Trend
    attendanceMonths: <?= json_encode($attendanceMonths) ?>,
    attendanceRates: <?= json_encode($attendanceRates) ?>,
    // Data for Pass vs Fail
    passedCount: <?= json_encode($passedCount) ?>,
    failedCount: <?= json_encode($failedCount) ?>,
    // Data for Student Marks
    subjectNames: <?= json_encode($subjectNames) ?>,
    avgMarks: <?= json_encode($avgMarks) ?>
  };

  // Initialize dashboard analytics when page loads
  document.addEventListener('DOMContentLoaded', function() {
    initTeacherDashboardAnalytics(teacherChartData);
  });
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>