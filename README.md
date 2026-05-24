# Academic Management Portal System (AMS)

A complete role-based Academic Management Portal System developed for **Admins**, **Teachers**, and **Students**. The system supports attendance management, assignments, results, class schedules, announcements, secure file uploads, OTP-based password reset, and dashboard analytics using Chart.js.

---

## 📦 Composer Dependencies Required

This project uses **PHPMailer** for sending OTP emails during the password reset process.

After cloning or downloading the project, Composer dependencies must be installed before using the OTP email feature.

### Steps

1. Install Composer from:

```text
https://getcomposer.org/
```

2. Open a terminal inside the project root folder.

3. Run:

```bash
composer install
```

4. This will create the `vendor/` folder.

The `vendor/` folder is ignored by Git, so it will not be uploaded to GitHub.

> Note: If `composer install` is not run, the OTP email sending feature may not work because `vendor/autoload.php` will be missing.

---

## 📌 Main Features

### 👑 Admin Features

- Manage students, teachers, courses, subjects, enrollments, and classes.
- Mark teacher and student attendance.
- Prevent duplicate attendance records.
- View dashboard statistics and analytics charts.
- View attendance trends, pass/fail ratio, and department distribution.
- Download dashboard charts as images.
- Post announcements for all users or specific roles.
- Manage users with role-based access.

---

### 👩‍🏫 Teacher Features

- View teacher dashboard.
- Mark student attendance by subject and date.
- Prevent duplicate attendance entries.
- Upload assignments.
- Upload study materials.
- View student submissions.
- Enter student results.
- Automatically calculate grade and GPA.
- View class schedules.
- View student lists.
- Download teacher dashboard charts.

---

### 👨‍🎓 Student Features

- View student dashboard.
- View personal attendance records.
- View results and grades.
- View assignments.
- Submit assignment files.
- View study materials.
- View class schedule.
- Download student dashboard charts.
- Request password reset using OTP email verification.

---

## 🔒 Security Features

- Role-based access control for Admin, Teacher, and Student.
- Secure login and logout system.
- Password hashing using bcrypt.
- Strong password validation.
- CSRF protection on forms.
- Session timeout protection.
- HTTP-only session cookies.
- Session regeneration after login.
- OTP-based password reset.
- Hashed OTP storage.
- OTP expiry and request limit.
- Secure file upload validation.
- File extension and MIME type checking.
- Dangerous file types blocked.
- Randomized uploaded file names.
- `.htaccess` protection in upload folders.
- Input validation and duplicate prevention.

---

## 📊 Dashboard Analytics

The system includes dashboard analytics using **Chart.js** and **html2canvas**.

### Admin Dashboard Charts

- Attendance trend chart.
- Pass/fail ratio chart.
- Students per department chart.

### Teacher Dashboard Charts

- Student performance chart.
- Attendance rate chart.

### Student Dashboard Charts

- Personal attendance chart.
- Grade distribution chart.

Charts can be downloaded as PNG images.

---

## 🛠️ Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.0+ |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, JavaScript |
| Charts | Chart.js |
| Chart Download | html2canvas |
| Email | PHPMailer |
| Local Server | XAMPP |
| Version Control | Git and GitHub |
| Project Management | Jira |

---

## 📁 Folder Structure

```text
Academic-Management-System/
│
├── assets/
│   ├── css/
│   │   ├── auth.css
│   │   ├── dashboard.css
│   │   └── style.css
│   │
│   ├── images/
│   │   ├── bg.jpg
│   │   ├── classes.jpg
│   │   ├── graduation.jpg
│   │   ├── logo.png
│   │   └── lounge.png
│   │
│   └── js/
│       ├── charts.js
│       ├── main.js
│       ├── student-charts.js
│       └── teacher-charts.js
│
├── config/
│   └── config.php
│
├── database/
│   └── database.sql
│
├── includes/
│   ├── auth.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   ├── sidebar.php
│   └── topnav.php
│
├── logs/
│   └── otp_log.txt
│
├── public/
│   ├── .htaccess
│   ├── forgot_password.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── open_file.php
│   ├── profile.php
│   ├── reset_password.php
│   │
│   ├── admin/
│   │   ├── announcements.php
│   │   ├── classes.php
│   │   ├── courses.php
│   │   ├── dashboard.php
│   │   ├── enrollments.php
│   │   ├── profile.php
│   │   ├── reports.php
│   │   ├── students.php
│   │   ├── student_attendance.php
│   │   ├── subjects.php
│   │   ├── teachers.php
│   │   ├── teacher_attendance.php
│   │   └── users.php
│   │
│   ├── student/
│   │   ├── announcements.php
│   │   ├── assignments.php
│   │   ├── attendance.php
│   │   ├── courses.php
│   │   ├── dashboard.php
│   │   ├── digital_id.php
│   │   ├── materials.php
│   │   ├── results.php
│   │   ├── schedule.php
│   │   ├── settings.php
│   │   └── subjects.php
│   │
│   └── teacher/
│       ├── announcements.php
│       ├── assignments.php
│       ├── attendance.php
│       ├── classes.php
│       ├── dashboard.php
│       ├── digital_id.php
│       ├── materials.php
│       ├── results.php
│       ├── schedule.php
│       ├── settings.php
│       ├── students.php
│       ├── submissions.php
│       └── teacher_attendance.php
│
├── uploads/
│   ├── assignments/
│   ├── materials/
│   ├── photos/
│   └── submissions/
│
├── vendor/
│   └── composer and PHPMailer files
│
├── composer.json
├── composer.lock
├── README.txt
└── README.md
```

