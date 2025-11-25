<?php

namespace App\Controllers\Curior;

use App\Models\Order;
use App\Core\Database;

class Returns extends BaseCuriorController
{
    private $orderModel;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->db = Database::getInstance();
    }

    /**
     * Returns & RTO page
     */
    public function index()
    {
        $sql = "SELECT o.*, 
                       o.customer_name as order_customer_name,
                       CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                       u.email as customer_email,
                       pm.name as payment_method
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE o.curior_id = ? 
                AND o.status IN ('return_requested', 'return_picked_up', 'return_in_transit', 'returned')
                ORDER BY o.created_at DESC";
        
        $orders = $this->db->query($sql, [$this->curiorId])->all();
        
        $this->view('curior/returns/index', [
            'orders' => $orders,
            'page' => 'returns',
            'title' => 'Returns & RTO'
        ]);
    }
}

