<?php
session_start();

require_once __DIR__ . '/../config/db.php';

function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /AMS/public/login.php');
        exit;
    }
}

function require_role($role)
{
    require_login();

    if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== $role) {
        header('Location: /AMS/public/login.php');
        exit;
    }
}

function login_user($email, $password)
{
    global $pdo;

    $sql = "SELECT users.*, roles.name AS role_name
            FROM users
            INNER JOIN roles ON users.role_id = roles.id
            WHERE users.email = :email
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid email or password.'
        ];
    }

    if ($user['status'] !== 'active') {
        return [
            'success' => false,
            'message' => 'Your account is inactive.'
        ];
    }

    if (!password_verify($password, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Invalid email or password.'
        ];
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role_name'] = $user['role_name'];

    return [
        'success' => true,
        'role' => $user['role_name']
    ];
}