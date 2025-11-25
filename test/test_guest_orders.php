<?php
/**
 * Test Guest Order Creation - Create 20 orders with different products
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

if (session_status() === PHP_SESSION_NONE) session_start();

ob_start();

echo "=== Guest Order Creation Test (20 Orders) ===\n\n";

$db = \App\Core\Database::getInstance();
$productModel = new \App\Models\Product();
$orderModel = new \App\Models\Order();
$deliveryModel = new \App\Models\DeliveryCharge();

// Get available products
$products = $productModel->getAllProducts(50, 0, []);
if (empty($products)) {
    echo "❌ No products found in database. Please add products first.\n";
    exit(1);
}

echo "Found " . count($products) . " products\n\n";

// Get delivery charges
$deliveryCharges = $deliveryModel->getAllCharges();
$cities = ['Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Chitwan'];
if (!empty($deliveryCharges)) {
    $cities = array_column($deliveryCharges, 'location_name');
}

$successCount = 0;
$failCount = 0;

// Create 20 orders
for ($i = 1; $i <= 20; $i++) {
    echo "Creating Order #$i...\n";
    
    try {
        // Select random products (1-3 products per order)
        $numProducts = rand(1, 3);
        $selectedProducts = array_rand($products, min($numProducts, count($products)));
        if (!is_array($selectedProducts)) {
            $selectedProducts = [$selectedProducts];
        }
        
        $orderItems = [];
        $subtotal = 0;
        
        foreach ($selectedProducts as $productIndex) {
            $product = $products[$productIndex];
            $quantity = rand(1, 3);
            
            // Check stock availability
            if ($product['stock_quantity'] < $quantity) {
                $quantity = max(1, $product['stock_quantity']);
            }
            
            $price = !empty($product['sale_price']) && $product['sale_price'] < $product['price'] 
                ? $product['sale_price'] 
                : $product['price'];
            
            $itemTotal = $price * $quantity;
            $subtotal += $itemTotal;
            
            $orderItems[] = [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'price' => $price,
                'total' => $itemTotal,
                'product_name' => $product['product_name']
            ];
        }
        
        // Calculate delivery fee
        $city = $cities[array_rand($cities)];
        $deliveryCharge = $deliveryModel->getChargeByLocation($city);
        $deliveryFee = $deliveryCharge ? $deliveryCharge['charge'] : 300;
        
        // Calculate tax
        $settingModel = new \App\Models\Setting();
        $taxRate = $settingModel->get('tax_rate', 13) / 100;
        $taxAmount = $subtotal * $taxRate;
        
        // Final amount
        $finalAmount = $subtotal + $taxAmount + $deliveryFee;
        
        // Create order data
        $orderData = [
            'user_id' => null, // Guest order
            'total_amount' => $finalAmount,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'delivery_fee' => $deliveryFee,
            'final_amount' => $finalAmount,
            'coupon_code' => null,
            'payment_method_id' => 1, // COD
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'recipient_name' => 'Guest Customer ' . $i,
            'phone' => '98' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'address_line1' => 'Test Address ' . $i,
            'city' => $city,
            'state' => 'Bagmati',
            'country' => 'Nepal',
            'order_notes' => 'Test order #' . $i
        ];
        
        // Create order
        $orderId = $orderModel->createOrder($orderData, $orderItems);
        
        if ($orderId) {
            echo "  ✅ Order #$i created successfully (ID: $orderId, Invoice: " . ($orderData['invoice'] ?? 'N/A') . ")\n";
            echo "     Products: " . count($orderItems) . ", Total: Rs" . number_format($finalAmount, 2) . "\n";
            $successCount++;
        } else {
            echo "  ❌ Failed to create order #$i\n";
            $failCount++;
        }
        
    } catch (Exception $e) {
        echo "  ❌ Error creating order #$i: " . $e->getMessage() . "\n";
        $failCount++;
    }
    
    echo "\n";
}

echo "=== Test Summary ===\n";
echo "✅ Successful: $successCount\n";
echo "❌ Failed: $failCount\n";
echo "📊 Total: 20\n\n";

if ($successCount === 20) {
    echo "🎉 All 20 orders created successfully!\n";
    echo "Check admin orders page to verify all orders are visible.\n";
} else {
    echo "⚠️  Some orders failed. Please check the errors above.\n";
}

