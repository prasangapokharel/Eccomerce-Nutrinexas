<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Core/Database.php';

class CreateEmailQueueTable
{
    public function up()
    {
        $db = \App\Core\Database::getInstance();
        
        $sql = "CREATE TABLE IF NOT EXISTS email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            to_email VARCHAR(255) NOT NULL,
            to_name VARCHAR(255) DEFAULT NULL,
            subject VARCHAR(500) NOT NULL,
            template VARCHAR(100) NOT NULL,
            template_data TEXT NOT NULL,
            status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            error_message TEXT DEFAULT NULL,
            scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_scheduled_at (scheduled_at),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $db->query($sql)->execute();
            echo "Email queue table created successfully!\n";
        } catch (Exception $e) {
            echo "Error creating email queue table: " . $e->getMessage() . "\n";
        }
    }
}

// Run the migration
$migration = new CreateEmailQueueTable();
$migration->up();
















