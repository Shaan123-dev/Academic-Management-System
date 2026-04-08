<?php
// Add this at the top after <?php
define('BASE_URL', '/AMS');  // Change if your folder name is different

$host = 'localhost';
$dbname = 'academic_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}