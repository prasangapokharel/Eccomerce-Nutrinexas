<?php

namespace App\Services;

use App\Core\Database;
use App\Models\OrderItem;

/**
 * Best Selling Products Report Service
 */
class BestSellingProductsService
{
    private $db;
    private $orderItemModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->orderItemModel = new OrderItem();
    }

    /**
     * Get best selling products
     */
    public function getBestSellingProducts(int $limit = 10, string $period = 'all'): array
    {
        $dateFilter = '';
        $params = [$limit];
        
        switch ($period) {
            case 'today':
                $dateFilter = "AND DATE(o.created_at) = CURDATE()";
                break;
            case 'week':
                $dateFilter = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateFilter = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'year':
                $dateFilter = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
        }
        
        $sql = "SELECT 
                    p.id,
                    p.product_name,
                    p.image,
                    p.price,
                    p.sale_price,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.total) as total_revenue,
                    COUNT(DISTINCT oi.order_id) as order_count
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status != 'cancelled' {$dateFilter}
                GROUP BY p.id, p.product_name, p.image, p.price, p.sale_price
                ORDER BY total_sold DESC, total_revenue DESC
                LIMIT ?";
        
        return $this->db->query($sql)->bind($params)->all();
    }

    /**
     * Get best selling products by category
     */
    public function getBestSellingByCategory(string $category, int $limit = 10): array
    {
        $sql = "SELECT 
                    p.id,
                    p.product_name,
                    p.category,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.total) as total_revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE p.category = ? AND o.status != 'cancelled'
                GROUP BY p.id, p.product_name, p.category
                ORDER BY total_sold DESC
                LIMIT ?";
        
        return $this->db->query($sql)->bind([$category, $limit])->all();
    }

    /**
     * Get sales trends
     */
    public function getSalesTrends(int $days = 30): array
    {
        $sql = "SELECT 
                    DATE(o.created_at) as date,
                    COUNT(DISTINCT o.id) as order_count,
                    SUM(o.total_amount) as revenue,
                    SUM(oi.quantity) as items_sold
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND o.status != 'cancelled'
                GROUP BY DATE(o.created_at)
                ORDER BY date DESC";
        
        return $this->db->query($sql)->bind([$days])->all();
    }
}

