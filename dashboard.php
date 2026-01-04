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

// Fetch categories
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY id");
$stmt->execute();
$categories = $stmt->fetchAll();

// Fetch tasks for each category
$tasks = [];
foreach ($categories as $category) {
    $stmt = $pdo->prepare("
        SELECT id, title, description, position, created_at 
        FROM tasks 
        WHERE user_id = ? AND category_id = ? 
        ORDER BY position ASC
    ");
    $stmt->execute([$userId, $category['id']]);
    $tasks[$category['id']] = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager</title>
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-inner">
                <div class="sidebar-brand">Task Manager</div>
                <nav class="sidebar-nav">
                    <a href="<?php echo APP_URL; ?>/task-history.php" class="sidebar-link">History</a>
                    <form method="POST" action="<?php echo APP_URL; ?>/auth/logout.php" class="sidebar-logout">
                        <button type="submit" class="btn btn-danger btn-small">Logout</button>
                    </form>
                </nav>
            </div>
        </aside>
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button id="sidebarToggle" class="btn btn-icon" aria-label="Toggle sidebar">☰</button>
                <h1 class="app-title">Task Manager</h1>
            </div>
            <div class="header-right">
                <span class="user-greeting">Welcome, <?php echo htmlspecialchars($username); ?></span>
                <?php if ($isAdmin): ?>
                    <a href="<?php echo APP_URL; ?>/admin/dashboard.php" class="btn btn-primary btn-small" title="Admin Panel">Admin</a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main Dashboard -->
        <main class="dashboard-main">
            <div class="dashboard-grid">
                <!-- Category Columns -->
                <?php foreach ($categories as $category): ?>
                    <div class="category-column" data-category-id="<?php echo $category['id']; ?>" style="border-top: 4px solid var(--color-<?php echo $category['color']; ?>)">
                        <div class="category-header">
                            <h2 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h2>
                            <button class="btn btn-icon btn-add-task" onclick="openAddTaskModal(<?php echo $category['id']; ?>)" title="Add task">
                                <span>+</span>
                            </button>
                        </div>

                        <div class="tasks-container" id="tasks-<?php echo $category['id']; ?>">
                            <?php if (!empty($tasks[$category['id']])): ?>
                                <?php foreach ($tasks[$category['id']] as $task): ?>
                                    <div class="task-card" data-task-id="<?php echo $task['id']; ?>">
                                        <div class="task-header">
                                            <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                                            <div class="task-menu">
                                                <button class="btn btn-icon btn-menu" onclick="toggleTaskMenu(this)">⋯</button>
                                                <div class="task-actions-menu" style="display: none;">
                                                    <button class="action-btn" onclick="openEditTaskModal(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($task['description'], ENT_QUOTES); ?>', <?php echo $category['id']; ?>)">Update</button>
                                                    <button class="action-btn action-danger" onclick="openDeleteConfirm(<?php echo $task['id']; ?>)">Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($task['description']): ?>
                                            <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                                        <?php endif; ?>
                                        <div class="task-actions">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">No tasks yet</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="addTaskModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addTaskModal')">&times;</span>
            <h2>Add New Task</h2>
            <form id="addTaskForm">
                <div class="form-group">
                    <label for="categorySelect">Category</label>
                    <select id="categorySelect" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="taskTitle">Task Title</label>
                    <input type="text" id="taskTitle" name="title" required placeholder="Enter task title">
                </div>
                <div class="form-group">
                    <label for="taskDescription">Description (optional)</label>
                    <textarea id="taskDescription" name="description" placeholder="Enter task description"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addTaskModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Task</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editTaskModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editTaskModal')">&times;</span>
            <h2>Edit Task</h2>
            <form id="editTaskForm">
                <input type="hidden" id="editTaskId" value="">
                <div class="form-group">
                    <label for="editCategorySelect">Category</label>
                    <select id="editCategorySelect" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editTaskTitle">Task Title</label>
                    <input type="text" id="editTaskTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="editTaskDescription">Description</label>
                    <textarea id="editTaskDescription" name="description"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editTaskModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content modal-small">
            <h2>Delete Task?</h2>
            <p>This action cannot be undone.</p>
            <input type="hidden" id="deleteTaskId" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
    <script src="scripts/dashboard.js"></script>
</body>
</html>
