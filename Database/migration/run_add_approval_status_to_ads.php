<?php
require_once __DIR__ . '/../../App/Config/config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$db = \App\Core\Database::getInstance();

// Check if column exists
$columnExists = $db->query(
    "SELECT COUNT(*) as count 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'ads' 
     AND COLUMN_NAME = 'approval_status'"
)->single();

if ((int)$columnExists['count'] == 0) {
    // Add approval_status column
    try {
        $db->query(
            "ALTER TABLE `ads` 
             ADD COLUMN `approval_status` ENUM('pending','approved','rejected') DEFAULT 'pending' AFTER `status`"
        )->execute();
        echo "✓ Added approval_status column\n";
    } catch (Exception $e) {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
} else {
    echo "✓ Column already exists\n";
}

// Check if index exists
$indexExists = $db->query(
    "SELECT COUNT(*) as count 
     FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'ads' 
     AND INDEX_NAME = 'idx_approval_status'"
)->single();

if ((int)$indexExists['count'] == 0) {
    try {
        $db->query("CREATE INDEX `idx_approval_status` ON `ads` (`approval_status`)")->execute();
        echo "✓ Added index\n";
    } catch (Exception $e) {
        echo "Error adding index: " . $e->getMessage() . "\n";
    }
} else {
    echo "✓ Index already exists\n";
}

echo "Migration completed!\n";

