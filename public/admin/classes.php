<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    // Get form data
    $course_id = (int)$_POST['course_id'];
    $subject_id = (int)$_POST['subject_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $day_name = trim($_POST['day_name']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $classroom = trim($_POST['classroom']);
    $year_label = trim($_POST['year_label']);
    $semester = trim($_POST['semester']);

    // ============================================================
    // DUPLICATE CHECK: same teacher, same day, same time
    // ============================================================
    if ($action === 'create') {
        $checkStmt = $pdo->prepare('
            SELECT id FROM class_schedules
            WHERE teacher_id = ? AND day_name = ? AND start_time = ? AND end_time = ?
        ');
        $checkStmt->execute([$teacher_id, $day_name, $start_time, $end_time]);
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate class schedule! This teacher already has a class on this day at the same time.');
            redirect_to(BASE_URL . '/admin/classes.php');
        }

        $stmt = $pdo->prepare('
            INSERT INTO class_schedules
            (course_id, subject_id, teacher_id, day_name, start_time, end_time, classroom, year_label, semester)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$course_id, $subject_id, $teacher_id, $day_name, $start_time, $end_time, $classroom, $year_label, $semester]);
        flash('success', 'Class added.');
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];

        $checkStmt = $pdo->prepare('
            SELECT id FROM class_schedules
            WHERE teacher_id = ? AND day_name = ? AND start_time = ? AND end_time = ? AND id != ?
        ');
        $checkStmt->execute([$teacher_id, $day_name, $start_time, $end_time, $id]);
        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate class schedule! Another class already exists for this teacher on the same day and time.');
            redirect_to(BASE_URL . '/admin/classes.php');
        }

        $stmt = $pdo->prepare('
            UPDATE class_schedules
            SET course_id = ?, subject_id = ?, teacher_id = ?, day_name = ?, start_time = ?, end_time = ?, classroom = ?, year_label = ?, semester = ?
            WHERE id = ?
        ');
        $stmt->execute([$course_id, $subject_id, $teacher_id, $day_name, $start_time, $end_time, $classroom, $year_label, $semester, $id]);
        flash('success', 'Class updated.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM class_schedules WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Class deleted.');
    }

    redirect_to(BASE_URL . '/admin/classes.php');
}

