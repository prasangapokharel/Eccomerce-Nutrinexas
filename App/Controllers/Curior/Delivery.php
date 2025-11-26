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
        
        // Get orders that are picked up or in transit (ready for delivery)
        $orders = $this->orderModel->getOrdersByCurior($this->curiorId);
        
        // Filter for delivery-ready orders
        $deliveryOrders = array_filter($orders, function($order) use ($status) {
            $orderStatus = $order['status'];
            // Show picked_up, in_transit orders (ready for delivery)
            if ($status) {
                return $orderStatus === $status;
            }
            return in_array($orderStatus, ['picked_up', 'in_transit']);
        });
        
        // Sort by created_at (newest first)
        usort($deliveryOrders, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        $this->view('curior/delivery/index', [
            'orders' => array_values($deliveryOrders),
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
                $this->notifyOrderUpdate($orderId, 'out_for_delivery', $activityData);
                
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

    private function notifyOrderUpdate($orderId, $action, $data = [])
    {
        try {
            $notificationService = new \App\Services\OrderNotificationService();
            $notificationService->sendStatusChangeSMS($orderId, $action, $data);
        } catch (\Exception $e) {
            error_log('Delivery: Error sending notification: ' . $e->getMessage());
        }
    }
    
}

