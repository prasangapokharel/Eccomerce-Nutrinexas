<?php

namespace App\Controllers\Seller;

use App\Models\Product;
use App\Models\Order;
use Exception;

class Dashboard extends BaseSellerController
{
    private $productModel;
    private $orderModel;
    private $sellerModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->orderModel = new Order();
        $this->sellerModel = new \App\Models\Seller();
    }

    /**
     * Dashboard index
     */
    public function index()
    {
        try {
            $stats = $this->sellerModel->getStats($this->sellerId);
            
            // Recent orders
            $recentOrders = $this->orderModel->getOrdersBySellerId($this->sellerId, 5);
            
            // Recent products
            $recentProducts = $this->productModel->getProductsBySellerId($this->sellerId, 5);
            
            // Sales chart data (last 7 days)
            $salesData = $this->getSalesChartData();
            
            // Top products
            $topProducts = $this->getTopProducts(5);

            $this->view('seller/dashboard/index', [
                'title' => 'Seller Dashboard',
                'stats' => $stats,
                'recentOrders' => $recentOrders,
                'recentProducts' => $recentProducts,
                'salesData' => $salesData,
                'topProducts' => $topProducts,
                'seller' => $this->sellerData
            ]);
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
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $db = \App\Core\Database::getInstance();
            $result = $db->query(
                "SELECT COALESCE(SUM(oi.total), 0) as total 
                 FROM orders o
                 INNER JOIN order_items oi ON o.id = oi.order_id
                 WHERE oi.seller_id = ? 
                   AND DATE(o.created_at) = ? 
                   AND o.payment_status = 'paid'
                   AND o.status != 'cancelled'",
                [$this->sellerId, $date]
            )->single();
            $data[] = [
                'date' => $date,
                'total' => (float)($result['total'] ?? 0)
            ];
        }
        return $data;
    }

    /**
     * Get top products
     */
    private function getTopProducts($limit = 5)
    {
        $db = \App\Core\Database::getInstance();
        return $db->query(
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
    }
}

