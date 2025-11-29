<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use Exception;

class MigrationController extends Controller
{
    public function createSellerStaff()
    {
        $this->requireAdmin();
        
        try {
            $db = Database::getInstance();
            
            // Check if table exists
            $tableCheck = $db->query("SHOW TABLES LIKE 'seller_staff'")->single();
            
            if ($tableCheck) {
                // Check columns
                $roleCheck = $db->query("SHOW COLUMNS FROM seller_staff LIKE 'role'")->single();
                $cityCheck = $db->query("SHOW COLUMNS FROM seller_staff LIKE 'city'")->single();
                
                if (!$roleCheck) {
                    $db->query("ALTER TABLE seller_staff ADD COLUMN role VARCHAR(50) DEFAULT 'delivery_boy' AFTER password")->execute();
                }
                
                if (!$cityCheck) {
                    $db->query("ALTER TABLE seller_staff ADD COLUMN city VARCHAR(100) NOT NULL DEFAULT '' AFTER password")->execute();
                }
                
                $this->setFlash('success', 'Table already exists. Columns checked and updated if needed.');
            } else {
                // Create table
                $db->query("
                    CREATE TABLE seller_staff (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        seller_id INT NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL UNIQUE,
                        phone VARCHAR(20),
                        password VARCHAR(255) NOT NULL,
                        city VARCHAR(100) NOT NULL,
                        role VARCHAR(50) DEFAULT 'delivery_boy',
                        status VARCHAR(20) DEFAULT 'active',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_seller_id (seller_id),
                        INDEX idx_email (email),
                        INDEX idx_city (city),
                        INDEX idx_status (status),
                        FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ")->execute();
                
                $this->setFlash('success', 'seller_staff table created successfully!');
            }
        } catch (Exception $e) {
            error_log('Migration error: ' . $e->getMessage());
            $this->setFlash('error', 'Migration failed: ' . $e->getMessage());
        }
        
        $this->redirect('admin/settings');
    }
}

