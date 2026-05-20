-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 20, 2026 at 04:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ams_portal`
--

DROP DATABASE IF EXISTS `ams_portal`;
CREATE DATABASE `ams_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ams_portal`;

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `body` text NOT NULL,
  `visibility_role` enum('all','teacher','student') NOT NULL DEFAULT 'all',
  `target_audience` varchar(120) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `posted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `visibility_role`, `target_audience`, `subject_id`, `created_by`, `posted_at`) VALUES
(1, 'test', 'test', 'student', 'Semester 1 Student', NULL, 3, '2026-05-19 13:15:44');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `instructions` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `deadline` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `subject_id`, `teacher_id`, `title`, `instructions`, `file_name`, `deadline`, `created_at`) VALUES
(1, 8, 3, 'test', '', 'd8c3b9c7b5b32a4e03889809e5f654aa.pdf', '2026-05-20 00:00:00', '2026-05-19 13:14:31');

-- --------------------------------------------------------

--
-- Table structure for table `class_schedules`
--

CREATE TABLE `class_schedules` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `classroom` varchar(80) NOT NULL,
  `year_label` varchar(30) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_schedules`
--

INSERT INTO `class_schedules` (`id`, `course_id`, `subject_id`, `teacher_id`, `day_name`, `start_time`, `end_time`, `classroom`, `year_label`, `semester`, `created_at`) VALUES
(2, 3, 4, 3, 'Tuesday', '10:00:00', '12:00:00', 'Room 202', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(3, 9, 10, 4, 'Wednesday', '14:00:00', '16:00:00', 'Cyber Lab', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(4, 5, 6, 2, 'Thursday', '11:00:00', '13:00:00', 'Lab 303', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(5, 1, 1, 2, 'Monday', '09:00:00', '11:00:00', 'Lab 101', 'Year 1', 'Semester 1', '2026-05-20 06:18:47'),
(8, 9, 12, 4, 'Sunday', '13:15:00', '14:15:00', 'TR 20', 'Year 1', 'Semester 1', '2026-05-20 07:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `year_label` varchar(30) NOT NULL,
  `semester` varchar(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `year_label`, `semester`, `created_at`) VALUES
(1, 'BSc Computer Science', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(2, 'BSc Computer Science', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(3, 'BSc Information Technology', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(4, 'BSc Information Technology', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(5, 'BSc Software Engineering', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(6, 'BSc Software Engineering', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(7, 'Diploma in Networking', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(8, 'Diploma in Networking', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(9, 'BSc Cyber Security', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(10, 'BSc Cyber Security', 'Year 2', 'Semester 2', '2026-05-18 17:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `year_label` varchar(30) NOT NULL,
  `semester` varchar(60) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `year_label`, `semester`, `status`, `created_at`) VALUES
(1, 5, 1, 'Year 1', 'Semester 1', 'active', '2026-05-18 17:05:08'),
(2, 6, 3, 'Year 1', 'Semester 1', 'active', '2026-05-18 17:05:08'),
(3, 7, 9, 'Year 1', 'Semester 1', 'active', '2026-05-18 17:05:08'),
(4, 8, 5, 'Year 1', 'Semester 1', 'active', '2026-05-18 17:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `attempt_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `assignment_marks` decimal(5,2) NOT NULL,
  `internal_marks` decimal(5,2) NOT NULL,
  `exam_marks` decimal(5,2) NOT NULL,
  `final_total` decimal(5,2) NOT NULL DEFAULT 0.00,
  `final_grade` varchar(10) NOT NULL,
  `gpa` decimal(3,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `student_id`, `subject_id`, `teacher_id`, `assignment_marks`, `internal_marks`, `exam_marks`, `final_total`, `final_grade`, `gpa`, `created_at`) VALUES
(5, 6, 4, 3, 30.00, 20.00, 40.00, 90.00, 'A', 4.00, '2026-05-19 08:11:35');

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(30) NOT NULL,
  `marked_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_attendance`
--

INSERT INTO `student_attendance` (`id`, `student_id`, `subject_id`, `attendance_date`, `status`, `marked_by`, `created_at`) VALUES
(1, 6, 4, '2026-05-20', 'Present', 3, '2026-05-20 06:43:15'),
(2, 7, 10, '2026-05-20', 'Present', 1, '2026-05-20 06:44:37'),
(4, 7, 10, '2026-05-19', 'Present', 1, '2026-05-20 06:45:02'),
(5, 6, 4, '2026-05-19', 'Absent', 3, '2026-05-20 06:49:48');

-- --------------------------------------------------------

--
-- Table structure for table `study_materials`
--

