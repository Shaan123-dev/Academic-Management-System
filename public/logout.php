<?php
require_once dirname(__DIR__) . '/includes/auth.php';

// ============================================================
// SECURITY: Complete session destruction on logout
// ============================================================
destroy_session();

// Start a new session for flash message (optional)
session_start();
flash('success', 'You have been logged out successfully.');
header('Location: ' . BASE_URL . '/login.php');
exit;