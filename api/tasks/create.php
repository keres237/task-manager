<?php
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Task.php';
require_once '../../includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError('Method not allowed', 405);
}

$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    handleError('Unauthorized', 401);
}

$userId = $auth->getUserId();
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$categoryId = $data['category_id'] ?? null;
$title = sanitize($data['title'] ?? '');
$description = sanitize($data['description'] ?? '');

if (!$categoryId || !$title) {
    handleError('Category ID and title are required', 400);
}

$task = new Task($pdo);
$result = $task->createTask($userId, $categoryId, $title, $description);

if ($result['success']) {
    handleSuccess($result['message'], ['task_id' => $result['task_id']], 201);
} else {
    handleError($result['message'], 400);
}
?>
