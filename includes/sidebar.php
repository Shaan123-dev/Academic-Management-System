<?php
$current = basename($_SERVER['PHP_SELF']);
$role = user()['role'] ?? '';
$base = BASE_URL . '/' . $role;
$links = ['dashboard.php' => 'Dashboard'];

if ($role === 'admin') {
    $links += [
        'students.php' => 'Manage Students',
        'teachers.php' => 'Manage Teachers',
        'users.php' => 'Manage Users',
        'courses.php' => 'Courses',
        'subjects.php' => 'Subjects',
        'enrollments.php' => 'Enrollments',
        'classes.php' => 'Classes',
        'announcements.php' => 'Announcements',
        'teacher_attendance.php' => 'Teacher Attendance',
        'student_attendance.php' => 'Student Attendance',
        'reports.php' => 'Reports',
        'profile.php' => 'Profile',
    ];
} elseif ($role === 'teacher') {
    $links += [
        'teacher_attendance.php' => 'My Attendance',
        'attendance.php' => 'Student Attendance',
        'classes.php' => 'Assigned Classes',
        'students.php' => 'Class Students',
        'assignments.php' => 'Assignments',
        'materials.php' => 'Study Materials',
        'results.php' => 'Results',
        'announcements.php' => 'Announcements',
        'schedule.php' => 'Schedule',
        'digital_id.php' => 'Digital ID',
        'settings.php' => 'Settings',
    ];
} elseif ($role === 'student') {
    $links += [
        'attendance.php' => 'My Attendance',
        'courses.php' => 'My Courses',
        'subjects.php' => 'My Subjects',
        'assignments.php' => 'Assignments',
        'materials.php' => 'Study Materials',
        'results.php' => 'Results',
        'announcements.php' => 'Announcements',
        'digital_id.php' => 'Digital ID',
        'schedule.php' => 'Schedule',
        'settings.php' => 'Settings',
    ];
}
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="<?= BASE_URL ?>/../assets/images/logo.png" alt="Logo">
    <div>
      <div class="title">Marks Mafias</div>
      <div class="subtitle">Academic Management Portal</div>
    </div>
  </div>
  <nav class="sidebar-menu">
    <?php foreach ($links as $file => $label): ?>
      <a href="<?= $base . '/' . $file ?>" class="<?= $current === $file ? 'active' : '' ?>">
        <span><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/logout.php"><span>Logout</span></a>
  </nav>
</aside>