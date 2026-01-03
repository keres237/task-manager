<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/Auth.php';

// Only accept POST logout to reduce CSRF risk
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: ' . APP_URL . '/dashboard.php');
	exit();
}

$auth = new Auth($pdo);
$auth->logout();

header('Location: ' . APP_URL . '/auth/login.php');
exit();
?>
