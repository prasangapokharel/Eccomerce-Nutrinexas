<?php

namespace App\Core;

class Security
{
    private static $instance = null;
    private $db;
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->db = Database::getInstance();
        
        // Load security configuration
        if (!defined('SECURITY_CONFIG_LOADED')) {
            require_once ROOT_DIR . '/App/Config/security.php';
        }
    }
    
    /**
     * Generate unique idempotency key
     */
    public function generateIdempotencyKey()
    {
        return 'idem_' . uniqid() . '_' . bin2hex(random_bytes(16));
    }
    
    /**
     * Check if request is idempotent
     */
    public function checkIdempotency($key, $userId = null)
    {
        $result = $this->db->query(
            "SELECT * FROM idempotency_keys WHERE key_hash = ? AND (user_id = ? OR user_id IS NULL) AND expires_at > NOW()",
            [hash('sha256', $key), $userId]
        )->single();
        
        return $result ? $result['response_data'] : null;
    }
    
    /**
     * Store idempotency response
     */
    public function storeIdempotencyResponse($key, $response, $userId = null, $ttl = null)
    {
        if ($ttl === null) {
            $ttl = getSecuritySetting('idempotency_ttl', 3600);
        }
        
        $this->db->query(
            "INSERT INTO idempotency_keys (key_hash, user_id, response_data, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))",
            [hash('sha256', $key), $userId, json_encode($response), $ttl]
        );
    }
    
    /**
     * Validate request timestamp (prevent replay attacks)
     */
    public function validateTimestamp($timestamp, $tolerance = 300)
    {
        $currentTime = time();
        $requestTime = (int)$timestamp;
        
        return abs($currentTime - $requestTime) <= $tolerance;
    }
    
    /**
     * Generate unique trace ID for logging
     */
    public function generateTraceId()
    {
        return 'trace_' . uniqid() . '_' . bin2hex(random_bytes(8));
    }
    
    /**
     * Verify HMAC signature
     */
    public function verifyHMAC($data, $signature, $secret)
    {
        $expectedSignature = hash_hmac('sha256', $data, $secret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Generate HMAC signature
     */
    public function generateHMAC($data, $secret)
    {
        return hash_hmac('sha256', $data, $secret);
    }
    
    /**
     * Check for fraud indicators
     */
    public function detectFraud($orderData, $userId = null)
    {
        $fraudScore = 0;
        $indicators = [];
        
        $fraudIndicators = getFraudPattern('fraud_indicators');
        $highAmountThreshold = getSecuritySetting('high_amount_threshold', 50000);
        $maxAttemptsPerHour = getSecuritySetting('max_attempts_per_hour', 5);
        
        // Check for unusual amounts
        if ($orderData['total'] > $highAmountThreshold) {
            $fraudScore += $fraudIndicators['high_amount'] ?? 30;
            $indicators[] = 'High amount transaction';
        }
        
        // Check for rapid repeated attempts
        $recentAttempts = $this->db->query(
            "SELECT COUNT(*) as count FROM order_attempts WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$userId]
        )->single();
        
        if ($recentAttempts['count'] > $maxAttemptsPerHour) {
            $fraudScore += $fraudIndicators['rapid_attempts'] ?? 40;
            $indicators[] = 'Too many recent attempts';
        }
        
        // Check for mismatched addresses
        if (isset($orderData['billing_address']) && isset($orderData['shipping_address'])) {
            if ($orderData['billing_address'] !== $orderData['shipping_address']) {
                $fraudScore += $fraudIndicators['address_mismatch'] ?? 20;
                $indicators[] = 'Address mismatch';
            }
        }
        
        // Log the attempt
        $this->db->query(
            "INSERT INTO order_attempts (user_id, total_amount, fraud_score, indicators, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $userId,
                $orderData['total'],
                $fraudScore,
                json_encode($indicators),
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        );
        
        $fraudThreshold = getSecuritySetting('fraud_threshold', 50);
        
        return [
            'score' => $fraudScore,
            'indicators' => $indicators,
            'is_fraud' => $fraudScore > $fraudThreshold
        ];
    }
    
    /**
     * Lock order for payment processing
     */
    public function lockOrder($orderId)
    {
        $this->db->query(
            "SELECT * FROM orders WHERE id = ? FOR UPDATE",
            [$orderId]
        );
    }
    
    /**
     * Set secure headers
     */
    public function setSecurityHeaders()
    {
        // Set all security headers from configuration
        foreach (SECURITY_HEADERS as $header => $value) {
            // Skip HSTS if not HTTPS
            if ($header === 'Strict-Transport-Security' && !isset($_SERVER['HTTPS'])) {
                continue;
            }
            header("$header: $value");
        }
    }
    
    /**
     * Set secure cookie
     */
    public function setSecureCookie($name, $value, $expires = 0, $path = '/', $domain = '', $secure = true, $httponly = true, $samesite = 'Strict')
    {
        $options = [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ];
        
        setcookie($name, $value, $options);
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data, $key = null)
    {
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($encryptedData, $key = null)
    {
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     */
    private function getEncryptionKey()
    {
        $key = getenv('ENCRYPTION_KEY');
        if (!$key) {
            throw new \Exception('Encryption key not set');
        }
        return hash('sha256', $key, true);
    }
    
    /**
     * Rate limiting
     */
    public function checkRateLimit($identifier, $maxAttempts = null, $window = null)
    {
        if ($maxAttempts === null) {
            $maxAttempts = getSecuritySetting('rate_limit_attempts', 10);
        }
        if ($window === null) {
            $window = getSecuritySetting('rate_limit_window', 3600);
        }
        
        $attempts = $this->db->query(
            "SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$identifier, $window]
        )->single();
        
        if ($attempts['count'] >= $maxAttempts) {
            return false;
        }
        
        $this->db->query(
            "INSERT INTO rate_limits (identifier, ip_address, created_at) VALUES (?, ?, NOW())",
            [$identifier, $_SERVER['REMOTE_ADDR'] ?? '']
        );
        
        return true;
    }
}
