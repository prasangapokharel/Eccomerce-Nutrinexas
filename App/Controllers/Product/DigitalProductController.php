<?php

namespace App\Controllers\Product;

use App\Core\Controller;
use App\Models\DigitalProduct;
use App\Models\DigitalProductDownload;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Core\Session;
use Exception;

class DigitalProductController extends Controller
{
    protected $digitalProductModel;
    protected $digitalDownloadModel;
    protected $productModel;
    protected $orderModel;
    protected $orderItemModel;

    public function __construct()
    {
        parent::__construct();
        $this->digitalProductModel = new DigitalProduct();
        $this->digitalDownloadModel = new DigitalProductDownload();
        $this->productModel = new Product();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
    }

    /**
     * Grant download access after successful order payment
     * @deprecated Use DigitalProductService::grantAccess() instead
     */
    public function grantDownloadAccess($orderId)
    {
        try {
            $order = $this->orderModel->getOrderWithItems($orderId);
            if (!$order) {
                error_log("DigitalProduct: Order {$orderId} not found");
                return false;
            }

            $service = new \App\Services\DigitalProductService();
            return $service->grantAccess($order);
        } catch (Exception $e) {
            error_log("DigitalProduct: Error granting download access: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Auto-deliver digital products when order is paid
     * @deprecated Use DigitalProductService::grantAccess() instead (handles both)
     */
    public function autoDeliverDigitalProducts($orderId)
    {
        return $this->grantDownloadAccess($orderId);
    }

    /**
     * Get download link for user
     */
    public function getDownloadLink($productId)
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }

        $access = $this->digitalDownloadModel->canDownload($userId, $productId);
        if (!$access) {
            return null;
        }

        $digitalProduct = $this->digitalProductModel->getByProductId($productId);
        if (!$digitalProduct) {
            return null;
        }

        $this->digitalDownloadModel->incrementDownloadCount($access['id']);

        return $digitalProduct['file_download_link'];
    }

    /**
     * Download file (redirect to download link)
     */
    public function download($productId)
    {
        $this->requireLogin();
        
        $downloadLink = $this->getDownloadLink($productId);
        if (!$downloadLink) {
            $this->setFlash('error', 'Download access expired or not available');
            $this->redirect('user/downloads');
            return;
        }

        header("Location: " . $downloadLink);
        exit;
    }

    /**
     * Show digital download page for order
     */
    public function downloadPage($orderId)
    {
        $this->requireLogin();
        
        try {
            $userId = Session::get('user_id');
            $order = $this->orderModel->getOrderWithItems($orderId);
            
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('orders');
                return;
            }
            
            if ($order['user_id'] != $userId) {
                $this->setFlash('error', 'Access denied');
                $this->redirect('orders');
                return;
            }
            
            if ($order['payment_status'] !== 'paid') {
                $this->setFlash('error', 'Order payment is pending');
                $this->redirect('orders');
                return;
            }
            
            $digitalProducts = [];
            foreach ($order['items'] ?? [] as $item) {
                $digitalProduct = $this->digitalProductModel->getByProductId($item['product_id']);
                if ($digitalProduct) {
                    $access = $this->digitalDownloadModel->getByUserAndProduct($userId, $item['product_id']);
                    if ($access) {
                        $product = $this->productModel->getProductById($item['product_id']);
                        $fileSize = $this->getFileSize($digitalProduct['file_download_link']);
                        
                        $digitalProducts[] = [
                            'product_id' => $item['product_id'],
                            'product_name' => $item['product_name'] ?? $product['product_name'] ?? 'Product',
                            'download_link' => $digitalProduct['file_download_link'],
                            'file_size' => $fileSize,
                            'expire_date' => $access['expire_date'],
                            'download_count' => $access['download_count'] ?? 0,
                            'max_download' => $access['max_download'] ?? 5,
                            'remaining_downloads' => ($access['max_download'] ?? 5) - ($access['download_count'] ?? 0)
                        ];
                    }
                }
            }
            
            if (empty($digitalProducts)) {
                $this->setFlash('error', 'No digital products found for this order');
                $this->redirect('orders');
                return;
            }
            
            $this->view('products/digital-download', [
                'order' => $order,
                'digitalProducts' => $digitalProducts,
                'title' => 'Download Digital Products'
            ]);
            
        } catch (Exception $e) {
            error_log("DigitalProduct: Error loading download page: " . $e->getMessage());
            $this->setFlash('error', 'Failed to load download page');
            $this->redirect('orders');
        }
    }

