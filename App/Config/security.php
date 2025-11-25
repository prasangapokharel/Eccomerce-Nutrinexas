<?php

/**
 * Security Configuration
 * Contains all security-related settings and environment variables
 */

// Security configuration constants
if (!defined('SECURITY_CONFIG_LOADED')) {
    define('SECURITY_CONFIG_LOADED', true);
    
    // Encryption key for sensitive data
    if (!getenv('ENCRYPTION_KEY')) {
        // Generate a secure encryption key if not set
        $encryptionKey = bin2hex(random_bytes(32));
        putenv("ENCRYPTION_KEY=$encryptionKey");
    }

    
    // Security settings
    define('SECURITY_SETTINGS', [
        // Idempotency settings
        'idempotency_ttl' => 3600, // 1 hour
        
        // Rate limiting settings
        'rate_limit_attempts' => 10,
        'rate_limit_window' => 3600, // 1 hour
        
        // Fraud detection settings
        'fraud_threshold' => 50,
        'high_amount_threshold' => 50000, // Rs. 50,000
        'max_attempts_per_hour' => 5,
        
        // Session settings
        'session_lifetime' => 86400 * 30, // 30 days
        'remember_token_lifetime' => 86400 * 365 * 5, // 5 years
        
        // Security headers
        'csp_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';",
        
        // File upload settings
        'max_upload_size' => 10 * 1024 * 1024, // 10MB
        'allowed_upload_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
        
        // Logging settings
        'security_log_retention' => 90, // days
        'fraud_log_retention' => 30, // days
        'rate_limit_log_retention' => 1, // days
    ]);
    
    // Security middleware configuration
    define('SECURITY_MIDDLEWARE', [
        'enabled' => true,
        'check_suspicious_patterns' => true,
        'validate_content_type' => true,
        'enforce_request_size_limit' => true,
        'log_security_events' => true,
    ]);
    
    // Fraud detection patterns
    define('FRAUD_PATTERNS', [
        'suspicious_patterns' => [
            '/script/i',
            '/javascript/i',
            '/vbscript/i',
            '/onload/i',
            '/onerror/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/union.*select/i',
            '/drop.*table/i',
            '/delete.*from/i',
            '/insert.*into/i',
            '/update.*set/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/expression\s*\(/i',
        ],
        'fraud_indicators' => [
            'high_amount' => 30,
            'rapid_attempts' => 40,
            'address_mismatch' => 20,
            'unusual_location' => 25,
            'suspicious_user_agent' => 15,
        ]
    ]);
    
    // Security headers configuration
    define('SECURITY_HEADERS', [
        'Content-Security-Policy' => SECURITY_SETTINGS['csp_policy'],
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    ]);
    
    // Cookie security settings
    define('COOKIE_SECURITY', [
        'httponly' => true,
        'secure' => true, // Will be set based on HTTPS
        'samesite' => 'Strict',
        'path' => '/',
        'domain' => '',
    ]);
    
    // Database security settings
    define('DATABASE_SECURITY', [
        'use_prepared_statements' => true,
        'escape_output' => true,
        'validate_input' => true,
        'log_queries' => false, // Set to true for debugging
        'connection_timeout' => 30,
    ]);
    
    // Payment security settings
    define('PAYMENT_SECURITY', [
        'verify_signatures' => true,
        'validate_amounts' => true,
        'check_duplicate_transactions' => true,
        'log_all_transactions' => true,
        'max_retry_attempts' => 3,
        'transaction_timeout' => 300, // 5 minutes
    ]);
    
    // File security settings
    define('FILE_SECURITY', [
        'upload_directory' => 'public/uploads/',
        'allowed_extensions' => SECURITY_SETTINGS['allowed_upload_types'],
        'max_file_size' => SECURITY_SETTINGS['max_upload_size'],
        'scan_uploads' => true,
        'quarantine_suspicious' => true,
    ]);
    
    // Logging configuration
    define('SECURITY_LOGGING', [
        'enabled' => true,
        'log_level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'log_file' => 'App/storage/logs/security.log',
        'max_log_size' => 10 * 1024 * 1024, // 10MB
        'rotate_logs' => true,
        'retention_days' => SECURITY_SETTINGS['security_log_retention'],
    ]);
}

/**
 * Get security setting
 */
function getSecuritySetting($key, $default = null)
{
    return SECURITY_SETTINGS[$key] ?? $default;
}

/**
 * Get fraud pattern
 */
function getFraudPattern($type)
{
    return FRAUD_PATTERNS[$type] ?? [];
}

/**
 * Get security header
 */
function getSecurityHeader($name)
{
    return SECURITY_HEADERS[$name] ?? null;
}

/**
 * Check if security feature is enabled
 */
function isSecurityFeatureEnabled($feature)
{
    return SECURITY_MIDDLEWARE[$feature] ?? false;
}

/**
 * Generate secure random string
 */
function generateSecureToken($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash sensitive data
 */
function hashSensitiveData($data)
{
    return hash('sha256', $data);
}

/**
 * Validate security configuration
 */
function validateSecurityConfig()
{
    $errors = [];
    
    // Check required environment variables
    if (!getenv('ENCRYPTION_KEY')) {
        $errors[] = 'ENCRYPTION_KEY not set';
    }
    
    if (!getenv('ESEWA_SECRET')) {
        $errors[] = 'ESEWA_SECRET not set';
    }
    
    if (!getenv('KHALTI_SECRET')) {
        $errors[] = 'KHALTI_SECRET not set';
    }
    
    // Check directory permissions
    $storageDir = ROOT_DIR . '/App/storage';
    if (!is_writable($storageDir)) {
        $errors[] = 'Storage directory not writable';
    }
    
    $logsDir = $storageDir . '/logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0750, true);
    }
    
    if (!is_writable($logsDir)) {
        $errors[] = 'Logs directory not writable';
    }
    
    return $errors;
}

// Validate configuration on load
$configErrors = validateSecurityConfig();
if (!empty($configErrors)) {
    error_log('Security configuration errors: ' . implode(', ', $configErrors));
}








