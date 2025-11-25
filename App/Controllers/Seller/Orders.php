<?php

namespace App\Controllers\Seller;

use App\Models\Order;
use App\Models\OrderItem;
use Exception;

class Orders extends BaseSellerController
{
    private $orderModel;
    private $orderItemModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
    }

    /**
     * List all orders
     */
    public function index()
    {
        $status = $_GET['status'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $orders = $this->getOrders($status, $limit, $offset);
        $total = $this->getOrderCount($status);
        $totalPages = ceil($total / $limit);

        $this->view('seller/orders/index', [
            'title' => 'Orders',
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'statusFilter' => $status
        ]);
    }

    /**
     * View order details
     */
    public function detail($id)
    {
        $order = $this->orderModel->find($id);
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('seller/orders');
            return;
        }

        // Get order items and filter by seller_id
        $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
        
        // Verify that this order has items from this seller
        if (empty($orderItems)) {
            // Log unauthorized access attempt
            $securityLog = new \App\Services\SecurityLogService();
            $securityLog->logUnauthorizedAccess(
                'unauthorized_order_access',
                $this->sellerId,
                $id,
                'order',
                [
                    'order_exists' => true,
                    'order_seller_ids' => $this->getOrderSellerIds($id)
                ]
            );
            
            $this->setFlash('error', 'Order not found or no items belong to you');
            $this->redirect('seller/orders');
            return;
        }

        // Calculate seller's portion of the order
        $sellerSubtotal = 0;
        foreach ($orderItems as $item) {
            $sellerSubtotal += ($item['total'] ?? 0);
        }

        // Get total order subtotal to calculate proportions
        $db = \App\Core\Database::getInstance();
        $totalOrderSubtotal = $db->query(
            "SELECT COALESCE(SUM(total), 0) as subtotal 
             FROM order_items 
             WHERE order_id = ?",
            [$id]
        )->single()['subtotal'] ?? 0;

        // Calculate proportional amounts
        $proportion = $totalOrderSubtotal > 0 ? ($sellerSubtotal / $totalOrderSubtotal) : 0;
        
        $sellerDiscount = ($order['discount_amount'] ?? 0) * $proportion;
        $sellerTax = ($order['tax_amount'] ?? 0) * $proportion;
        $sellerDeliveryFee = ($order['delivery_fee'] ?? 0) * $proportion;
        $sellerTotal = $sellerSubtotal - $sellerDiscount + $sellerTax + $sellerDeliveryFee;

        // Add seller-specific calculations to order array
        $order['seller_subtotal'] = $sellerSubtotal;
        $order['seller_discount'] = $sellerDiscount;
        $order['seller_tax'] = $sellerTax;
        $order['seller_delivery_fee'] = $sellerDeliveryFee;
        $order['seller_total'] = $sellerTotal;

        $this->view('seller/orders/detail', [
            'title' => 'Order Details',
            'order' => $order,
            'orderItems' => $orderItems
        ]);
    }

    /**
     * Get all seller IDs associated with an order
     */
    private function getOrderSellerIds($orderId)
    {
        $db = \App\Core\Database::getInstance();
        $sellers = $db->query(
            "SELECT DISTINCT seller_id FROM order_items WHERE order_id = ? AND seller_id IS NOT NULL",
            [$orderId]
        )->all();
        return array_column($sellers, 'seller_id');
    }

    /**
     * Accept order
     */
    public function accept($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/orders');
            return;
        }

        try {
            $order = $this->orderModel->find($id);
            $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
            
            if (!$order || empty($orderItems)) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('seller/orders');
                return;
            }

            $result = $this->orderModel->updateStatus($id, 'confirmed');
            
            if ($result) {
                $this->setFlash('success', 'Order accepted successfully');
            } else {
                $this->setFlash('error', 'Failed to accept order');
            }
        } catch (Exception $e) {
            error_log('Accept order error: ' . $e->getMessage());
            $this->setFlash('error', 'Error accepting order');
        }

        $this->redirect('seller/orders/detail/' . $id);
    }

    /**
     * Reject order
     */
    public function reject($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/orders');
            return;
        }

        try {
            $order = $this->orderModel->find($id);
            $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
            
            if (!$order || empty($orderItems)) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('seller/orders');
                return;
            }

            $reason = trim($_POST['rejection_reason'] ?? 'Order rejected by seller');
            $result = $this->orderModel->updateStatus($id, 'cancelled');
            
            if ($result) {
                // Log rejection reason
                error_log("Order #{$id} rejected by seller #{$this->sellerId}: {$reason}");
                $this->setFlash('success', 'Order rejected successfully');
            } else {
                $this->setFlash('error', 'Failed to reject order');
            }
        } catch (Exception $e) {
            error_log('Reject order error: ' . $e->getMessage());
            $this->setFlash('error', 'Error rejecting order');
        }

        $this->redirect('seller/orders');
    }

    /**
     * Print invoice
     */
    public function printInvoice($id)
    {
        $order = $this->orderModel->find($id);
        $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
        
        if (!$order || empty($orderItems)) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('seller/orders');
            return;
        }

        // Redirect to receipt controller with seller context
        $this->redirect('orders/receipt/' . $id);
    }

    /**
     * Print shipping label
     */
    public function printShippingLabel($id)
    {
        $order = $this->orderModel->getOrderById($id);
        $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
        
        if (!$order || empty($orderItems)) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('seller/orders');
            return;
        }

        $this->renderShippingLabel($order, $orderItems);
    }

    /**
     * Bulk print shipping labels
     */
    public function bulkPrintLabels()
    {
        $orderIds = explode(',', $_GET['ids'] ?? '');
        $orderIds = array_filter(array_map('intval', $orderIds));
        
        if (empty($orderIds)) {
            $this->setFlash('error', 'No orders selected');
            $this->redirect('seller/orders');
            return;
        }

        $orders = [];
        foreach ($orderIds as $orderId) {
            $order = $this->orderModel->find($orderId);
            $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($orderId, $this->sellerId);
            
            if ($order && !empty($orderItems)) {
                $orders[] = [
                    'order' => $order,
                    'orderItems' => $orderItems
                ];
            }
        }

        if (empty($orders)) {
            $this->setFlash('error', 'No valid orders found');
            $this->redirect('seller/orders');
            return;
        }

        $this->renderBulkShippingLabels($orders);
    }

    /**
     * Render single shipping label using HTML template
     */
    private function renderShippingLabel($order, $orderItems)
    {
        $sellerModel = new \App\Models\Seller();
        $seller = $sellerModel->find($this->sellerId);
        
        $format = $_GET['format'] ?? 'html';
        
        try {
            if ($format === 'pdf') {
                $this->generateShippingLabelPDF($order, $orderItems, $seller);
            } else {
                $this->generateShippingLabelHTML($order, $orderItems, $seller);
            }
        } catch (Exception $e) {
            error_log('Shipping label generation error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $this->setFlash('error', 'Failed to generate shipping label: ' . $e->getMessage());
            $this->redirect('seller/orders');
        }
    }
    
    /**
     * Generate shipping label as HTML using template
     */
    private function generateShippingLabelHTML($order, $orderItems, $seller)
    {
        $templatePath = (defined('ROOT') ? ROOT : dirname(dirname(dirname(__DIR__)))) . '/assets/templates/shipping/label.html';
        
        if (!file_exists($templatePath)) {
            throw new Exception('Shipping label template not found at: ' . $templatePath);
        }
        
        $template = file_get_contents($templatePath);
        $data = $this->prepareShippingLabelData($order, $orderItems, $seller);
        
        $barcodeNumber = $data['4711081517432'];
        $trackingNumber = $data['00000-0000-0000-000'];
        
        $logoUrl = $seller['logo_url'] ?? '';
        $logoHtml = '';
        if (!empty($logoUrl)) {
            $logoHtml = '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES) . '" alt="' . htmlspecialchars($data['COMPANY NAME'], ENT_QUOTES) . '" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';"><div class="logo-placeholder" style="display:none;">(LOGO HERE)</div>';
        } else {
            $logoHtml = '<div class="logo-placeholder">(LOGO HERE)</div>';
        }
        
        $replacements = [
            '{{LOGO_IMAGE}}' => $logoHtml,
            '{{COMPANY NAME}}' => htmlspecialchars($data['COMPANY NAME']),
            '{{YOUR STREET ADDRESS}}' => htmlspecialchars($data['YOUR STREET ADDRESS']),
            '{{CITY, STATE, ZIP CODE}}' => htmlspecialchars($data['CITY, STATE, ZIP CODE']),
            '{Date}' => htmlspecialchars($data['{Date}']),
            '{}' => htmlspecialchars($data['{}']),
            '{{Type Recipient Name Here}}' => htmlspecialchars($data['Type Recipient Name Here']),
            '{{STREET ADDRESS}}' => htmlspecialchars($data['STREET ADDRESS']),
            '00000-0000-0000-000' => htmlspecialchars($trackingNumber),
            '4711081517432' => htmlspecialchars($barcodeNumber)
        ];
        
        foreach ($replacements as $search => $replace) {
            $template = str_replace($search, $replace, $template);
        }
        
        $template = str_replace(
            'JsBarcode("#barcode-bottom", "4711081517432"',
            'JsBarcode("#barcode-bottom", "' . htmlspecialchars($barcodeNumber, ENT_QUOTES) . '"',
            $template
        );
        
        header('Content-Type: text/html; charset=utf-8');
        echo $template;
        exit;
    }
    
    /**
     * Get payment method label
     */
    private function getPaymentMethodLabel($order)
    {
        $paymentMethod = $order['payment_method'] ?? '';
        if (stripos($paymentMethod, 'COD') !== false || stripos($paymentMethod, 'Cash') !== false) {
            return 'COD';
        }
        return 'PREPAID';
    }
    
    /**
     * Format order items for label
     */
    private function formatOrderItems($orderItems)
    {
        $items = [];
        foreach ($orderItems as $item) {
            $productName = $item['product_name'] ?? 'Product';
            $quantity = $item['quantity'] ?? 1;
            $items[] = htmlspecialchars($productName) . ' (' . $quantity . ' pcs)';
        }
        return implode(',<br />', $items);
    }
    
    /**
     * Generate shipping label as Word document using PHPOffice
     */
    private function generateShippingLabelWord($order, $orderItems, $seller)
    {
        if (!class_exists('\PhpOffice\PhpWord\PhpWord')) {
            throw new Exception('PHPOffice PhpWord library not installed');
        }
        
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 400,
            'marginBottom' => 400,
            'marginLeft' => 400,
            'marginRight' => 400
        ]);
        
        $data = $this->prepareShippingLabelData($order, $orderItems, $seller);
        
        $section->addText('SHIPPING FROM:', ['bold' => true, 'size' => 11]);
        $section->addText($data['COMPANY NAME'], ['size' => 10]);
        $section->addText($data['YOUR STREET ADDRESS'], ['size' => 10]);
        $section->addText($data['CITY, STATE, ZIP CODE'], ['size' => 10]);
        
        $section->addTextBreak(1);
        $section->addText('Tracking no: ' . $data['00000-0000-0000-000'], ['bold' => true, 'size' => 10]);
        
        $section->addTextBreak(2);
        
        $section->addText('SHIPPING TO:', ['bold' => true, 'size' => 11]);
        $section->addText($data['Type Recipient Name Here'], ['size' => 10]);
        $section->addText($data['STREET ADDRESS'], ['size' => 10]);
        
        $section->addTextBreak(1);
        $section->addText('Shipping Date: ' . $data['{Date}'], ['size' => 9]);
        $section->addText('Weight: ' . $data['{}'], ['size' => 9]);
        
        $invoice = $order['invoice'] ?? 'NTX' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        $filename = 'Shipping-Label-' . $invoice . '.docx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Generate shipping label as PDF using mPDF
     */
    private function generateShippingLabelPDF($order, $orderItems, $seller)
    {
        if (!class_exists('\Mpdf\Mpdf')) {
            throw new Exception('mPDF library not installed');
        }
        
        $templatePath = (defined('ROOT') ? ROOT : dirname(dirname(dirname(__DIR__)))) . '/assets/templates/shipping/label.html';
        
        if (!file_exists($templatePath)) {
            throw new Exception('Shipping label template not found');
        }
        
        $template = file_get_contents($templatePath);
        $data = $this->prepareShippingLabelData($order, $orderItems, $seller);
        
        $barcodeNumber = $data['4711081517432'];
        $trackingNumber = $data['00000-0000-0000-000'];
        
        $logoUrl = $seller['logo_url'] ?? '';
        $logoHtml = '';
        if (!empty($logoUrl)) {
            $logoHtml = '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES) . '" alt="' . htmlspecialchars($data['COMPANY NAME'], ENT_QUOTES) . '" style="max-width: 100%; max-height: 15mm; object-fit: contain;">';
        } else {
            $logoHtml = '<div class="logo-placeholder">(LOGO HERE)</div>';
        }
        
        $replacements = [
            '{{LOGO_IMAGE}}' => $logoHtml,
            '{{COMPANY NAME}}' => htmlspecialchars($data['COMPANY NAME']),
            '{{YOUR STREET ADDRESS}}' => htmlspecialchars($data['YOUR STREET ADDRESS']),
            '{{CITY, STATE, ZIP CODE}}' => htmlspecialchars($data['CITY, STATE, ZIP CODE']),
            '{Date}' => htmlspecialchars($data['{Date}']),
            '{}' => htmlspecialchars($data['{}']),
            '{{Type Recipient Name Here}}' => htmlspecialchars($data['Type Recipient Name Here']),
            '{{STREET ADDRESS}}' => htmlspecialchars($data['STREET ADDRESS']),
            '00000-0000-0000-000' => htmlspecialchars($trackingNumber),
            '4711081517432' => htmlspecialchars($barcodeNumber)
        ];
        
        foreach ($replacements as $search => $replace) {
            $template = str_replace($search, $replace, $template);
        }
        
        $template = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $template);
        
        $barcodeSvg = $this->generateBarcodeSVG($barcodeNumber, 'CODE128');
        $template = str_replace(
            '<svg id="barcode-bottom"></svg>',
            $barcodeSvg,
            $template
        );
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [110, 150],
            'margin_left' => 3,
            'margin_right' => 3,
            'margin_top' => 3,
            'margin_bottom' => 3,
            'margin_header' => 0,
            'margin_footer' => 0,
            'default_font_size' => 10,
            'default_font' => 'Arial',
            'tempDir' => sys_get_temp_dir(),
            'dpi' => 96
        ]);
        
        $mpdf->WriteHTML($template);
        
        $invoice = $order['invoice'] ?? 'NTX' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        $filename = 'Shipping-Label-' . $invoice . '.pdf';
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }
    
    /**
     * Generate barcode SVG for PDF
     */
    private function generateBarcodeSVG($code, $format = 'CODE128')
    {
        $width = 1;
        $height = 10;
        
        $svg = '<svg width="100%" height="' . $height . 'mm" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>';
        
        $barcodeData = $this->generateBarcodePattern($code, $format);
        $x = 0;
        $barWidth = 0.5;
        
        foreach ($barcodeData as $bar) {
            if ($bar) {
                $svg .= '<rect x="' . $x . 'mm" y="0" width="' . $barWidth . 'mm" height="' . $height . 'mm" fill="black"/>';
            }
            $x += $barWidth;
        }
        
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Generate barcode pattern (simplified)
     */
    private function generateBarcodePattern($code, $format)
    {
        $pattern = [];
        $codeLength = strlen($code);
        
        for ($i = 0; $i < $codeLength * 10; $i++) {
            $pattern[] = ($i % 2 == 0);
        }
        
        return $pattern;
    }
    
    /**
     * Prepare shipping label data from order
     */
    private function prepareShippingLabelData($order, $orderItems, $seller)
    {
        // Seller/Company information
        $companyName = $seller['company_name'] ?? $seller['name'] ?? 'Nutri Nexus';
        $sellerAddress = $seller['address'] ?? '';
        $sellerCityStateZip = '';
        
        if ($sellerAddress) {
            $addressParts = array_map('trim', explode(',', $sellerAddress));
            if (count($addressParts) > 1) {
                $sellerCityStateZip = implode(', ', array_slice($addressParts, -2));
            } else {
                $sellerCityStateZip = $sellerAddress;
            }
        }
        
        // Tracking number - format as shown in template
        $trackingCode = $order['invoice'] ?? 'NTX' . str_pad($order['id'], 8, '0', STR_PAD_LEFT);
        $trackingFormatted = $this->formatTrackingNumber($trackingCode);
        
        // Shipping date
        $shippingDate = date('Y-m-d');
        if (!empty($order['shipped_at'])) {
            $shippingDate = date('Y-m-d', strtotime($order['shipped_at']));
        }
        
        // Calculate weight
        $totalWeight = 0;
        foreach ($orderItems as $item) {
            $weight = floatval($item['weight'] ?? 0);
            $quantity = intval($item['quantity'] ?? 1);
            $totalWeight += $weight * $quantity;
        }
        $weightText = $totalWeight > 0 ? number_format($totalWeight, 2) . ' kg' : '0.5 kg';
        
        // Recipient information
        $recipientName = $order['customer_name'] ?? $order['order_customer_name'] ?? $order['user_full_name'] ?? 'Customer';
        $recipientAddress = $order['address'] ?? $order['shipping_address'] ?? '';
        
        // Parse recipient address
        $recipientStreet = $recipientAddress;
        if ($recipientAddress) {
            $recipientParts = array_map('trim', explode(',', $recipientAddress));
            $recipientStreet = $recipientParts[0] ?? $recipientAddress;
        }
        
        // Barcode number (EAN-13 format)
        $barcodeNumber = $this->generateBarcodeNumber($order);
        
        return [
            'COMPANY NAME' => $companyName,
            'YOUR STREET ADDRESS' => $sellerAddress,
            'CITY, STATE, ZIP CODE' => $sellerCityStateZip,
            '00000-0000-0000-000' => $trackingFormatted,
            'Type Recipient Name Here' => $recipientName,
            'STREET ADDRESS' => $recipientStreet,
            '{Date}' => $shippingDate,
            '{}' => $weightText,
            '4711081517432' => $barcodeNumber
        ];
    }
    
    /**
     * Format tracking number
     */
    private function formatTrackingNumber($code)
    {
        $clean = preg_replace('/[^0-9A-Z]/', '', strtoupper($code));
        $len = strlen($clean);
        
        if ($len <= 5) {
            return str_pad($clean, 5, '0', STR_PAD_LEFT) . '-0000-0000-000';
        } elseif ($len <= 9) {
            return substr($clean, 0, 5) . '-' . str_pad(substr($clean, 5), 4, '0', STR_PAD_LEFT) . '-0000-000';
        } elseif ($len <= 13) {
            return substr($clean, 0, 5) . '-' . substr($clean, 5, 4) . '-' . str_pad(substr($clean, 9), 4, '0', STR_PAD_LEFT) . '-000';
        } else {
            return substr($clean, 0, 5) . '-' . substr($clean, 5, 4) . '-' . substr($clean, 9, 4) . '-' . substr($clean, 13, 3);
        }
    }
    
    /**
     * Generate barcode number (EAN-13 format)
     */
    private function generateBarcodeNumber($order)
    {
        $code = $order['invoice'] ?? str_pad($order['id'], 8, '0', STR_PAD_LEFT);
        $clean = preg_replace('/[^0-9]/', '', $code);
        
        // Ensure 13 digits for EAN-13
        if (strlen($clean) < 13) {
            $clean = '471' . str_pad($clean, 10, '0', STR_PAD_LEFT);
        }
        
        return substr($clean, 0, 13);
    }

    /**
     * Render bulk shipping labels using template
     */
    private function renderBulkShippingLabels($orders)
    {
        $sellerModel = new \App\Models\Seller();
        $seller = $sellerModel->find($this->sellerId);
        
        // Read template
        $templatePath = __DIR__ . '/../../../assets/templates/shipping/label.html';
        $fullTemplate = file_get_contents($templatePath);
        
        // Extract head and body structure
        preg_match('/(.*<body[^>]*>)/s', $fullTemplate, $headMatch);
        preg_match('/(<\/body>.*<\/html>)/s', $fullTemplate, $footMatch);
        
        $head = $headMatch[1] ?? '';
        $foot = $footMatch[1] ?? '';
        
        // Extract single page content
        preg_match('/<div class="page[^"]*">(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $fullTemplate, $pageMatch);
        $singlePageContent = $pageMatch[1] ?? '';
        
        // Build pages
        $pagesHtml = '';
        $allScripts = '';
        foreach ($orders as $index => $orderData) {
            $pageHtml = $this->replaceLabelPlaceholders($singlePageContent, $orderData['order'], $orderData['orderItems'], $seller);
            $pageClass = 'page' . ($index > 0 ? ' page-breaker' : '');
            $pagesHtml .= '<div class="' . $pageClass . '">' . $pageHtml . '</div></div>';
            
            // Extract scripts from this page
            preg_match('/<script>(.*?)<\/script>/s', $pageHtml, $scriptMatch);
            if (!empty($scriptMatch[1])) {
                $allScripts .= $scriptMatch[1];
            }
        }
        
        // Build final HTML
        $html = $head . '<div class="container">' . $pagesHtml . '</div>';
        
        // Add scripts once at the end
        if (!empty($allScripts)) {
            $html .= '<script>' . $allScripts . '</script>';
        }
        
        $html .= $foot;
        
        echo $html;
        exit;
    }

    /**
     * Replace placeholders in template
     */
    private function replaceLabelPlaceholders($template, $order, $orderItems, $seller)
    {
        // Get payment method
        $paymentMethod = 'Prepaid';
        $isCOD = false;
        if (!empty($order['payment_method_id'])) {
            $db = \App\Core\Database::getInstance();
            $pm = $db->query("SELECT name FROM payment_methods WHERE id = ?", [$order['payment_method_id']])->single();
            if ($pm && (stripos($pm['name'], 'COD') !== false || stripos($pm['name'], 'Cash') !== false)) {
                $paymentMethod = 'COD';
                $isCOD = true;
            } else {
                $paymentMethod = $pm['name'] ?? 'Prepaid';
            }
        } else {
            $paymentMethod = 'COD';
            $isCOD = true;
        }
        
        // Tracking code
        $trackingCode = $order['invoice'] ?? 'NTX' . str_pad($order['id'], 8, '0', STR_PAD_LEFT);
        
        // Calculate weight
        $totalWeight = 0;
        foreach ($orderItems as $item) {
            $weight = $item['weight'] ?? 0.5;
            $totalWeight += $weight * ($item['quantity'] ?? 1);
        }
        
        // Parse addresses
        $senderAddress = $seller['address'] ?? '';
        $senderParts = explode(',', $senderAddress);
        $senderCity = trim($senderParts[count($senderParts) - 1] ?? '');
        
        $recipientAddress = $order['address'] ?? $order['shipping_address'] ?? '';
        $recipientParts = explode(',', $recipientAddress);
        $recipientCity = trim($recipientParts[count($recipientParts) - 1] ?? '');
        
        // Product list
        $productList = [];
        foreach ($orderItems as $item) {
            $productList[] = htmlspecialchars($item['product_name']) . ' (' . $item['quantity'] . ' pcs)';
        }
        $productListStr = implode(',<br />', $productList);
        
        // Generate barcode placeholder (will be generated by JS)
        $barcodePlaceholder = '<svg id="barcode-' . $order['id'] . '" class="barcode__image"></svg>';
        $qrPlaceholder = '<canvas id="qr-' . $order['id'] . '" class="barcode__image"></canvas>';
        
        // Replace placeholders
        $replacements = [
            '$courier-label' => '',
            'J&amp;T Express' => htmlspecialchars($seller['company_name'] ?? $seller['name'] ?? 'NutriNexus'),
            'AWB No. JP9365780159' => 'AWB No. ' . htmlspecialchars($trackingCode),
            'NON COD' => $isCOD ? 'COD' : 'NON COD',
            '4 kg' => number_format($totalWeight, 1) . ' kg',
            'INDONESIAMALL' => htmlspecialchars($seller['company_name'] ?? $seller['name'] ?? 'Company Name'),
            '08155556586' => htmlspecialchars($seller['phone'] ?? ''),
            'Suryodiningratan - Mantrijeron' => htmlspecialchars($senderAddress ?: 'Address'),
            'Oriana paramita dewi' => htmlspecialchars($order['customer_name'] ?: 'Customer Name'),
            '6281542222291' => htmlspecialchars($order['contact_no'] ?? ''),
            'Jalan Gondang Waras No.17C, RT.10 RW.04, Sendangadi, Mlati, Sleman, Yogyakarta, KAB. SLEMAN, MLATI, DI YOGYAKARTA, ID, 55597' => htmlspecialchars($recipientAddress ?: 'Address'),
            'Sendangadi, Mlati, Sleman' => htmlspecialchars($recipientCity ?: 'City'),
            'DI YOGYAKARTA' => htmlspecialchars($recipientCity ?: 'City'),
            ' - 55597' => '',
            'Rp 0' => 'Rs ' . number_format($order['total_amount'] ?? 0, 2),
            'ADS-TBM-001 (5 pcs),<br />DRR-KPB-002 (1 pcs)' => $productListStr,
            'DLV15265' => htmlspecialchars($trackingCode)
        ];
        
        foreach ($replacements as $search => $replace) {
            $template = str_replace($search, $replace, $template);
        }
        
        // Replace barcode images with placeholders and add JS generation
        $template = preg_replace('/<img[^>]*class="barcode__image"[^>]*>/', $barcodePlaceholder, $template);
        $template = preg_replace('/<img[^>]*alt=""[^>]*class="barcode__image"[^>]*>/', $qrPlaceholder, $template);
        
        // Add barcode generation script if not exists
        if (strpos($template, 'JsBarcode') === false) {
            $barcodeScript = '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>';
            $qrScript = '<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>';
            $template = str_replace('</head>', $barcodeScript . $qrScript . '</head>', $template);
        }
        
        // Add initialization script
        $initScript = '<script>
            document.addEventListener("DOMContentLoaded", function() {
                JsBarcode("#barcode-' . $order['id'] . '", "' . htmlspecialchars($trackingCode) . '", {
                    format: "CODE128",
                    width: 1,
                    height: 40,
                    displayValue: false
                });
                QRCode.toCanvas(document.getElementById("qr-' . $order['id'] . '"), "' . htmlspecialchars($trackingCode) . '", {
                    width: 80,
                    margin: 1
                });
            });
        </script>';
        $template = str_replace('</body>', $initScript . '</body>', $template);
        
        return $template;
    }

    /**
     * Update order status
     */
    public function updateStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/orders');
            return;
        }

        try {
            $order = $this->orderModel->find($id);
            
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('seller/orders');
                return;
            }
            
            // Verify order has items from this seller
            $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
            if (empty($orderItems)) {
                $this->setFlash('error', 'Order not found or no items belong to you');
                $this->redirect('seller/orders');
                return;
            }

            $status = $_POST['status'] ?? '';
            $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($status, $allowedStatuses)) {
                $this->setFlash('error', 'Invalid status');
                $this->redirect('seller/orders/detail/' . $id);
                return;
            }

            $oldStatus = $order['status'];
            $result = $this->orderModel->updateStatus($id, $status);
            
            if ($result) {
                // Notify admin about seller's status update (optional)
                // The seller notification service can also be used here if needed
                $this->setFlash('success', 'Order status updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update order status');
            }
        } catch (Exception $e) {
            error_log('Update order status error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating order status');
        }

        $this->redirect('seller/orders/detail/' . $id);
    }

    /**
     * Get orders with filter
     * Orders are filtered by seller_id in order_items (since orders can have products from multiple sellers)
     */
    private function getOrders($status, $limit, $offset)
    {
        $paymentFilter = $_GET['payment_type'] ?? '';
        
        $sql = "SELECT DISTINCT o.*, u.first_name, u.last_name, u.email as customer_email,
                       pm.name as payment_method_name
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE oi.seller_id = ?";
        
        $params = [$this->sellerId];
        
        if (!empty($status)) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        
        // Filter by payment type (COD/Prepaid)
        if ($paymentFilter === 'cod') {
            $sql .= " AND (pm.name LIKE '%COD%' OR pm.name LIKE '%Cash%' OR o.payment_method_id IS NULL)";
        } elseif ($paymentFilter === 'prepaid') {
            $sql .= " AND pm.name NOT LIKE '%COD%' AND pm.name NOT LIKE '%Cash%' AND o.payment_method_id IS NOT NULL";
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $db = \App\Core\Database::getInstance();
        $orders = $db->query($sql, $params)->all();
        
        // Calculate seller's portion for each order
        foreach ($orders as &$order) {
            $sellerTotal = $this->calculateSellerOrderTotal($order['id']);
            $order['seller_total'] = $sellerTotal;
        }
        
        return $orders;
    }

    /**
     * Calculate seller's total for an order
     */
    private function calculateSellerOrderTotal($orderId)
    {
        $db = \App\Core\Database::getInstance();
        
        // Get seller's subtotal
        $sellerSubtotal = $db->query(
            "SELECT COALESCE(SUM(total), 0) as subtotal 
             FROM order_items 
             WHERE order_id = ? AND seller_id = ?",
            [$orderId, $this->sellerId]
        )->single()['subtotal'] ?? 0;

        // Get total order subtotal
        $totalOrderSubtotal = $db->query(
            "SELECT COALESCE(SUM(total), 0) as subtotal 
             FROM order_items 
             WHERE order_id = ?",
            [$orderId]
        )->single()['subtotal'] ?? 0;

        // Get order details
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return 0;
        }

        // Calculate proportional amounts
        $proportion = $totalOrderSubtotal > 0 ? ($sellerSubtotal / $totalOrderSubtotal) : 0;
        
        $sellerDiscount = ($order['discount_amount'] ?? 0) * $proportion;
        $sellerTax = ($order['tax_amount'] ?? 0) * $proportion;
        $sellerDeliveryFee = ($order['delivery_fee'] ?? 0) * $proportion;
        $sellerTotal = $sellerSubtotal - $sellerDiscount + $sellerTax + $sellerDeliveryFee;

        return $sellerTotal;
    }

    /**
     * Get order count with filter
     */
    private function getOrderCount($status)
    {
        $paymentFilter = $_GET['payment_type'] ?? '';
        
        $sql = "SELECT COUNT(DISTINCT o.id) as count 
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE oi.seller_id = ?";
        $params = [$this->sellerId];
        
        if (!empty($status)) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        
        // Filter by payment type (COD/Prepaid)
        if ($paymentFilter === 'cod') {
            $sql .= " AND (pm.name LIKE '%COD%' OR pm.name LIKE '%Cash%' OR o.payment_method_id IS NULL)";
        } elseif ($paymentFilter === 'prepaid') {
            $sql .= " AND pm.name NOT LIKE '%COD%' AND pm.name NOT LIKE '%Cash%' AND o.payment_method_id IS NOT NULL";
        }
        
        $db = \App\Core\Database::getInstance();
        $result = $db->query($sql, $params)->single();
        return $result['count'] ?? 0;
    }
}

