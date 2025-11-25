<?php

namespace App\Controllers\Curior;

use App\Models\Order;
use App\Models\Curior\CourierSettlement as SettlementModel;
use App\Models\Curior\CourierLocation as LocationModel;
use App\Core\Database;

class Performance extends BaseCuriorController
{
    private $orderModel;
    private $settlementModel;
    private $locationModel;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->settlementModel = new SettlementModel();
        $this->locationModel = new LocationModel();
        $this->db = Database::getInstance();
    }

    /**
     * Performance dashboard
     */
    public function index()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $stats = $this->getPerformanceStats($dateFrom, $dateTo);
        
        $this->view('curior/performance/index', [
            'stats' => $stats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'title' => 'Performance Report'
        ]);
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStats($dateFrom, $dateTo)
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_orders
                FROM orders 
                WHERE curior_id = ? 
                AND DATE(created_at) BETWEEN ? AND ?";
        
        $orderStats = $this->db->query($sql, [$this->curiorId, $dateFrom, $dateTo])->single();
        
        $sql = "SELECT COUNT(*) as failed_attempts
                FROM order_activities 
                WHERE action = 'delivery_attempted' 
                AND created_by = ?
                AND DATE(created_at) BETWEEN ? AND ?";
        
        $failedAttempts = $this->db->query($sql, ['curior_' . $this->curiorId, $dateFrom, $dateTo])->single();
        
        $codCollected = $this->settlementModel->getTotalCollected($this->curiorId, $dateFrom, $dateTo);
        
        $sql = "SELECT COUNT(*) as return_pickups
                FROM order_activities 
                WHERE action IN ('return_picked_up', 'return_in_transit', 'returned')
                AND created_by = ?
                AND DATE(created_at) BETWEEN ? AND ?";
        
        $returnPickups = $this->db->query($sql, ['curior_' . $this->curiorId, $dateFrom, $dateTo])->single();
        
        $deliveryAccuracy = 0;
        if ($orderStats['total_orders'] > 0) {
            $deliveryAccuracy = ($orderStats['delivered_orders'] / $orderStats['total_orders']) * 100;
        }
        
        return [
            'total_orders' => $orderStats['total_orders'] ?? 0,
            'delivered_orders' => $orderStats['delivered_orders'] ?? 0,
            'cancelled_orders' => $orderStats['cancelled_orders'] ?? 0,
            'returned_orders' => $orderStats['returned_orders'] ?? 0,
            'failed_attempts' => $failedAttempts['failed_attempts'] ?? 0,
            'cod_collected' => $codCollected,
            'return_pickups' => $returnPickups['return_pickups'] ?? 0,
            'delivery_accuracy' => round($deliveryAccuracy, 2)
        ];
    }

    /**
     * Get performance data (AJAX)
     */
    public function getData()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $stats = $this->getPerformanceStats($dateFrom, $dateTo);
        
        $this->jsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
    }
}

