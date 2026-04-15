<?php
declare(strict_types=1);

function e(null|string|int|float $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function redirect_to(string $path): void { header('Location: ' . $path); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; }
function display_flash(): void {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $item) {
        echo '<div class="alert alert-' . e($item['type']) . '">' . e($item['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function user(): ?array { return $_SESSION['user'] ?? null; }
function logged_in(): bool { return user() !== null; }

function require_login(): void {
    if (!logged_in()) {
        flash('danger', 'Please login first.');
        redirect_to(BASE_URL . '/login.php');
    }
}

function require_role(array $roles): void {
    require_login();
    $role = user()['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function dashboard_path(?string $role): string {
    if (!$role) {
        return BASE_URL . '/login.php';
    }

    return match($role) {
        'admin' => BASE_URL . '/admin/dashboard.php',
        'teacher' => BASE_URL . '/teacher/dashboard.php',
        'student' => BASE_URL . '/student/dashboard.php',
        default => BASE_URL . '/login.php',
    };
}

function current_datetime(): string { return date('l, d M Y'); }
function format_dt(?string $value, string $format = 'd M Y, h:i A'): string { return $value ? date($format, strtotime($value)) : '-'; }

function upload_file(string $field, array $allowedExtensions, int $maxBytes, string $targetDir): ?string {
    if (empty($_FILES[$field]['name'])) return null;

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed.');
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('File is too large.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        throw new RuntimeException('Invalid file type.');
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $safeName = bin2hex(random_bytes(10)) . '.' . $ext;
    $fullPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new RuntimeException('Unable to save uploaded file.');
    }

    return $safeName;
}

function validate_required(array $fields, array $input): array {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
            $errors[] = $label . ' is required.';
        }
    }
    return $errors;
}

function validate_password_strength(string $password): array {
    $errors = [];
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must include at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must include at least one lowercase letter.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must include at least one number.';
    return $errors;
}

function stats(PDO $pdo): array {
    $tables = [
        'students' => 'SELECT COUNT(*) FROM users WHERE role = "student"',
        'teachers' => 'SELECT COUNT(*) FROM users WHERE role = "teacher"',
        'courses' => 'SELECT COUNT(*) FROM courses',
        'subjects' => 'SELECT COUNT(*) FROM subjects',
        'assignments' => 'SELECT COUNT(*) FROM assignments',
        'announcements' => 'SELECT COUNT(*) FROM announcements',
    ];
    $data = [];
    foreach ($tables as $key => $sql) {
        $data[$key] = (int)$pdo->query($sql)->fetchColumn();
    }
    return $data;
}

function grade_from_total(float $total): string {
    return match (true) {
        $total >= 90 => 'A+',
        $total >= 80 => 'A',
        $total >= 70 => 'B+',
        $total >= 60 => 'B',
        $total >= 50 => 'C+',
        $total >= 40 => 'C',
        default => 'F',
    };
}

function gpa_from_total(float $total): float {
    return match (true) {
        $total >= 90 => 4.0,
        $total >= 80 => 3.7,
        $total >= 70 => 3.3,
        $total >= 60 => 3.0,
        $total >= 50 => 2.7,
        $total >= 40 => 2.0,
        default => 0.0,
    };
}

function photo_path(?string $photo): string {
    return $photo ? BASE_URL . '/../uploads/photos/' . rawurlencode($photo) : BASE_URL . '/../assets/images/logo.png';
}

function qr_url(string $text): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . rawurlencode($text);
}

function day_options(): array { return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; }
function attendance_percent(int $present, int $total): float { return $total > 0 ? round(($present / $total) * 100, 2) : 0.0; }
function digital_code(string $prefix, int $id): string { return strtoupper($prefix) . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); }

function next_role_code(PDO $pdo, string $role): string {
    $prefix = $role === 'teacher' ? 'TCH' : ($role === 'student' ? 'STD' : 'ADM');
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
    $countStmt->execute([$role]);
    $next = (int)$countStmt->fetchColumn() + 1;
    return $prefix . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}