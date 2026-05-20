<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_role(['admin']);

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    $course_id = (int)$_POST['course_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $year_label = trim($_POST['year_label']);
    $semester = trim($_POST['semester']);

    if ($action === 'create') {
        // Check for duplicate subject
        $checkStmt = $pdo->prepare('
            SELECT id FROM subjects 
            WHERE course_id = ? AND subject_name = ? AND year_label = ? AND semester = ?
        ');
        $checkStmt->execute([$course_id, $subject_name, $year_label, $semester]);

        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate subject! A subject with same name, year, and semester already exists.');
            redirect_to(BASE_URL . '/admin/subjects.php');
        }

        $stmt = $pdo->prepare('INSERT INTO subjects (course_id, teacher_id, subject_code, subject_name, year_label, semester) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$course_id, $teacher_id, $subject_code, $subject_name, $year_label, $semester]);
        flash('success', 'Subject added successfully.');
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];

        $checkStmt = $pdo->prepare('
            SELECT id FROM subjects 
            WHERE course_id = ? AND subject_name = ? AND year_label = ? AND semester = ? AND id != ?
        ');
        $checkStmt->execute([$course_id, $subject_name, $year_label, $semester, $id]);

        if ($checkStmt->fetch()) {
            flash('danger', 'Duplicate subject! Another subject with same name, year, and semester already exists.');
            redirect_to(BASE_URL . '/admin/subjects.php');
        }

        $stmt = $pdo->prepare('UPDATE subjects SET course_id=?, teacher_id=?, subject_code=?, subject_name=?, year_label=?, semester=? WHERE id=?');
        $stmt->execute([$course_id, $teacher_id, $subject_code, $subject_name, $year_label, $semester, $id]);
        flash('success', 'Subject updated successfully.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM subjects WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Subject deleted successfully.');
    }

    redirect_to(BASE_URL . '/admin/subjects.php');
}

// Fetch courses
$courses = $pdo->query('
    SELECT id, course_name, year_label, semester 
    FROM courses 
    ORDER BY course_name, year_label, semester
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

// Create lookup array for quick filtering
$teacherCourseMap = [];
foreach ($teacherCourses as $tc) {
    $teacherCourseMap[$tc['teacher_id']][] = $tc['course_id'];
}

$edit = null;
$currentCourseId = 0;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM subjects WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
    if ($edit) {
        $currentCourseId = $edit['course_id'];
    }
}

$subjects = $pdo->query('
    SELECT 
        s.*, 
        c.course_name, 
        u.name AS teacher_name
    FROM subjects s
    JOIN courses c ON c.id = s.course_id
    JOIN users u ON u.id = s.teacher_id
    ORDER BY c.course_name, s.year_label, s.semester, s.subject_name
')->fetchAll();

$pageTitle = 'Subjects | ' . APP_NAME;
include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<div class="dashboard-shell">
    <?php include dirname(dirname(__DIR__)) . '/includes/sidebar.php'; ?>

    <main class="main-panel">
        <div class="dashboard-top">
            <div class="dashboard-title">
                <h1>Subject Management</h1>
                <p>Add subjects with course and teacher assignment. Teachers are filtered by selected course.</p>
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
                            <small>Select course - Year & Semester will auto-fill</small>
                        </div>

                        <div class="form-group">
                            <label>Teacher <span class="required">*</span></label>
                            <select name="teacher_id" id="teacher_id" required>
                                <option value="">Select Course First</option>
                            </select>
                            <small>Only teachers assigned to the selected course will appear</small>
                        </div>

                        <div class="form-group">
                            <label>Subject Code <span class="required">*</span></label>
                            <input type="text" name="subject_code" required value="<?= e($edit['subject_code'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Subject Name <span class="required">*</span></label>
                            <input type="text" name="subject_name" required value="<?= e($edit['subject_name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Year <span class="required">*</span></label>
                            <input type="text" name="year_label" id="year_label" required value="<?= e($edit['year_label'] ?? '') ?>" readonly style="background:#f5f5f5;">
                            <small>Auto-filled from selected course</small>
                        </div>

                        <div class="form-group">
                            <label>Semester <span class="required">*</span></label>
                            <input type="text" name="semester" id="semester" required value="<?= e($edit['semester'] ?? '') ?>" readonly style="background:#f5f5f5;">
                            <small>Auto-filled from selected course</small>
                        </div>
                    </div>

                    <button class="btn btn-primary"><?= $edit ? 'Update Subject' : 'Add Subject' ?></button>
                    <?php if ($edit): ?>
                        <a class="btn btn-secondary" href="subjects.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <div class="search-row">
                    <input type="text" placeholder="Search subjects" data-table-search="subjectTable">
                </div>

                <div class="table-wrap">
                    <table id="subjectTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Subject</th>
                                <th>Course</th>
                                <th>Teacher</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $s): ?>
                                <tr>
                                    <td><?= e($s['subject_code']) ?></td>
                                    <td><?= e($s['subject_name']) ?></td>
                                    <td><?= e($s['course_name']) ?> - <?= e($s['year_label']) ?> - <?= e($s['semester']) ?></td>
                                    <td><?= e($s['teacher_name']) ?></td>
                                    <td><?= e($s['year_label']) ?></td>
                                    <td><?= e($s['semester']) ?></td>
                                    <td>
                                        <div class="inline-actions">
                                            <a class="icon-btn" title="Edit" href="?edit=<?= (int)$s['id'] ?>">✎</a>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                                <button class="icon-btn danger" title="Delete">🗑</button>
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
    // Teacher data with course assignments
    const allTeachers = <?= json_encode($allTeachers) ?>;
    const teacherCourseMap = <?= json_encode($teacherCourseMap) ?>;

    // Function to update teacher dropdown based on selected course
    function updateTeacherDropdown(courseId) {
        const teacherSelect = document.getElementById('teacher_id');

        if (!courseId) {
            teacherSelect.innerHTML = '<option value="">Select Course First</option>';
            return;
        }

        // Filter teachers who are assigned to this course
        const filteredTeachers = allTeachers.filter(teacher => {
            return teacherCourseMap[teacher.id] && teacherCourseMap[teacher.id].includes(parseInt(courseId));
        });

        if (filteredTeachers.length === 0) {
            teacherSelect.innerHTML = '<option value="">No teachers assigned to this course</option>';
            return;
        }

        // Build dropdown options
        let options = '<option value="">Select Teacher</option>';
        filteredTeachers.forEach(teacher => {
            options += `<option value="${teacher.id}">${teacher.name} (${teacher.department})</option>`;
        });
        teacherSelect.innerHTML = options;
    }

    // Auto-fill year and semester when course is selected
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

        // Update teacher dropdown based on selected course
        updateTeacherDropdown(courseId);
    });

    // Trigger on page load if editing
    document.addEventListener('DOMContentLoaded', function() {
        var courseSelect = document.getElementById('course_id');
        var currentCourseId = '<?= $currentCourseId ?>';

        if (courseSelect && currentCourseId) {
            courseSelect.value = currentCourseId;
            var event = new Event('change');
            courseSelect.dispatchEvent(event);

            // Also set the current teacher if editing
            var teacherId = '<?= $edit['teacher_id'] ?? '' ?>';
            if (teacherId) {
                document.getElementById('teacher_id').value = teacherId;
            }
        }
    });
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>