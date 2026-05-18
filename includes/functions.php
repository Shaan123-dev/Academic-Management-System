<?php
declare(strict_types=1);

// Load Composer autoloader for PHPMailer (if not already loaded)
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ============================================================
// SESSION AND REDIRECT FUNCTIONS
// ============================================================

/**
 * Redirect to a specific URL
 */
function redirect_to(string $path): void { 
    header('Location: ' . $path); 
    exit; 
}

/**
 * Set a flash message in session
 */
function flash(string $type, string $message): void { 
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; 
}

/**
 * Display all flash messages and clear them
 */
function display_flash(): void {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $item) {
        echo '<div class="alert alert-' . e($item['type']) . '">' . e($item['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/**
 * Check if request is POST
 */
function is_post(): bool { 
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; 
}

/**
 * Generate CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

// ============================================================
// SESSION TIMEOUT CHECK
// ============================================================

/**
 * Check if session has expired due to inactivity
 */
function check_session_timeout() {
    if (!isset($_SESSION['user'])) {
        return;
    }
    
    $last_activity = $_SESSION['last_activity'] ?? 0;
    $timeout = SESSION_TIMEOUT_MINUTES * 60;
    
    if (time() - $last_activity > $timeout) {
        // Session expired - destroy it
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        session_start(); // Start new session for flash message
        flash('danger', 'Session expired. Please login again.');
        redirect_to(BASE_URL . '/login.php');
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function e(null|string|int|float $value): string { 
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); 
}

// ============================================================
// VALIDATION FUNCTIONS
// ============================================================

/**
 * Validate email format (any valid email) - for password reset
 */
function validate_email_format(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Gmail address only - for registration
 */
function validate_gmail_email(string $email): bool {
    return preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $email) === 1;
}

/**
 * Validate registration email with specific error message
 */
function validate_registration_email(string $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }
    
    if (!preg_match('/@gmail\.com$/i', $email)) {
        return 'Only Gmail addresses are allowed for registration. Please use a @gmail.com email.';
    }
    
    return true;
}

function validate_required(array $fields, array $input): array {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (!isset($input[$field]) || trim((string)$input[$field]) === '') {
            $errors[] = $label . ' is required.';
        }
    }
    return $errors;
}

function validate_password_strength(string $password): array {
    $errors = [];
    
    // Check minimum length
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    
    // Check for uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least 1 uppercase letter.';
    }
    
    // Check for lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least 1 lowercase letter.';
    }
    
    // Check for number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least 1 number.';
    }
    
    // Check for special character
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least 1 special character (!@#$%^&*).';
    }
    
    return $errors;
}

/**
 * Validate strong password and return true/false
 */
function is_strong_password(string $password): bool {
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password) 
        && preg_match('/[a-z]/', $password) 
        && preg_match('/[0-9]/', $password) 
        && preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);
}

// ============================================================
// SECURE FILE UPLOAD FUNCTION
// ============================================================

/**
 * Securely upload files with validation
 */
function secure_upload_file(string $field, array $allowedExtensions, int $maxBytes, string $targetDir): ?string {
    if (empty($_FILES[$field]['name'])) {
        return null;
    }
    
    $file = $_FILES[$field];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server max size.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form max size.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
        ];
        throw new RuntimeException($errors[$file['error']] ?? 'Unknown upload error.');
    }
    
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException(sprintf('File is too large. Maximum size is %sMB.', $maxBytes / 1024 / 1024));
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimeTypes = [
        'application/pdf', 'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg', 'image/png', 'image/webp'
    ];
    
    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        throw new RuntimeException('Invalid file type. Only PDF, DOC, DOCX, JPG, PNG, WEBP are allowed.');
    }
    
    $originalName = $file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    $blockedExtensions = [
        'php', 'phtml', 'phar', 'html', 'htm', 'js', 'exe', 'sh', 'bat', 'cmd', 'vbs', 'svg', 'xml', 'htaccess', 'ini'
    ];
    
    if (in_array($extension, $blockedExtensions, true)) {
        throw new RuntimeException('This file type is not allowed for security reasons.');
    }
    
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('File extension not allowed.');
    }
    
    if (strpos($mimeType, 'image/') === 0) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new RuntimeException('Uploaded file is not a valid image.');
        }
        
        if ($extension === 'webp' && $imageInfo[2] !== IMAGETYPE_WEBP) {
            throw new RuntimeException('Invalid WebP image file.');
        }
    }
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $htaccessPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccessContent = "# Disable PHP execution in this directory\n";
        $htaccessContent .= "<FilesMatch \"\\.(php|phtml|phar|html|htm|js|exe|sh|bat|cgi|pl)$\">\n";
        $htaccessContent .= "    Require all denied\n";
        $htaccessContent .= "</FilesMatch>\n\n";
        $htaccessContent .= "# Serve files as downloads instead of executing\n";
        $htaccessContent .= "ForceType application/octet-stream\n";
        $htaccessContent .= "<FilesMatch \"\\.(pdf|doc|docx|jpg|jpeg|png|webp)$\">\n";
        $htaccessContent .= "    ForceType application/octet-stream\n";
        $htaccessContent .= "</FilesMatch>\n";
        file_put_contents($htaccessPath, $htaccessContent);
    }
    
    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
    $fullPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
    
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }
    
    chmod($fullPath, 0644);
    return $safeName;
}

