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

echo "Checking order_cancel_log table...\n";

try {
    $result = $db->query('SELECT COUNT(*) as count FROM order_cancel_log')->single();
    echo "✓ Table exists. Records: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Creating table...\n";
    
    try {
        $db->query("CREATE TABLE IF NOT EXISTS `order_cancel_log` (
            `id` int NOT NULL AUTO_INCREMENT,
            `order_id` int NOT NULL,
            `seller_id` int NULL,
            `reason` text COLLATE utf8mb4_general_ci NOT NULL,
            `status` enum('processing','refunded','failed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'processing',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_order_id` (`order_id`),
            KEY `idx_status` (`status`),
            KEY `idx_seller_id` (`seller_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci")->execute();
        echo "✓ Table created successfully\n";
    } catch (Exception $e2) {
        echo "✗ Failed to create table: " . $e2->getMessage() . "\n";
    }
}




