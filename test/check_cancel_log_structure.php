<?php
require_once __DIR__ . '/../App/Config/config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$db = \App\Core\Database::getInstance();

echo "Checking order_cancel_log table structure...\n";

try {
    // Check if seller_id column exists
    $columns = $db->query(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'order_cancel_log' 
         AND COLUMN_NAME = 'seller_id'"
    )->single();
    
    if (!$columns) {
        echo "✗ seller_id column does not exist. Adding it...\n";
        $db->query(
            "ALTER TABLE order_cancel_log 
             ADD COLUMN seller_id INT NULL AFTER order_id,
             ADD INDEX idx_seller_id (seller_id)"
        )->execute();
        echo "✓ seller_id column added\n";
    } else {
        echo "✓ seller_id column exists\n";
    }
    
    // Test the query
    echo "\nTesting getAllWithOrders query...\n";
    $cancelLog = new \App\Models\CancelLog();
    $result = $cancelLog->getAllWithOrders();
    echo "✓ Query executed successfully. Found " . count($result) . " records\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}




