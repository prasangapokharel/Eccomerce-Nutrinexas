<?php

namespace App\Controllers\Sms;

use App\Core\Controller;
use App\Core\Database;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class SmsOrderController extends Controller
{
    private $httpClient;
    private $apiConfig;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->loadApiConfig();
        $this->initializeHttpClient();
    }

    private function loadApiConfig()
    {
        $this->apiConfig = [
            'base_url' => defined('BIR_SMS_BASE_URL') ? BIR_SMS_BASE_URL : 'https://user.birasms.com/api/smsapi',
            'api_key' => defined('BIR_SMS_API_KEY') ? BIR_SMS_API_KEY : (defined('API_KEYS') ? API_KEYS : ''),
            'route_id' => defined('BIR_SMS_ROUTE_ID') ? BIR_SMS_ROUTE_ID : (defined('ROUTE_ID') ? ROUTE_ID : 'SI_Alert'),
            'campaign' => defined('BIR_SMS_CAMPAIGN') ? BIR_SMS_CAMPAIGN : (defined('CAMPAIGN') ? CAMPAIGN : 'Default'),
            'type' => defined('BIR_SMS_TYPE') ? BIR_SMS_TYPE : 'text',
            'timeout' => 30,
            'connect_timeout' => 10
        ];
    }

    private function initializeHttpClient()
    {
        if (!$this->loadComposerAutoloader()) {
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
                        'Accept' => 'application/json'
                    ],
                    'verify' => false
                ]);
            }
        } catch (Exception $e) {
            error_log('SmsOrderController: Guzzle initialization error: ' . $e->getMessage());
            $this->httpClient = null;
        }
    }

    private function loadComposerAutoloader()
    {
        $autoloadPaths = [
            __DIR__ . '/../../../vendor/autoload.php',
            __DIR__ . '/../../../../vendor/autoload.php',
            __DIR__ . '/../../../../../vendor/autoload.php'
        ];

        foreach ($autoloadPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return true;
            }
        }

        return false;
    }

    /**
     * Send SMS when order status changes
     * 
     * @param int $orderId Order ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @return bool
     */
    public function sendOrderStatusSms($orderId, $oldStatus, $newStatus)
    {
        if (!$this->isSmsEnabled()) {
            error_log("SmsOrderController: SMS notifications are disabled for order status change on order #{$orderId}");
            return false;
        }

        try {
            $order = $this->db->query(
                "SELECT invoice, contact_no, customer_name, status FROM orders WHERE id = ?",
                [$orderId]
            )->single();

            if (!$order || empty($order['contact_no'])) {
                error_log("SmsOrderController: Order #{$orderId} not found or no contact number");
                return false;
            }

            $hasDigitalProducts = $this->db->query(
                "SELECT COUNT(*) as count 
                 FROM order_items oi
                 INNER JOIN products p ON oi.product_id = p.id
                 INNER JOIN digital_product dp ON p.id = dp.product_id
                 WHERE oi.order_id = ?",
                [$orderId]
            )->single()['count'] ?? 0;

            if ($hasDigitalProducts > 0) {
                error_log("SmsOrderController: Skipping SMS for order #{$orderId} - contains digital products");
                return false;
            }

            $phoneNumber = $this->formatPhoneNumber($order['contact_no']);
            $orderInvoice = $order['invoice'] ?? 'NTX' . $orderId;
            $customerName = $order['customer_name'] ?? 'Customer';

            // Only send SMS for: processing, shipped, delivered
            $allowedStatuses = ['processing', 'shipped', 'delivered'];
            if (!in_array($newStatus, $allowedStatuses)) {
                error_log("SmsOrderController: Skipping SMS for status '{$newStatus}' (only sending for: processing, shipped, delivered)");
                return false;
            }

            $messages = [
                'processing' => "Dear {$customerName}, your order #{$orderInvoice} is now being processed.",
                'shipped' => "Dear {$customerName}, your order #{$orderInvoice} has been shipped and is on its way.",
                'delivered' => "Dear {$customerName}, your order #{$orderInvoice} has been delivered successfully. Thank you for shopping with us!"
            ];

            $message = $messages[$newStatus] ?? "Dear {$customerName}, your order #{$orderInvoice} status has been updated to {$newStatus}.";

            return $this->sendSms($phoneNumber, $message);
        } catch (Exception $e) {
            error_log("SmsOrderController: Error sending order status SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS using BIR SMS API (internal method)
     * 
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    private function sendSmsInternal($phoneNumber, $message)
    {
        if (empty($phoneNumber) || empty($message)) {
            return false;
        }

        $postData = [
            'key' => $this->apiConfig['api_key'],
            'campaign' => $this->apiConfig['campaign'],
            'routeid' => $this->apiConfig['route_id'],
            'type' => $this->apiConfig['type'],
            'contacts' => $phoneNumber,
            'msg' => $message
        ];

        if (!$this->httpClient) {
            return $this->sendViaCurl($postData);
        }

        try {
            $response = $this->httpClient->post('', [
                'form_params' => $postData,
                'timeout' => $this->apiConfig['timeout']
            ]);

            $responseBody = $response->getBody()->getContents();
            $success = $this->parseResponse($responseBody, $response->getStatusCode());

            if ($success) {
                error_log("SmsOrderController: SMS sent successfully to {$phoneNumber}");
            }

            return $success;
        } catch (RequestException $e) {
            error_log("SmsOrderController: Guzzle request failed: " . $e->getMessage());
            return $this->sendViaCurl($postData);
        } catch (Exception $e) {
            error_log("SmsOrderController: Error sending SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS via cURL fallback
     * 
     * @param array $postData
     * @return bool
     */
    private function sendViaCurl($postData)
    {
        try {
            $postString = http_build_query($postData);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiConfig['base_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->apiConfig['timeout']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $this->parseResponse($responseBody, $httpCode);
        } catch (Exception $e) {
            error_log("SmsOrderController: cURL error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse SMS API response
     * 
     * @param string $responseBody
     * @param int $httpCode
     * @return bool
     */
    private function parseResponse($responseBody, $httpCode)
    {
        if ($httpCode !== 200) {
            return false;
        }

        $response = json_decode($responseBody, true);
        if (isset($response['status']) && $response['status'] === 'success') {
            return true;
        }

        if (stripos($responseBody, 'success') !== false || stripos($responseBody, 'sent') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Format phone number
     * 
     * @param string $phoneNumber
     * @return string
     */
    private function formatPhoneNumber($phoneNumber)
    {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        if (strlen($phone) === 10) {
            return '977' . $phone;
        }
        
        if (strlen($phone) === 13 && substr($phone, 0, 3) === '977') {
            return $phone;
        }
        
        return $phone;
    }

    /**
     * Check if SMS is enabled
     */
    private function isSmsEnabled()
    {
        if (defined('SMS_NOTIFICATIONS_ENABLED')) {
            return SMS_NOTIFICATIONS_ENABLED;
        }
        if (defined('SMS_STATUS')) {
            return strtolower(trim(SMS_STATUS)) === 'enable';
        }
        return false;
    }

    /**
     * Send SMS directly (public API)
     * 
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function sendSms($phoneNumber, $message)
    {
        if (!$this->isSmsEnabled()) {
            error_log("SmsOrderController: SMS notifications are disabled (SMS_STATUS: " . (defined('SMS_STATUS') ? SMS_STATUS : 'not defined') . ")");
            return false;
        }

        try {
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            if (empty($formattedPhone) || empty($message)) {
                error_log("SmsOrderController: Invalid phone or message");
                return false;
            }

            return $this->sendSmsInternal($formattedPhone, $message);
        } catch (Exception $e) {
            error_log("SmsOrderController: Error sending SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send payment confirmation SMS with digital product link if applicable
     * 
     * @param int $orderId Order ID
     * @return bool
     */
    public function sendPaymentConfirmationSms($orderId)
    {
        if (!$this->isSmsEnabled()) {
            error_log("SmsOrderController: SMS notifications are disabled for payment confirmation on order #{$orderId}");
            return false;
        }

        try {
            $order = $this->db->query(
                "SELECT invoice, contact_no, customer_name, total_amount, payment_status FROM orders WHERE id = ?",
                [$orderId]
            )->single();

            if (!$order || empty($order['contact_no'])) {
                error_log("SmsOrderController: Order #{$orderId} not found or no contact number");
                return false;
            }

            if ($order['payment_status'] !== 'paid') {
                error_log("SmsOrderController: Order #{$orderId} payment status is not 'paid', skipping SMS");
                return false;
            }

            $phoneNumber = $this->formatPhoneNumber($order['contact_no']);
            $orderInvoice = $order['invoice'] ?? 'NTX' . $orderId;
            $customerName = $order['customer_name'] ?? 'Customer';
            $amount = number_format($order['total_amount'], 2);

            // Check if order has digital products
            $digitalProducts = $this->db->query(
                "SELECT dp.file_download_link, p.product_name
                 FROM order_items oi
                 INNER JOIN products p ON oi.product_id = p.id
                 INNER JOIN digital_product dp ON p.id = dp.product_id
                 WHERE oi.order_id = ? AND p.is_digital = 1
                 LIMIT 1",
                [$orderId]
            )->single();

            $baseUrl = defined('BASE_URL') ? BASE_URL : (defined('URLROOT') ? URLROOT : 'http://localhost:8000');
            $downloadUrl = $baseUrl . '/products/digitaldownload/' . $orderId;

            // Build message
            $message = "Dear {$customerName}, your order #{$orderInvoice} amount रु {$amount} is confirmed.";

            // Add digital product download link if applicable
            if ($digitalProducts) {
                $message .= " Download: {$downloadUrl}";
            }

            return $this->sendSms($phoneNumber, $message);
        } catch (Exception $e) {
            error_log("SmsOrderController: Error sending payment confirmation SMS: " . $e->getMessage());
            return false;
        }
    }
}

