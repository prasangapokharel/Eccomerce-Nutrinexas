<?php

namespace App\Controllers\Notification;

use App\Core\Controller;
use App\Core\Database;
use Exception;

class NotificationCourierController extends Controller
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Notify courier about order assignment
     * 
     * @param int $courierId Courier ID
     * @param int $orderId Order ID
     * @return bool
     */
    public function notifyOrderAssigned($courierId, $orderId)
    {
        try {
            $order = $this->db->query(
                "SELECT invoice, customer_name, address FROM orders WHERE id = ?",
                [$orderId]
            )->single();

            if (!$order) {
                return false;
            }

            $orderInvoice = $order['invoice'] ?? '#' . $orderId;
            $customerName = $order['customer_name'] ?? 'Customer';
            $address = $order['address'] ?? '';

            $message = "New order {$orderInvoice} has been assigned to you.\n\n";
            $message .= "Customer: {$customerName}\n";
            if (!empty($address)) {
                $message .= "Address: {$address}";
            }

            $this->createOrderActivity(
                $orderId,
                'order_assigned',
                $message,
                $courierId
            );

            return true;
        } catch (Exception $e) {
            error_log("NotificationCourierController: Error creating order assignment notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify courier about order status update
     * 
     * @param int $courierId Courier ID
     * @param int $orderId Order ID
     * @param string $status New order status
     * @param string $message Additional message
     * @return bool
     */
    public function notifyOrderStatusUpdate($courierId, $orderId, $status, $message = '')
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

            $statusMessages = [
                'picked_up' => "Order {$orderInvoice} has been picked up.",
                'in_transit' => "Order {$orderInvoice} is now in transit.",
                'delivery_attempted' => "Delivery attempt made for order {$orderInvoice}.",
                'delivered' => "Order {$orderInvoice} has been delivered successfully.",
                'returned' => "Order {$orderInvoice} has been returned."
            ];

            $notificationMessage = $statusMessages[$status] ?? "Order {$orderInvoice} status updated to {$status}.";
            
            if (!empty($message)) {
                $notificationMessage .= "\n\n{$message}";
            }

            $this->createOrderActivity(
                $orderId,
                'order_status_update',
                $notificationMessage,
                $courierId
            );

            return true;
        } catch (Exception $e) {
            error_log("NotificationCourierController: Error creating order status notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify courier about urgent delivery
     * 
     * @param int $courierId Courier ID
     * @param int $orderId Order ID
     * @return bool
     */
    public function notifyUrgentDelivery($courierId, $orderId)
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
            $message = "⚠️ URGENT: Order {$orderInvoice} requires immediate delivery attention.";

            $this->createOrderActivity(
                $orderId,
                'urgent_delivery',
                $message,
                $courierId
            );

            return true;
        } catch (Exception $e) {
            error_log("NotificationCourierController: Error creating urgent delivery notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify courier about COD reminder
     * 
     * @param int $courierId Courier ID
     * @param int $orderId Order ID
     * @param float $amount COD amount
     * @return bool
     */
    public function notifyCodReminder($courierId, $orderId, $amount)
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
            $formattedAmount = 'रु ' . number_format($amount, 2);
            $message = "COD Reminder: Order {$orderInvoice} requires cash collection of {$formattedAmount} on delivery.";

            $this->createOrderActivity(
                $orderId,
                'cod_reminder',
                $message,
                $courierId
            );

            return true;
        } catch (Exception $e) {
            error_log("NotificationCourierController: Error creating COD reminder notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create order activity notification for courier
     * 
     * @param int $orderId
     * @param string $action
     * @param string $message
     * @param int $courierId
     * @return bool
     */
    private function createOrderActivity($orderId, $action, $message, $courierId)
    {
        try {
            $activityModel = new \App\Models\Curior\OrderActivity();
            $activityModel->logEntry($orderId, $action, $message, 'curior_' . $courierId);
            return true;
        } catch (Exception $e) {
            error_log("NotificationCourierController: Error creating order activity: " . $e->getMessage());
            return false;
        }
    }
}

