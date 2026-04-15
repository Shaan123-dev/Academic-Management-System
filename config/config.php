<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) { session_start(); }

define('APP_NAME', 'Academic Management Portal System');
define('BASE_URL', '/Academic-Management-System/public');
define('ROOT_PATH', dirname(__DIR__));
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
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
