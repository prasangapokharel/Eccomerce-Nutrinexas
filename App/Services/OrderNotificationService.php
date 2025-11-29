<?php

namespace App\Services;

use App\Controllers\Sms\SMSController;
use App\Models\Order;
use Exception;

/**
 * Order Notification Service
 * Handles SMS and email notifications for order status changes
 */
class OrderNotificationService
{
    private $smsController;
    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->smsController = $this->isSMSEnabled() ? new SMSController() : null;
    }

    private function isSMSEnabled(): bool
    {
        if (!defined('SMS_STATUS')) {
            return false;
        }
        return SMS_STATUS === 'enable';
    }

    /**
     * Send SMS notification when order status changes
     * 
     * @param int $orderId
     * @param string $oldStatus
     * @param string $newStatus
     * @return array
     */
    public function sendStatusChangeSMS(int $orderId, string $oldStatus, string $newStatus): array
    {
        if (!$this->isSMSEnabled()) {
            error_log("SMS disabled: SMS_STATUS is not 'enable' for order #{$orderId}");
            return [
                'success' => false,
                'message' => 'SMS notifications are disabled'
            ];
        }

        try {
            if ($this->smsController === null) {
                $this->smsController = new SMSController();
            }

            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $rawPhoneNumber = $order['contact_no'] ?? $order['user_phone'] ?? null;
            if (empty($rawPhoneNumber)) {
                return ['success' => false, 'message' => 'No phone number found for order'];
            }

            $phoneNumber = $this->formatPhoneNumber($rawPhoneNumber);
            if (!$phoneNumber) {
                return ['success' => false, 'message' => 'Invalid phone number format: ' . $rawPhoneNumber];
            }

            $message = $this->generateStatusMessage($order, $newStatus);
            $result = $this->smsController->sendBirSMS($phoneNumber, $message);

            if ($result['success']) {
                error_log("Order Status SMS sent to {$phoneNumber} for order #{$orderId}");
                return [
                    'success' => true,
                    'message' => 'SMS notification sent successfully',
                    'phone' => $phoneNumber
                ];
            }

            error_log("Failed to send SMS for order #{$orderId}: " . ($result['message'] ?? 'Unknown error'));
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to send SMS'
            ];

        } catch (Exception $e) {
            error_log("OrderNotificationService Error for order #{$orderId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error sending notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to BirSMS format (98XXXXXXXX)
     */
    private function formatPhoneNumber(string $phone): ?string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Already in 97/98 format
        if (strlen($phone) === 10 && preg_match('/^9[78]\d{8}$/', $phone)) {
            return $phone;
        }

        // Remove leading 0 (e.g. 097XXXXXXXX)
        if (strlen($phone) === 11 && $phone[0] === '0') {
            $phone = substr($phone, 1);
        }

        // Remove country code 977 (e.g. 97797XXXXXXXX)
        if (strlen($phone) === 13 && substr($phone, 0, 3) === '977') {
            $phone = substr($phone, 3);
        }

        // After normalization ensure we have 10-digit Nepal mobile starting with 97/98
        if (strlen($phone) === 10 && preg_match('/^9[78]\d{8}$/', $phone)) {
            return $phone;
        }

        return null;
    }

    /**
     * Generate professional SMS message for order status
     * Clean, professional, no emojis
     */
    private function generateStatusMessage(array $order, string $status): string
    {
        $invoice = $order['invoice'] ?? 'NTX' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        $customerName = $order['customer_name'] ?? $order['order_customer_name'] ?? 'Customer';
        $totalAmount = number_format($order['total_amount'] ?? 0, 2);
        
        $messages = [
            'pending' => "Dear {$customerName}, your order #{$invoice} has been received and is being processed. Total: Rs {$totalAmount}. Thank you for shopping with us!",
            
            'processing' => "Dear {$customerName}, your order #{$invoice} is now being processed. We will notify you once it's ready for dispatch. Thank you!",
            
            'dispatched' => "Dear {$customerName}, your order #{$invoice} has been dispatched. You will receive it soon. Track your order on our website. Thank you!",
            
            'shipped' => "Dear {$customerName}, your order #{$invoice} has been shipped. Expected delivery soon. Thank you for your patience!",
            
            'delivered' => "Dear {$customerName}, your order #{$invoice} has been delivered. We hope you enjoy your purchase! Thank you for choosing us.",
            
            'cancelled' => "Dear {$customerName}, your order #{$invoice} has been cancelled. If you have any questions, please contact our support team.",
            
            'paid' => "Dear {$customerName}, payment for order #{$invoice} has been confirmed. Your order is being processed. Thank you!",
            
            'unpaid' => "Dear {$customerName}, payment for order #{$invoice} is pending. Please complete the payment to proceed with your order."
        ];

        return $messages[strtolower($status)] ?? "Dear {$customerName}, your order #{$invoice} status has been updated to " . ucfirst($status) . ". Thank you!";
    }

    /**
     * Send SMS notification when payment status changes
     * 
     * @param int $orderId
     * @param string $oldStatus
     * @param string $newStatus
     * @return array
     */
    public function sendPaymentStatusChangeSMS(int $orderId, string $oldStatus, string $newStatus): array
    {
        if (!$this->isSMSEnabled()) {
            error_log("SMS disabled: SMS_STATUS is not 'enable' for payment status change on order #{$orderId}");
            return [
                'success' => false,
                'message' => 'SMS notifications are disabled'
            ];
        }

        try {
            if ($this->smsController === null) {
                $this->smsController = new SMSController();
            }

            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }

            $phoneNumber = $order['contact_no'] ?? $order['user_phone'] ?? null;
            if (empty($phoneNumber)) {
                return ['success' => false, 'message' => 'No phone number found for order'];
            }

            $phoneNumber = $this->formatPhoneNumber($phoneNumber);
            if (!$phoneNumber) {
                return ['success' => false, 'message' => 'Invalid phone number format'];
            }

            $message = $this->generatePaymentStatusMessage($order, $newStatus);
            $result = $this->smsController->sendBirSMS($phoneNumber, $message);

            if ($result['success']) {
                error_log("Payment Status SMS sent to {$phoneNumber} for order #{$orderId}");
                return [
                    'success' => true,
                    'message' => 'SMS notification sent successfully',
                    'phone' => $phoneNumber
                ];
            }

            error_log("Failed to send payment status SMS for order #{$orderId}: " . ($result['message'] ?? 'Unknown error'));
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to send SMS'
            ];

        } catch (Exception $e) {
            error_log("OrderNotificationService Payment Status Error for order #{$orderId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error sending notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate professional SMS message for payment status
     */
    private function generatePaymentStatusMessage(array $order, string $status): string
    {
        $invoice = $order['invoice'] ?? 'NTX' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        $customerName = $order['customer_name'] ?? $order['order_customer_name'] ?? 'Customer';
        $totalAmount = number_format($order['total_amount'] ?? 0, 2);
        
        $messages = [
            'pending' => "Dear {$customerName}, payment for order #{$invoice} is pending. Amount: Rs {$totalAmount}. Please complete payment to proceed.",
            
            'paid' => "Dear {$customerName}, payment of Rs {$totalAmount} for order #{$invoice} has been confirmed. Your order is being processed. Thank you!",
            
            'failed' => "Dear {$customerName}, payment for order #{$invoice} has failed. Please contact support or try again. Thank you.",
            
            'refunded' => "Dear {$customerName}, payment of Rs {$totalAmount} for order #{$invoice} has been refunded. Refund will be processed to your account. Thank you."
        ];

        return $messages[strtolower($status)] ?? "Dear {$customerName}, payment status for order #{$invoice} has been updated to " . ucfirst($status) . ". Thank you!";
    }
}