    /**
     * Get file size from URL (approximate)
     */
    private function getFileSize($url)
    {
        if (empty($url)) {
            return 'Unknown';
        }
        
        try {
            $headers = @get_headers($url, 1);
            if ($headers && isset($headers['Content-Length'])) {
                $size = is_array($headers['Content-Length']) 
                    ? end($headers['Content-Length']) 
                    : $headers['Content-Length'];
                return $this->formatFileSize($size);
            }
        } catch (Exception $e) {
            error_log("DigitalProduct: Error getting file size: " . $e->getMessage());
        }
        
        return 'Unknown';
    }

    /**
     * Format file size
     */
    private function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 B';
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Process digital products after payment (auto-deliver, grant access)
     * 
     * @param int $orderId
     * @return bool
     */
    public function processDigitalProductsAfterPayment($orderId)
    {
        try {
            $order = $this->orderModel->getOrderWithItems($orderId);
            if (!$order) {
                return false;
            }

            $service = new \App\Services\DigitalProductService();
            return $service->grantAccess($order);
        } catch (Exception $e) {
            error_log("DigitalProduct: Error processing digital products: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS with download links for digital products
     * @deprecated SMS removed for digital products - use download page instead
     */
    private function sendDownloadLinkSms($orderId)
    {
        try {
            $order = $this->orderModel->find($orderId);
            if (!$order || empty($order['contact_no'])) {
                error_log("DigitalProduct: Order {$orderId} has no contact number for SMS");
                return false;
            }

            $orderItems = $this->orderItemModel->getByOrderId($orderId);
            if (empty($orderItems)) {
                return false;
            }

            $digitalProducts = [];
            foreach ($orderItems as $item) {
                $digitalProduct = $this->digitalProductModel->getByProductId($item['product_id']);
                if ($digitalProduct && !empty($digitalProduct['file_download_link'])) {
                    $access = $this->digitalDownloadModel->getByUserAndProduct($order['user_id'], $item['product_id']);
                    if ($access) {
                        $digitalProducts[] = [
                            'name' => $item['product_name'] ?? 'Product',
                            'link' => $digitalProduct['file_download_link']
                        ];
                    }
                }
            }

            if (empty($digitalProducts)) {
                error_log("DigitalProduct: No digital products with download links found for order {$orderId}");
                return false;
            }

            $smsController = new \App\Controllers\Sms\SmsOrderController();
            $customerName = $order['customer_name'] ?? 'Customer';
            $orderInvoice = $order['invoice'] ?? '#' . $orderId;
            
            $message = "Dear {$customerName}, your order {$orderInvoice} has been confirmed. ";
            
            if (count($digitalProducts) === 1) {
                $message .= "Download link: " . $digitalProducts[0]['link'];
            } else {
                $message .= "Download links:\n";
                foreach ($digitalProducts as $product) {
                    $message .= $product['name'] . ": " . $product['link'] . "\n";
                }
            }

            $result = $smsController->sendSms($order['contact_no'], $message);
            if ($result) {
                error_log("DigitalProduct: SMS sent successfully to {$order['contact_no']} for order {$orderId}");
            } else {
                error_log("DigitalProduct: Failed to send SMS to {$order['contact_no']} for order {$orderId}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("DigitalProduct: Error sending download link SMS: " . $e->getMessage());
            return false;
        }
    }
}

