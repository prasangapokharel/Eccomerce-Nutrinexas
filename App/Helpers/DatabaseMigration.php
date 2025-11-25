<?php

namespace App\Helpers;

/**
 * Database Migration Helper
 * Handles missing tables and database structure issues
 */
class DatabaseMigration
{
    /**
     * Check and create missing tables
     */
    public static function checkAndCreateMissingTables()
    {
        try {
            $db = \App\Core\Database::getInstance();
            
            // Check if sliders table exists
            $result = $db->query("SHOW TABLES LIKE 'sliders'")->fetch();
            if (!$result) {
                self::createSlidersTable();
            }
            
            // Check if banners table exists
            $result = $db->query("SHOW TABLES LIKE 'banners'")->fetch();
            if (!$result) {
                self::createBannersTable();
            }
            
            // Check if other essential tables exist
            $essentialTables = ['products', 'categories', 'users', 'orders', 'cart_items'];
            foreach ($essentialTables as $table) {
                $result = $db->query("SHOW TABLES LIKE '$table'")->fetch();
                if (!$result) {
                    error_log("Warning: Essential table '$table' is missing from database");
                }
            }
            
        } catch (\Exception $e) {
            error_log("Database migration error: " . $e->getMessage());
        }
    }
    
    /**
     * Create sliders table
     */
    private static function createSlidersTable()
    {
        try {
            $db = \App\Core\Database::getInstance();
            
            $sql = "CREATE TABLE `sliders` (
                `id` int NOT NULL AUTO_INCREMENT,
                `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
                `image_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
                `link_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT '1',
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $db->query($sql);
            error_log("Sliders table created successfully");
            
            // Insert default slider
            $insertSql = "INSERT INTO `sliders` (`title`, `image_url`, `link_url`, `is_active`) VALUES 
                ('Welcome to NutriNexus', '/assets/images/slider/default.jpg', '/products', 1)";
            $db->query($insertSql);
            
        } catch (\Exception $e) {
            error_log("Error creating sliders table: " . $e->getMessage());
        }
    }
    
    /**
     * Get database status
     */
    public static function getDatabaseStatus()
    {
        try {
            $db = \App\Core\Database::getInstance();
            
            $tables = $db->query("SHOW TABLES")->fetchAll();
            $tableList = array_map(function($table) {
                return array_values($table)[0];
            }, $tables);
            
            return [
                'status' => 'connected',
                'tables_count' => count($tables),
                'tables' => $tableList,
                'missing_essential' => self::getMissingEssentialTables($tableList)
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get missing essential tables
     */
    private static function getMissingEssentialTables($existingTables)
    {
        $essentialTables = ['products', 'categories', 'users', 'orders', 'cart_items', 'sliders', 'banners'];
        return array_diff($essentialTables, $existingTables);
    }
    
    /**
     * Create banners table
     */
    private static function createBannersTable()
    {
        try {
            $db = \App\Core\Database::getInstance();
            
            $sql = "CREATE TABLE IF NOT EXISTS `banners` (
                `id` int NOT NULL AUTO_INCREMENT,
                `image_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
                `clicks` int NOT NULL DEFAULT '0',
                `views` int NOT NULL DEFAULT '0',
                `status` enum('active','inactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
                `link_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            $db->query($sql)->execute();
            error_log("Banners table created successfully");
        } catch (\Exception $e) {
            error_log("Error creating banners table: " . $e->getMessage());
        }
    }
}
