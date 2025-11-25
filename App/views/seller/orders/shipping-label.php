<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - Order #<?= $order['invoice'] ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        @page {
            size: 100mm 150mm;
            margin: 0;
        }
        @media print {
            body { 
                margin: 0; 
                padding: 0;
            }
            .no-print { display: none; }
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, Verdana, sans-serif;
            width: 100mm;
            min-height: 150mm;
            margin: 0 auto;
            padding: 3mm;
            background: white;
            border: 1pt solid #000;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        
        td {
            padding: 2mm;
            vertical-align: top;
            border: 1px solid #000;
        }
        
        .logo-box {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            padding: 4mm;
            background: white;
        }
        
        .product-name-box {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            padding: 4mm;
            background: white;
        }
        
        .address-label {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }
        
        .address-content {
            font-size: 8px;
            line-height: 1.3;
        }
        
        .address-content p {
            margin: 0.5mm 0;
        }
        
        .tracking-code {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2mm;
            text-align: center;
        }
        
        .barcode-container {
            text-align: center;
        }
        
        .barcode-container svg {
            max-width: 100%;
            height: auto;
        }
        
        .icons-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-bottom: 2mm;
        }
        
        .icon {
            font-size: 16px;
            text-align: center;
        }
        
        .item-number {
            text-align: center;
        }
        
        .item-label {
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 1mm;
        }
        
        .item-value {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .qr-container {
            text-align: center;
        }
        
        .qr-container canvas {
            max-width: 100%;
            height: auto;
        }
        
        .instructions-header {
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }
        
        .instructions-body {
            font-size: 8px;
            line-height: 1.3;
        }
        
        .logo-img {
            max-width: 100%;
            max-height: 15mm;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        
        .no-print {
            text-align: center;
            margin-top: 10mm;
        }
        
        .print-button {
            padding: 10px 20px;
            background: #0A3167;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        
        .print-button:hover {
            background: #082a54;
        }
    </style>
</head>
<body>
    <?php
    // Get seller information
    $sellerModel = new \App\Models\Seller();
    $seller = $sellerModel->find($sellerId);
    
    // Generate tracking code (use invoice number)
    $trackingCode = $order['invoice'] ?? 'NTX' . str_pad($order['id'], 8, '0', STR_PAD_LEFT);
    
    // Parse addresses
    $senderAddress = $seller['address'] ?? '';
    $senderCity = '';
    $senderZip = '';
    if ($senderAddress) {
        $senderParts = explode(',', $senderAddress);
        $senderCity = trim($senderParts[count($senderParts) - 1] ?? '');
    }
    
    $recipientAddress = $order['address'] ?? $order['shipping_address'] ?? '';
    $recipientCity = '';
    $recipientZip = '';
    if ($recipientAddress) {
        $recipientParts = explode(',', $recipientAddress);
        $recipientCity = trim($recipientParts[count($recipientParts) - 1] ?? '');
    }
    
    // Get delivery instructions
    $deliveryInstructions = $order['order_notes'] ?? $order['notes'] ?? 'Important Information.';
    
    // Item number (use order ID padded)
    $itemNumber = str_pad($order['id'], 7, '0', STR_PAD_LEFT);
    ?>
    
    <table>
        <!-- Top Section: LOGO and Product Name -->
        <tr>
            <td class="logo-box" style="width: 50%;">
                <?php if (!empty($seller['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($seller['logo_url']) ?>" alt="Logo" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div style="display: none;">LOGO</div>
                <?php else: ?>
                    LOGO
                <?php endif; ?>
            </td>
            <td class="product-name-box" style="width: 50%;">
                Shipping Label
            </td>
        </tr>
        
        <!-- Middle Section: Addresses and Tracking -->
        <tr>
            <!-- Upper Left: From Address -->
            <td style="width: 50%;">
                <div class="address-label">From:</div>
                <div class="address-content">
                    <p><strong><?= htmlspecialchars($seller['company_name'] ?? $seller['name'] ?? 'Company Name') ?></strong></p>
                    <p><?= htmlspecialchars($senderAddress ?: 'Address') ?></p>
                    <p><?= htmlspecialchars($senderCity ?: 'City') ?></p>
                    <p><?= htmlspecialchars($senderZip ?: 'Zip') ?></p>
                </div>
            </td>
            <!-- Upper Right: To Address -->
            <td style="width: 50%;">
                <div class="address-label">To:</div>
                <div class="address-content">
                    <p><strong><?= htmlspecialchars($order['customer_name'] ?: 'Company Name') ?></strong></p>
                    <p><?= htmlspecialchars($recipientAddress ?: 'Address') ?></p>
                    <p><?= htmlspecialchars($recipientCity ?: 'City') ?></p>
                    <p><?= htmlspecialchars($recipientZip ?: 'Zip') ?></p>
                </div>
            </td>
        </tr>
        
        <!-- Lower Section: Tracking, Icons, Item Number, QR Code -->
        <tr>
            <!-- Lower Left: Tracking Code and Barcode -->
            <td style="width: 33.33%;">
                <div class="tracking-code"><?= htmlspecialchars($trackingCode) ?></div>
                <div class="barcode-container">
                    <svg id="barcode"></svg>
                </div>
            </td>
            <!-- Lower Middle: Icons and Item Number -->
            <td style="width: 33.33%;">
                <div class="icons-container">
                    <div class="icon" title="Fragile">🍷</div>
                    <div class="icon" title="Handle with Care">📦</div>
                    <div class="icon" title="This Way Up">⬆️</div>
                </div>
                <div class="item-number">
                    <div class="item-label">Item N°:</div>
                    <div class="item-value"><?= htmlspecialchars($itemNumber) ?></div>
                </div>
            </td>
            <!-- Lower Right: QR Code -->
            <td style="width: 33.33%;">
                <div class="qr-container">
                    <canvas id="qr-code"></canvas>
                </div>
            </td>
        </tr>
        
        <!-- Bottom Section: Delivery Instructions -->
        <tr>
            <td colspan="3" style="width: 100%;">
                <div class="instructions-header">Delivery Instruction</div>
                <div class="instructions-body">
                    <?= htmlspecialchars($deliveryInstructions) ?>
                </div>
            </td>
        </tr>
    </table>
    
    <!-- Print Button -->
    <div class="no-print">
        <button onclick="window.print()" class="print-button">
            Print Label
        </button>
    </div>
    
    <script>
        // Generate Barcode
        JsBarcode("#barcode", "<?= htmlspecialchars($trackingCode) ?>", {
            format: "CODE128",
            width: 1,
            height: 30,
            displayValue: false,
            margin: 0
        });
        
        // Generate QR Code
        QRCode.toCanvas(document.getElementById('qr-code'), "<?= htmlspecialchars($trackingCode) ?>", {
            width: 80,
            margin: 1
        }, function (error) {
            if (error) console.error(error);
        });
    </script>
</body>
</html>
