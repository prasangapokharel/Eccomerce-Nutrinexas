<?php

namespace App\Controllers\Notification;

use App\Core\Controller;
use App\Core\Database;
use Exception;

class NotificationCustomerController extends Controller
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Notify customer about order status change
     * 
     * @param int $userId Customer user ID
     * @param int $orderId Order ID
     * @param string $oldStatus Previous order status
     * @param string $newStatus New order status
     * @return bool
     */
    public function notifyOrderStatusChange($userId, $orderId, $oldStatus, $newStatus)
    {
        try {
            // Only send notifications for: processing, shipped, delivered
            $allowedStatuses = ['processing', 'shipped', 'delivered'];
            if (!in_array($newStatus, $allowedStatuses)) {
                return false;
            }

            // Get order details
            $order = $this->db->query(
                "SELECT invoice, customer_name FROM orders WHERE id = ?",
                [$orderId]
            )->single();

            if (!$order) {
                return false;
            }

            $orderInvoice = $order['invoice'] ?? 'NTX' . $orderId;
            $customerName = $order['customer_name'] ?? 'Customer';

            $messages = [
                'processing' => "Your order #{$orderInvoice} is now being processed.",
                'shipped' => "Your order #{$orderInvoice} has been shipped and is on its way.",
                'delivered' => "Your order #{$orderInvoice} has been delivered successfully."
            ];

            $titles = [
                'processing' => "Order Processing",
                'shipped' => "Order Shipped",
                'delivered' => "Order Delivered"
            ];

            $icons = [
                'processing' => 'fas fa-cog',
                'shipped' => 'fas fa-shipping-fast',
                'delivered' => 'fas fa-check-double'
            ];

            $message = $messages[$newStatus] ?? "Your order #{$orderInvoice} status has been updated to {$newStatus}.";
            $title = $titles[$newStatus] ?? "Order Status Updated";
            $icon = $icons[$newStatus] ?? 'fas fa-bell';

            $this->createNotification(
                $userId,
                'order_status_change',
                $title,
                $message,
                'orders/view/' . $orderId,
                $icon
            );

            return true;
        } catch (Exception $e) {
            error_log("NotificationCustomerController: Error creating order status notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify customer about payment status change
     * 
     * @param int $userId Customer user ID
     * @param int $orderId Order ID
     * @param string $oldStatus Previous payment status
     * @param string $newStatus New payment status
     * @return bool
     */
    public function notifyPaymentStatusChange($userId, $orderId, $oldStatus, $newStatus)
    {
        try {
            $order = $this->db->query(
                "SELECT invoice FROM orders WHERE id = ?",
                [$orderId]
            )->single();

            if (!$order) {
                return false;
            }

            $orderInvoice = $order['invoice'] ?? '#' . $orderId;

            $messages = [
                'paid' => "Payment for order {$orderInvoice} has been confirmed.",
                'pending' => "Payment for order {$orderInvoice} is pending.",
                'failed' => "Payment for order {$orderInvoice} has failed. Please try again.",
                'refunded' => "Payment for order {$orderInvoice} has been refunded."
            ];

            $titles = [
                'paid' => "Payment Confirmed",
                'pending' => "Payment Pending",
                'failed' => "Payment Failed",
                'refunded' => "Payment Refunded"
            ];

            $message = $messages[$newStatus] ?? "Payment status for order {$orderInvoice} has been updated to {$newStatus}.";
            $title = $titles[$newStatus] ?? "Payment Status Updated";

            $this->createNotification(
                $userId,
                'payment_status_change',
                $title,
                $message,
                'orders/view/' . $orderId,
                'fas fa-credit-card'
            );

            return true;
        } catch (Exception $e) {
            error_log("NotificationCustomerController: Error creating payment status notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create notification for customer
     * 
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string $link
     * @param string $icon
     * @return bool
     */
    private function createNotification($userId, $type, $title, $message, $link = '', $icon = 'fas fa-bell')
    {
        try {
            $columns = ['user_id', 'type', 'title', 'message', 'created_at'];
            $values = [$userId, $type, $title, $message];
            
            if (!empty($link)) {
                $columns[] = 'link';
                $values[] = $link;
            }
            
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            $columnList = implode(', ', $columns);
            
            $this->db->query(
                "INSERT INTO notifications ({$columnList}) 
                 VALUES ({$placeholders})",
                $values
            )->execute();

            return true;
        } catch (Exception $e) {
            error_log("NotificationCustomerController: Error creating notification: " . $e->getMessage());
            return false;
        }
    }
}

