<?php

namespace App\Controllers\Payment;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Database;
use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;
use App\Core\Security;
use Exception;

/**
 * Base controller for payment gateways
 * Contains shared functionality for all payment methods
 */
abstract class PaymentBaseController extends Controller
{
    protected $gatewayModel;
    protected $orderModel;
    protected $productModel;
    protected $cartModel;
    protected $gatewayTransactionModel;
    protected $security;
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->gatewayModel = new \App\Models\PaymentGateway();
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->cartModel = new Cart();
        $this->gatewayTransactionModel = new \App\Models\GatewayTransaction();
        $this->security = Security::getInstance();
        $this->db = Database::getInstance();
        
        // Set security headers
        $this->security->setSecurityHeaders();
    }

    /**
     * Extract gateway parameters
     */
    protected function extractGatewayParameters($gateway)
    {
        $parameters = $gateway['parameters'] ?? [];
        if (is_string($parameters)) {
            $decoded = json_decode($parameters, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $parameters = $decoded;
            }
        }
        return is_array($parameters) ? $parameters : [];
    }

    /**
     * Get JSON input
     */
    protected function getJsonInput()
    {
        $rawInput = file_get_contents('php://input');
        return json_decode($rawInput, true) ?? [];
    }
    
    /**
     * Log security events
     */
    protected function logSecurityEvent($traceId, $action, $status, $data = [])
    {
        $this->db->query(
            "INSERT INTO payment_security_logs (trace_id, user_id, action, status, ip_address, user_agent, request_data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $traceId,
                Session::get('user_id'),
                $action,
                $status,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($data)
            ]
        );
    }

    /**
     * Clear cart and coupon after successful payment
     */
    protected function clearCartAndCoupon($userId)
    {
        if (!empty($userId) && is_numeric($userId)) {
            $this->cartModel->clearCart($userId);
        }
        if (isset($_SESSION['applied_coupon'])) {
            unset($_SESSION['applied_coupon']);
        }
    }

    /**
     * Send post-purchase email
     */
    protected function sendPostPurchaseEmail($orderId)
    {
        try {
            $emailService = new \App\Services\EmailAutomationService();
            $emailService->sendPostPurchaseEmail($orderId);
        } catch (Exception $e) {
            error_log('Post-purchase email error: ' . $e->getMessage());
        }
    }
}






