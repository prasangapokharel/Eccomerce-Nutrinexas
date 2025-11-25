<?php

namespace App\Controllers\Curior;

use App\Models\Order;

class Orders extends BaseCuriorController
{
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
    }

    /**
     * List all assigned orders with filters
     */
    public function index()
    {
        $status = $_GET['status'] ?? null;
        $sort = $_GET['sort'] ?? 'newest';
        
        $orders = $this->orderModel->getOrdersByCurior($this->curiorId);
        
        if ($status) {
            $orders = array_filter($orders, function($order) use ($status) {
                return $order['status'] === $status;
            });
        }
        
        if ($sort === 'newest') {
            usort($orders, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        } elseif ($sort === 'oldest') {
            usort($orders, function($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });
        }
        
        $this->view('curior/orders/index', [
            'orders' => array_values($orders),
            'status' => $status,
            'sort' => $sort,
            'page' => 'orders',
            'title' => 'Assigned Orders'
        ]);
    }
}

