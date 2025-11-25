<?php

namespace App\Controllers\Seller;

use Exception;

class Analytics extends BaseSellerController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \App\Core\Database::getInstance();
    }

    /**
     * Analytics dashboard
     */
    public function index()
    {
        $period = $_GET['period'] ?? 'month'; // day, week, month, year
        
        // Get date range based on period
        $dateRange = $this->getDateRange($period);
        
        // Sales data
        $salesData = $this->getSalesData($dateRange);
        
        // Order statistics
        $orderStats = $this->getOrderStats($dateRange);
        
        // Conversion rate
        $conversionRate = $this->getConversionRate($dateRange);
        
        // Traffic insights (visits, unique visitors - simulated from orders)
        $trafficInsights = $this->getTrafficInsights($dateRange);
        
        // Best selling products
        $bestSelling = $this->getBestSellingProducts($dateRange);
        
        // Cancelled/Returned items
        $cancelledReturned = $this->getCancelledReturned($dateRange);

        $this->view('seller/analytics/index', [
            'title' => 'Analytics & Reports',
            'period' => $period,
            'salesData' => $salesData,
            'orderStats' => $orderStats,
            'conversionRate' => $conversionRate,
            'trafficInsights' => $trafficInsights,
            'bestSelling' => $bestSelling,
            'cancelledReturned' => $cancelledReturned
        ]);
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        $end = date('Y-m-d 23:59:59');
        switch ($period) {
            case 'day':
                $start = date('Y-m-d 00:00:00');
                break;
            case 'week':
                $start = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'month':
                $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            case 'year':
                $start = date('Y-m-d 00:00:00', strtotime('-365 days'));
                break;
            default:
                $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
        }
        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get sales data for period
     */
    private function getSalesData($dateRange)
    {
        $sql = "SELECT DATE(o.created_at) as date, 
                       SUM(oi.total) as revenue,
                       COUNT(DISTINCT o.id) as orders
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.seller_id = ? 
                  AND o.created_at BETWEEN ? AND ?
                  AND o.status != 'cancelled'
                GROUP BY DATE(o.created_at)
                ORDER BY date ASC";
        
        return $this->db->query($sql, [$this->sellerId, $dateRange['start'], $dateRange['end']])->all();
    }

    /**
     * Get order statistics
     */
    private function getOrderStats($dateRange)
    {
        $sql = "SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(oi.total) as total_revenue,
                    AVG(oi.total) as avg_order_value,
                    COUNT(DISTINCT o.user_id) as unique_customers
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.seller_id = ? 
                  AND o.created_at BETWEEN ? AND ?
                  AND o.status != 'cancelled'";
        
        return $this->db->query($sql, [$this->sellerId, $dateRange['start'], $dateRange['end']])->single();
    }

    /**
     * Get conversion rate
     */
    private function getConversionRate($dateRange)
    {
        // Get total orders
        $ordersSql = "SELECT COUNT(DISTINCT o.id) as count
                      FROM orders o
                      INNER JOIN order_items oi ON o.id = oi.order_id
                      WHERE oi.seller_id = ? 
                        AND o.created_at BETWEEN ? AND ?
                        AND o.status != 'cancelled'";
        $orders = $this->db->query($ordersSql, [$this->sellerId, $dateRange['start'], $dateRange['end']])->single();
        
        // Estimate visitors (using unique customers * 3 as approximation)
        $visitorsSql = "SELECT COUNT(DISTINCT o.user_id) as count
                        FROM orders o
                        INNER JOIN order_items oi ON o.id = oi.order_id
                        WHERE oi.seller_id = ? 
                          AND o.created_at BETWEEN ? AND ?";
        $visitors = $this->db->query($visitorsSql, [$this->sellerId, $dateRange['start'], $dateRange['end']])->single();
        
        $estimatedVisitors = max($visitors['count'] * 3, $orders['count']); // At least equal to orders
        
        $conversionRate = $estimatedVisitors > 0 ? ($orders['count'] / $estimatedVisitors) * 100 : 0;
        
        return [
            'orders' => $orders['count'],
            'visitors' => $estimatedVisitors,
            'rate' => round($conversionRate, 2)
        ];
    }

    /**
     * Get traffic insights
     */
    private function getTrafficInsights($dateRange)
    {
        // Get unique customers (as proxy for unique visitors)
        $uniqueCustomers = $this->db->query(
            "SELECT COUNT(DISTINCT o.user_id) as count
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             WHERE oi.seller_id = ? 
               AND o.created_at BETWEEN ? AND ?",
            [$this->sellerId, $dateRange['start'], $dateRange['end']]
        )->single();
        
        // Estimate total visits (unique customers * average visits per customer)
        $totalVisits = $uniqueCustomers['count'] * 2.5; // Approximation
        
        return [
            'unique_visitors' => $uniqueCustomers['count'],
            'total_visits' => (int)$totalVisits,
            'bounce_rate' => 35.5, // Estimated
            'avg_session_duration' => '3:45' // Estimated
        ];
    }

    /**
     * Get best selling products
     */
    private function getBestSellingProducts($dateRange)
    {
        $sql = "SELECT p.id, p.product_name, 
                       SUM(oi.quantity) as total_sold,
                       SUM(oi.total) as total_revenue
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.id
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.seller_id = ? 
                  AND o.created_at BETWEEN ? AND ?
                  AND o.status != 'cancelled'
                GROUP BY p.id, p.product_name
                ORDER BY total_sold DESC
                LIMIT 10";
        
        return $this->db->query($sql, [$this->sellerId, $dateRange['start'], $dateRange['end']])->all();
    }

    /**
     * Get cancelled and returned items
     */
    private function getCancelledReturned($dateRange)
    {
        // Cancelled orders
        $cancelled = $this->db->query(
            "SELECT COUNT(DISTINCT o.id) as count, SUM(oi.total) as total
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             WHERE oi.seller_id = ? 
               AND o.status = 'cancelled'
               AND o.created_at BETWEEN ? AND ?",
            [$this->sellerId, $dateRange['start'], $dateRange['end']]
        )->single();
        
        // Returned items (if you have a returns table, otherwise use cancelled as proxy)
        $returned = $this->db->query(
            "SELECT COUNT(DISTINCT o.id) as count, SUM(oi.total) as total
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             WHERE oi.seller_id = ? 
               AND o.status = 'returned'
               AND o.created_at BETWEEN ? AND ?",
            [$this->sellerId, $dateRange['start'], $dateRange['end']]
        )->single();
        
        return [
            'cancelled' => [
                'count' => $cancelled['count'] ?? 0,
                'total' => $cancelled['total'] ?? 0
            ],
            'returned' => [
                'count' => $returned['count'] ?? 0,
                'total' => $returned['total'] ?? 0
            ]
        ];
    }
}
