<?php
class Task {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createTask($userId, $categoryId, $title, $description = '') {
        try {
            // Get the next position
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(MAX(position), -1) + 1 as next_position 
                FROM tasks 
                WHERE user_id = ? AND category_id = ?
            ");
            $stmt->execute([$userId, $categoryId]);
            $result = $stmt->fetch();
            $nextPosition = $result['next_position'] ?? 0;
            
            // Insert task
            $stmt = $this->pdo->prepare("
                INSERT INTO tasks (user_id, category_id, title, description, position) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $categoryId, $title, $description, $nextPosition]);
            $taskId = $this->pdo->lastInsertId();
            
            // Log to history
            $this->logHistory($userId, $taskId, 'created', null, [
                'title' => $title,
                'description' => $description,
                'category_id' => $categoryId
            ]);
            
            return ['success' => true, 'task_id' => $taskId, 'message' => 'Task created successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create task: ' . $e->getMessage()];
        }
    }
    
    public function updateTask($taskId, $userId, $title, $description = '') {
        try {
            // Get old data
            $stmt = $this->pdo->prepare("SELECT title, description FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$taskId, $userId]);
            $oldData = $stmt->fetch();
            
            if (!$oldData) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Update task
            $stmt = $this->pdo->prepare("
                UPDATE tasks 
                SET title = ?, description = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$title, $description, $taskId, $userId]);
            
            // Log to history
            $this->logHistory($userId, $taskId, 'updated', $oldData, [
                'title' => $title,
                'description' => $description
            ]);
            
            return ['success' => true, 'message' => 'Task updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update task'];
        }
    }
    
    public function deleteTask($taskId, $userId) {
        try {
            // Get task data before deletion
            $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$taskId, $userId]);
            $taskData = $stmt->fetch();
            
            if (!$taskData) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            // Log to history before deletion
            $this->logHistory($userId, $taskId, 'deleted', $taskData, null);
            
            // Delete task
            $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$taskId, $userId]);
            
            return ['success' => true, 'message' => 'Task deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete task'];
        }
    }
    
    public function moveTask($taskId, $userId, $newCategoryId) {
        try {
            // Get task and old category
            $stmt = $this->pdo->prepare("SELECT category_id FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$taskId, $userId]);
            $task = $stmt->fetch();
            
            if (!$task) {
                return ['success' => false, 'message' => 'Task not found'];
            }
            
            $oldCategoryId = $task['category_id'];
            
            // Get max position in new category
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(MAX(position), -1) + 1 as next_position 
                FROM tasks 
                WHERE user_id = ? AND category_id = ?
            ");
            $stmt->execute([$userId, $newCategoryId]);
            $result = $stmt->fetch();
            $newPosition = $result['next_position'] ?? 0;
            
            // Update task
            $stmt = $this->pdo->prepare("
                UPDATE tasks 
                SET category_id = ?, position = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$newCategoryId, $newPosition, $taskId, $userId]);
            
            // Log to history
            $this->logHistory($userId, $taskId, 'moved', 
                ['category_id' => $oldCategoryId], 
                ['category_id' => $newCategoryId]
            );
            
            return ['success' => true, 'message' => 'Task moved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to move task'];
        }
    }
    
    public function getTask($taskId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT t.*, c.name as category_name 
            FROM tasks t
            JOIN categories c ON t.category_id = c.id
            WHERE t.id = ? AND t.user_id = ?
        ");
        $stmt->execute([$taskId, $userId]);
        return $stmt->fetch();
    }
    
    public function getUserTasks($userId, $categoryId = null) {
        if ($categoryId) {
            $stmt = $this->pdo->prepare("
                SELECT t.*, c.name as category_name 
                FROM tasks t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.category_id = ?
                ORDER BY t.position ASC
            ");
            $stmt->execute([$userId, $categoryId]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT t.*, c.name as category_name 
                FROM tasks t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ?
                ORDER BY t.category_id, t.position ASC
            ");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }
    
    private function logHistory($userId, $taskId, $action, $oldData = null, $newData = null) {
        try {
            $oldDataJson = $oldData ? json_encode($oldData) : null;
            $newDataJson = $newData ? json_encode($newData) : null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO task_history (task_id, user_id, action, old_data, new_data) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$taskId, $userId, $action, $oldDataJson, $newDataJson]);
        } catch (Exception $e) {
            // Silently fail history logging to not break main operations
        }
    }
}
?>
