<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

$auth = new Auth($pdo);
requireAdmin(); // Requires both login and admin status

// Fetch system statistics using advanced JOIN queries
$stats = [];

// Total users
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$stats['total_users'] = $stmt->fetch()['count'];

// Total tasks
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks");
$stmt->execute();
$stats['total_tasks'] = $stmt->fetch()['count'];

// Total task history entries
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM task_history");
$stmt->execute();
$stats['total_history'] = $stmt->fetch()['count'];

// Fetch all users with their task counts
$stmt = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.is_admin,
        u.created_at,
        COUNT(t.id) as task_count
    FROM users u
    LEFT JOIN tasks t ON u.id = t.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

// Fetch all tasks with user and category info
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.title,
        t.description,
        t.created_at,
        u.username,
        c.name as category_name,
        COUNT(DISTINCT th.id) as history_count
    FROM tasks t
    JOIN users u ON t.user_id = u.id
    JOIN categories c ON t.category_id = c.id
    LEFT JOIN task_history th ON t.id = th.task_id
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT 50
");
$stmt->execute();
$tasks = $stmt->fetchAll();

// Fetch system-wide task history
$stmt = $pdo->prepare("
    SELECT 
        th.id,
        th.action,
        th.created_at,
        u.username,
        t.title as task_title
    FROM task_history th
    JOIN users u ON th.user_id = u.id
    LEFT JOIN tasks t ON th.task_id = t.id
    ORDER BY th.created_at DESC
    LIMIT 100
");
$stmt->execute();
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Task Manager</title>
    <link rel="stylesheet" href="../styles/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="tasks.php" class="nav-link">Tasks</a>
                <a href="history.php" class="nav-link">History</a>
            </nav>
            <div class="sidebar-footer">
                <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn btn-secondary btn-small">Back to Dashboard</a>
                <form method="POST" action="<?php echo APP_URL; ?>/auth/logout.php" style="display: inline-block; width: 100%;">
                    <button type="submit" class="btn btn-danger btn-small" style="width: 100%; margin-top: 0.5rem;">Logout</button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </header>

            <!-- Statistics Tab -->
            <section id="stats" class="tab-content active">
                <div class="section-title">System Statistics</div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_tasks']; ?></div>
                        <div class="stat-label">Total Tasks</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_history']; ?></div>
                        <div class="stat-label">History Entries</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_users'] > 0 ? round($stats['total_tasks'] / $stats['total_users'], 1) : 0; ?></div>
                        <div class="stat-label">Avg Tasks per User</div>
                    </div>
                </div>
            </section>

            <!-- Users Tab -->
            <section id="users" class="tab-content">
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Tasks Tab -->
            <section id="tasks" class="tab-content">
                <div class="section-title">Recent Tasks</div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>User</th>
                                <th>Category</th>
                                <th>History</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <div class="task-name">
                                            <strong><?php echo htmlspecialchars(substr($task['title'], 0, 30)); ?></strong>
                                            <?php if ($task['description']): ?>
                                                <br><small><?php echo htmlspecialchars(substr($task['description'], 0, 50)); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['username']); ?></td>
                                    <td><?php echo htmlspecialchars($task['category_name']); ?></td>
                                    <td><?php echo $task['history_count']; ?></td>
                                    <td><?php echo (new DateTime($task['created_at']))->format('M d, Y'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- History Tab -->
            <section id="history" class="tab-content">
                <div class="section-title">System-Wide Task History</div>
                <div class="history-list">
                    <?php foreach ($history as $entry): ?>
                        <div class="history-item">
                            <span class="history-user"><?php echo htmlspecialchars($entry['username']); ?></span>
                            <span class="history-action"><?php echo strtoupper($entry['action']); ?></span>
                            <span class="history-task"><?php echo htmlspecialchars($entry['task_title'] ?? 'Unknown'); ?></span>
                            <span class="history-time"><?php echo (new DateTime($entry['created_at']))->format('M d, H:i'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="/scripts/admin.js"></script>
</body>
</html>