CREATE TABLE `study_materials` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `study_materials`
--

INSERT INTO `study_materials` (`id`, `subject_id`, `teacher_id`, `title`, `description`, `file_name`, `created_at`) VALUES
(1, 9, 3, 'test', '', '004f645a9416f8b304a080447ffe8246.pdf', '2026-05-18 19:37:45');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_code` varchar(40) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `year_label` varchar(30) NOT NULL,
  `semester` varchar(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `course_id`, `teacher_id`, `subject_code`, `subject_name`, `year_label`, `semester`, `created_at`) VALUES
(1, 1, 2, 'CSE111', 'Programming Fundamentals', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(2, 2, 2, 'CSE112', 'Database Systems', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(3, 2, 2, 'CSE113', 'Data Structures', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(4, 3, 3, 'ITE121', 'Web Development', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(5, 4, 3, 'ITE122', 'Networking Basics', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(6, 5, 2, 'SEE131', 'Software Testing', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(7, 6, 2, 'SEE132', 'Agile Development', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(8, 7, 3, 'NET141', 'CCNA Fundamentals', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(9, 8, 3, 'NET142', 'Linux Administration', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(10, 9, 4, 'CYE151', 'Ethical Hacking', 'Year 1', 'Semester 1', '2026-05-18 17:05:08'),
(11, 10, 4, 'CYE152', 'Digital Forensics', 'Year 2', 'Semester 2', '2026-05-18 17:05:08'),
(12, 9, 4, 'CYE155', 'Computer Networking', 'Year 1', 'Semester 1', '2026-05-20 07:21:56');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `submitted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(30) NOT NULL,
  `marked_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_attendance`
--

INSERT INTO `teacher_attendance` (`id`, `teacher_id`, `subject_id`, `attendance_date`, `status`, `marked_by`, `created_at`) VALUES
(1, 3, NULL, '2026-05-18', 'Present', 1, '2026-05-18 17:05:08'),
(2, 2, NULL, '2026-05-18', 'Present', 1, '2026-05-18 17:05:08'),
(3, 4, NULL, '2026-05-18', 'Present', 1, '2026-05-18 17:05:08'),
(4, 3, NULL, '2026-05-17', 'Absent', 1, '2026-05-18 17:05:08'),
(5, 2, NULL, '2026-05-17', 'Late', 1, '2026-05-18 17:05:08'),
(6, 4, NULL, '2026-05-17', 'Absent', 1, '2026-05-18 17:05:08'),
(7, 3, NULL, '2026-05-20', 'Present', 1, '2026-05-20 06:43:46'),
(9, 3, 9, '2026-05-20', 'Present', 1, '2026-05-20 07:11:14'),
(10, 3, 8, '2026-05-20', 'Present', 1, '2026-05-20 07:11:32');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_courses`
--

CREATE TABLE `teacher_courses` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_courses`
--

INSERT INTO `teacher_courses` (`id`, `teacher_id`, `course_id`, `created_at`) VALUES
(1, 2, 1, '2026-05-18 17:05:08'),
(2, 2, 5, '2026-05-18 17:05:08'),
(3, 2, 6, '2026-05-18 17:05:08'),
(4, 3, 3, '2026-05-18 17:05:08'),
(5, 3, 4, '2026-05-18 17:05:08'),
(6, 3, 7, '2026-05-18 17:05:08'),
(7, 3, 8, '2026-05-18 17:05:08'),
(8, 4, 9, '2026-05-18 17:05:08'),
(9, 4, 10, '2026-05-18 17:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `role_code` varchar(30) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact` varchar(40) DEFAULT NULL,
  `guardian` varchar(120) DEFAULT NULL,
  `qualification` varchar(120) DEFAULT NULL,
  `department` varchar(120) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `reset_token` varchar(64) DEFAULT NULL,
  `otp_code` varchar(255) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `otp_attempts` int(11) DEFAULT 0,
  `last_otp_request` datetime DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `role_code`, `name`, `email`, `password`, `photo`, `dob`, `address`, `contact`, `guardian`, `qualification`, `department`, `status`, `reset_token`, `otp_code`, `otp_expires_at`, `otp_attempts`, `last_otp_request`, `reset_expires_at`, `created_at`) VALUES
