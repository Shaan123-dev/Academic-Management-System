<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Check if admin already exists
$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute(['admin@marksmafias.com']);

if ($check->fetch()) {
    echo "Admin already exists.\n";
    echo "Email: admin@marksmafias.com\n";
    echo "Password: Admin@12345\n";
    exit;
}

// Get admin role
$stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
$stmt->execute(['admin']);
$role = $stmt->fetch();

if (!$role) {
    die('Admin role not found. Please import database.sql first.');
}

$hashedPassword = password_hash('Admin@12345', PASSWORD_BCRYPT);

$insert = $pdo->prepare("
    INSERT INTO users (role_id, full_name, email, password_hash, status)
    VALUES (?, ?, ?, ?, 'active')
");
$insert->execute([$role['id'], 'System Admin', 'admin@marksmafias.com', $hashedPassword]);

echo "✅ Admin created successfully!\n";
echo "Email: admin@marksmafias.com\n";
echo "Password: Admin@12345\n";