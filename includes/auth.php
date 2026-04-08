<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function login_user(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare("
        SELECT u.id, u.role_id, u.full_name, u.email, u.password_hash, u.status, r.name AS role_name
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    if ($user['status'] !== 'active') {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'role_id' => (int)$user['role_id'],
        'role_name' => $user['role_name'],
        'full_name' => $user['full_name'],
        'email' => $user['email']
    ];

    $update = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    $update->execute([$user['id']]);

    log_audit($pdo, (int)$user['id'], 'login', 'auth', (int)$user['id']);

    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect(BASE_URL . '/public/index.php');
    }
}

function require_role(string $role): void
{
    require_login();

    $user = current_user();
    if (!$user || $user['role_name'] !== $role) {
        http_response_code(403);
        die('Access denied.');
    }
}

function require_any_role(array $roles): void
{
    require_login();

    $user = current_user();
    if (!$user || !in_array($user['role_name'], $roles, true)) {
        http_response_code(403);
        die('Access denied.');
    }
}

function dashboard_url_by_role(string $role): string
{
    return match ($role) {
        'admin' => BASE_URL . '/public/admin/dashboard.php',
        'teacher' => BASE_URL . '/public/teacher/dashboard.php',
        'student' => BASE_URL . '/public/student/dashboard.php',
        default => BASE_URL . '/public/index.php',
    };
}
?>