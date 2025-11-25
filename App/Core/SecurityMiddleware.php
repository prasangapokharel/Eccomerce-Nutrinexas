<?php

namespace App\Core;

class SecurityMiddleware
{
    private $security;
    private $advancedSecurity;
    
    public function __construct()
    {
        $this->security = Security::getInstance();
        $this->advancedSecurity = AdvancedSecurity::getInstance();
        
        // Load security configuration
        if (!defined('SECURITY_CONFIG_LOADED')) {
            require_once ROOT_DIR . '/App/Config/security.php';
        }
    }
    
    /**
     * Apply security middleware to a route
     */
    public function handle($request, $next)
    {
        // Set comprehensive security headers
        $this->advancedSecurity->setSecurityHeaders();
        
        // Check for suspicious patterns
        $this->checkSuspiciousActivity();
        
        // Validate request
        $this->validateRequest($request);
        
        // Check rate limiting
        $this->checkRateLimiting();
        
        // Log security event
        $this->logSecurityEvent('request_processed', 'allowed');
        
        // Continue to next middleware/controller
        return $next($request);
    }
    
    /**
     * Check for suspicious activity
     */
    private function checkSuspiciousActivity()
    {
        if (!isSecurityFeatureEnabled('check_suspicious_patterns')) {
            return;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Get suspicious patterns from configuration
        $suspiciousPatterns = getFraudPattern('suspicious_patterns');
        
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $postData = json_encode($_POST);
        
        $allData = $requestUri . ' ' . $queryString . ' ' . $postData;
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $allData)) {
                $this->logSecurityEvent('suspicious_activity', 'blocked', [
                    'pattern' => $pattern,
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'data' => $allData
                ]);
                
                http_response_code(403);
                die('Access denied');
            }
        }
    }
    
    /**
     * Validate request
     */
    private function validateRequest($request)
    {
        if (!isSecurityFeatureEnabled('enforce_request_size_limit')) {
            return;
        }
        
        // Check request size
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $maxSize = getSecuritySetting('max_upload_size', 10 * 1024 * 1024);
        if ($contentLength > $maxSize) {
            http_response_code(413);
            die('Request too large');
        }
        
        // Check for valid content type on POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isSecurityFeatureEnabled('validate_content_type')) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $allowedTypes = [
                'application/x-www-form-urlencoded',
                'multipart/form-data',
                'application/json',
                'text/plain'
            ];
            
            $isAllowed = false;
            foreach ($allowedTypes as $type) {
                if (strpos($contentType, $type) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
            
            if (!$isAllowed) {
                http_response_code(415);
                die('Unsupported media type');
            }
        }
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimiting()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userId = Session::get('user_id');
        $identifier = $userId ? "user_$userId" : "ip_$ip";
        
        if (!$this->advancedSecurity->checkRateLimit($identifier)) {
            $this->logSecurityEvent('rate_limit_exceeded', 'blocked', [
                'identifier' => $identifier,
                'ip' => $ip
            ]);
            
            http_response_code(429);
            die('Rate limit exceeded. Please try again later.');
        }
    }

    /**
     * Enhanced fraud detection
     */
    private function detectFraud()
    {
        $data = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'post_data' => $_POST
        ];
        
        $userId = Session::get('user_id');
        $fraudCheck = $this->advancedSecurity->detectFraud($data, $userId);
        
        if ($fraudCheck['is_fraud']) {
            $this->logSecurityEvent('fraud_detected', 'blocked', [
                'fraud_score' => $fraudCheck['score'],
                'indicators' => $fraudCheck['indicators'],
                'user_id' => $userId
            ]);
            
            http_response_code(403);
            die('Access denied due to security concerns.');
        }
    }

    /**
     * Log security events
     */
    private function logSecurityEvent($action, $status, $data = [])
    {
        if (!isSecurityFeatureEnabled('log_security_events')) {
            return;
        }
        
        $this->advancedSecurity->logSecurityEvent($action, $status, $data);
    }
}
