<?php

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../App/Config/database.php';
require_once __DIR__ . '/../App/Core/Database.php';

use App\Core\Database;

$db = Database::getInstance();

try {
    // Table 1: digital_product
    $db->query("
        CREATE TABLE IF NOT EXISTS digital_product (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            file_download_link TEXT NOT NULL,
            file_size VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id)
                ON DELETE CASCADE,
            INDEX idx_product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ")->execute();

    // Table 2: digital_product_download
    $db->query("
        CREATE TABLE IF NOT EXISTS digital_product_download (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            order_id INT NULL,
            expire_date DATE NOT NULL,
            download_count INT DEFAULT 0,
            max_download INT DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
                ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_product_id (product_id),
            INDEX idx_order_id (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ")->execute();

    echo "✅ Digital product tables created successfully!\n";
} catch (Exception $e) {
    echo "❌ Error creating digital product tables: " . $e->getMessage() . "\n";
    exit(1);
}

