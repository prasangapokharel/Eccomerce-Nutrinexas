<?php

/**
 * Migration: Create blog_views table
 * This table tracks blog post views by IP address to prevent duplicate counting
 */

class CreateBlogViewsTable
{
    public function up()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS blog_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post_ip (post_id, ip_address),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        return $sql;
    }
    
    public function down()
    {
        return "DROP TABLE IF EXISTS blog_views;";
    }
}








