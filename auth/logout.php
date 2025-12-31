<?php
require_once '../config/constants.php';
require_once '../includes/Auth.php';

$auth = new Auth($pdo);
$auth->logout();

header('Location: ' . APP_URL . '/auth/login.php');
exit();
?>
