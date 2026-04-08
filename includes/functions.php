<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function clean_input(?string $value): string
{
    return trim(strip_tags((string)$value));
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }
    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(generate_csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            http_response_code(403);
            die('Invalid CSRF token.');
        }
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function validate_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function strong_password(string $password): bool
{
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password) === 1;
}

function upload_file(array $file, array $allowedExtensions, array $allowedMimeTypes, string $uploadDir, int $maxSize = 5242880): ?array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > $maxSize) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExtensions, true)) {
        return null;
    }

    if (!in_array($mime, $allowedMimeTypes, true)) {
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = rtrim($uploadDir, '/') . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return [
        'original_name' => basename($file['name']),
        'saved_name' => $safeName,
        'path' => $destination,
        'mime' => $mime,
        'size' => $file['size']
    ];
}

function log_audit(PDO $pdo, ?int $userId, string $action, string $moduleName, ?int $recordId = null): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, module_name, record_id, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $action, $moduleName, $recordId, $ip]);
}

function role_name_by_id(PDO $pdo, int $roleId): ?string
{
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    $row = $stmt->fetch();
    return $row['name'] ?? null;
}

function current_year(): string
{
    return date('Y');
}
?>