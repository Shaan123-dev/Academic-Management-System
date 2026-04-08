<?php
/*
|--------------------------------------------------------------------------
| Global Sidebar (Reusable)
|--------------------------------------------------------------------------
| Shows different menus based on user role
|--------------------------------------------------------------------------
*/

$user = current_user();
$role = $user['role_name'] ?? '';
?>

<aside class="sidebar">

    <!-- COMMON -->
    <a href="<?= BASE_URL ?>/public/index.php">Home</a>

    <?php if ($role === 'admin'): ?>

        <a href="<?= BASE_URL ?>/public/admin/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/public/admin/teachers/teacher_index.php">Teachers</a>
        <a href="<?= BASE_URL ?>/public/admin/students/student_index.php">Students</a>
        <a href="<?= BASE_URL ?>/public/admin/courses/course_index.php">Courses</a>
        <a href="<?= BASE_URL ?>/public/admin/attendance/attendance_index.php">Attendance</a>
        <a href="<?= BASE_URL ?>/public/admin/grades.php">Results / Grades</a>
        <a href="<?= BASE_URL ?>/public/admin/assignments/assignment_index.php">Assignments</a>
        <a href="<?= BASE_URL ?>/public/admin/announcements/announcement_index.php">Announcements</a>

    <?php elseif ($role === 'teacher'): ?>

        <a href="<?= BASE_URL ?>/public/teacher/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/public/teacher/students.php">Students</a>
        <a href="<?= BASE_URL ?>/public/teacher/assignments/assignment_index.php">Assignments</a>
        <a href="<?= BASE_URL ?>/public/teacher/assignments/submission_index.php">Submissions</a>
        <a href="<?= BASE_URL ?>/public/teacher/grades.php">Results / Grades</a>
        <a href="<?= BASE_URL ?>/public/teacher/attendance/attendance_index.php">Attendance</a>

    <?php elseif ($role === 'student'): ?>

        <a href="<?= BASE_URL ?>/public/student/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/public/student/attendance/attendance_index.php">My Attendance</a>
        <a href="<?= BASE_URL ?>/public/student/grades.php">Results / Grades</a>
        <a href="<?= BASE_URL ?>/public/student/assignments/assignment_index.php">Assignments</a>

    <?php endif; ?>

    <a href="<?= BASE_URL ?>/public/logout.php">Logout</a>

</aside>