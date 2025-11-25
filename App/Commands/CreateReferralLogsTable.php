<?php
namespace App\Commands;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Core/Database.php';

use App\Core\Database;

class CreateReferralLogsTable
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance();
        } catch (\Exception $e) {
            error_log('CreateReferralLogsTable: Database connection failed: ' . $e->getMessage());
            $this->db = null;
        }
    }

    public function run()
    {
        if (!$this->db) {
            echo "❌ Database connection failed\n";
            return false;
        }

        try {
            $sql = "CREATE TABLE IF NOT EXISTS `referral_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `referrer_id` int(11) NOT NULL,
                `referred_id` int(11) NOT NULL,
                `action` varchar(50) NOT NULL,
                `amount` decimal(10,2) DEFAULT NULL,
                `ip_address` varchar(45) NOT NULL,
                `user_agent` text,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_referrer_id` (`referrer_id`),
                KEY `idx_referred_id` (`referred_id`),
                KEY `idx_action` (`action`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_ip_address` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $this->db->query($sql)->execute();
            echo "✅ Referral logs table created successfully\n";
            return true;

        } catch (\Exception $e) {
            echo "❌ Error creating referral logs table: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    $command = new CreateReferralLogsTable();
    $command->run();
}
