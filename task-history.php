<?php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/Auth.php';
require_once 'includes/functions.php';

$auth = new Auth($pdo);
requireLogin();

$userId = $auth->getUserId();
$username = $_SESSION['username'];
$isAdmin = $auth->isAdmin();

// Fetch task history with advanced JOIN query
$stmt = $pdo->prepare("
    SELECT 
        th.id,
        th.action,
        th.old_data,
        th.new_data,
        th.created_at,
        t.title as task_title,
        c.name as category_name
    FROM task_history th
    LEFT JOIN tasks t ON th.task_id = t.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE th.user_id = ?
    ORDER BY th.created_at DESC
    LIMIT 100
");
$stmt->execute([$userId]);
$history = $stmt->fetchAll();

// Filter options
$filterAction = $_GET['action'] ?? '';
if ($filterAction && in_array($filterAction, ['created', 'updated', 'deleted', 'moved'])) {
    $stmt = $pdo->prepare("
        SELECT 
            th.id,
            th.action,
            th.old_data,
            th.new_data,
            th.created_at,
            t.title as task_title,
            c.name as category_name
        FROM task_history th
        LEFT JOIN tasks t ON th.task_id = t.id
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE th.user_id = ? AND th.action = ?
        ORDER BY th.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$userId, $filterAction]);
    $history = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task History - Task Manager</title>
    <link rel="stylesheet" href="styles/history.css">
</head>
<body>
    <div class="history-container">
        <!-- Header -->
        <header class="history-header">
            <div class="header-left">
                <a href="<?php echo APP_URL; ?>/dashboard.php" class="btn btn-secondary btn-small">Back to Dashboard</a>
                <h1>Task History</h1>
            </div>
                <div class="header-right">
                    <?php if ($isAdmin): ?>
                        <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="btn btn-primary btn-small" title="Admin Panel">Admin</a>
                    <?php endif; ?>
                    <form method="GET" class="filter-form" style="display:inline-block; margin-left:0.5rem;">
                        <select name="action" onchange="this.form.submit()" class="filter-select">
                            <option value="">All Actions</option>
                            <option value="created" <?php echo $filterAction === 'created' ? 'selected' : ''; ?>>Created</option>
                            <option value="updated" <?php echo $filterAction === 'updated' ? 'selected' : ''; ?>>Updated</option>
                            <option value="deleted" <?php echo $filterAction === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                        </select>
                    </form>
                </div>
        </header>

        <!-- History List -->
        <main class="history-main">
            <div class="history-list">
                <?php if (empty($history)): ?>
                    <div class="empty-state">No task history yet</div>
                <?php else: ?>
                    <?php foreach ($history as $entry): ?>
                        <div class="history-entry history-<?php echo htmlspecialchars($entry['action']); ?>">
                            <div class="history-action">
                                <span class="action-badge action-<?php echo htmlspecialchars($entry['action']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($entry['action'])); ?>
                                </span>
                            </div>
                            <div class="history-details">
                                <h3 class="history-title">
                                    <?php if ($entry['action'] === 'deleted'): ?>
                                        Task deleted
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($entry['task_title'] ?? 'Unknown task'); ?>
                                    <?php endif; ?>
                                </h3>
                                <p class="history-category">
                                    Category: <?php echo htmlspecialchars($entry['category_name'] ?? 'N/A'); ?>
                                </p>
                                
                                <?php if ($entry['action'] === 'updated'): ?>
                                    <div class="history-changes">
                                        <?php 
                                            $oldData = json_decode($entry['old_data'], true);
                                            $newData = json_decode($entry['new_data'], true);
                                            if ($oldData && $newData):
                                        ?>
                                            <p class="change-item">
                                                <strong>Title:</strong> 
                                                <?php echo htmlspecialchars($oldData['title'] ?? ''); ?> 
                                                → 
                                                <?php echo htmlspecialchars($newData['title'] ?? ''); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($entry['action'] === 'moved'): ?>
                                    <div class="history-changes">
                                        <?php 
                                            $oldData = json_decode($entry['old_data'], true);
                                            $newData = json_decode($entry['new_data'], true);
                                        ?>
                                        <p class="change-item">
                                            <strong>Moved:</strong> Category ID <?php echo $oldData['category_id'] ?? ''; ?> → <?php echo $newData['category_id'] ?? ''; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="history-timestamp">
                                    <?php 
                                        $date = new DateTime($entry['created_at']);
                                        echo $date->format('M d, Y H:i:s');
                                    ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
