<?php

namespace App\Commands;

use App\Core\Command;
use App\Core\Database;

class AddCouponStatus extends Command
{
    public function handle()
    {
        try {
            $db = new Database();
            $pdo = $db->getPdo();
            
            echo "Adding status field to coupons table...\n";
            
            // Check if status column already exists
            $checkSql = "SHOW COLUMNS FROM coupons LIKE 'status'";
            $stmt = $pdo->prepare($checkSql);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Add status column
                $sql = "ALTER TABLE coupons ADD COLUMN status ENUM('public', 'private') DEFAULT 'private' AFTER is_active";
                $pdo->exec($sql);
                echo "Status field added successfully!\n";
                
                // Update existing coupons to be public by default
                $sql = "UPDATE coupons SET status = 'public' WHERE is_active = 1";
                $pdo->exec($sql);
                echo "Existing active coupons set to public status.\n";
            } else {
                echo "Status field already exists.\n";
            }
            
            echo "Migration completed successfully!\n";
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
        
        return true;
    }
}
