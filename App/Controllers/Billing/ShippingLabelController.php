<?php

namespace App\Controllers\Billing;

use App\Core\Controller;
use App\Models\Order;
use Exception;

class ShippingLabelController extends Controller
{
    private $orderModel;
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->db = \App\Core\Database::getInstance();
    }

    /**
     * Generate and display shipping label HTML
     * Shows in browser for printing
     */
    public function print($orderId)
    {
        try {
            $order = $this->orderModel->getOrderById($orderId);
            
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('orders');
                return;
            }

            // Get label data
            $labelData = $this->prepareLabelData($order);
            
            // Generate HTML for label
            $html = $this->generateLabelHTML($labelData);

            // Output HTML directly
            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;

        } catch (Exception $e) {
            error_log("ShippingLabelController: Error generating label for order #{$orderId}: " . $e->getMessage());
            $this->setFlash('error', 'Failed to generate shipping label: ' . $e->getMessage());
            $this->redirect('orders');
        }
    }

    /**
     * Prepare label data from order
     */
    private function prepareLabelData($order)
    {
        // Get seller details from order items
        $sellerInfo = $this->db->query(
            "SELECT DISTINCT s.id, s.name, s.company_name, s.address, s.city, s.phone, s.email
             FROM order_items oi
             INNER JOIN products p ON oi.product_id = p.id
             INNER JOIN sellers s ON p.seller_id = s.id
             WHERE oi.order_id = ?
             LIMIT 1",
            [$order['id']]
        )->single();
        
        // Use seller information if available, otherwise fallback to company settings
        $senderPhone = '';
        if ($sellerInfo) {
            $companyName = $sellerInfo['company_name'] ?? $sellerInfo['name'] ?? 'Nutrinexus';
            $senderAddress = $sellerInfo['address'] ?? '';
            $senderCity = $sellerInfo['city'] ?? '';
            $senderPhone = $sellerInfo['phone'] ?? '';
            $senderCityStateZip = $senderCity;
            if ($senderAddress) {
                $addressParts = array_map('trim', explode(',', $senderAddress));
                if (count($addressParts) > 1) {
                    $senderCityStateZip = implode(', ', array_slice($addressParts, -2));
                } else {
                    $senderCityStateZip = $senderCity ? $senderCity : $senderAddress;
                }
            }
        } else {
            // Fallback to company settings
            try {
                $settingModel = new \App\Models\Setting();
                $companyName = $settingModel->get('company_name', 'Nutrinexus');
                $senderAddress = $settingModel->get('company_address', '');
                $senderCity = $settingModel->get('company_city', '');
                $senderState = $settingModel->get('company_state', '');
                $senderZip = $settingModel->get('company_zip', '');
                $senderPhone = $settingModel->get('company_phone', '');
                $senderCityStateZip = trim($senderCity . ', ' . $senderState . ' ' . $senderZip);
            } catch (Exception $e) {
                $companyName = 'Nutrinexus';
                $senderAddress = '';
                $senderCityStateZip = '';
            }
        }
        
        // Get customer information
        $recipientName = $order['customer_name'] ?? $order['order_customer_name'] ?? 'Customer';
        $recipientAddress = $order['address'] ?? '';
        $recipientPhone = $order['phone'] ?? $order['customer_phone'] ?? '';
        
        $trackingNo = $order['invoice'] ?? 'NTX' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        $weight = $this->calculateOrderWeight($order['id']);
        $date = date('M d, Y');
        
        // Generate QR code URL
        $qrCodeUrl = $this->generateQRCode($trackingNo, $order);
        
        // Determine if fragile (check order notes or items)
        $fragile = false;
        if (isset($order['notes']) && stripos($order['notes'], 'fragile') !== false) {
            $fragile = true;
        }

        return [
            'company_name' => $companyName,
            'sender_address' => $senderAddress,
            'sender_city_state_zip' => $senderCityStateZip,
            'sender_phone' => $senderPhone,
            'recipient_name' => $recipientName,
            'recipient_address' => $recipientAddress,
            'recipient_phone' => $recipientPhone,
            'tracking_no' => $trackingNo,
            'weight' => $weight,
            'date' => $date,
            'qr_code_url' => $qrCodeUrl,
            'fragile' => $fragile,
            'mail_type' => 'PRIORITY MAIL',
            'priority_indicator' => 'P',
            'additional_info' => ''
        ];
    }

    /**
     * Generate QR code URL
     */
    private function generateQRCode($trackingNo, $order)
    {
        $qrData = sprintf(
            "Tracking: %s | Order: %s | Customer: %s",
            $trackingNo,
            $order['invoice'] ?? $order['id'],
            $order['customer_name'] ?? 'N/A'
        );
        
        return 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($qrData) . '&color=000000&bgcolor=ffffff';
    }

    /**
     * Generate HTML for shipping label using template
     */
    private function generateLabelHTML($labelData)
    {
        $rootDir = dirname(dirname(dirname(__DIR__)));
        $templatePaths = [
            $rootDir . '/assets/templates/shipping/label.html',
            __DIR__ . '/../../assets/templates/shipping/label.html',
            dirname(dirname(__DIR__)) . '/assets/templates/shipping/label.html',
            $_SERVER['DOCUMENT_ROOT'] . '/assets/templates/shipping/label.html'
        ];
        
        $template = null;
        $templatePath = null;
        foreach ($templatePaths as $path) {
            $realPath = realpath($path);
            if ($realPath && file_exists($realPath)) {
                $template = file_get_contents($realPath);
                $templatePath = $realPath;
                break;
            }
        }
        
        if ($template === null || $template === false) {
            error_log("ShippingLabelController: Template not found. Searched paths: " . implode(', ', $templatePaths));
            throw new Exception('Shipping label template not found at: assets/templates/shipping/label.html');
        }
        
        $templateData = $this->prepareTemplateData($labelData);
        
        foreach ($templateData as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);
        
        return $template;
    }

    /**
     * Prepare template data with all placeholders
     */
    private function prepareTemplateData($labelData)
    {
        $senderPhone = $labelData['sender_phone'] ?? '';
        $recipientPhone = $labelData['recipient_phone'] ?? '';
        $fragile = $labelData['fragile'] ?? false;
        $mailType = $labelData['mail_type'] ?? 'PRIORITY MAIL';
        $priorityIndicator = $labelData['priority_indicator'] ?? 'P';
        $weight = $labelData['weight'] ?? '';
        $additionalInfo = $labelData['additional_info'] ?? '';
        
        $senderAddressFull = trim($labelData['sender_address'] . ', ' . $labelData['sender_city_state_zip']);
        if (empty($senderAddressFull) || $senderAddressFull === ', ') {
            $senderAddressFull = $labelData['company_name'];
        }
        
        $fragileSection = '';
        if ($fragile) {
            $fragileSection = '<strong style="font-size: 24px;">FRAGILE</strong><span>PLEASE HANDLE WITH CARE</span>';
        }
        
        if (empty($additionalInfo)) {
            $infoParts = [];
            if ($weight) {
                $infoParts[] = 'Weight: ' . $weight;
            }
            $additionalInfo = !empty($infoParts) ? implode(' | ', $infoParts) : 'Standard Shipping';
        }
        
        $barcodeUrl = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($labelData['tracking_no']) . '&code=Code128&dpi=96&dataseparator=';
        
        return [
            'priority_indicator' => htmlspecialchars($priorityIndicator),
            'sender_address_full' => htmlspecialchars($senderAddressFull),
            'fragile_section' => $fragileSection,
            'mail_type' => htmlspecialchars($mailType),
            'shipping_date' => htmlspecialchars($labelData['date']),
            'sender_name' => htmlspecialchars($labelData['company_name']),
            'sender_address' => htmlspecialchars($labelData['sender_address']),
            'sender_phone' => $senderPhone ? 'Phone: ' . htmlspecialchars($senderPhone) : '',
            'recipient_name' => htmlspecialchars($labelData['recipient_name']),
            'recipient_address' => htmlspecialchars($labelData['recipient_address']),
            'recipient_phone' => $recipientPhone ? 'Phone: ' . htmlspecialchars($recipientPhone) : '',
            'additional_info' => htmlspecialchars($additionalInfo),
            'tracking_no' => htmlspecialchars($labelData['tracking_no']),
            'barcode_url' => htmlspecialchars($barcodeUrl)
        ];
    }

    /**
     * Calculate order weight
     */
    private function calculateOrderWeight($orderId)
    {
        $items = $this->db->query(
            "SELECT oi.quantity, p.weight 
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?",
            [$orderId]
        )->all();

        $totalWeight = 0;
        foreach ($items as $item) {
            $itemWeight = (float)($item['weight'] ?? 0.5);
            $quantity = (int)($item['quantity'] ?? 1);
            $totalWeight += $itemWeight * $quantity;
        }

        return number_format($totalWeight, 2) . ' kg';
    }
}
