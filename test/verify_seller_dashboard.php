<?php
/**
 * Verify Seller Dashboard Shows Products
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Product;
use App\Models\Seller;

echo "=== Verifying Seller Dashboard ===\n\n";

try {
    $db = Database::getInstance();
    $productModel = new Product();
    $sellerModel = new Seller();
    
    $seller = $sellerModel->findByEmail('test-seller@nutrinexus.com');
    
    if (!$seller) {
        echo "❌ Seller not found\n";
        exit(1);
    }
    
    echo "Seller: {$seller['name']} (ID: {$seller['id']})\n\n";
    
    // Get seller products
    $sellerProducts = $productModel->getProductsBySellerId($seller['id'], 20, 0);
    
    echo "Products in Seller Dashboard: " . count($sellerProducts) . "\n\n";
    
    foreach ($sellerProducts as $product) {
        $status = $product['status'];
        $approval = $product['approval_status'] ?? 'N/A';
        
        echo "  Product ID {$product['id']}: {$product['product_name']}\n";
        echo "    Status: {$status}\n";
        echo "    Approval: {$approval}\n";
        echo "    Price: रु " . number_format($product['price'], 2) . "\n";
        
        if ($status === 'active' && $approval === 'approved') {
            echo "    ✅ Live on website\n";
        } elseif ($status === 'pending' || $approval === 'pending') {
            echo "    ⏳ Pending approval\n";
        } else {
            echo "    ❌ Not visible\n";
        }
        echo "\n";
    }
    
    // Check product counts
    $stats = [
        'total' => count($sellerProducts),
        'active' => 0,
        'pending' => 0,
        'approved' => 0
    ];
    
    foreach ($sellerProducts as $product) {
        if ($product['status'] === 'active') $stats['active']++;
        if ($product['status'] === 'pending') $stats['pending']++;
        if ($product['approval_status'] === 'approved') $stats['approved']++;
    }
    
    echo "=== Seller Product Stats ===\n";
    echo "Total Products: {$stats['total']}\n";
    echo "Active: {$stats['active']}\n";
    echo "Pending: {$stats['pending']}\n";
    echo "Approved: {$stats['approved']}\n";
    
    if ($stats['total'] === 5 && $stats['approved'] === 5 && $stats['active'] === 5) {
        echo "\n✅ All products visible in seller dashboard!\n";
    } else {
        echo "\n⚠️  Some products may not be showing correctly\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

