<?php

namespace App\Controllers\Security;

use App\Core\Controller;
use App\Core\Database;
use Exception;

class SecurityController extends Controller
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Check if in development mode
     */
    private function isDevelopmentMode()
    {
        if (defined('DEVELOPMENT') && DEVELOPMENT === true) {
            return true;
        }
        if (defined('APP_ENV')) {
            $appEnv = constant('APP_ENV');
            if ($appEnv === 'development') {
                return true;
            }
        }
        if (defined('ENVIRONMENT')) {
            $env = constant('ENVIRONMENT');
            if ($env === 'development') {
                return true;
            }
        }
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
            return true;
        }
        if (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'development') {
            return true;
        }
        if (defined('DEBUG') && DEBUG === true) {
            return true;
        }
        return false;
    }

    /**
     * Detect fraud in order
     */
    public function detectFraudOrder($orderData, $userId)
    {
        // Skip fraud detection in development mode
        if ($this->isDevelopmentMode()) {
            return [
                'is_fraud' => false,
                'score' => 0,
                'indicators' => [],
                'trace_id' => $this->generateTraceId()
            ];
        }

        $fraudScore = 0;
        $indicators = [];
        $traceId = $this->generateTraceId();

        // Check 1: Unusual order amount (increased threshold for development)
        $orderAmount = (float)($orderData['total_amount'] ?? 0);
        if ($orderAmount > 100000) {
            $fraudScore += 30;
            $indicators[] = 'unusual_high_amount';
        }

        // Check 2: Multiple orders in short time (more lenient)
        $recentOrders = $this->getRecentOrderCount($userId, 300);
        if ($recentOrders > 10) {
            $fraudScore += 25;
            $indicators[] = 'rapid_order_creation';
        }

        // Check 3: IP mismatch with user location (disabled in development)
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->isDevelopmentMode() && $this->isSuspiciousIP($ipAddress, $userId)) {
            $fraudScore += 20;
            $indicators[] = 'suspicious_ip';
        }

        // Check 4: Unusual product quantity
        $items = $orderData['items'] ?? [];
        foreach ($items as $item) {
            if (($item['quantity'] ?? 0) > 100) {
                $fraudScore += 15;
                $indicators[] = 'unusual_quantity';
                break;
            }
        }

        // Check 5: Payment method mismatch
        $paymentMethod = $orderData['payment_method'] ?? '';
        if ($paymentMethod === 'cod' && $orderAmount > 50000) {
            $fraudScore += 20;
            $indicators[] = 'high_cod_amount';
        }

        // Check 6: Velocity check (more lenient)
        if ($this->checkVelocity($userId)) {
            $fraudScore += 25;
            $indicators[] = 'velocity_abuse';
        }

        $isFraud = $fraudScore >= 50;
        
        $this->logTransaction([
            'trace_id' => $traceId,
            'user_id' => $userId,
            'action' => 'fraud_detection',
            'order_id' => $orderData['order_id'] ?? null,
            'fraud_score' => $fraudScore,
            'is_fraud' => $isFraud ? 1 : 0,
            'indicators' => json_encode($indicators),
            'ip_address' => $ipAddress,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_data' => json_encode($orderData)
        ]);

        if ($isFraud) {
            $this->logSecurityEvent($traceId, 'fraud_detected', 'blocked', [
                'user_id' => $userId,
                'fraud_score' => $fraudScore,
                'indicators' => $indicators,
                'order_id' => $orderData['order_id'] ?? null
            ]);
        }

        return [
            'is_fraud' => $isFraud,
            'score' => $fraudScore,
            'indicators' => $indicators,
            'trace_id' => $traceId
        ];
    }

    /**
     * Secure payment processing layer
     */
    public function processSecurePayment($paymentData, $userId)
    {
        $traceId = $this->generateTraceId();
        $isDev = $this->isDevelopmentMode();
        
        // Step 1: Fraud detection (skipped in development)
        $fraudCheck = $this->detectFraudOrder([
            'order_id' => $paymentData['order_id'] ?? null,
            'total_amount' => $paymentData['amount'] ?? 0,
            'payment_method' => $paymentData['method'] ?? '',
            'items' => $paymentData['items'] ?? []
        ], $userId);

        if (!$isDev && $fraudCheck['is_fraud']) {
            throw new Exception('Payment blocked: Fraud detected. Score: ' . $fraudCheck['score']);
        }

        // Step 2: Validate payment amount (always validate, even in development)
        if (!isset($paymentData['amount']) || $paymentData['amount'] <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // Step 3: Check duplicate transaction (skipped in development)
        if (!$isDev && $this->isDuplicateTransaction($paymentData, $userId)) {
            throw new Exception('Duplicate transaction detected');
        }

        // Step 4: Rate limiting (disabled in development)
        if (!$isDev && !$this->checkRateLimit("payment_{$userId}")) {
            throw new Exception('Rate limit exceeded. Please try again later.');
        }

        // Step 5: Log transaction
        $transactionId = $this->logTransaction([
            'trace_id' => $traceId,
            'user_id' => $userId,
            'action' => 'payment_initiated',
            'order_id' => $paymentData['order_id'] ?? null,
            'amount' => $paymentData['amount'],
            'payment_method' => $paymentData['method'] ?? 'unknown',
            'status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_data' => json_encode($paymentData)
        ]);

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'trace_id' => $traceId
        ];
    }

    /**
     * Log transaction
     */
    public function logTransaction($data)
    {
        $sql = "INSERT INTO security_transactions 
                (trace_id, user_id, action, order_id, amount, payment_method, fraud_score, is_fraud, indicators, status, ip_address, user_agent, request_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $this->db->query($sql, [
            $data['trace_id'] ?? $this->generateTraceId(),
            $data['user_id'] ?? null,
            $data['action'] ?? 'unknown',
            $data['order_id'] ?? null,
            $data['amount'] ?? null,
            $data['payment_method'] ?? null,
            $data['fraud_score'] ?? null,
            $data['is_fraud'] ?? 0,
            $data['indicators'] ?? null,
            $data['status'] ?? 'pending',
            $data['ip_address'] ?? '',
            $data['user_agent'] ?? '',
            $data['request_data'] ?? null
        ])->execute();

        return $this->db->lastInsertId();
    }

    /**
     * Log security event
     */
    public function logSecurityEvent($traceId, $action, $status, $data = [])
    {
        $sql = "INSERT INTO security_events 
                (trace_id, action, status, ip_address, user_agent, request_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $this->db->query($sql, [
            $traceId,
            $action,
            $status,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            json_encode($data)
        ]);
    }

    /**
     * Get recent order count
     */
    private function getRecentOrderCount($userId, $seconds = 300)
    {
        $sql = "SELECT COUNT(*) as count FROM orders 
                WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        $result = $this->db->query($sql, [$userId, $seconds])->single();
        return (int)($result['count'] ?? 0);
    }

    /**
     * Check if IP is suspicious
     */
    private function isSuspiciousIP($ipAddress, $userId)
    {
        $sql = "SELECT COUNT(DISTINCT user_id) as count FROM security_transactions 
                WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND user_id != ?";
        $result = $this->db->query($sql, [$ipAddress, $userId])->single();
        return (int)($result['count'] ?? 0) > 3;
    }

    /**
     * Check velocity (too many actions in short time)
     */
    private function checkVelocity($userId)
    {
        if ($this->isDevelopmentMode()) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as count FROM security_transactions 
                WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        $result = $this->db->query($sql, [$userId])->single();
        return (int)($result['count'] ?? 0) > 10;
    }

    /**
     * Check for duplicate transaction
     */
    private function isDuplicateTransaction($paymentData, $userId)
    {
        if ($this->isDevelopmentMode()) {
            return false;
        }
        
        $orderId = $paymentData['order_id'] ?? null;
        $amount = $paymentData['amount'] ?? 0;
        
        if (!$orderId) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM security_transactions 
                WHERE user_id = ? AND order_id = ? AND amount = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $result = $this->db->query($sql, [$userId, $orderId, $amount])->single();
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Rate limiting check (disabled in development)
     */
    public function checkRateLimit($key)
    {
        if ($this->isDevelopmentMode()) {
            return true;
        }
        
        $sql = "SELECT COUNT(*) as count FROM rate_limits 
                WHERE rate_key = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        $result = $this->db->query($sql, [$key])->single();
        $count = (int)($result['count'] ?? 0);

        if ($count >= 20) {
            return false;
        }

        $this->db->query(
            "INSERT INTO rate_limits (rate_key, created_at) VALUES (?, NOW())",
            [$key]
        );

        return true;
    }

    /**
     * Generate trace ID
     */
    private function generateTraceId()
    {
        return bin2hex(random_bytes(16));
    }

}

