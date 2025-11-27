<?php
/**
 * Migration: gateway_transactions table for Omnipay support
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

$db = Database::getInstance();

echo "=== Adding gateway_transactions table ===\n";

try {
    $db->query("
        CREATE TABLE IF NOT EXISTS gateway_transactions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            gateway_slug VARCHAR(120) NOT NULL,
            driver VARCHAR(120) NOT NULL,
            status ENUM('pending','completed','failed','cancelled') DEFAULT 'pending',
            provider_reference VARCHAR(191) DEFAULT NULL,
            request_payload JSON NULL,
            response_payload JSON NULL,
            metadata JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_provider_reference (provider_reference),
            KEY idx_order_gateway (order_id, gateway_slug),
            KEY idx_status (status),
            CONSTRAINT fk_gateway_transactions_orders
                FOREIGN KEY (order_id) REFERENCES orders(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ")->execute();

    echo "✅ gateway_transactions table ready\n";
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}




