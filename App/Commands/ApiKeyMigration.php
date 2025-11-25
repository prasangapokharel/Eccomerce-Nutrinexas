<?php
namespace App\Commands;

use App\Core\Database;

class ApiKeyMigration
{
    public function up()
    {
        $db = new Database();
        
        $sql = "CREATE TABLE IF NOT EXISTS api_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(255) NOT NULL,
            key_hash VARCHAR(255) NOT NULL UNIQUE,
            permissions TEXT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            last_used TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_key_hash (key_hash),
            INDEX idx_is_active (is_active)
        )";
        
        return $db->execute($sql);
    }
    
    public function down()
    {
        $db = new Database();
        return $db->execute("DROP TABLE IF EXISTS api_keys");
    }
}




























