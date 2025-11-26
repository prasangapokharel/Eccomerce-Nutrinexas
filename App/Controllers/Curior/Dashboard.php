<?php

namespace App\Controllers\Curior;

use App\Models\Order;

class Dashboard extends BaseCuriorController
{
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
    }

    /**
     * Curior dashboard
     */
    public function index()
    {
        $allOrders = $this->orderModel->getOrdersByCurior($this->curiorId);
        $stats = $this->orderModel->getCuriorStats($this->curiorId);
        
        $today = date('Y-m-d');
        $todayOrders = array_filter($allOrders, function($order) use ($today) {
            return date('Y-m-d', strtotime($order['created_at'])) === $today;
        });
        
        $pendingPickups = array_filter($allOrders, function($order) {
            return in_array($order['status'], ['processing', 'confirmed', 'shipped', 'ready_for_pickup']);
        });
        
        $inProgress = array_filter($allOrders, function($order) {
            return in_array($order['status'], ['picked_up', 'in_transit', 'shipped']);
        });
        
        $completed = array_filter($allOrders, function($order) {
            return $order['status'] === 'delivered';
        });
        
        $codPending = 0;
        foreach ($allOrders as $order) {
            if ($order['payment_method_id'] == 1 && $order['payment_status'] === 'pending' && $order['status'] !== 'delivered') {
                $codPending += $order['total_amount'] ?? 0;
            }
        }
        
        $this->view('curior/dashboard/index', [
            'orders' => $allOrders,
            'todayOrders' => array_values($todayOrders),
            'pendingPickups' => array_values($pendingPickups),
            'inProgress' => array_values($inProgress),
            'completed' => array_values($completed),
            'codPending' => $codPending,
            'stats' => $stats,
            'curior' => $this->curiorData,
            'page' => 'dashboard',
            'title' => 'Courier Dashboard'
        ]);
    }

    /**
     * Get dashboard stats (AJAX)
     */
    public function getStats()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $stats = $this->orderModel->getCuriorStats($this->curiorId);
        
        $this->jsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
    }
}