// Fetch courses
$courses = $pdo->query('
    SELECT id, course_name, year_label, semester 
    FROM courses 
    ORDER BY course_name, year_label, semester
')->fetchAll();

// Fetch all subjects (will be filtered by JavaScript)
$allSubjects = $pdo->query('
    SELECT id, subject_name, course_id 
    FROM subjects 
    ORDER BY subject_name
')->fetchAll();

// Fetch all teachers (will be filtered by JavaScript)
$allTeachers = $pdo->query('
    SELECT id, name, department FROM users 
    WHERE role = "teacher" AND status = "active" 
    ORDER BY name
')->fetchAll();

// Get teacher-course assignments
$teacherCourses = $pdo->query('
    SELECT teacher_id, course_id FROM teacher_courses
')->fetchAll();

// Create lookup arrays
$subjectsByCourse = [];
foreach ($allSubjects as $subject) {
    $subjectsByCourse[$subject['course_id']][] = $subject;
}

$teacherCourseMap = [];
foreach ($teacherCourses as $tc) {
    $teacherCourseMap[$tc['teacher_id']][] = $tc['course_id'];
}

$edit = null;
$currentCourseId = 0;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM class_schedules WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
    if ($edit) {
        $currentCourseId = $edit['course_id'];
    }
}

$classes = $pdo->query('
    SELECT
        cs.*,
        c.course_name,
        s.subject_name,
        u.name AS teacher_name
    FROM class_schedules cs
    JOIN courses c ON c.id = cs.course_id
    JOIN subjects s ON s.id = cs.subject_id
    JOIN users u ON u.id = cs.teacher_id
    ORDER BY FIELD(day_name, "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"), start_time
')->fetchAll();

$pageTitle = 'Classes | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Class Management</h1>
                <p>Manage class schedule. Subjects and teachers are filtered by selected course. Year/Semester auto-fills from course. Duplicate classes (same teacher, same day, same time) are not allowed.</p>
            </div>
        </div>

        <div class="section-stack">
            <div class="form-card">
                <?php display_flash(); ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
                    <?= $edit ? '<input type="hidden" name="id" value="' . (int)$edit['id'] . '">' : '' ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Course <span class="required">*</span></label>
                            <select name="course_id" id="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"
                                        data-year="<?= e($c['year_label']) ?>"
                                        data-semester="<?= e($c['semester']) ?>"
                                        <?= ((int)($edit['course_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                                        <?= e($c['course_name']) ?> - <?= e($c['year_label']) ?> - <?= e($c['semester']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>Select course - Year, Semester, Subjects, and Teachers will auto-update</small>
                        </div>

                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <select name="subject_id" id="subject_id" required>
                                <option value="">Select Course First</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Teacher <span class="required">*</span></label>
                            <select name="teacher_id" id="teacher_id" required>
                                <option value="">Select Course First</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Day <span class="required">*</span></label>
                            <select name="day_name" required>
                                <?php foreach (day_options() as $day): ?>
                                    <option value="<?= e($day) ?>" <?= (($edit['day_name'] ?? '') === $day) ? 'selected' : '' ?>>
                                        <?= e($day) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Start Time <span class="required">*</span></label>
                            <input type="time" name="start_time" required value="<?= e($edit['start_time'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>End Time <span class="required">*</span></label>
                            <input type="time" name="end_time" required value="<?= e($edit['end_time'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Classroom <span class="required">*</span></label>
                            <input type="text" name="classroom" required value="<?= e($edit['classroom'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Year <span class="required">*</span></label>
                            <input type="text" name="year_label" id="year_label" required value="<?= e($edit['year_label'] ?? '') ?>" readonly style="background:#f5f5f5;">
                        </div>

                        <div class="form-group">
                            <label>Semester <span class="required">*</span></label>
                            <input type="text" name="semester" id="semester" required value="<?= e($edit['semester'] ?? '') ?>" readonly style="background:#f5f5f5;">
                        </div>
                    </div>

                    <button class="btn btn-primary"><?= $edit ? 'Update Class' : 'Add Class' ?></button>
                    <?php if ($edit): ?>
                        <a class="btn btn-secondary" href="classes.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search class schedule" data-table-search="classTable">
                </div>

                <div class="table-wrap">
                    <table id="classTable">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Course</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Room</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $row): ?>
                                <tr>
                                    <td><?= e($row['day_name']) ?></td>
                                    <td><?= e(substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5)) ?></td>
                                    <td><?= e($row['course_name']) ?> - <?= e($row['year_label']) ?> - <?= e($row['semester']) ?></td>
                                    <td><?= e($row['subject_name']) ?></td>
                                    <td><?= e($row['teacher_name']) ?></td>
                                    <td><?= e($row['classroom']) ?></td>
                                    <td><?= e($row['year_label']) ?></td>
                                    <td><?= e($row['semester']) ?></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" title="Edit" href="?edit=<?= (int)$row['id'] ?>">✎</a>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                                <button class="icon-btn danger" title="Delete" type="submit">🗑</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Data for filtering
    const allSubjects = <?= json_encode($allSubjects) ?>;
    const allTeachers = <?= json_encode($allTeachers) ?>;
    const teacherCourseMap = <?= json_encode($teacherCourseMap) ?>;

    // Group subjects by course for quick lookup
    const subjectsByCourse = {};
    allSubjects.forEach(subject => {
        if (!subjectsByCourse[subject.course_id]) {
            subjectsByCourse[subject.course_id] = [];
        }
        subjectsByCourse[subject.course_id].push(subject);
    });

    // Function to update subject dropdown
    function updateSubjectDropdown(courseId) {
        const subjectSelect = document.getElementById('subject_id');

        if (!courseId) {
            subjectSelect.innerHTML = '<option value="">Select Course First</option>';
            return;
        }

        const subjects = subjectsByCourse[courseId] || [];

        if (subjects.length === 0) {
            subjectSelect.innerHTML = '<option value="">No subjects available for this course</option>';
            return;
        }

        let options = '<option value="">Select Subject</option>';
        subjects.forEach(subject => {
            options += `<option value="${subject.id}">${subject.subject_name}</option>`;
        });
        subjectSelect.innerHTML = options;
    }

    // Function to update teacher dropdown based on selected course
    function updateTeacherDropdownForClass(courseId) {
        const teacherSelect = document.getElementById('teacher_id');

        if (!courseId) {
            teacherSelect.innerHTML = '<option value="">Select Course First</option>';
            return;
        }

        const filteredTeachers = allTeachers.filter(teacher => {
            return teacherCourseMap[teacher.id] && teacherCourseMap[teacher.id].includes(parseInt(courseId));
        });

        if (filteredTeachers.length === 0) {
            teacherSelect.innerHTML = '<option value="">No teachers assigned to this course</option>';
            return;
        }

        let options = '<option value="">Select Teacher</option>';
        filteredTeachers.forEach(teacher => {
            options += `<option value="${teacher.id}">${teacher.name} (${teacher.department})</option>`;
        });
        teacherSelect.innerHTML = options;
    }

    // Auto-fill year, semester, update subjects and teachers when course is selected
    document.getElementById('course_id').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        var year = selectedOption.getAttribute('data-year');
        var semester = selectedOption.getAttribute('data-semester');
        var courseId = this.value;

        if (year) {
            document.getElementById('year_label').value = year;
        }
        if (semester) {
            document.getElementById('semester').value = semester;
        }

        // Update subject and teacher dropdowns
        updateSubjectDropdown(courseId);
        updateTeacherDropdownForClass(courseId);
    });

    // Trigger on page load if editing
    document.addEventListener('DOMContentLoaded', function() {
        var courseSelect = document.getElementById('course_id');
        var currentCourseId = '<?= $currentCourseId ?>';

        if (courseSelect && currentCourseId) {
            courseSelect.value = currentCourseId;
            var event = new Event('change');
            courseSelect.dispatchEvent(event);

            // Set the current subject and teacher if editing
            var subjectId = '<?= $edit['subject_id'] ?? '' ?>';
            var teacherId = '<?= $edit['teacher_id'] ?? '' ?>';

            if (subjectId) {
                document.getElementById('subject_id').value = subjectId;
            }
            if (teacherId) {
                document.getElementById('teacher_id').value = teacherId;
            }
        }
    });
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>