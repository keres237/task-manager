<?php
// One-time migration: add due_date column to tasks table if missing
require_once __DIR__ . '/../config/database.php';

try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'due_date'");
    $col = $colStmt ? $colStmt->fetch() : false;
    if ($col) {
        echo "Column 'due_date' already exists.\n";
        exit(0);
    }

    $pdo->exec("ALTER TABLE tasks ADD COLUMN due_date DATETIME NULL AFTER position");
    echo "Added 'due_date' column to tasks table.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

?>
