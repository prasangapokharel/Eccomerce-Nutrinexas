<?php

namespace App\Services;

use App\Models\DigitalProduct;
use App\Models\DigitalProductDownload;
use App\Models\OrderItem;
use App\Models\User;
use App\Helpers\EmailHelper;
use Exception;

class DigitalProductService
{
    protected $digitalProductModel;
    protected $digitalDownloadModel;
    protected $orderItemModel;
    protected $userModel;

    public function __construct()
    {
        $this->digitalProductModel = new DigitalProduct();
        $this->digitalDownloadModel = new DigitalProductDownload();
        $this->orderItemModel = new OrderItem();
        $this->userModel = new User();
    }

    /**
     * Grant access to digital products after payment
     * 
     * @param array $order Order data with items
     * @return bool
     */
    public function grantAccess($order)
    {
        if ($order['payment_status'] != 'paid') {
            return false;
        }

        $granted = false;
        $digitalProducts = [];
        $hasNonDigitalProducts = false;

        // Batch check digital products for better performance
        $productIds = array_column($order['items'] ?? [], 'product_id');
        $digitalMap = !empty($productIds) ? $this->batchIsDigital($productIds) : [];

        // Check if order has non-digital products
        foreach ($order['items'] ?? [] as $item) {
            if (empty($digitalMap[$item['product_id']])) {
                $hasNonDigitalProducts = true;
                break;
            }
        }

        foreach ($order['items'] ?? [] as $item) {
            if (empty($digitalMap[$item['product_id']])) {
                continue;
            }

            try {
                $expireDate = date('Y-m-d', strtotime('+30 days'));

                $existingAccess = $this->digitalDownloadModel->getByUserAndProduct(
                    $order['user_id'],
                    $item['product_id']
                );

                if (!$existingAccess) {
                    $downloadData = [
                        'user_id' => $order['user_id'],
                        'product_id' => $item['product_id'],
                        'order_id' => $order['id'],
                        'expire_date' => $expireDate,
                        'max_download' => 5
                    ];

                    $this->digitalDownloadModel->create($downloadData);
                }

                $this->orderItemModel->update($item['id'], [
                    'status' => 'delivered',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $digitalProducts[] = $item;
                $granted = true;
                error_log("DigitalProductService: Granted access for user {$order['user_id']}, product {$item['product_id']}, order {$order['id']}");

            } catch (Exception $e) {
                error_log("DigitalProductService: Error granting access for product {$item['product_id']}: " . $e->getMessage());
            }
        }

        // If order has only digital products, mark order as delivered and process payout
        // Note: Seller payout is handled by OrderProcessor::processDelivery()
        if ($granted && !$hasNonDigitalProducts) {
            try {
                $orderModel = new \App\Models\Order();
                $orderModel->update($order['id'], [
                    'status' => 'delivered',
                    'delivered_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                error_log("DigitalProductService: Order {$order['id']} marked as delivered (digital only)");
                
                // Wait a moment to ensure order status is committed
                usleep(100000); // 0.1 seconds
                
                // Trigger payout processing via OrderProcessor
                // Pass false to skip POST requirement since this is an internal call
                // This ensures consistent handling for both digital and regular products
                $orderProcessor = new \App\Controllers\Order\OrderProcessor();
                $payoutResult = $orderProcessor->processDelivery($order['id'], false);
                
                if ($payoutResult) {
                    error_log("DigitalProductService: Payout processed successfully for order {$order['id']}");
                } else {
                    error_log("DigitalProductService: Payout processing returned false for order {$order['id']}");
                }
            } catch (Exception $e) {
                error_log("DigitalProductService: Error updating order status: " . $e->getMessage());
            }
        }

        // Send email alert if user has email and digital products were granted
        if ($granted && !empty($digitalProducts) && !empty($order['user_id'])) {
            $this->sendDigitalProductAccessEmail($order, $digitalProducts);
        }

        return $granted;
    }

    /**
     * Check if product is digital
     * 
     * @param int $productId
     * @return bool
     */
    private function isDigital($productId)
    {
        static $digitalCache = [];
        
        if (isset($digitalCache[$productId])) {
            return $digitalCache[$productId];
        }
        
        $digitalProduct = $this->digitalProductModel->getByProductId($productId);
        $digitalCache[$productId] = $digitalProduct ? true : false;
        
        return $digitalCache[$productId];
    }

    /**
     * Batch check if products are digital (optimized for multiple products)
     * 
     * @param array $productIds
     * @return array [product_id => bool]
     */
    private function batchIsDigital(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT product_id FROM digital_product WHERE product_id IN ($placeholders)";
        
        $db = \App\Core\Database::getInstance();
        $results = $db->query($sql, $productIds)->all();
        
        $digitalMap = [];
        foreach ($results as $row) {
            $digitalMap[$row['product_id']] = true;
        }
        
        // Fill in false for non-digital products
        $result = [];
        foreach ($productIds as $productId) {
            $result[$productId] = isset($digitalMap[$productId]);
        }
        
        return $result;
    }

    /**
     * Check if order has digital products
     * 
     * @param array $order Order data with items
     * @return bool
     */
    public function hasDigitalProducts($order)
    {
        if (empty($order['items']) || !is_array($order['items'])) {
            return false;
        }

        // Batch check for better performance
        $productIds = array_filter(array_column($order['items'], 'product_id'));
        if (empty($productIds)) {
            return false;
        }

        $digitalMap = $this->batchIsDigital($productIds);
        
        foreach ($digitalMap as $isDigital) {
            if ($isDigital) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Send email alert for digital product access
     * 
     * @param array $order Order data
     * @param array $digitalProducts Digital product items
     * @return bool
     */
    private function sendDigitalProductAccessEmail($order, $digitalProducts)
    {
        try {
            if (empty($order['user_id'])) {
                return false;
            }

            $user = $this->userModel->find($order['user_id']);
            if (!$user || empty($user['email'])) {
                error_log("DigitalProductService: User {$order['user_id']} has no email, skipping email alert");
                return false;
            }

            $baseUrl = defined('BASE_URL') ? BASE_URL : (defined('URLROOT') ? URLROOT : '');
            $downloadUrl = rtrim($baseUrl, '/') . '/products/digitaldownload/' . $order['id'];
            $customerName = $order['customer_name'] ?? ($user['first_name'] ?? 'Customer');
            $orderNumber = $order['invoice'] ?? '#' . $order['id'];

            $subject = 'Your Digital Products Are Ready - Order #' . $orderNumber;

            $emailBody = $this->buildDigitalProductEmailBody($customerName, $orderNumber, $downloadUrl, $digitalProducts);

            $result = EmailHelper::send($user['email'], $subject, $emailBody, $customerName, true);

            if ($result) {
                error_log("DigitalProductService: Email alert sent to {$user['email']} for order {$order['id']}");
            } else {
                error_log("DigitalProductService: Failed to send email alert to {$user['email']} for order {$order['id']}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("DigitalProductService: Error sending digital product email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build email body for digital product access
     * 
     * @param string $customerName
     * @param string $orderNumber
     * @param string $downloadUrl
     * @param array $digitalProducts
     * @return string
     */
    private function buildDigitalProductEmailBody($customerName, $orderNumber, $downloadUrl, $digitalProducts)
    {
        $productsList = '';
        foreach ($digitalProducts as $product) {
            $productName = $product['product_name'] ?? 'Product';
            $productsList .= '<li>' . htmlspecialchars($productName) . '</li>';
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #1976D2; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; padding: 12px 30px; background-color: #1976D2; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .products-list { background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Your Digital Products Are Ready!</h1>
                </div>
                <div class='content'>
                    <p>Hi <strong>{$customerName}</strong>,</p>
                    <p>Great news! Your digital products from order <strong>#{$orderNumber}</strong> are now available for download.</p>
                    
                    <div class='products-list'>
                        <h3 style='margin-top: 0;'>Your Digital Products:</h3>
                        <ul>{$productsList}</ul>
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='{$downloadUrl}' class='button'>Download Your Files</a>
                    </p>
                    
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>Your download link will expire in 30 days</li>
                        <li>You can download each file up to 5 times</li>
                        <li>If you have any issues, please contact our support team</li>
                    </ul>
                    
                    <div class='footer'>
                        <p>Thank you for your purchase!</p>
                        <p>NutriNexus Team</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

