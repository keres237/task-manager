<?php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/Auth.php';

$auth = new Auth($pdo);

// Redirect to dashboard if already logged in
if ($auth->isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit();
}

// Redirect to login page
header('Location: ' . APP_URL . '/auth/login.php');
exit();
?>
