<?php
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../includes/Auth.php';
require_once '../../includes/Task.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError('Method not allowed', 405);
}

$auth = new Auth($pdo);
if (!$auth->isLoggedIn()) {
    handleError('Unauthorized', 401);
}

$userId = $auth->getUserId();
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$taskId = $data['task_id'] ?? null;

if (!$taskId) {
    handleError('Task ID is required', 400);
}

$task = new Task($pdo);
$result = $task->deleteTask($taskId, $userId);

if ($result['success']) {
    handleSuccess($result['message']);
} else {
    handleError($result['message'], 400);
}
?>
