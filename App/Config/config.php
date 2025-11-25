<?php
/**
 * Application configuration
 */

// Load environment variables from .env.development or .env.production
$rootDir = dirname(dirname(dirname(__FILE__)));
$envFiles = [
    $rootDir . '/.env.production',
    $rootDir . '/.env.development'
];

foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
        break;
    }
}

// Helper function to get environment variable with default
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}


define('ADS_IP_LIMIT', 'disable');


// Environment setting (development, testing, production)
define('PRODUCTION', env('APP_ENV', 'development') === 'production');
define('DEVELOPMENT', env('APP_ENV', 'development') === 'development');

// Debug mode - get from environment
define('DEBUG', filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN));

// Base URL of the application - get from environment
define('BASE_URL', env('APP_URL', 'http://192.168.1.125:8000'));

// API CALL URL
define('API_URL', BASE_URL . '/api/v1');

// Database configuration - get from environment
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_DATABASE', 'nutrinexas')); // Make sure this matches your database name
define('DB_USER', env('DB_USERNAME', 'root')); 
define('DB_PASS', env('DB_PASSWORD', '123456'));


// App Root
if (!defined('APPROOT')) {
    define('APPROOT', dirname(dirname(__FILE__)));
}

// Site Name
define('SITENAME', env('SITENAME', 'Nutri Nexus'));

// App Version
define('APPVERSION', env('APPVERSION', '1.0.0'));

define('API_KEY', env('API_KEY', '')); // Replace with your actual

// Email configuration - get from environment
define('MAIL_HOST', env('MAIL_HOST', 'smtp.hostinger.com'));
define('MAIL_PORT', env('MAIL_PORT', 465));
define('MAIL_USERNAME', env('MAIL_USERNAME', 'support@nutrinexas.com'));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ':CG#Dn0?'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'support@nutrinexas.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Nutri Nexus'));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'ssl')); // SSL encryption for port 465
define('MAIL_DEBUG', env('MAIL_DEBUG', 2)); // Set to 2 for detailed debug output, 0 for production

define('CONTACT_EMAIL', env('CONTACT_EMAIL', 'prasanga@gmail.com'));
// API Keys for email and newsletter services
define('EMAIL_API_KEY', env('EMAIL_API_KEY', 'your-email-api-key-here')); // Replace with your actual email API key (e.g., Mailgun, SendGrid)
define('NEWSLETTER_API_KEY', env('NEWSLETTER_API_KEY', 'your-newsletter-api-key-here')); // Replace with your actual newsletter API key (e.g., Mailchimp)

// Cache configuration - get from environment
define('CACHE_ENABLED', filter_var(env('CACHE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN));
define('CACHE_LIFETIME', env('CACHE_LIFETIME', 3600)); // 1 hour


// Khalti API endpoints - get from environment
define('KHALTI_INITIATE_URL', env('KHALTI_INITIATE_URL', 'https://khalti.com/api/v2/epayment/initiate/'));
define('KHALTI_LOOKUP_URL', env('KHALTI_LOOKUP_URL', 'https://khalti.com/api/v2/epayment/lookup/'));
define('KHALTI_VERIFY_URL', env('KHALTI_VERIFY_URL', 'https://khalti.com/api/v2/payment/verify/'));

// BIR SMS API configuration - get from environment
// BIR SMS configuration
define('BIR_SMS_BASE_URL', env('BIR_SMS_BASE_URL', 'https://user.birasms.com/api/smsapi'));
define('BIR_SMS_API_KEY', env('BIR_SMS_API_KEY', '3B853539856F3FD36823E959EF82ABF6'));
define('BIR_SMS_ROUTE_ID', env('BIR_SMS_ROUTE_ID', 'SI_Alert'));
define('BIR_SMS_CAMPAIGN', env('BIR_SMS_CAMPAIGN', 'Default'));
define('BIR_SMS_TYPE', env('BIR_SMS_TYPE', 'text'));
define('BIR_SMS_RESPONSE_TYPE', env('BIR_SMS_RESPONSE_TYPE', 'json'));
define('BIR_SMS_TEST_MODE', filter_var(env('BIR_SMS_TEST_MODE', 'false'), FILTER_VALIDATE_BOOLEAN));
define('SMS_STATUS', strtolower(trim(env('SMS_STATUS', 'disable'))));
define('SMS_NOTIFICATIONS_ENABLED', SMS_STATUS === 'enable');

// Backwards compatibility constants
if (!defined('ROUTE_ID')) {
    define('ROUTE_ID', BIR_SMS_ROUTE_ID);
}
if (!defined('API_URL')) {
    define('API_URL', BIR_SMS_BASE_URL);
}
if (!defined('API_KEYS')) {
    define('API_KEYS', BIR_SMS_API_KEY);
}
if (!defined('CAMPAIGN')) {
    define('CAMPAIGN', BIR_SMS_CAMPAIGN);
}
if (!defined('SMS_TEST_MODE')) {
    define('SMS_TEST_MODE', BIR_SMS_TEST_MODE);
}

// Auth0 Configuration
define('AUTH0_DOMAIN', env('AUTH0_DOMAIN', ''));
define('AUTH0_CLIENT_ID', env('AUTH0_CLIENT_ID', ''));
define('AUTH0_CLIENT_SECRET', env('AUTH0_CLIENT_SECRET', ''));
define('AUTH0_CALLBACK_URL', env('AUTH0_CALLBACK_URL', ''));
define('AUTH0_LOGOUT_URL', env('AUTH0_LOGOUT_URL', ''));
define('AUTH0_COOKIE_SECRET', env('AUTH0_COOKIE_SECRET', ''));

// Define ROOT_DIR if not already defined
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__DIR__)));
}

// Upload directories
define('UPLOAD_DIR', ROOT_DIR . '/uploads');
define('PRODUCT_IMAGES_DIR', UPLOAD_DIR . '/products/');
define('PAYMENT_SCREENSHOTS_DIR', UPLOAD_DIR . '/payments/');

// Referral commission percentage
define('REFERRAL_COMMISSION', 5); // 10%

// Tax rate
define('TAX_RATE', 0.00); // 18% GST

// Khalti Payment Configuration
define('KHALTI_SECRET_KEY', 'live_secret_key_68791341fdd94846a146f0457ff7b455'); // Replace with your actual secret key

// Default timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting - Enhanced for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_DIR . '/logs/php_errors.log');
