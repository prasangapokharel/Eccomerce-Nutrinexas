<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SMSTemplate;
use App\Models\User;
use App\Models\Order;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class SMSController extends Controller
{
    private $smsTemplateModel;
    private $userModel;
    private $orderModel;
    private $httpClient;
    private $apiConfig;

    public function __construct()
    {
        parent::__construct();
        $this->smsTemplateModel = new SMSTemplate();
        $this->userModel = new User();
        
        // Initialize Order model if it exists
        if (class_exists('App\Models\Order')) {
            $this->orderModel = new Order();
        }

        // Load BIR SMS API configuration
        $this->loadBirApiConfig();
        
        // Initialize HTTP client
        $this->initializeHttpClient();

        // Set CORS headers for AJAX requests
        $this->setCorsHeaders();
        
        // Create default templates if none exist
        $this->smsTemplateModel->createDefaultTemplates();
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }

    private function loadBirApiConfig()
    {
        // BIR SMS API Configuration - Get from config file with sensible fallbacks
        $this->apiConfig = [
            'base_url' => defined('BIR_SMS_BASE_URL') ? \BIR_SMS_BASE_URL : 'https://user.birasms.com/api/smsapi',
            'api_key' => defined('BIR_SMS_API_KEY') ? \BIR_SMS_API_KEY : (defined('API_KEYS') ? \API_KEYS : ''),
            'route_id' => defined('BIR_SMS_ROUTE_ID') ? \BIR_SMS_ROUTE_ID : (defined('ROUTE_ID') ? \ROUTE_ID : 'SI_Alert'),
            'campaign' => defined('BIR_SMS_CAMPAIGN') ? \BIR_SMS_CAMPAIGN : (defined('CAMPAIGN') ? \CAMPAIGN : 'Default'),
            'type' => defined('BIR_SMS_TYPE') ? \BIR_SMS_TYPE : 'text',
            'response_type' => defined('BIR_SMS_RESPONSE_TYPE') ? \BIR_SMS_RESPONSE_TYPE : 'json',
            'test_mode' => defined('BIR_SMS_TEST_MODE') ? \BIR_SMS_TEST_MODE : (defined('SMS_TEST_MODE') ? \SMS_TEST_MODE : false),
            'timeout' => 30,
            'connect_timeout' => 10
        ];
        
        // Log configuration for debugging (without exposing the API key)
        $logConfig = $this->apiConfig;
        $logConfig['api_key'] = substr($logConfig['api_key'], 0, 6) . '********';
        error_log('SMS API Config loaded: ' . json_encode($logConfig));
    }

    private function initializeHttpClient()
    {
        if (!$this->loadComposerAutoloader()) {
            error_log('Guzzle autoloader not found. SMS will work in simulation mode.');
            $this->httpClient = null;
            return;
        }

        try {
            if (class_exists('GuzzleHttp\Client')) {
                $this->httpClient = new Client([
                    'base_uri' => $this->apiConfig['base_url'],
                    'timeout' => $this->apiConfig['timeout'],
                    'connect_timeout' => $this->apiConfig['connect_timeout'],
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                        'User-Agent' => 'NutriNexas-SMS/1.0'
                    ],
                    'verify' => false // For development - set to true in production
                ]);
            }
        } catch (Exception $e) {
            error_log('Guzzle initialization error: ' . $e->getMessage());
            $this->httpClient = null;
        }
    }

    private function setCorsHeaders()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    private function loadComposerAutoloader()
    {
        $autoloadPaths = [
            __DIR__ . '/../../vendor/autoload.php',
            dirname(dirname(__DIR__)) . '/vendor/autoload.php',
            dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php',
            $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'
        ];

        foreach ($autoloadPaths as $autoloadPath) {
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                return true;
            }
        }
        
        return false;
    }

    /**
     * Main SMS dashboard
     */
    public function index()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $category = isset($_GET['category']) ? trim($_GET['category']) : null;
        $isActive = isset($_GET['is_active']) ? filter_var($_GET['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $templates = $this->smsTemplateModel->getAllTemplates($limit, $offset, $category, $isActive);
        $totalTemplates = $this->smsTemplateModel->getTotalTemplates($category, $isActive);
        $totalPages = ceil($totalTemplates / $limit);
        
        // Get all users with phone numbers for the dropdown
        $users = $this->userModel->getUsersWithPhones();

        // Get recent SMS logs
        $recentLogs = $this->smsTemplateModel->getSMSLogs(5, 0, []);

        // Get SMS statistics
        $stats = $this->smsTemplateModel->getSMSStats([]);

        $this->view('admin/sms/sms', [
            'templates' => $templates,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalTemplates' => $totalTemplates,
            'categories' => SMSTemplate::CATEGORIES,
            'selectedCategory' => $category,
            'isActive' => $isActive,
            'users' => $users,
            'recentLogs' => $recentLogs,
            'stats' => $stats,
            'title' => 'Manage SMS Templates',
            '_csrf' => $_SESSION['_csrf']
        ]);
    }

    /**
     * Show SMS marketing page
     */
    public function marketing()
    {
        $this->requireAdmin();
        
        try {
            // Get SMS templates
            $templates = $this->smsTemplateModel->getActiveTemplates();
            
            // Get users with phone numbers
            $users = $this->userModel->getUsersWithPhones();
            
            // Get customers from customers table
            $customers = $this->smsTemplateModel->getAllCustomersForSMS();
            $customersCount = count($customers);
            
            // Get recent orders with phone numbers
            $recentOrders = $this->orderModel->getRecentOrdersWithPhones();
            
            // Get SMS statistics
            $stats = $this->getSMSStats();
            
            // Get recent SMS logs
            $logs = $this->getRecentSMSLogs();
            
            $this->view('admin/sms/marketing', [
                'templates' => $templates,
                'users' => $users,
                'customers' => $customers,
                'customersCount' => $customersCount,
                'recentOrders' => $recentOrders,
                'stats' => $stats,
                'logs' => $logs
            ]);
            
        } catch (Exception $e) {
            error_log('Error loading SMS marketing page: ' . $e->getMessage());
            $this->setFlash('error', 'Error loading SMS marketing page');
            $this->redirect('admin/sms');
        }
    }

    /**
     * Send single SMS
     */
    public function send()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request method');
            $this->redirect('admin/sms');
            return;
        }

        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid CSRF token');
            $this->redirect('admin/sms');
            return;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $phoneNumber = $this->sanitizePhoneNumber($_POST['phone_number'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $templateId = (int)($_POST['template_id'] ?? 0);
        $variables = isset($_POST['variables']) ? (array)$_POST['variables'] : [];

        // Validation
        $validation = $this->validateSingleSMS($userId, $phoneNumber, $message, $templateId);
        if (!$validation['valid']) {
            $this->setFlash('error', implode(', ', $validation['errors']));
            $this->redirect('admin/sms');
            return;
        }

        // Get user phone if user selected
        if ($userId > 0) {
            $user = $this->userModel->find($userId);
            if ($user && !empty($user['phone'])) {
                $phoneNumber = $this->sanitizePhoneNumber($user['phone']);
            }
        }

        // Process template if selected
        if ($templateId && empty($message)) {
            $processedMessage = $this->smsTemplateModel->processTemplate($templateId, $variables);
            if ($processedMessage === false) {
                $this->setFlash('error', 'Failed to process template');
                $this->redirect('admin/sms');
                return;
            }
            $message = $processedMessage;
        }

        try {
            $result = $this->sendBirSMS($phoneNumber, $message);
            
            // Log the SMS
            $this->logSMS([
                'user_id' => $userId ?: null,
                'phone_number' => $phoneNumber,
                'template_id' => $templateId ?: null,
                'message' => $message,
                'status' => $result['success'] ? 'sent' : 'failed',
                'provider_response' => $result['response'] ?? null,
                'cost' => $result['cost'] ?? 0.00,
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Unknown error')
            ]);

            if ($result['success']) {
                $this->setFlash('success', 'SMS sent successfully to ' . $phoneNumber);
            } else {
                $this->setFlash('error', 'Failed to send SMS: ' . ($result['message'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log('SMS send error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to send SMS: ' . $e->getMessage());
        }

        $this->redirect('admin/sms');
    }

    /**
     * Send bulk SMS to all recipients
     */
    public function sendAll()
    {
        $this->requireAdmin();
        
        error_log("SMS: sendAll method called");
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("SMS: Invalid request method: " . $_SERVER['REQUEST_METHOD']);
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            // Validate input
            $targetAudience = trim($_POST['target_audience'] ?? '');
            $templateId = trim($_POST['template_id'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $variables = json_decode($_POST['variables'] ?? '{}', true) ?: [];
            
            error_log("SMS: Parameters received - targetAudience: '{$targetAudience}', message length: " . strlen($message) . ", templateId: '{$templateId}'");
            error_log("SMS: POST data: " . json_encode($_POST));
            
            if (empty($targetAudience)) {
                error_log("SMS: Target audience is empty");
                echo json_encode(['success' => false, 'message' => 'Target audience is required']);
                return;
            }
            
            if (empty($message)) {
                error_log("SMS: Message is empty");
                echo json_encode(['success' => false, 'message' => 'Message is required']);
                return;
            }
            
            // Validate target audience
            if (!in_array($targetAudience, ['users', 'customers', 'orders'])) {
                error_log("SMS: Invalid target audience: '{$targetAudience}'");
                echo json_encode(['success' => false, 'message' => 'Invalid target audience']);
                return;
            }
            
            // Get phone numbers based on target audience
            error_log("SMS: Getting phone numbers for target audience: '{$targetAudience}'");
            $phoneNumbers = $this->getPhoneNumbersBySource($targetAudience);
            error_log("SMS: getPhoneNumbersBySource returned " . count($phoneNumbers) . " phone numbers");
            
            if (empty($phoneNumbers)) {
                error_log("SMS: No valid phone numbers found for target audience: '{$targetAudience}'");
                echo json_encode(['success' => false, 'message' => 'No valid phone numbers found for selected audience']);
                return;
            }
            
            error_log("SMS: Starting campaign with " . count($phoneNumbers) . " valid phone numbers");
            
            $totalRecipients = count($phoneNumbers);
            $successCount = 0;
            $failedCount = 0;
            $totalCost = 0.00;
            $results = [];
            
            // Process template variables if template is selected
            if ($templateId) {
                $template = $this->smsTemplateModel->find($templateId);
                if ($template) {
                    // Provide default values for common template variables if not provided
                    $defaultVariables = [
                        'discount' => '10',
                        'shop_url' => 'www.nutrinexas.shop',
                        'promo_code' => 'GET10',
                        'store_name' => 'NutriNexus',
                        'website' => 'www.nutrinexas.shop'
                    ];
                    
                    // Merge provided variables with defaults
                    $variables = array_merge($defaultVariables, $variables);
                    
                    error_log("SMS: Template variables: " . json_encode($variables));
                    $message = $this->processTemplateVariables($template['content'], $variables);
                    error_log("SMS: Template processed - Original: '{$template['content']}', Final: '{$message}'");
                }
            }
            
            // Send SMS to each recipient
            foreach ($phoneNumbers as $phoneNumber => $recipientData) {
                try {
                    error_log("SMS: Sending to {$phoneNumber} (original format) for {$recipientData['name']}");
                    
                    $result = $this->sendBirSMS($phoneNumber, $message);
                    
                    // Log the SMS
                    $this->logSMS([
                        'user_id' => $recipientData['user_id'],
                        'phone_number' => $phoneNumber,
                        'template_id' => $templateId ?: null,
                        'message' => $message,
                        'status' => $result['success'] ? 'sent' : 'failed',
                        'provider_response' => $result['response'] ?? null,
                        'cost' => $result['cost'] ?? 0.00,
                        'error_message' => $result['success'] ? null : ($result['message'] ?? 'Unknown error')
                    ]);
                    
                    if ($result['success']) {
                        $successCount++;
                        $totalCost += $result['cost'] ?? 0.00;
                        $results[] = [
                            'phone' => $phoneNumber,
                            'name' => $recipientData['name'] ?? 'Unknown',
                            'status' => 'success',
                            'message' => $result['message'] ?? 'SMS sent successfully'
                        ];
                        error_log("SMS: Successfully sent to {$phoneNumber}");
                    } else {
                        $failedCount++;
                        $results[] = [
                            'phone' => $phoneNumber,
                            'name' => $recipientData['name'] ?? 'Unknown',
                            'status' => 'failed',
                            'message' => $result['message'] ?? 'SMS failed'
                        ];
                        error_log("SMS: Failed to send to {$phoneNumber}: " . ($result['message'] ?? 'Unknown error'));
                    }
                    
                } catch (Exception $e) {
                    $failedCount++;
                    error_log("SMS sending error for {$phoneNumber}: " . $e->getMessage());
                    
                    $results[] = [
                        'phone' => $phoneNumber,
                        'name' => $recipientData['name'] ?? 'Unknown',
                        'status' => 'failed',
                        'message' => 'Exception: ' . $e->getMessage()
                    ];
                }
                
                // Small delay to avoid overwhelming the API
                usleep(100000); // 0.1 second delay
            }
            
            // Return comprehensive results
            echo json_encode([
                'success' => true,
                'message' => "Campaign completed. Success: {$successCount}, Failed: {$failedCount}",
                'data' => [
                    'total_recipients' => $totalRecipients,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'total_cost' => $totalCost,
                    'results' => $results
                ]
            ]);
            
        } catch (Exception $e) {
            error_log('Bulk SMS error: ' . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Bulk SMS failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Send SMS using BIR SMS API
     * Public method to allow other services to send SMS
     */
    public function sendBirSMS(string $phoneNumber, string $message, ?string $scheduleTime = null): array
    {
        $start = microtime(true);
        
        $contacts = $this->prepareContacts($phoneNumber);
        if (empty($contacts)) {
            return [
                'success' => false,
                'message' => 'No valid phone numbers found',
                'response' => null,
                'cost' => 0.00
            ];
        }
        
        $cleanMessage = $this->sanitizeMessage($message);
        if ($cleanMessage === '') {
            return [
                'success' => false,
                'message' => 'SMS message is empty after sanitization',
                'response' => null,
                'cost' => 0.00
            ];
        }
        
        $postData = $this->buildPostData($contacts, $cleanMessage, $scheduleTime);
        
        // If no HTTP client available, use cURL as fallback
        if (!$this->httpClient) {
            return $this->sendBirSMSViaCurl($postData, $start, $cleanMessage);
        }
        
        try {
            $logData = $postData;
            $logData['key'] = substr($logData['key'], 0, 6) . '********';
            error_log('BIR SMS API Request: ' . json_encode($logData));

            // Send via POST method using Guzzle
            $response = $this->httpClient->post('', [
                'form_params' => $postData,
                'timeout' => $this->apiConfig['timeout'],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'text/plain,application/json'
                ]
            ]);

            $time = (microtime(true) - $start) * 1000;
            $responseBody = $response->getBody()->getContents();
            
            error_log("BIR SMS API call took {$time}ms - Response: " . $responseBody);

            [$success, $parsedResponse, $messageResponse] = $this->parseBirSmsResponse($responseBody, $response->getStatusCode());

            return [
                'success' => $success,
                'message' => $messageResponse,
                'response' => $parsedResponse,
                'cost' => $success ? $this->calculateSMSCost($cleanMessage) : 0.00
            ];

        } catch (RequestException $e) {
            $time = (microtime(true) - $start) * 1000;
            error_log("BIR SMS API POST failed after {$time}ms: " . $e->getMessage());
        
            // Try cURL as fallback
            return $this->sendBirSMSViaCurl($postData, $start, $cleanMessage);
        } catch (Exception $e) {
            $time = (microtime(true) - $start) * 1000;
            error_log("BIR SMS API unexpected error after {$time}ms: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Unexpected BIR SMS error: ' . $e->getMessage(),
                'response' => null,
                'cost' => 0.00
            ];
        }
    }

    /**
     * Send SMS using BIR SMS API via cURL (fallback method)
     */
    private function sendBirSMSViaCurl(array $postData, float $start, string $cleanMessage): array
    {
        try {
            // Build POST string
            $postString = http_build_query($postData);
            
            error_log('BIR SMS API cURL Request: ' . $postString);

            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiConfig['base_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->apiConfig['timeout']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->apiConfig['connect_timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: text/plain,application/json'
            ]);

            // Execute cURL request
            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $time = (microtime(true) - $start) * 1000;
            
            if ($curlError) {
                error_log("BIR SMS API cURL error after {$time}ms: " . $curlError);
                return [
                    'success' => false,
                    'message' => 'cURL error: ' . $curlError,
                    'response' => null,
                    'cost' => 0.00
                ];
            }

            error_log("BIR SMS API cURL call took {$time}ms - Response: " . $responseBody);

            [$success, $parsedResponse, $messageResponse] = $this->parseBirSmsResponse($responseBody, $httpCode);

            return [
                'success' => $success,
                'message' => $messageResponse,
                'response' => $parsedResponse,
                'cost' => $success ? $this->calculateSMSCost($cleanMessage) : 0.00
            ];

        } catch (Exception $e) {
            $time = (microtime(true) - $start) * 1000;
            error_log("BIR SMS API cURL method failed after {$time}ms: " . $e->getMessage());
        
            return [
                'success' => false,
                'message' => 'BIR SMS cURL method failed: ' . $e->getMessage(),
                'response' => null,
                'cost' => 0.00
            ];
        }
    }

    /**
     * Prepare POST payload for BIR SMS
     */
    private function buildPostData(array $contacts, string $message, ?string $scheduleTime = null): array
    {
        $postData = [
            'key' => $this->apiConfig['api_key'],
            'campaign' => $this->apiConfig['campaign'],
            'contacts' => implode(',', $contacts),
            'routeid' => $this->apiConfig['route_id'],
            'msg' => $message,
            'type' => $this->apiConfig['type'],
            'responsetype' => $this->apiConfig['response_type']
        ];

        if (!empty($scheduleTime)) {
            $postData['time'] = $scheduleTime;
        }

        if ($this->apiConfig['test_mode']) {
            $postData['test'] = '1';
        }

        return $postData;
    }

    /**
     * Parse BIR SMS response into a standard structure
     */
    private function parseBirSmsResponse(string $responseBody, int $statusCode): array
    {
        $success = $statusCode === 200;
        $result = null;
        $message = null;

        $jsonResult = json_decode($responseBody, true);
        if ($jsonResult !== null) {
            $result = $jsonResult;
            $message = $jsonResult['message'] ?? ($jsonResult['error'] ?? $responseBody);

            if (isset($jsonResult['success'])) {
                $success = $success && filter_var($jsonResult['success'], FILTER_VALIDATE_BOOLEAN);
            } elseif (isset($jsonResult['status'])) {
                $statusValue = strtolower((string)$jsonResult['status']);
                $success = $success && in_array($statusValue, ['success', 'sent', '200'], true);
            } elseif (isset($jsonResult['response_code'])) {
                $success = $success && in_array((string)$jsonResult['response_code'], ['200', '202'], true);
            }
        } else {
            $result = ['raw_response' => $responseBody];
            $normalized = strtolower($responseBody);

            if (strpos($normalized, 'sms-shoot-id/') !== false || strpos($normalized, 'success') !== false) {
                $success = true;
                $message = 'SMS submitted successfully';
            } elseif (strpos($normalized, 'err') !== false) {
                $success = false;
                $message = trim($responseBody);
            } else {
                $message = $responseBody;
            }
        }

        return [$success, $result, $message];
    }

    /**
     * Sanitize SMS content (remove emojis, trim, enforce length)
     */
    private function sanitizeMessage(string $message): string
    {
        $message = html_entity_decode(strip_tags($message), ENT_QUOTES, 'UTF-8');
        $message = preg_replace('/[\r\n]+/', ' ', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        $message = preg_replace('/[\x{1F000}-\x{1F9FF}\x{1FA00}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $message);
        $message = trim($message);

        if (mb_strlen($message, 'UTF-8') > 720) {
            $message = mb_substr($message, 0, 720, 'UTF-8');
        }

        return $message;
    }

    /**
     * Prepare contacts array from comma separated string
     */
    private function prepareContacts(string $phoneNumber): array
    {
        $contacts = [];
        $rawNumbers = preg_split('/[,;]+/', $phoneNumber);

        foreach ($rawNumbers as $raw) {
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }

            $normalized = $this->normalizePhoneNumber($raw);
            if ($normalized !== null) {
                $contacts[$normalized] = $normalized;
            } else {
                error_log("SMS: Ignoring invalid phone number '{$raw}'");
            }
        }

        return array_values($contacts);
    }

    /**
     * Normalize Nepal phone numbers to 10-digit 97/98 format
     */
    private function normalizePhoneNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 13 && strpos($digits, '977') === 0) {
            $digits = substr($digits, 3);
        }

        if (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 && in_array(substr($digits, 0, 2), ['97', '98'], true)) {
            return $digits;
        }

        return null;
    }

    /**
     * Convert phone number to BIR SMS format (98XXXXXXXX)
     */
    private function convertToBirFormat(string $phoneNumber): string
    {
        error_log("SMS: Converting to BIR format: '{$phoneNumber}'");
        
        // Remove  prefix and return 98XXXXXXXX format
        if (preg_match('/^\([78]\d{8})$/', $phoneNumber, $matches)) {
            $birFormat = '9' . $matches[1];
            error_log("SMS: Converted  format to BIR format: '{$birFormat}'");
            return $birFormat;
        }
        
        // If already in 98XXXXXXXX format, return as is
        if (preg_match('/^9[78]\d{8}$/', $phoneNumber)) {
            error_log("SMS: Already in BIR format: '{$phoneNumber}'");
            return $phoneNumber;
        }
        
        // If starts with 0, remove it and add 9
        if (preg_match('/^0([78]\d{8})$/', $phoneNumber, $matches)) {
            $birFormat = '9' . $matches[1];
            error_log("SMS: Converted 0 format to BIR format: '{$birFormat}'");
            return $birFormat;
        }
        
        // If starts with 977, remove it and add 9
        if (preg_match('/^977([78]\d{8})$/', $phoneNumber, $matches)) {
            $birFormat = '9' . $matches[1];
            error_log("SMS: Converted 977 format to BIR format: '{$birFormat}'");
            return $birFormat;
        }
        
        // Default fallback - return as is
        error_log("SMS: Using fallback format: '{$phoneNumber}'");
        return $phoneNumber;
    }

    /**
     * Sanitize phone number for Nepal (BIR SMS compatible)
     */
    private function sanitizePhoneNumber(string $phone): string
    {
        $normalized = $this->normalizePhoneNumber($phone);
        if ($normalized !== null) {
            return $normalized;
        }
        
        return preg_replace('/\D+/', '', $phone);
    }

    /**
     * Validate phone number format - More flexible for Nepal numbers
     */
    private function isValidPhoneNumber(string $phone): bool
    {
        return $this->normalizePhoneNumber($phone) !== null;
    }

    /**
     * Calculate SMS cost based on message length
     */
    private function calculateSMSCost(string $message): float
    {
        $length = strlen($message);
        $parts = ceil($length / 160);
        return $parts * 0.02; // Adjust cost per SMS part for BIR SMS
    }

    /**
     * Log SMS to database
     */
    private function logSMS(array $data): bool
    {
        try {
            return $this->smsTemplateModel->logSMS($data);
        } catch (Exception $e) {
            error_log('SMS logging error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCSRF(): bool
    {
        return hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '');
    }

    /**
     * Validate single SMS data
     */
    private function validateSingleSMS(int $userId, string $phoneNumber, string $message, int $templateId): array
    {
        $errors = [];

        if ($userId > 0) {
            $user = $this->userModel->find($userId);
            if (!$user || empty($user['phone'])) {
                $errors[] = 'Selected user does not have a valid phone number';
            }
        } elseif (empty($phoneNumber) || !$this->isValidPhoneNumber($phoneNumber)) {
            $errors[] = 'Valid Nepal phone number is required (format: XXXXXXXXX)';
        }

        if (empty($message) && !$templateId) {
            $errors[] = 'Either a message or template is required';
        }

        if ($templateId) {
            $template = $this->smsTemplateModel->find($templateId);
            if (!$template) {
                $errors[] = 'Selected template not found';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate bulk SMS data
     */
    private function validateBulkSMS(string $source, int $templateId, string $message): array
    {
        $errors = [];

        if (!in_array($source, ['users', 'customers', 'orders'])) {
            $errors[] = 'Invalid source selected';
        }

        if (empty($message) && !$templateId) {
            $errors[] = 'Either a message or template is required';
        }

        if ($templateId) {
            $template = $this->smsTemplateModel->find($templateId);
            if (!$template) {
                $errors[] = 'Selected template not found';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get phone numbers by source (users, customers, orders)
     */
    private function getPhoneNumbersBySource(string $source): array
    {
        $phoneNumbers = [];
        
        if ($source === 'users') {
            $users = $this->userModel->getUsersWithPhones();
            foreach ($users as $user) {
                if (!empty($user['phone'])) {
                    $cleanPhone = $this->sanitizePhoneNumber($user['phone']);
                    if ($this->isValidPhoneNumber($cleanPhone)) {
                        $phoneNumbers[$cleanPhone] = [
                            'user_id' => $user['id'],
                            'customer_id' => null,
                            'name' => $user['first_name'] . ' ' . $user['last_name'],
                            'email' => $user['email']
                        ];
                    }
                }
            }
        } elseif ($source === 'customers') {
            // Get customers from the customers table
            $customers = $this->smsTemplateModel->getAllCustomersForSMS();
            error_log("SMS: Found " . count($customers) . " customers for SMS");
            
            $validCount = 0;
            $invalidCount = 0;
            $emptyCount = 0;
            
            foreach ($customers as $customer) {
                if (!empty($customer['contact_no'])) {
                    $originalPhone = $customer['contact_no'];
                    
                    // Validate the original phone number first
                    $isValid = $this->isValidPhoneNumber($originalPhone);
                    
                    error_log("SMS: Customer {$customer['customer_name']} - Original: '{$originalPhone}', Valid: " . ($isValid ? 'true' : 'false'));
                    
                    if ($isValid) {
                        // Store the original phone number (BIR SMS expects 98XXXXXXXX format)
                        $originalPhone = $customer['contact_no'];
                        $phoneNumbers[$originalPhone] = [
                            'user_id' => null,
                            'customer_id' => $customer['id'],
                            'name' => $customer['customer_name'],
                            'email' => $customer['email']
                        ];
                        $validCount++;
                        error_log("SMS: ✓ Added {$customer['customer_name']} with phone {$originalPhone}");
                    } else {
                        $invalidCount++;
                        error_log("SMS: ✗ INVALID phone for {$customer['customer_name']}: '{$originalPhone}'");
                    }
                } else {
                    $emptyCount++;
                    error_log("SMS: Customer {$customer['customer_name']} has no contact number");
                }
            }
            
            error_log("SMS: Summary - Valid: {$validCount}, Invalid: {$invalidCount}, Empty: {$emptyCount}, Total: " . count($customers));
            error_log("SMS: Final phoneNumbers array count: " . count($phoneNumbers));
            error_log("SMS: Phone numbers array keys: " . implode(', ', array_keys($phoneNumbers)));
        } elseif ($source === 'orders') {
            $recentOrders = $this->orderModel->getRecentOrdersWithPhones();
            foreach ($recentOrders as $order) {
                if (!empty($order['phone'])) {
                    $cleanPhone = $this->sanitizePhoneNumber($order['phone']);
                    if ($this->isValidPhoneNumber($cleanPhone)) {
                        $phoneNumbers[$cleanPhone] = [
                            'user_id' => $order['user_id'],
                            'customer_id' => null,
                            'name' => $order['customer_name'] ?? 'Order Customer',
                            'email' => $order['email']
                        ];
                    }
                }
            }
        }
        
        return $phoneNumbers;
    }

    /**
     * Get user count by source
     */
    private function getUserCountBySource(string $source): int
    {
        if ($source === 'users') {
            $users = $this->userModel->getUsersWithPhones();
            return count($users);
        } elseif ($source === 'customers') {
            $customers = $this->smsTemplateModel->getAllCustomersForSMS();
            return count($customers);
        } elseif ($source === 'orders') {
            $recentOrders = $this->orderModel->getRecentOrdersWithPhones();
            return count($recentOrders);
        }
        
        return 0;
    }

    /**
     * Validate phone number format for Nepal
     */
    private function validateNepalPhoneNumber(string $phone): bool
    {
        // Remove all non-numeric characters except +
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    
        // Check various Nepal phone number formats
        $patterns = [
            '/^\[78]\d{8}$/',     // 98XXXXXXXX
            '/^977[78]\d{8}$/',       // 97798XXXXXXXX
            '/^9[78]\d{8}$/',         // 98XXXXXXXX
            '/^0?9[78]\d{8}$/'        // 098XXXXXXXX
        ];
    
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanPhone)) {
                return true;
            }
        }
    
        return false;
    }

    /**
     * Format phone number to standard Nepal format
     */
    private function formatNepalPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    
        // Convert to XXXXXXXXX format
        if (preg_match('/^9[78]\d{8}$/', $cleanPhone)) {
            return '' . $cleanPhone;
        } elseif (preg_match('/^0(9[78]\d{8})$/', $cleanPhone, $matches)) {
            return '' . $matches[1];
        } elseif (preg_match('/^977([78]\d{8})$/', $cleanPhone, $matches)) {
            return '' . $matches[1];
        } elseif (preg_match('/^\[78]\d{8}$/', $cleanPhone)) {
            return $cleanPhone;
        }
    
        return $phone; // Return original if no pattern matches
    }

    /**
     * Get template via AJAX
     */
    public function getTemplate($id = null)
    {
        header('Content-Type: application/json');
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
            return;
        }

        try {
            $template = $this->smsTemplateModel->find($id);
            if ($template) {
                echo json_encode([
                    'success' => true, 
                    'template' => $template,
                    'variables' => json_decode($template['variables'] ?? '[]', true)
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Template not found']);
            }
        } catch (Exception $e) {
            error_log('Get template error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch template']);
        }
    }

    /**
     * View SMS logs
     */
    public function viewLogs()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $filters = [
            'status' => isset($_GET['status']) ? trim($_GET['status']) : null,
            'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
            'phone_number' => isset($_GET['phone_number']) ? trim($_GET['phone_number']) : null,
            'date_from' => isset($_GET['date_from']) ? trim($_GET['date_from']) : null,
            'date_to' => isset($_GET['date_to']) ? trim($_GET['date_to']) : null
        ];

        $logs = $this->smsTemplateModel->getSMSLogs($limit, $offset, $filters);
        $stats = $this->smsTemplateModel->getSMSStats($filters);
        $totalLogs = $stats['total_sent'] ?? 0;
        $totalPages = ceil($totalLogs / $limit);

        $this->view('admin/sms/logs', [
            'logs' => $logs,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'title' => 'SMS Logs',
            '_csrf' => $_SESSION['_csrf']
        ]);
    }

    /**
     * Create new template
     */
    public function createTemplate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request method');
            $this->redirect('admin/sms');
            return;
        }

        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid CSRF token');
            $this->redirect('admin/sms');
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'category' => trim($_POST['category'] ?? 'promotional'),
            'content' => trim($_POST['content'] ?? ''),
            'variables' => isset($_POST['variables']) ? (array)$_POST['variables'] : [],
            'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            'priority' => (int)($_POST['priority'] ?? 1)
        ];

        $validation = $this->smsTemplateModel->validateTemplate($data['content']);
        $errors = [];

        if (!$validation['valid']) {
            $errors = array_merge($errors, $validation['errors']);
        }

        if (empty($data['name'])) {
            $errors[] = 'Template name is required';
        }

        if (empty($data['content'])) {
            $errors[] = 'Template content is required';
        }

        if (!in_array($data['category'], array_keys(SMSTemplate::CATEGORIES))) {
            $errors[] = 'Invalid category selected';
        }

        if (empty($errors)) {
            $result = $this->smsTemplateModel->create($data);
            if ($result !== false) {
                $this->setFlash('success', 'Template created successfully');
            } else {
                $this->setFlash('error', 'Failed to create template');
            }
        } else {
            $this->setFlash('error', implode(', ', $errors));
        }

        $this->redirect('admin/sms');
    }

    /**
     * Get template variables via AJAX
     */
    public function variables($templateId = null)
    {
        if (!$templateId) {
            $this->jsonResponse(['success' => false, 'message' => 'Template ID required'], 400);
            return;
        }

        $template = $this->smsTemplateModel->find($templateId);
        if (!$template) {
            $this->jsonResponse(['success' => false, 'message' => 'Template not found'], 404);
            return;
        }

        $variables = json_decode($template['variables'] ?? '[]', true) ?: [];
        
        $this->jsonResponse([
            'success' => true,
            'variables' => $variables,
            'template_name' => $template['name']
        ]);
    }

    /**
     * Delete template
     */
    public function deleteTemplate($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request');
            $this->redirect('admin/sms');
            return;
        }

        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid CSRF token');
            $this->redirect('admin/sms');
            return;
        }

        if ($this->smsTemplateModel->delete($id)) {
            $this->setFlash('success', 'Template deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete template');
        }

        $this->redirect('admin/sms');
    }

    /**
     * Toggle template status
     */
    public function toggleTemplate($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request');
            $this->redirect('admin/sms');
            return;
        }

        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid CSRF token');
            $this->redirect('admin/sms');
            return;
        }

        if ($this->smsTemplateModel->toggleActive($id)) {
            $this->setFlash('success', 'Template status updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update template status');
        }

        $this->redirect('admin/sms');
    }

    /**
     * Test SMS functionality (for debugging)
     */
    public function testSMS()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request method');
            $this->redirect('admin/sms');
            return;
        }
        
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $message = trim($_POST['message'] ?? 'Test SMS from NutriNexus');
        
        if (empty($phoneNumber)) {
            $this->setFlash('error', 'Phone number is required');
            $this->redirect('admin/sms');
            return;
        }
        
        try {
            $result = $this->sendBirSMS($phoneNumber, $message);
            
            // Log the test SMS
            $this->logSMS([
                'user_id' => null,
                'phone_number' => $phoneNumber,
                'template_id' => null,
                'message' => $message,
                'status' => $result['success'] ? 'sent' : 'failed',
                'provider_response' => $result['response'] ?? null,
                'cost' => $result['cost'] ?? 0.00,
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Unknown error')
            ]);

            if ($result['success']) {
                $this->setFlash('success', 'Test SMS sent successfully to ' . $phoneNumber);
            } else {
                $this->setFlash('error', 'Test SMS failed: ' . ($result['message'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log('Test SMS error: ' . $e->getMessage());
            $this->setFlash('error', 'Test SMS failed: ' . $e->getMessage());
        }

        $this->redirect('admin/sms');
    }

    /**
     * Get base URL for the application
     */
    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $port = $_SERVER['SERVER_PORT'] ?? '80';
        
        // Add port if not standard
        if (($protocol === 'http' && $port !== '80') || ($protocol === 'https' && $port !== '443')) {
            $host .= ':' . $port;
        }
        
        return $protocol . "://" . $host;
    }

    /**
     * Process template variables for bulk sending
     */
    private function processTemplateVariables(string $templateContent, array $variables): string
    {
        // Replace placeholders in the template content with actual values
        $processedMessage = $templateContent;
        foreach ($variables as $key => $value) {
            $processedMessage = str_replace('{{' . $key . '}}', $value, $processedMessage);
        }
        return $processedMessage;
    }

    /**
     * Get SMS statistics
     */
    private function getSMSStats(): array
    {
        try {
            // Get total sent SMS count
            $totalSent = $this->smsTemplateModel->getSMSLogs(0, 0, ['status' => 'sent']);
            $totalSentCount = is_array($totalSent) ? count($totalSent) : 0;
            
            // Get total failed SMS count
            $totalFailed = $this->smsTemplateModel->getSMSLogs(0, 0, ['status' => 'failed']);
            $totalFailedCount = is_array($totalFailed) ? count($totalFailed) : 0;
            
            // Calculate delivery rate
            $totalSMS = $totalSentCount + $totalFailedCount;
            $deliveryRate = $totalSMS > 0 ? round(($totalSentCount / $totalSMS) * 100) : 0;
            
            return [
                'total_sent' => $totalSentCount,
                'total_failed' => $totalFailedCount,
                'delivery_rate' => $deliveryRate
            ];
        } catch (Exception $e) {
            error_log('Error getting SMS stats: ' . $e->getMessage());
            return [
                'total_sent' => 0,
                'total_failed' => 0,
                'delivery_rate' => 0
            ];
        }
    }

    /**
     * Get recent SMS logs
     */
    private function getRecentSMSLogs(int $limit = 10): array
    {
        try {
            return $this->smsTemplateModel->getSMSLogs($limit, 0, []);
        } catch (Exception $e) {
            error_log('Error getting SMS logs: ' . $e->getMessage());
            return [];
        }
    }
}
