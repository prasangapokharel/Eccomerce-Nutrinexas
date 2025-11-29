<?php

define('ROOT_DIR', dirname(__DIR__));
define('APP_ROOT', ROOT_DIR);

require_once ROOT_DIR . '/App/Config/config.php';
require_once ROOT_DIR . '/App/Config/database.php';
require_once ROOT_DIR . '/App/Core/Database.php';

use App\Core\Database;

$db = Database::getInstance();

echo "=== Creating Security Tables ===\n\n";

try {
    // Create security_transactions table
    $sql1 = "CREATE TABLE IF NOT EXISTS security_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trace_id VARCHAR(64) NOT NULL,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        order_id INT,
        amount DECIMAL(10,2),
        payment_method VARCHAR(50),
        fraud_score INT DEFAULT 0,
        is_fraud TINYINT(1) DEFAULT 0,
        indicators TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        ip_address VARCHAR(45),
        user_agent TEXT,
        request_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_trace_id (trace_id),
        INDEX idx_user_id (user_id),
        INDEX idx_order_id (order_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->query($sql1)->execute();
    echo "✓ Created security_transactions table\n";

    // Create security_events table
    $sql2 = "CREATE TABLE IF NOT EXISTS security_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        trace_id VARCHAR(64) NOT NULL,
        action VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        request_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_trace_id (trace_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->query($sql2)->execute();
    echo "✓ Created security_events table\n";

    // Create rate_limits table
    $sql3 = "CREATE TABLE IF NOT EXISTS rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rate_key VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_rate_key (rate_key),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->query($sql3)->execute();
    echo "✓ Created rate_limits table\n";

    echo "\n=== Migration Complete ===\n";
    echo "All security tables created successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

