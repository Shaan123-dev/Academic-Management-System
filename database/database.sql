DROP DATABASE IF EXISTS ams_portal;
CREATE DATABASE ams_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ams_portal;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin','teacher','student') NOT NULL,
    role_code VARCHAR(30) NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    photo VARCHAR(255) NULL,
    dob DATE NULL,
    address TEXT NULL,
    contact VARCHAR(40) NULL,
    guardian VARCHAR(120) NULL,
    qualification VARCHAR(120) NULL,
    department VARCHAR(120) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    reset_token VARCHAR(64) NULL,
    reset_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(150) NOT NULL,
    year_label VARCHAR(30) NOT NULL,
    semester VARCHAR(60) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    subject_code VARCHAR(40) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    year_label VARCHAR(30) NOT NULL,
    semester VARCHAR(60) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subject_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_subject_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE class_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    day_name VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    classroom VARCHAR(80) NOT NULL,
    year_label VARCHAR(30) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    visibility_role ENUM('all','teacher','student') NOT NULL DEFAULT 'all',
    target_audience VARCHAR(120) NULL,
    subject_id INT NULL,
    created_by INT NOT NULL,
    posted_at DATETIME NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE teacher_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL,
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL,
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    instructions TEXT NULL,
    file_name VARCHAR(255) NULL,
    deadline DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    comment VARCHAR(255) NULL,
    submitted_at DATETIME NOT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    assignment_marks DECIMAL(5,2) NOT NULL,
    internal_marks DECIMAL(5,2) NOT NULL,
    exam_marks DECIMAL(5,2) NOT NULL,
    final_total DECIMAL(5,2) NOT NULL DEFAULT 0,
    final_grade VARCHAR(10) NOT NULL,
    gpa DECIMAL(3,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (role, role_code, name, email, password, photo, dob, address, contact, guardian, qualification, department, status) VALUES
('admin', 'ADM-0001', 'System Admin', 'admin@ams.com', '$2y$12$2OCtPMqenFCNfbicRUQYBuwyDIQTgOIO3cN8yCD0HKv2ygdDtljoW', NULL, '1995-01-01', 'Kathmandu', '9800000000', 'N/A', 'MSc IT', 'Administration', 'active'),
('teacher', 'TCH-0001', 'Kritika Gautam', 'teacher@ams.com', '$2y$12$2OCtPMqenFCNfbicRUQYBuwyDIQTgOIO3cN8yCD0HKv2ygdDtljoW', NULL, '1998-02-10', 'Lalitpur', '9800000001', 'N/A', 'BSc CSIT', 'Computing', 'active'),
('student', 'STD-0001', 'Rishav Pradhan', 'student@ams.com', '$2y$12$2OCtPMqenFCNfbicRUQYBuwyDIQTgOIO3cN8yCD0HKv2ygdDtljoW', NULL, '2003-06-12', 'Patan', '9800000002', 'Parent Name', 'Plus Two', 'Computing', 'active');

INSERT INTO courses (course_name, year_label, semester) VALUES
('BSc Computing', 'Year 1', 'Semester 1'),
('BSc Computing', 'Year 1', 'Semester 2');

INSERT INTO subjects (course_id, teacher_id, subject_code, subject_name, year_label, semester) VALUES
(1, 2, 'WD101', 'Web Development', 'Year 1', 'Semester 1'),
(1, 2, 'DB102', 'Database Systems', 'Year 1', 'Semester 1');

INSERT INTO class_schedules (course_id, subject_id, teacher_id, day_name, start_time, end_time, classroom, year_label, semester) VALUES
(1, 1, 2, 'Sunday', '10:00:00', '11:00:00', 'Lab 4', 'Year 1', 'Semester 1'),
(1, 2, 2, 'Tuesday', '11:00:00', '12:00:00', 'Room 203', 'Year 1', 'Semester 1');

INSERT INTO announcements (title, body, visibility_role, target_audience, subject_id, created_by, posted_at) VALUES
('Welcome to AMS', 'This portal is active for academic operations, attendance, schedules, assignments, and results.', 'all', 'All Users', NULL, 1, NOW()),
('Web Development Notice', 'Please review the assignment instructions before submission and submit before the deadline.', 'student', 'Semester 1 Students', 1, 2, NOW());

INSERT INTO teacher_attendance (teacher_id, attendance_date, status, marked_by) VALUES
(2, CURDATE(), 'Present', 1);

INSERT INTO student_attendance (student_id, subject_id, attendance_date, status, marked_by) VALUES
(3, 1, CURDATE(), 'Present', 2),
(3, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Absent', 2);

INSERT INTO assignments (subject_id, teacher_id, title, instructions, file_name, deadline, created_at) VALUES
(1, 2, 'coursework', 'complete it.', NULL, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW()),
(2, 2, 'Design Database ERD', 'Prepare a complete ER diagram for the academic management portal.', NULL, DATE_ADD(NOW(), INTERVAL 10 DAY), NOW());

INSERT INTO submissions (assignment_id, student_id, file_name, comment, submitted_at) VALUES
(1, 3, 'sample-submission.pdf', 'Initial submission', NOW());

INSERT INTO results (student_id, subject_id, teacher_id, assignment_marks, internal_marks, exam_marks, final_total, final_grade, gpa) VALUES
(3, 1, 2, 18, 17, 52, 87, 'A', 3.70),
(3, 2, 2, 16, 15, 48, 79, 'B+', 3.30);