---

## 🚀 Installation Guide for Localhost

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/Academic-Management-System.git
```

Or download the ZIP file and extract it.

---

### 2. Move the Project to XAMPP

Move the project folder to:

```text
C:\xampp\htdocs\Academic-Management-System
```

---

### 3. Start XAMPP

Start the following services:

- Apache
- MySQL

---

### 4. Create the Database

1. Open phpMyAdmin.
2. Create a new database.
3. Recommended database name:

```text
ams_portal
```

4. Import the SQL file from:

```text
database/database.sql
```

This will create the required tables and demo data.

---

### 5. Configure Database Connection

Open:

```text
config/config.php
```

Update the database details if needed:

```php
$dbHost = 'localhost';
$dbName = 'ams_portal';
$dbUser = 'root';
$dbPass = '';
```

For XAMPP, the default username is usually `root` and the password is empty.

---

### 6. Install Composer Dependencies

Open terminal inside the project root and run:

```bash
composer install
```

This will install PHPMailer and create the `vendor/` folder.

---

### 7. Configure SMTP for OTP Email

The system uses PHPMailer for sending OTP emails during password reset.

Open:

```text
config/config.php
```

Update your SMTP email settings if needed:

```php
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

Use a Gmail App Password, not your normal Gmail password.

You may also use the SMTP email settings that are already configured in the project, such as Shaan’s existing SMTP email configuration, if it is already working correctly.

To create a Gmail App Password:

1. Open your Google Account.
2. Go to Security.
3. Enable 2-Step Verification.
4. Generate an App Password.
5. Paste that App Password in `SMTP_PASS`.

> Note: If SMTP is not configured correctly, the password reset OTP email feature will not work.

---

### 8. Run the System

Open your browser and visit:

```text
http://localhost/Academic-Management-System/public/login.php
```

---

## 🔐 Default Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | shaanstha2060@gmail.com | Admin@123 |
| Teacher | kritikasgautam@gmail.com | Kritika@123 |
| Student | rehangrx@gmail.com | Rehan@123 |

> Note: These accounts are available after importing the provided `database/database.sql` file.

---

## 🧪 Testing the System

After installation, test the following:

- Login as Admin, Teacher, and Student.
- Check role-based dashboard access.
- Add and manage students, teachers, courses, and subjects.
- Mark attendance.
- Upload assignments and materials.
- Submit assignments as a student.
- Enter and view results.
- Test dashboard charts.
- Download charts.
- Test forgot password and OTP reset.
- Test logout and session timeout.

---

## 🐛 Common Issues and Fixes

| Issue | Solution |
|---|---|
| White screen or 500 error | Check PHP error logs and make sure `vendor/autoload.php` exists. |
| OTP email not sending | Run `composer install` and check SMTP settings. |
| CSS not loading | Make sure the project path and `BASE_URL` are correct. |
| Database connection error | Check database name, username, and password in `config/config.php`. |
| File upload not working | Check upload folder permissions. |
| Session expires too quickly | Update `SESSION_TIMEOUT_MINUTES` in `config.php`. |
| Page not found | Make sure the project is inside `htdocs` and URL path is correct. |

---

## 📌 Git Ignore Notes

The following files and folders should not be pushed to GitHub:

```text
/vendor/
composer.phar
/logs/
*.log
*.tmp
/cache/
/sessions/
```

Upload folders may be ignored except important placeholder files like `.gitkeep` and `.htaccess`.

Recommended `.gitignore` example:

```gitignore
/vendor/
composer.phar

/uploads/*
!/uploads/.gitkeep
!/uploads/**/.gitkeep
!/uploads/**/.htaccess

/logs/
*.log
*.tmp
/cache/
/sessions/
```

---

## 🤝 Team and Collaboration

This project was developed by team **Marks Mafias** as part of the Collaborative Development module.

The team used:

- GitHub for version control.
- Jira for task tracking and sprint management.
- Google Drive for documentation and file sharing.
- Discord and WhatsApp for communication.
- Physical meetings for discussion and review.

---

## 👨‍💻 Author

**Shaan Shrestha**  
Student ID: 2548925  
Group: L5SG4  
Module: Collaborative Development  
Project: Academic Management Portal System

---

## 🙏 Acknowledgements

- PHPMailer
- Chart.js
- html2canvas
- XAMPP
- GitHub
- Jira
- Google Drive

---

## 📄 License

This project is developed for educational purposes only.  
No commercial license is implied.
