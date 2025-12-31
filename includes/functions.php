<?php
// Ensure constants (including APP_URL) are available
if (!defined('APP_URL')) {
    require_once dirname(__DIR__) . '/config/constants.php';
}

// Helper functions

// Check if user is logged in, redirect to login if not
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit();
    }
}

// Check if user is admin, redirect if not
function requireAdmin() {
    requireLogin();
    
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit();
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// JSON response helper
function jsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Error handler
function handleError($message, $statusCode = 500) {
    jsonResponse(false, $message, null, $statusCode);
}

// Success response
function handleSuccess($message, $data = null, $statusCode = 200) {
    jsonResponse(true, $message, $data, $statusCode);
}
?>
