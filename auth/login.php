<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

$auth = new Auth($pdo);

$error = '';
$success = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $requireAdmin = isset($_POST['admin_login']) && $_POST['admin_login'] == '1';
    $result = $auth->login($username, $password, $requireAdmin);
    
    if ($result['success']) {
            // Redirect based on admin flag
            if ($auth->isAdmin()) {
                header('Location: ' . APP_URL . '/admin/dashboard.php');
            } else {
                header('Location: ' . APP_URL . '/admin/dashboard.php');
            }
            exit();
    } else {
        $error = $result['message'];
    }
}

// If already logged in, redirect to appropriate dashboard
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: ' . APP_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . APP_URL . '/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Task Manager</title>
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1>Login</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="auth-link">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>