(1, 'admin', 'ADM-0001', 'Shaan Shrestha', 'shaanstha2060@gmail.com', '$2y$10$OB2BaA9Xb8XfLZwjlu5egemac3C8RyKmHWIfSeqsYLaBQ.oYtBtsG', NULL, '1995-01-01', 'Kathmandu', '9800000000', 'N/A', 'MSc IT', 'Administration', 'active', NULL, NULL, NULL, 0, '2026-05-19 14:33:14', NULL, '2026-05-18 17:05:08'),
(2, 'teacher', 'TCH-0001', 'Kritika Gautam', 'kritikasgautam@gmail.com', '$2y$10$g8evxh49wEpMs3riC0vlneqqPyNoTvZZaxejbeIs6wDv9vXL1HFPa', NULL, '1990-05-15', 'Lalitpur', '9812345678', 'N/A', 'MSc CS', 'Computing', 'active', NULL, NULL, NULL, 0, NULL, NULL, '2026-05-18 17:05:08'),
(3, 'teacher', 'TCH-0002', 'Hrishika Kamat', 'kamathrishika101@gmail.com', '$2y$10$bKO.S2RG8LRuJ.P2tur0SeP7CVRSrkxsiH4HYKVc.CdwRVGsasWBS', NULL, '1988-08-22', 'Pokhara', '9823456789', 'N/A', 'CCNA', 'Networking', 'active', NULL, NULL, NULL, 0, NULL, NULL, '2026-05-18 17:05:08'),
(4, 'teacher', 'TCH-0003', 'Niroj Basnet', 'niroj.0421@gmail.com', '$2y$10$tkCgMEtglU0XE8Xcwm8m.utEeiV/UreOt//119CQglejreXqbAyZm', NULL, '1992-11-30', 'Bhaktapur', '9834567890', 'N/A', 'MSc Security', 'Security', 'active', NULL, NULL, NULL, 0, NULL, NULL, '2026-05-18 17:05:08'),
(5, 'student', 'STD-0001', 'Rehan Gurung', 'rehangrx@gmail.com', '$2y$10$vbGJdmFY/HEC1.BYy4PfF.ezhxGwCmTyzjz/r1UqPUrHuGbJfHzRG', NULL, '2005-03-12', 'Kathmandu', '9841234567', 'Mr. Gurung', 'High School', 'CS101', 'active', NULL, NULL, NULL, 0, NULL, NULL, '2026-05-18 17:05:08'),
(6, 'student', 'STD-0002', 'Maria Garcia', 'esportsstubborn@gmail.com', '$2y$10$puyNFY5DH3vh5GiPKxpgc.aW5qJZKY/y5C7gYnJTbq.0ipwMlyGze', NULL, '2006-07-19', 'Lalitpur', '9852345678', 'Mrs. Garcia', 'High School', 'IT102', 'active', NULL, NULL, NULL, 0, NULL, NULL, '2026-05-18 17:05:08'),
(7, 'student', 'STD-0003', 'David Miller', 'wiselion999@gmail.com', '$2y$10$8nyzbLiX3YExqvE52wKkCOSXcG3OfxeYa/A5whaB9i7O30CtvdKia', NULL, '2004-11-05', 'Bhaktapur', '9863456789', 'Mr. Miller', 'High School', 'CY105', 'active', NULL, NULL, NULL, 0, NULL, NULL, '2026-05-18 17:05:08'),
(8, 'student', 'STD-0004', 'Sophia Davis', 'nirojb338@gmail.com', '$2y$10$jHJauAPBWjTRcG/OeYuy.uIP5JCh8REz4HyR2tZO7IttL3Gkm9meW', NULL, '2005-01-25', 'Pokhara', '9874567890', 'Mrs. Davis', 'High School', 'SE103', 'active', NULL, NULL, NULL, 0, NULL, NULL, '2026-05-18 17:05:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enrollment` (`student_id`,`course_id`,`year_label`,`semester`),
  ADD KEY `fk_enroll_course` (`course_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_ip` (`email`,`ip_address`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `identifier` (`identifier`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_attendance` (`student_id`,`subject_id`,`attendance_date`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_material_subject` (`subject_id`),
  ADD KEY `fk_material_teacher` (`teacher_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_subject_course` (`course_id`),
  ADD KEY `fk_subject_teacher` (`teacher_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_subject_date` (`teacher_id`,`subject_id`,`attendance_date`),
  ADD KEY `marked_by` (`marked_by`),
  ADD KEY `fk_teacher_attendance_subject` (`subject_id`);

--
-- Indexes for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_course` (`teacher_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_otp` (`otp_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class_schedules`
--
ALTER TABLE `class_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `study_materials`
--
ALTER TABLE `study_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD CONSTRAINT `class_schedules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_schedules_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_schedules_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_enroll_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `results_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD CONSTRAINT `fk_material_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_material_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `fk_subject_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subject_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD CONSTRAINT `fk_teacher_attendance_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_attendance_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_attendance_ibfk_2` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  ADD CONSTRAINT `teacher_courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
