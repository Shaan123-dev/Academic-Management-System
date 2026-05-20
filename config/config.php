<?php

declare(strict_types=1);

// ============================================================
// SECURITY REQUIREMENT 3: Proper Session Security
// ============================================================
function set_secure_session_settings()
{
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

set_secure_session_settings();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// APP CONFIGURATION
// ============================================================
define('SESSION_TIMEOUT_MINUTES', 30);
define('APP_NAME', 'Academic Management Portal System');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$publicPosition = strpos($scriptName, '/public');

if ($publicPosition !== false) {
    $basePath = substr($scriptName, 0, $publicPosition + strlen('/public'));
} else {
    $basePath = '';
}

define('BASE_URL', $protocol . '://' . $host . $basePath);
define('ROOT_PATH', dirname(__DIR__));

// ============================================================
// FILE UPLOAD CONFIGURATION
// ============================================================
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
    'image/webp',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation'
]);

// ============================================================
// GMAIL SMTP CONFIGURATION
// ============================================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'shaanstha2060@gmail.com');
define('SMTP_PASS', 'YOUR_GMAIL_APP_PASSWORD_HERE');
define('SMTP_FROM_EMAIL', 'shaanstha2060@gmail.com');
define('SMTP_FROM_NAME', APP_NAME);

// ============================================================
// DATABASE CONNECTION
// ============================================================
date_default_timezone_set('Asia/Kathmandu');

$dbHost = '127.0.0.1';
$dbName = 'ams_portal';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database Connection Failed.");
}