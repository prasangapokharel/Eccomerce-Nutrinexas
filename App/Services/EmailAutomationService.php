<?php

namespace App\Services;

use App\Helpers\EmailHelper;
use App\Models\User;
use App\Models\Order;
use Exception;

/**
 * Email Automation Service
 * Handles welcome series, post-purchase, and win-back emails
 */
class EmailAutomationService
{
    private $userModel;
    private $orderModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->orderModel = new Order();
    }

    /**
     * Send welcome email series
     */
    public function sendWelcomeEmail(int $userId): bool
    {
        try {
            $user = $this->userModel->find($userId);
            if (!$user || empty($user['email'])) {
                return false;
            }

            // TODO: Implement email sending
            // For now, log the action
            error_log("Welcome email queued for user #{$userId} ({$user['email']})");
            return true;
        } catch (Exception $e) {
            error_log("Welcome email error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send post-purchase email
     */
    public function sendPostPurchaseEmail(int $orderId): bool
    {
        try {
            $order = $this->orderModel->getOrderWithDetails($orderId);
            if (!$order) {
                error_log("Post-purchase email skipped: order #{$orderId} not found");
                return false;
            }

            $recipientEmail = $this->resolveRecipientEmail($order);
            if (!$recipientEmail) {
                error_log("Post-purchase email skipped: no email for order #{$orderId}");
                return false;
            }

            $orderItems = $order['items'] ?? [];
            if (empty($orderItems)) {
                $orderItems = $this->orderModel->getOrderItems($orderId);
            }

            $userData = $this->buildRecipientData($order, $recipientEmail);

            $emailSent = EmailHelper::sendOrderConfirmation($order, $orderItems, $userData);

            if ($emailSent) {
                error_log("Post-purchase email sent for order #{$orderId} ({$recipientEmail})");
                return true;
            }

            error_log("Post-purchase email failed for order #{$orderId}");
            return false;
        } catch (Exception $e) {
            error_log("Post-purchase email error: " . $e->getMessage());
            return false;
        }
    }

    private function resolveRecipientEmail(array $order): ?string
    {
        $candidates = [
            $order['customer_email'] ?? null,
            $order['user_email'] ?? null
        ];

        if (!empty($order['user_id'])) {
            $user = $this->userModel->find($order['user_id']);
            if ($user && !empty($user['email'])) {
                $candidates[] = $user['email'];
            }
        }

        foreach ($candidates as $email) {
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }

    private function buildRecipientData(array $order, string $email): array
    {
        $fullName = trim($order['customer_name'] ?? $order['user_full_name'] ?? 'Customer');
        $parts = preg_split('/\s+/', $fullName, 2);

        return [
            'email' => $email,
            'first_name' => $parts[0] ?? 'Customer',
            'last_name' => $parts[1] ?? ''
        ];
    }

    /**
     * Send win-back email to inactive users
     */
    public function sendWinBackEmail(int $userId): bool
    {
        try {
            $user = $this->userModel->find($userId);
            if (!$user || empty($user['email'])) {
                return false;
            }

            // Check last order date
            $orders = $this->orderModel->getOrdersByUserId($userId);
            if (!empty($orders)) {
                $lastOrder = $orders[0]; // Most recent order
                $daysSinceLastOrder = (time() - strtotime($lastOrder['created_at'])) / (60 * 60 * 24);
                if ($daysSinceLastOrder < 30) {
                    return false; // Too recent, not inactive
                }
            }

            // TODO: Implement email sending
            error_log("Win-back email queued for user #{$userId}");
            return true;
        } catch (Exception $e) {
            error_log("Win-back email error: " . $e->getMessage());
            return false;
        }
    }
}

