<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

$auth = new Auth($pdo);
requireAdmin();

// Fetch users for filter dropdown 
$usersStmt = $pdo->prepare('SELECT id, username FROM users ORDER BY username ASC');
$usersStmt->execute();
$allUsers = $usersStmt->fetchAll();

$isAjax = isset($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');

    // Initialize inputs 
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
    $userId = isset($_GET['user']) ? (int)$_GET['user'] : 0;
    $overdue = isset($_GET['overdue']) && ($_GET['overdue'] === '1' || $_GET['overdue'] === 'true');
    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

    // Detect due_date existence
    $hasDueDate = false;
    $colStmt = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'due_date'");
    if ($colStmt && $colStmt->fetch()) {
        $hasDueDate = true;
    }

    // Build where
    $where = [];
    $params = [];
    if ($status !== '') {
        $map = [
            'Done' => 'Done',
            'Doing' => 'Doing',
            'Macrotask' => 'Macrotasks',
            'Microtask' => 'Microtasks'
        ];
        if (isset($map[$status])) {
            $where[] = 'c.name = ?';
            $params[] = $map[$status];
        }
    }
    if ($userId > 0) {
        $where[] = 'u.id = ?';
        $params[] = $userId;
    }
    if ($overdue && $hasDueDate) {
        $where[] = 't.due_date < NOW() AND c.name != ?';
        $params[] = 'Done';
    }
    if ($search !== '') {
        $where[] = '(t.title LIKE ? OR t.description LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = 'WHERE ' . implode(' AND ', $where);
    }

    // Stats
    $stats = [];
    $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM tasks');
    $stmt->execute();
    $stats['total_tasks'] = (int)($stmt->fetch()['cnt'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM tasks t JOIN categories c ON t.category_id = c.id WHERE c.name = 'Done'");
    $stmt->execute();
    $stats['done'] = (int)($stmt->fetch()['cnt'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM tasks t JOIN categories c ON t.category_id = c.id WHERE c.name = 'Doing'");
    $stmt->execute();
    $stats['doing'] = (int)($stmt->fetch()['cnt'] ?? 0);

    if ($hasDueDate) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM tasks t JOIN categories c ON t.category_id = c.id WHERE t.due_date < NOW() AND c.name != 'Done'");
        $stmt->execute();
        $stats['overdue'] = (int)($stmt->fetch()['cnt'] ?? 0);
    } else {
        $stats['overdue'] = 0;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM tasks t JOIN categories c ON t.category_id = c.id WHERE c.name = 'Macrotasks'");
    $stmt->execute();
    $stats['macrotasks'] = (int)($stmt->fetch()['cnt'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM tasks t JOIN categories c ON t.category_id = c.id WHERE c.name = 'Microtasks'");
    $stmt->execute();
    $stats['microtasks'] = (int)($stmt->fetch()['cnt'] ?? 0);

    // Tasks (limit for safety)
    $query = "SELECT t.id, t.title, t.description, t.created_at, u.username, c.name AS category_name";
    if ($hasDueDate) $query .= ", t.due_date";
    $query .= " FROM tasks t JOIN users u ON t.user_id = u.id JOIN categories c ON t.category_id = c.id " . $whereSql . " ORDER BY t.created_at DESC LIMIT 1000";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    // Clear any previous output (PHP warnings/notices) before emitting JSON
    @ob_end_clean();
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'tasks' => $tasks,
        'hasDueDate' => $hasDueDate
    ]);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tasks Management - Admin - Task Manager</title>
    <link rel="stylesheet" href="../styles/admin.css" />
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-brand">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="tasks.php" class="nav-link active">Tasks</a>
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
                <h1>Tasks Management</h1>
                <p>Monitor and manage all user tasks across boards</p>
            </header>

            <section class="section">
                <div class="section-title">Quick Stats</div>
                <div id="tasks-stats" class="stats-grid">
                    <!-- Filled by AJAX -->
                </div>
            </section>

            <section class="section">
                <div class="section-title">Filters & Search</div>
                <form id="tasks-filters" method="GET" class="filters-form" style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end;">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="">Any</option>
                            <option value="Done">Done</option>
                            <option value="Doing">Doing</option>
                            <option value="Macrotask">Macrotask</option>
                            <option value="Microtask">Microtask</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="user">User</label>
                        <select name="user" id="user">
                            <option value="0">Any</option>
                            <?php foreach($allUsers as $u): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="overdue">Overdue</label>
                        <select name="overdue" id="overdue">
                            <option value="0">Any</option>
                            <option value="1">Overdue</option>
                        </select>
                    </div>

                    <div class="form-group flex-grow">
                        <label for="search">Search</label>
                        <input type="search" name="search" id="search" placeholder="Search title or description">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <button type="button" id="reset-filters" class="btn btn-secondary">Reset</button>
                    </div>
                </form>

                <div id="tasks-warning"></div>
            </section>

            <section class="section">
                <div class="section-title">Tasks</div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>User</th>
                                <th>Category</th>
                                <th id="due-header" style="display:none;">Due</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody id="tasks-tbody">
                            <!-- Filled by AJAX -->
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
            <script src="../scripts/admin.js"></script>
</body>
</html>