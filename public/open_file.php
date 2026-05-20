<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Only logged in users can view files
require_login();

$file = $_GET['file'] ?? '';
$type = $_GET['type'] ?? ''; // 'assignment', 'submission', 'material'

$folders = [
    'assignment' => ROOT_PATH . '/uploads/assignments/',
    'submission' => ROOT_PATH . '/uploads/submissions/',
    'material'   => ROOT_PATH . '/uploads/materials/',
];

if (!isset($folders[$type]) || empty($file)) {
    die('Invalid request.');
}

$filePath = $folders[$type] . basename($file);
if (!file_exists($filePath)) {
    die('File not found.');
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if ($ext === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($file) . '"');
} else {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
}
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
