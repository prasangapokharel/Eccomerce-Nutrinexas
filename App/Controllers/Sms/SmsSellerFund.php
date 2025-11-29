<?php

namespace App\Controllers\Sms;

use App\Core\Controller;
use App\Core\Database;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class SmsSellerFund extends Controller
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
            error_log('SmsSellerFund: Guzzle initialization error: ' . $e->getMessage());
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
     * Send SMS when payout is added to seller wallet
     * 
     * @param int $sellerId Seller ID
     * @param int $orderId Order ID
     * @param float $amount Payout amount
     * @param array $deductions Deduction breakdown
     * @return bool
     */
    public function sendPayoutSms($sellerId, $orderId, $amount, $deductions = [])
    {
        try {
            $seller = $this->db->query(
                "SELECT name, company_name, phone FROM sellers WHERE id = ?",
                [$sellerId]
            )->single();

            if (!$seller || empty($seller['phone'])) {
                error_log("SmsSellerFund: Seller #{$sellerId} not found or no phone number");
                return false;
            }

            $order = $this->db->query(
                "SELECT invoice FROM orders WHERE id = ?",
                [$orderId]
            )->single();

            $phoneNumber = $this->formatPhoneNumber($seller['phone']);
            $sellerName = $seller['company_name'] ?? $seller['name'] ?? 'Seller';
            $orderInvoice = $order['invoice'] ?? '#' . $orderId;
            $formattedAmount = 'रु ' . number_format($amount, 2);

            $message = "Dear {$sellerName}, your payout {$formattedAmount} for order {$orderInvoice} has been credited to your wallet.";

            if (!empty($deductions)) {
                $taxRate = $deductions['tax_rate'] ?? (new \App\Models\Setting())->get('tax_rate', 12);
                $deductionText = [];
                if (!empty($deductions['tax']) && $deductions['tax'] > 0) {
                    $deductionText[] = "Tax ({$taxRate}%): रु " . number_format($deductions['tax'], 2);
                }
                if (!empty($deductions['coupon']) && $deductions['coupon'] > 0) {
                    $deductionText[] = "Coupon: रु " . number_format($deductions['coupon'], 2);
                }
                if (!empty($deductions['affiliate']) && $deductions['affiliate'] > 0) {
                    $deductionText[] = "Affiliate: रु " . number_format($deductions['affiliate'], 2);
                }
                if (!empty($deductions['delivery_fee']) && $deductions['delivery_fee'] > 0) {
                    $deductionText[] = "Delivery: रु " . number_format($deductions['delivery_fee'], 2);
                }

                if (!empty($deductionText)) {
                    $message .= " Deductions: " . implode(', ', $deductionText);
                }
            }

            $message .= " Thank you!";

            return $this->sendSms($phoneNumber, $message);
        } catch (Exception $e) {
            error_log("SmsSellerFund: Error sending payout SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS using BIR SMS API
     * 
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    private function sendSms($phoneNumber, $message)
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
                error_log("SmsSellerFund: SMS sent successfully to {$phoneNumber}");
            }

            return $success;
        } catch (RequestException $e) {
            error_log("SmsSellerFund: Guzzle request failed: " . $e->getMessage());
            return $this->sendViaCurl($postData);
        } catch (Exception $e) {
            error_log("SmsSellerFund: Error sending SMS: " . $e->getMessage());
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
            error_log("SmsSellerFund: cURL error: " . $e->getMessage());
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
}

