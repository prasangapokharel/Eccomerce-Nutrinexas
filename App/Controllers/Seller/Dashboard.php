<?php

namespace App\Controllers\Seller;

use App\Models\Product;
use App\Models\Order;
use App\Core\Cache;
use Exception;

class Dashboard extends BaseSellerController
{
    private $productModel;
    private $orderModel;
    private $sellerModel;
    private $cache;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->orderModel = new Order();
        $this->sellerModel = new \App\Models\Seller();
        $this->cache = new Cache();
    }

    /**
     * Dashboard index
     */
    public function index()
    {
        try {
            $cacheKey = 'seller_dashboard_' . $this->sellerId;
            $cached = $this->cache->get($cacheKey);
            
            if ($cached !== false) {
                $this->view('seller/dashboard/index', $cached);
                return;
            }
            
            $stats = $this->sellerModel->getStats($this->sellerId);
            
            // Recent orders (cache for 5 minutes)
            $recentOrdersKey = 'seller_recent_orders_' . $this->sellerId;
            $recentOrders = $this->cache->remember($recentOrdersKey, function() {
                return $this->orderModel->getOrdersBySellerId($this->sellerId, 5);
            }, 300);
            
            // Recent products (cache for 10 minutes)
            $recentProductsKey = 'seller_recent_products_' . $this->sellerId;
            $recentProducts = $this->cache->remember($recentProductsKey, function() {
                return $this->productModel->getProductsBySellerId($this->sellerId, 5);
            }, 600);
            
            // Sales chart data (last 7 days) - cache for 15 minutes
            $salesDataKey = 'seller_sales_data_' . $this->sellerId;
            $salesData = $this->cache->remember($salesDataKey, function() {
                return $this->getSalesChartData();
            }, 900);
            
            // Top products (cache for 10 minutes)
            $topProductsKey = 'seller_top_products_' . $this->sellerId;
            $topProducts = $this->cache->remember($topProductsKey, function() {
                return $this->getTopProducts(5);
            }, 600);

            $viewData = [
                'title' => 'Seller Dashboard',
                'stats' => $stats,
                'recentOrders' => $recentOrders,
                'recentProducts' => $recentProducts,
                'salesData' => $salesData,
                'topProducts' => $topProducts,
                'seller' => $this->sellerData
            ];
            
            // Cache full dashboard for 5 minutes
            $this->cache->set($cacheKey, $viewData, 300);
            
            $this->view('seller/dashboard/index', $viewData);
        } catch (Exception $e) {
            error_log('Seller dashboard error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load dashboard');
            $this->view('seller/dashboard/index', [
                'title' => 'Seller Dashboard',
                'stats' => [],
                'recentOrders' => [],
                'recentProducts' => [],
                'salesData' => [],
                'topProducts' => [],
                'seller' => $this->sellerData
            ]);
        }
    }

    /**
     * Get sales chart data
     */
    private function getSalesChartData()
    {
        $cacheKey = 'seller_sales_chart_' . $this->sellerId . '_' . date('Y-m-d');
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $data = [];
        $db = \App\Core\Database::getInstance();
        
        // Optimize: Single query for all dates
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $endDate = date('Y-m-d');
        
        $results = $db->query(
            "SELECT DATE(o.created_at) as date, COALESCE(SUM(oi.total), 0) as total 
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             WHERE oi.seller_id = ? 
               AND DATE(o.created_at) BETWEEN ? AND ?
               AND o.payment_status = 'paid'
               AND o.status != 'cancelled'
             GROUP BY DATE(o.created_at)",
            [$this->sellerId, $startDate, $endDate]
        )->all();
        
        // Create a map for quick lookup
        $salesMap = [];
        foreach ($results as $row) {
            $salesMap[$row['date']] = (float)$row['total'];
        }
        
        // Fill in all 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data[] = [
                'date' => $date,
                'total' => $salesMap[$date] ?? 0
            ];
        }
        
        // Cache for 15 minutes
        $this->cache->set($cacheKey, $data, 900);
        
        return $data;
    }

    /**
     * Get top products
     */
    private function getTopProducts($limit = 5)
    {
        $cacheKey = 'seller_top_products_' . $this->sellerId . '_' . $limit;
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $db = \App\Core\Database::getInstance();
        $products = $db->query(
            "SELECT p.*, 
                    COALESCE(SUM(oi.quantity), 0) as total_sold, 
                    COALESCE(SUM(oi.total), 0) as total_revenue 
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id AND oi.seller_id = ?
             LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid' AND o.status != 'cancelled'
             WHERE p.seller_id = ?
             GROUP BY p.id
             ORDER BY total_sold DESC
             LIMIT ?",
            [$this->sellerId, $this->sellerId, $limit]
        )->all();
        
        // Cache for 10 minutes
        $this->cache->set($cacheKey, $products, 600);
        
        return $products;
    }
}

