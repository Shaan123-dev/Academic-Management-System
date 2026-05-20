<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();

$file = $_GET['file'] ?? '';
$type = $_GET['type'] ?? '';

$allowedFolders = [
    'assignment' => 'assignments',
    'submission' => 'submissions',
    'material'   => 'materials',
];

if (!isset($allowedFolders[$type]) || empty($file)) {
    die('Invalid request.');
}

$fileName = basename($file);
$folderName = $allowedFolders[$type];

$possiblePaths = [
    ROOT_PATH . '/uploads/' . $folderName . '/' . $fileName,
    ROOT_PATH . '/public/uploads/' . $folderName . '/' . $fileName,
];

$filePath = null;

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $filePath = $path;
        break;
    }
}

if ($filePath === null) {
    die('File not found.');
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

$mimeTypes = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
];

$contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $contentType);

if ($ext === 'pdf' || in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
    header('Content-Disposition: inline; filename="' . $fileName . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
}

header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;