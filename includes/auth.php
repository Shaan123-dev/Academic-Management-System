<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// ============================================================
// SECURITY: Check session timeout on every request
// ============================================================
check_session_timeout();

// ============================================================
// SESSION REGENERATION - Prevent session fixation
// ============================================================

/**
 * Regenerate session ID securely
 * Call this after successful login
 */
function regenerate_session()
{
    // Regenerate session ID to prevent fixation attacks
    session_regenerate_id(true);

    // Update last activity time
    $_SESSION['last_activity'] = time();

    // Store IP and User Agent for additional security (optional)
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Validate session integrity (check IP/User Agent mismatch)
 * Optional but recommended for high security
 */
function validate_session_integrity()
{
    if (!isset($_SESSION['user'])) {
        return true;
    }

    $stored_ip = $_SESSION['ip_address'] ?? '';
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stored_ua = $_SESSION['user_agent'] ?? '';
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // If IP changed significantly, destroy session
    if ($stored_ip && $stored_ip !== $current_ip) {
        destroy_session();
        return false;
    }

    return true;
}

/**
 * Destroy session completely
 */
function destroy_session()
{
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

// ============================================================
// AUTHENTICATION FUNCTIONS
// ============================================================

/**
 * Get current logged-in user data
 */
function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is logged in
 */
function logged_in(): bool
{
    return user() !== null && validate_session_integrity();
}

/**
 * Require user to be logged in
 */
function require_login(): void
{
    if (!logged_in()) {
        flash('danger', 'Please login first.');
        redirect_to(BASE_URL . '/login.php');
    }
}

/**
 * Require specific role(s) for access
 * @param array $roles - Array of allowed roles (admin, teacher, student)
 */
function require_role(array $roles): void
{
    require_login();
    $role = user()['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        exit('Access denied. You do not have permission to view this page.');
    }
}

/**
 * Get dashboard path based on user role
 */
function dashboard_path(?string $role): string
{
    if (!$role) {
        return BASE_URL . '/login.php';
    }

    return match ($role) {
        'admin' => BASE_URL . '/admin/dashboard.php',
        'teacher' => BASE_URL . '/teacher/dashboard.php',
        'student' => BASE_URL . '/student/dashboard.php',
        default => BASE_URL . '/login.php',
    };
}

/**
 * Get current formatted datetime
 */
function current_datetime(): string
{
    return date('l, d M Y');
}

/**
 * Format datetime for display
 */
function format_dt(?string $value, string $format = 'd M Y, h:i A'): string
{
    return $value ? date($format, strtotime($value)) : '-';
}