/**
 * Delete old file securely
 */
function delete_secure_file(string $filePath, string $uploadDir): bool {
    if (empty($filePath)) {
        return true;
    }
    
    $fullPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filePath;
    $realPath = realpath($fullPath);
    $realUploadDir = realpath($uploadDir);
    
    if ($realPath === false || strpos($realPath, $realUploadDir) !== 0) {
        return false;
    }
    
    if (file_exists($realPath) && is_file($realPath)) {
        return unlink($realPath);
    }
    
    return true;
}

function upload_file(string $field, array $allowedExtensions, int $maxBytes, string $targetDir): ?string {
    return secure_upload_file($field, $allowedExtensions, $maxBytes, $targetDir);
}

// ============================================================
// OTP FUNCTIONS
// ============================================================

function generate_secure_otp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function hash_otp(string $otp): string {
    return password_hash($otp, PASSWORD_DEFAULT);
}

function verify_otp(string $otp, string $hashedOtp): bool {
    return password_verify($otp, $hashedOtp);
}

/**
 * Send OTP email using Gmail SMTP
 */
function send_otp_email(string $to, string $otp): bool {
    $logFile = ROOT_PATH . '/logs/otp_log.txt';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $logEntry = date('Y-m-d H:i:s') . " - To: $to - OTP: $otp\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    $_SESSION['debug_otp'] = $otp;
    
    require_once ROOT_PATH . '/vendor/autoload.php';
    
    try {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 30;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        $mail->isHTML(false);
        $mail->Subject = APP_NAME . ' - Password Reset OTP';
        $mail->Body    = "Hello,\n\n"
                       . "You requested to reset your password.\n\n"
                       . "Your OTP for password reset is: " . $otp . "\n\n"
                       . "This OTP is valid for 10 minutes and can only be used once.\n\n"
                       . "If you did not request this, please ignore this email.\n\n"
                       . "Regards,\n" . APP_NAME;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $e->getMessage());
        return false;
    }
}

function check_otp_rate_limit(PDO $pdo, string $email): bool {
    $stmt = $pdo->prepare('SELECT last_otp_request, otp_attempts FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) return true;
    
    if ($user['last_otp_request']) {
        $lastRequest = strtotime($user['last_otp_request']);
        $now = time();
        $minutesSince = ($now - $lastRequest) / 60;
        if ($minutesSince < 2) return false;
    }
    
    if ($user['otp_attempts'] >= 5) {
        $lastAttempt = strtotime($user['last_otp_request'] ?? '2000-01-01');
        if (time() - $lastAttempt < 3600) return false;
    }
    
    return true;
}

// ============================================================
// STATS AND OTHER HELPER FUNCTIONS
// ============================================================

function stats(PDO $pdo): array {
    $tables = [
        'students' => 'SELECT COUNT(*) FROM users WHERE role = "student"',
        'teachers' => 'SELECT COUNT(*) FROM users WHERE role = "teacher"',
        'courses' => 'SELECT COUNT(*) FROM courses',
        'subjects' => 'SELECT COUNT(*) FROM subjects',
        'assignments' => 'SELECT COUNT(*) FROM assignments',
        'announcements' => 'SELECT COUNT(*) FROM announcements',
    ];
    $data = [];
    foreach ($tables as $key => $sql) {
        $data[$key] = (int)$pdo->query($sql)->fetchColumn();
    }
    return $data;
}

// OLD GRADING SYSTEM (Keep if needed for backward compatibility)
function grade_from_total(float $total): string {
    return match (true) {
        $total >= 70 => 'A',
        $total >= 60 => 'B',
        $total >= 50 => 'C',
        $total >= 40 => 'D',
        default => 'F',
    };
}

function gpa_from_total(float $total): float {
    return match (true) {
        $total >= 70 => 4.0,
        $total >= 60 => 3.0,
        $total >= 50 => 2.0,
        $total >= 40 => 1.0,
        default => 0.0,
    };
}

// NEW UK GRADING SYSTEM (Use these in teacher/results.php)
function grade_from_total_uk(float $total): string {
    return match (true) {
        $total >= 70 => 'A',
        $total >= 60 => 'B',
        $total >= 50 => 'C',
        $total >= 40 => 'D',
        default => 'F',
    };
}

function gpa_from_total_uk(float $total): float {
    return match (true) {
        $total >= 70 => 4.0,
        $total >= 60 => 3.0,
        $total >= 50 => 2.0,
        $total >= 40 => 1.0,
        default => 0.0,
    };
}

function photo_path(?string $photo): string {
    return $photo ? BASE_URL . '/../uploads/photos/' . rawurlencode($photo) : BASE_URL . '/../assets/images/logo.png';
}

function qr_url(string $text): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . rawurlencode($text);
}

function day_options(): array { 
    return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; 
}

function attendance_percent(int $present, int $total): float { 
    return $total > 0 ? round(($present / $total) * 100, 2) : 0.0; 
}

function digital_code(string $prefix, int $id): string { 
    return strtoupper($prefix) . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); 
}

function next_role_code(PDO $pdo, string $role): string {
    $prefix = $role === 'teacher' ? 'TCH' : ($role === 'student' ? 'STD' : 'ADM');
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
    $countStmt->execute([$role]);
    $next = (int)$countStmt->fetchColumn() + 1;
    return $prefix . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}