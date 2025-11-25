<?php
namespace App\Core;

use App\Core\Database;
use App\Models\IdempotencyKey;

/**
 * Advanced Security Class
 * Comprehensive security implementation with fraud detection and rate limiting
 */
class AdvancedSecurity
{
    private static $instance = null;
    private $db;
    private $idempotencyModel;
    private $securityConfig;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->idempotencyModel = new IdempotencyKey();
        $this->securityConfig = SECURITY_SETTINGS;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set comprehensive security headers
     */
    public function setSecurityHeaders()
    {
        if (headers_sent()) {
            return;
        }

        foreach (SECURITY_HEADERS as $header => $value) {
            header("$header: $value");
        }

        // Additional security headers
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Generate secure trace ID for logging
     */
    public function generateTraceId()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Advanced fraud detection
     */
    public function detectFraud($data, $userId = null)
    {
        $fraudScore = 0;
        $indicators = [];

        // Check for suspicious patterns
        $patterns = getFraudPattern('suspicious_patterns');
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, json_encode($data))) {
                $fraudScore += 10;
                $indicators[] = 'suspicious_pattern';
            }
        }

        // Check for high amount transactions
        if (isset($data['amount']) && $data['amount'] > $this->securityConfig['high_amount_threshold']) {
            $fraudScore += $this->securityConfig['fraud_indicators']['high_amount'];
            $indicators[] = 'high_amount';
        }

        // Check for rapid attempts
        if ($userId) {
            $rapidAttempts = $this->checkRapidAttempts($userId);
            if ($rapidAttempts > $this->securityConfig['max_attempts_per_hour']) {
                $fraudScore += $this->securityConfig['fraud_indicators']['rapid_attempts'];
                $indicators[] = 'rapid_attempts';
            }
        }

        // Check for unusual location
        $location = $this->getClientLocation();
        if ($this->isUnusualLocation($location, $userId)) {
            $fraudScore += $this->securityConfig['fraud_indicators']['unusual_location'];
            $indicators[] = 'unusual_location';
        }

        // Check for suspicious user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($this->isSuspiciousUserAgent($userAgent)) {
            $fraudScore += $this->securityConfig['fraud_indicators']['suspicious_user_agent'];
            $indicators[] = 'suspicious_user_agent';
        }

        return [
            'score' => $fraudScore,
            'indicators' => $indicators,
            'is_fraud' => $fraudScore >= $this->securityConfig['fraud_threshold']
        ];
    }

    /**
     * Rate limiting implementation
     */
    public function checkRateLimit($identifier, $maxAttempts = null, $windowSeconds = null)
    {
        $maxAttempts = $maxAttempts ?? $this->securityConfig['rate_limit_attempts'];
        $windowSeconds = $windowSeconds ?? $this->securityConfig['rate_limit_window'];

        $sql = "SELECT COUNT(*) as attempts FROM rate_limits 
                WHERE identifier = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $result = $this->db->query($sql, [$identifier, $windowSeconds])->single();
        
        if ($result['attempts'] >= $maxAttempts) {
            return false;
        }

        // Record this attempt
        $this->recordRateLimitAttempt($identifier);
        
        return true;
    }

    /**
     * Record rate limit attempt
     */
    private function recordRateLimitAttempt($identifier)
    {
        $sql = "INSERT INTO rate_limits (identifier, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, NOW())";
        
        $this->db->query($sql, [
            $identifier,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    /**
     * Check for rapid attempts
     */
    private function checkRapidAttempts($userId)
    {
        $sql = "SELECT COUNT(*) as attempts FROM payment_attempts 
                WHERE user_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $result = $this->db->query($sql, [$userId])->single();
        return $result['attempts'];
    }

    /**
     * Get client location
     */
    private function getClientLocation()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Use a geolocation service (implement your preferred service)
        // This is a simplified version
        return [
            'ip' => $ip,
            'country' => 'NP', // Nepal
            'city' => 'Kathmandu'
        ];
    }

    /**
     * Check for unusual location
     */
    private function isUnusualLocation($location, $userId)
    {
        if (!$userId) {
            return false;
        }

        // Get user's previous locations
        $sql = "SELECT DISTINCT country, city FROM user_locations 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5";
        
        $previousLocations = $this->db->query($sql, [$userId])->all();
        
        if (empty($previousLocations)) {
            return false; // No previous data to compare
        }

        // Check if current location is in previous locations
        foreach ($previousLocations as $prevLocation) {
            if ($prevLocation['country'] === $location['country'] && 
                $prevLocation['city'] === $location['city']) {
                return false; // Location is not unusual
            }
        }

        return true; // Unusual location detected
    }

    /**
     * Check for suspicious user agent
     */
    private function isSuspiciousUserAgent($userAgent)
    {
        $suspiciousPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/php/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Secure payment processing with idempotency
     */
    public function processSecurePayment($paymentData, $userId)
    {
        // Generate idempotency key
        $idempotencyKey = $this->idempotencyModel->generatePaymentKey(
            $userId, 
            $paymentData['order_id'], 
            $paymentData['amount']
        );

        // Check for fraud
        $fraudCheck = $this->detectFraud($paymentData, $userId);
        if ($fraudCheck['is_fraud']) {
            $this->logSecurityEvent('fraud_detected', 'blocked', [
                'user_id' => $userId,
                'fraud_score' => $fraudCheck['score'],
                'indicators' => $fraudCheck['indicators']
            ]);
            
            throw new \Exception('Fraud detected. Payment blocked.');
        }

        // Check rate limiting
        if (!$this->checkRateLimit("payment_$userId")) {
            throw new \Exception('Rate limit exceeded. Please try again later.');
        }

        // Process payment with idempotency
        return $this->executePaymentWithIdempotency($paymentData, $idempotencyKey);
    }

    /**
     * Execute payment with idempotency protection
     */
    private function executePaymentWithIdempotency($paymentData, $idempotencyKey)
    {
        // Check if this key has already been used
        $cachedResponse = $this->idempotencyModel->getCachedResponse($idempotencyKey);
        if ($cachedResponse) {
            return $cachedResponse;
        }

        // Execute the payment
        $result = $this->executePayment($paymentData);
        
        // Store the result for idempotency
        $this->idempotencyModel->storeResponse($idempotencyKey, $result);
        
        return $result;
    }

    /**
     * Execute actual payment
     */
    private function executePayment($paymentData)
    {
        // Implement your payment gateway logic here
        // This is a placeholder
        return [
            'success' => true,
            'transaction_id' => bin2hex(random_bytes(16)),
            'amount' => $paymentData['amount'],
            'timestamp' => time()
        ];
    }

    /**
     * Log security events
     */
    public function logSecurityEvent($action, $status, $data = [])
    {
        $traceId = $this->generateTraceId();
        
        $sql = "INSERT INTO security_logs 
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
     * Clean up expired data
     */
    public function cleanupExpiredData()
    {
        // Clean up expired idempotency keys
        $this->idempotencyModel->cleanupExpired();
        
        // Clean up old rate limit records
        $sql = "DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $this->db->query($sql)->execute();
        
        // Clean up old security logs
        $retentionDays = $this->securityConfig['security_log_retention'];
        $sql = "DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $this->db->query($sql, [$retentionDays])->execute();
    }

    /**
     * Get security statistics
     */
    public function getSecurityStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN status = 'blocked' THEN 1 END) as blocked_events,
                    COUNT(CASE WHEN action = 'fraud_detected' THEN 1 END) as fraud_events,
                    COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as recent_events
                FROM security_logs";
        
        return $this->db->query($sql)->single();
    }
}
