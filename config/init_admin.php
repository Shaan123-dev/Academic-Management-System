<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$adminName = 'System Admin';
$adminEmail = 'admin@marksmafias.com';
$adminPassword = 'Admin@12345';
$hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
$stmt->execute(['admin']);
$role = $stmt->fetch();

if (!$role) {
    die('Admin role not found. Please import database.sql first.');
}

$roleId = (int)$role['id'];

$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$adminEmail]);

if ($check->fetch()) {
    echo "Admin already exists.";
    exit;
}

$insert = $pdo->prepare("
    INSERT INTO users (role_id, full_name, email, password_hash, status)
    VALUES (?, ?, ?, ?, 'active')
");
$insert->execute([$roleId, $adminName, $adminEmail, $hashedPassword]);

echo "Admin created successfully.<br>";
echo "Email: admin@marksmafias.com<br>";
echo "Password: Admin@12345";
?>