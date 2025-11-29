<?php

namespace App\Controllers\Curior;

use App\Models\Order;
use App\Core\Database;

class Delivery extends BaseCuriorController
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
     * Delivery page - shows orders ready for delivery (picked_up, in_transit)
     */
    public function index()
    {
        $status = $_GET['status'] ?? null;
        
        // Query orders that are picked up or in transit (ready for delivery)
        $sql = "SELECT 
                    o.*,
                    o.customer_name AS order_customer_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS user_full_name,
                    u.email AS customer_email,
                    pm.name AS payment_method,
                    (
                        SELECT COALESCE(oi2.seller_id, p2.seller_id)
                        FROM order_items oi2
                        INNER JOIN products p2 ON oi2.product_id = p2.id
                        WHERE oi2.order_id = o.id
                        ORDER BY oi2.id ASC
                        LIMIT 1
                    ) AS seller_id,
                    (
                        SELECT COALESCE(s.company_name, s.name) 
                        FROM order_items oi2
                        INNER JOIN products p2 ON oi2.product_id = p2.id
                        LEFT JOIN sellers s ON COALESCE(oi2.seller_id, p2.seller_id) = s.id
                        WHERE oi2.order_id = o.id
                        ORDER BY oi2.id ASC
                        LIMIT 1
                    ) AS seller_company,
                    (
                        SELECT s.name 
                        FROM order_items oi2
                        INNER JOIN products p2 ON oi2.product_id = p2.id
                        LEFT JOIN sellers s ON COALESCE(oi2.seller_id, p2.seller_id) = s.id
                        WHERE oi2.order_id = o.id
                        ORDER BY oi2.id ASC
                        LIMIT 1
                    ) AS seller_name
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE o.curior_id = ?
                AND o.curior_id IS NOT NULL";
        
        $params = [$this->curiorId];
        
        // Filter by status
        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        } else {
            // Show picked_up and in_transit orders (ready for delivery)
            $sql .= " AND o.status IN ('picked_up', 'in_transit')";
        }
        
        $sql .= " ORDER BY o.updated_at DESC, o.created_at DESC";
        
        $orders = $this->db->query($sql, $params)->all();
        
        // Debug logging
        error_log("Courier Delivery: Courier #{$this->curiorId} - Found " . count($orders) . " orders for delivery");
        
        $this->view('curior/delivery/index', [
            'orders' => $orders,
            'status' => $status,
            'page' => 'delivery',
            'title' => 'Delivery Management'
        ]);
    }

    /**
     * Update order status to "out for delivery" (in_transit)
     */
    public function outForDelivery()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($orderId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid order ID']);
            return;
        }

        $order = $this->orderModel->find($orderId);
        if (!$order || $order['curior_id'] != $this->curiorId) {
            $this->jsonResponse(['success' => false, 'message' => 'Order not found or not assigned to you']);
            return;
        }

        // Only allow if order is picked_up
        if ($order['status'] !== 'picked_up') {
            $this->jsonResponse(['success' => false, 'message' => 'Order must be picked up first']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $oldStatus = $order['status'];
            
            $updateData = [
                'status' => 'in_transit',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->orderModel->update($orderId, $updateData)) {
                $activityData = [
                    'location' => $location,
                    'notes' => $notes,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $this->logOrderActivity($orderId, 'out_for_delivery', json_encode($activityData));
                $this->notifyOrderUpdate($orderId, 'out_for_delivery', $oldStatus);
                
                $this->db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Order marked as out for delivery']);
            } else {
                $this->db->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update order status']);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Delivery: Out for delivery error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error updating order status']);
        }
    }

    /**
     * Helper methods
     */
    private function logOrderActivity($orderId, $action, $data)
    {
        try {
            $activityModel = new \App\Models\Curior\OrderActivity();
            $activityModel->logEntry($orderId, $action, $data, 'curior_' . $this->curiorId);
        } catch (\Exception $e) {
            error_log('Delivery: Error logging activity: ' . $e->getMessage());
        }
    }

    private function notifyOrderUpdate($orderId, $action, $oldStatus)
    {
        try {
            $newStatus = $this->getStatusFromAction($action);
            
            if ($newStatus && $oldStatus) {
                $notificationService = new \App\Services\OrderNotificationService();
                $notificationService->sendStatusChangeSMS($orderId, $oldStatus, $newStatus);
            }
        } catch (\Exception $e) {
            error_log('Delivery: Error sending notification: ' . $e->getMessage());
        }
    }

    private function getStatusFromAction($action)
    {
        $statusMap = [
            'out_for_delivery' => 'in_transit',
            'delivered' => 'delivered',
            'delivery_attempted' => 'delivery_attempted'
        ];
        
        return $statusMap[$action] ?? null;
    }
    
}

