<?php

namespace App\Controllers\Curior;

use App\Models\Order;
use App\Models\Curior\OrderActivity;
use App\Core\Database;

class History extends BaseCuriorController
{
    private $orderModel;
    private $activityModel;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->activityModel = new OrderActivity();
        $this->db = Database::getInstance();
    }

    /**
     * Log / History page
     */
    public function index()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $status = $_GET['status'] ?? null;
        $action = $_GET['action'] ?? null;

        $sql = "SELECT o.*, 
                       o.customer_name as order_customer_name,
                       CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                       u.email as customer_email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.curior_id = ? 
                AND DATE(o.created_at) BETWEEN ? AND ?";
        
        $params = [$this->curiorId, $dateFrom, $dateTo];
        
        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $orders = $this->db->query($sql, $params)->all();
        
        $sql = "SELECT oa.*, o.invoice, o.customer_name 
                FROM order_activities oa
                LEFT JOIN orders o ON oa.order_id = o.id
                WHERE oa.created_by = ?
                AND DATE(oa.created_at) BETWEEN ? AND ?";
        
        $activityParams = ['curior_' . $this->curiorId, $dateFrom, $dateTo];
        
        if ($action) {
            $sql .= " AND oa.action = ?";
            $activityParams[] = $action;
        }
        
        $sql .= " ORDER BY oa.created_at DESC";
        
        $activities = $this->db->query($sql, $activityParams)->all();
        
        $this->view('curior/history/index', [
            'orders' => $orders,
            'activities' => $activities,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'status' => $status,
            'action' => $action,
            'page' => 'history',
            'title' => 'Order History & Logs'
        ]);
    }
}

