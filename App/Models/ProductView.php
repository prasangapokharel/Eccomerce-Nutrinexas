<?php

namespace App\Models;

use App\Core\Model;

/**
 * ProductView Model
 * Tracks product views by IP address
 * Allows duplicate IPs to track high traffic
 */
class ProductView extends Model
{
    protected $table = 'products_views';
    protected $primaryKey = 'id';

    /**
     * Record a product view
     * 
     * @param int $productId
     * @param string|null $ip IP address (defaults to current user's IP)
     * @return int|false View record ID or false on failure
     */
    public function recordView($productId, $ip = null)
    {
        if (!$productId) {
            return false;
        }

        if ($ip === null) {
            $ip = $this->getClientIp();
        }

        try {
            $sql = "INSERT INTO {$this->table} (product_id, ip, created_at) VALUES (?, ?, NOW())";
            $result = $this->db->query($sql, [$productId, $ip])->execute();
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\Exception $e) {
            error_log('ProductView recordView error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get view count for a product
     * 
     * @param int $productId
     * @return int View count
     */
    public function getViewCount($productId)
    {
        if (!$productId) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE product_id = ?";
            $result = $this->db->query($sql, [$productId])->single();
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            error_log('ProductView getViewCount error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get view counts for multiple products
     * 
     * @param array $productIds
     * @return array Associative array with product_id => view_count
     */
    public function getViewCounts(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT product_id, COUNT(*) as count 
                    FROM {$this->table} 
                    WHERE product_id IN ({$placeholders}) 
                    GROUP BY product_id";
            $results = $this->db->query($sql, $productIds)->all();
            
            $counts = [];
            foreach ($results as $row) {
                $counts[$row['product_id']] = (int)$row['count'];
            }
            
            // Fill in 0 for products with no views
            foreach ($productIds as $id) {
                if (!isset($counts[$id])) {
                    $counts[$id] = 0;
                }
            }
            
            return $counts;
        } catch (\Exception $e) {
            error_log('ProductView getViewCounts error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get views by date range for analytics
     * 
     * @param int $productId
     * @param string|null $startDate Y-m-d format
     * @param string|null $endDate Y-m-d format
     * @return array View records
     */
    public function getViewsByDateRange($productId, $startDate = null, $endDate = null)
    {
        if (!$productId) {
            return [];
        }

        try {
            $sql = "SELECT * FROM {$this->table} WHERE product_id = ?";
            $params = [$productId];

            if ($startDate) {
                $sql .= " AND DATE(created_at) >= ?";
                $params[] = $startDate;
            }

            if ($endDate) {
                $sql .= " AND DATE(created_at) <= ?";
                $params[] = $endDate;
            }

            $sql .= " ORDER BY created_at DESC";

            return $this->db->query($sql, $params)->all();
        } catch (\Exception $e) {
            error_log('ProductView getViewsByDateRange error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function getClientIp()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}




