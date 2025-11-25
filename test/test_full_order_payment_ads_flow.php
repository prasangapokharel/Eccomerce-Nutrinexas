<?php
/**
 * Test Full Order Payment and Ads Flow
 * 
 * Action: 
 * 1. User places order successfully and pays
 * 2. Payment is successful for seller
 * 3. Seller wallet has minimum 10000
 * 4. Test if ads will show
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Seller;
use App\Models\Ad;
use App\Models\AdType;
use App\Models\AdCost;
use App\Models\AdPayment;
use App\Models\SellerWallet;
use App\Services\AdPaymentService;
use App\Services\BannerAdDisplayService;
use App\Services\SellerBalanceService;
use App\Core\Database;

$db = Database::getInstance();
$orderModel = new Order();
$productModel = new Product();
$userModel = new User();
$sellerModel = new Seller();
$adModel = new Ad();
$adTypeModel = new AdType();
$adCostModel = new AdCost();
$adPaymentModel = new AdPayment();
$walletModel = new SellerWallet();
$paymentService = new AdPaymentService();
$bannerService = new BannerAdDisplayService();
$balanceService = new SellerBalanceService();

echo "=== Full Order Payment and Ads Flow Test ===\n\n";

// Step 1: Get or create test user
echo "--- Step 1: Setting up test user ---\n";
$user = $db->query("SELECT * FROM users WHERE role = 'customer' LIMIT 1")->single();
if (!$user) {
    echo "ERROR: No customer user found. Please create a customer first.\n";
    exit(1);
}
$userId = $user['id'];
echo "Using user ID: $userId\n\n";

// Step 2: Get seller ID 2 and ensure wallet has minimum 10000
echo "--- Step 2: Ensuring seller wallet has minimum 10000 ---\n";
$seller = $db->query("SELECT * FROM sellers WHERE id = 2 LIMIT 1")->single();
if (!$seller) {
    echo "ERROR: Seller ID 2 not found. Please create seller with ID 2 first.\n";
    exit(1);
}
$sellerId = 2;

$wallet = $walletModel->getWalletBySellerId($sellerId);
$currentBalance = (float)($wallet['balance'] ?? 0);
$minBalance = 10000.00;

if ($currentBalance < $minBalance) {
    $topUpAmount = $minBalance - $currentBalance;
    $newBalance = $currentBalance + $topUpAmount;
    
    $db->query(
        "UPDATE seller_wallet SET balance = ? WHERE seller_id = ?",
        [$newBalance, $sellerId]
    )->execute();
    
    echo "Wallet topped up: Rs $topUpAmount\n";
    echo "New balance: Rs $newBalance\n";
} else {
    echo "Wallet balance already sufficient: Rs $currentBalance\n";
}

$wallet = $walletModel->getWalletBySellerId($sellerId);
echo "Current wallet balance: Rs {$wallet['balance']}\n\n";

// Step 3: Get product from this seller
echo "--- Step 3: Getting seller product ---\n";
$product = $db->query(
    "SELECT * FROM products WHERE seller_id = ? AND status = 'active' LIMIT 1",
    [$sellerId]
)->single();

if (!$product) {
    echo "ERROR: No products found for seller. Please create a product first.\n";
    exit(1);
}

echo "Product ID: {$product['id']}\n";
echo "Product Name: {$product['product_name']}\n";
echo "Price: Rs {$product['price']}\n\n";

// Step 4: Create multiple orders
echo "--- Step 4: Creating multiple orders ---\n";
$numOrders = 5;
$orderIds = [];
$totalOrderAmount = 0;

for ($i = 1; $i <= $numOrders; $i++) {
    echo "\n--- Creating Order #$i ---\n";
    
    // Generate invoice number
    $invoice = 'NTX' . date('Ymd') . rand(1000, 9999);
    
    // Create order using direct SQL to ensure all required fields
    $db->query(
        "INSERT INTO orders (
            invoice, user_id, customer_name, contact_no, payment_method_id,
            status, address, total_amount, tax_amount, discount_amount, 
            delivery_fee, payment_status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
        [
            $invoice,
            $userId,
            'Test Customer',
            '9765470926',
            1, // payment_method_id (assuming 1 exists)
            'pending',
            "Test Address #$i, Test City",
            $product['price'],
            0, // tax_amount
            0, // discount_amount
            0, // delivery_fee
            'pending'
        ]
    )->execute();
    
    $orderId = $db->lastInsertId();
    $orderIds[] = $orderId;
    $totalOrderAmount += $product['price'];
    
    echo "Order created: ID $orderId (Invoice: $invoice)\n";

    // Add order item with seller_id
    $db->query(
        "INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, total, invoice) 
         VALUES (?, ?, ?, 1, ?, ?, ?)",
        [$orderId, $product['id'], $sellerId, $product['price'], $product['price'], $invoice]
    )->execute();

    echo "Order item added\n";
}

echo "\nTotal orders created: " . count($orderIds) . "\n";
echo "Total order amount: Rs $totalOrderAmount\n\n";

// Step 5: Admin changes status to delivered and marks payment as paid
echo "--- Step 5: Admin marking orders as delivered and paid ---\n";
$deliveredAt = date('Y-m-d H:i:s', strtotime('-25 hours')); // 25 hours ago to bypass wait period

foreach ($orderIds as $orderId) {
    echo "\n--- Processing Order #$orderId ---\n";
    
    // Mark as delivered with delivered_at set to 25 hours ago
    $db->query(
        "UPDATE orders SET 
         status = 'delivered', 
         payment_status = 'paid', 
         delivered_at = ?,
         updated_at = NOW() 
         WHERE id = ?",
        [$deliveredAt, $orderId]
    )->execute();

    $order = $orderModel->find($orderId);
    echo "Order status: {$order['status']}\n";
    echo "Payment status: {$order['payment_status']}\n";
    echo "Delivered at: {$order['delivered_at']}\n";
}

echo "\n";

// Step 6: Process balance release for all orders
echo "--- Step 6: Processing seller balance release for all orders ---\n";
$totalReleased = 0;

foreach ($orderIds as $orderId) {
    echo "\n--- Releasing balance for Order #$orderId ---\n";
    
    $result = $balanceService->processBalanceRelease($orderId);
    
    if ($result['success']) {
        $released = $result['total_released'] ?? 0;
        $totalReleased += $released;
        echo "✓ Balance released: Rs $released\n";
    } else {
        echo "✗ Failed: {$result['message']}\n";
    }
}

$wallet = $walletModel->getWalletBySellerId($sellerId);
echo "\n--- Balance Summary ---\n";
echo "Total balance released: Rs $totalReleased\n";
echo "Current wallet balance: Rs {$wallet['balance']}\n";
echo "Total earnings: Rs {$wallet['total_earnings']}\n\n";

// Step 7: Verify wallet meets minimum for ads
echo "--- Step 7: Verifying wallet meets minimum for ads ---\n";
$wallet = $walletModel->getWalletBySellerId($sellerId);
$currentBalance = (float)$wallet['balance'];
$minBalance = 10000.00;

if ($currentBalance >= $minBalance) {
    echo "✓ Wallet balance (Rs $currentBalance) meets minimum requirement (Rs $minBalance)\n";
    echo "✓ Ads can be displayed\n\n";
} else {
    echo "✗ Wallet balance (Rs $currentBalance) is below minimum (Rs $minBalance)\n";
    echo "⚠ Ads may not be displayed\n\n";
}

// Step 8: Create banner ad
echo "--- Step 8: Creating banner ad ---\n";
$bannerAdType = $adTypeModel->findByName('banner_external');
if (!$bannerAdType) {
    echo "ERROR: banner_external ad type not found\n";
    exit(1);
}

$bannerCosts = $adCostModel->getByAdType($bannerAdType['id']);
if (empty($bannerCosts)) {
    echo "ERROR: No banner ad costs found\n";
    exit(1);
}

$bannerCostId = $bannerCosts[0]['id'];
$bannerCostAmount = $bannerCosts[0]['cost_amount'];

$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));

$bannerAdId = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/test-banner.jpg',
    'banner_link' => 'https://example.com',
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $bannerCostId,
    'status' => 'inactive',
    'notes' => 'Test: Full flow banner ad'
]);

echo "Banner ad created: ID $bannerAdId\n";

// Create payment record
$paymentId = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $bannerAdId,
    'amount' => $bannerCostAmount,
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

echo "Payment record created: ID $paymentId\n\n";

// Step 9: Process ad payment from wallet
echo "--- Step 9: Processing ad payment from wallet ---\n";
$walletBefore = $walletModel->getWalletBySellerId($sellerId);
echo "Wallet balance before payment: Rs {$walletBefore['balance']}\n";
echo "Ad cost: Rs $bannerCostAmount\n";

try {
    $paymentService->processPayment($bannerAdId, 'wallet');
    echo "✓ Payment processed successfully\n";
    
    $walletAfter = $walletModel->getWalletBySellerId($sellerId);
    echo "Wallet balance after payment: Rs {$walletAfter['balance']}\n";
    
    $deducted = $walletBefore['balance'] - $walletAfter['balance'];
    echo "Amount deducted: Rs $deducted\n";
    
    $payment = $adPaymentModel->getByAdId($bannerAdId);
    echo "Payment status: {$payment['payment_status']}\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: Payment failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Step 10: Activate ad
echo "--- Step 10: Activating ad ---\n";
$adModel->updateStatus($bannerAdId, 'active');
$ad = $adModel->find($bannerAdId);
echo "Ad status: {$ad['status']}\n\n";

// Step 11: Test if ad shows
echo "--- Step 11: Testing if ad shows ---\n";

// Test getHomepageBanner
$homepageBanner = $bannerService->getHomepageBanner();
if ($homepageBanner) {
    echo "✓ Homepage banner found:\n";
    echo "  Ad ID: {$homepageBanner['id']}\n";
    echo "  Banner Image: {$homepageBanner['banner_image']}\n";
    echo "  Banner Link: {$homepageBanner['banner_link']}\n";
    
    if ($homepageBanner['id'] == $bannerAdId) {
        echo "  ✓ Our test ad is being displayed!\n";
    } else {
        echo "  ⚠ INFO: Different ad is being displayed (may be due to probability formula)\n";
    }
} else {
    echo "✗ ERROR: Homepage banner not found\n";
}

echo "\n";

// Test getActiveBannerAds
$activeBanners = $adModel->getActiveBannerAds(10);
if (!empty($activeBanners)) {
    echo "✓ Found " . count($activeBanners) . " active banner ads:\n";
    $ourAdFound = false;
    foreach ($activeBanners as $banner) {
        echo "  Ad #{$banner['id']}: {$banner['banner_image']}\n";
        if ($banner['id'] == $bannerAdId) {
            $ourAdFound = true;
        }
    }
    
    if ($ourAdFound) {
        echo "  ✓ Our test ad is in active banners list!\n";
    } else {
        echo "  ⚠ INFO: Our test ad not in list (may be filtered by other criteria)\n";
    }
} else {
    echo "✗ ERROR: No active banner ads found\n";
}

echo "\n";

// Verify ad requirements
echo "--- Step 12: Verifying ad requirements ---\n";
$ad = $adModel->find($bannerAdId);
$payment = $adPaymentModel->getByAdId($bannerAdId);

$checks = [
    'Status is active' => $ad['status'] === 'active',
    'Not suspended' => $ad['status'] !== 'suspended',
    'Payment is paid' => $payment['payment_status'] === 'paid',
    'Date range valid' => ($ad['start_date'] <= date('Y-m-d') && $ad['end_date'] >= date('Y-m-d')),
    'Has banner_image' => !empty($ad['banner_image']),
    'Has banner_link' => !empty($ad['banner_link'])
];

echo "Ad Requirements Check:\n";
$allPassed = true;
foreach ($checks as $check => $passed) {
    $status = $passed ? '✓' : '✗';
    echo "  $status $check\n";
    if (!$passed) {
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\n✓ All requirements met - ad should display!\n";
} else {
    echo "\n✗ Some requirements not met - ad may not display\n";
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
$finalWallet = $walletModel->getWalletBySellerId($sellerId);
echo "Number of Orders Created: " . count($orderIds) . "\n";
echo "Order IDs: " . implode(', ', $orderIds) . "\n";
echo "Total Order Amount: Rs $totalOrderAmount\n";
echo "Total Balance Released: Rs $totalReleased\n";
echo "Seller Wallet Balance: Rs {$finalWallet['balance']}\n";
echo "Seller Total Earnings: Rs {$finalWallet['total_earnings']}\n";
echo "Wallet Minimum Required: Rs $minBalance\n";
echo "Wallet Status: " . ($finalWallet['balance'] >= $minBalance ? "✓ Meets minimum" : "✗ Below minimum") . "\n";
echo "Banner Ad ID: $bannerAdId\n";
echo "Ad Status: {$ad['status']}\n";
echo "Payment Status: {$payment['payment_status']}\n";
echo "\n";
echo "Expected Results:\n";
echo "✓ Multiple orders created successfully\n";
echo "✓ All orders marked as delivered\n";
echo "✓ All payments marked as paid\n";
echo "✓ Seller balance released for all orders\n";
echo "✓ Seller wallet balance calculated correctly\n";
echo "✓ Seller wallet has minimum 10000 (or topped up)\n";
echo "✓ Banner ad created and paid\n";
echo "✓ Ad activated\n";
echo "✓ Ad displays on homepage and other positions\n";
echo "\n";
echo "Test completed!\n";

