<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

$auth = new Auth($pdo);
requireAdmin();

$error = '';
$success = '';

// Handle admin actions: toggle admin, delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if ($action === 'toggle_admin') {
        if ($targetId === $auth->getUserId()) {
            $error = 'You cannot change your own admin status.';
        } else {
            $stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
            $stmt->execute([$targetId]);
            $user = $stmt->fetch();
            if ($user) {
                $new = $user['is_admin'] ? 0 : 1;
                $stmt = $pdo->prepare('UPDATE users SET is_admin = ? WHERE id = ?');
                $stmt->execute([$new, $targetId]);
                $success = 'User admin status updated.';
            } else {
                $error = 'User not found.';
            }
        }
    }

    if ($action === 'delete_user') {
        if ($targetId === $auth->getUserId()) {
            $error = 'You cannot delete your own account from the admin panel.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$targetId]);
            $success = 'User deleted.';
        }
    }

    // Reload users after action
}

// Fetch users with task counts
$stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.is_admin, u.created_at,
    (SELECT COUNT(*) FROM tasks t WHERE t.user_id = u.id) AS task_count
    FROM users u
    ORDER BY u.created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin - Task Manager</title>
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="users.php" class="nav-link active">Users</a>
                <a href="tasks.php" class="nav-link">Tasks</a>
                <a href="history.php" class="nav-link">History</a>
            </nav>
            <div class="sidebar-footer">
                <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn btn-secondary btn-small">Back to App</a>
                <form method="POST" action="<?php echo APP_URL; ?>/auth/logout.php" style="display:inline-block; width:100%;">
                    <button type="submit" class="btn btn-danger btn-small" style="width:100%; margin-top:0.5rem;">Logout</button>
                </form>
            </div>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Users</h1>
                <p>Manage registered users and their privileges.</p>
            </header>

            <section class="section">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="section-title">Registered Users</div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Tasks</th>
                                <th>Admin</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['task_count']; ?></td>
                                    <td><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo (new DateTime($user['created_at']))->format('M d, Y'); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline-block; margin-right:6px;" onsubmit="return confirm('Change admin status for this user?');">
                                            <input type="hidden" name="action" value="toggle_admin">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-small"><?php echo $user['is_admin'] ? 'Demote' : 'Promote'; ?></button>
                                        </form>

                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this user? This action is irreversible.');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
