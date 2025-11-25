<?php
/**
 * Verify Products Display on Homepage
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Product;

echo "=== Verifying Products Display ===\n\n";

try {
    $db = Database::getInstance();
    $productModel = new Product();
    
    // Get the 5 products we just created
    $productIds = [112, 113, 114, 115, 116];
    
    echo "Checking products:\n";
    foreach ($productIds as $productId) {
        $product = $productModel->find($productId);
        
        if ($product) {
            $status = $product['status'];
            $approvalStatus = $product['approval_status'] ?? 'N/A';
            $sellerId = $product['seller_id'] ?? 'N/A';
            
            echo "  Product ID {$productId}: {$product['product_name']}\n";
            echo "    Status: {$status}\n";
            echo "    Approval: {$approvalStatus}\n";
            echo "    Seller ID: {$sellerId}\n";
            
            // Check images
            $images = $db->query(
                "SELECT * FROM product_images WHERE product_id = ?",
                [$productId]
            )->all();
            
            if (!empty($images)) {
                echo "    Images: " . count($images) . " found\n";
                foreach ($images as $img) {
                    echo "      - {$img['image_url']}\n";
                }
            } else {
                echo "    ⚠ No images found\n";
            }
            
            // Check if product is visible (active and approved)
            if ($status === 'active' && $approvalStatus === 'approved') {
                echo "    ✅ Product is ACTIVE and APPROVED - Should show on homepage\n";
            } else {
                echo "    ❌ Product is NOT visible (Status: {$status}, Approval: {$approvalStatus})\n";
            }
            
            echo "\n";
        } else {
            echo "  ❌ Product ID {$productId} NOT FOUND\n\n";
        }
    }
    
    // Check homepage query
    echo "Checking homepage product query...\n";
    $homepageProducts = $productModel->getAllProducts(0, 20, ['status' => 'active']);
    
    $foundCount = 0;
    foreach ($homepageProducts as $prod) {
        if (in_array($prod['id'], $productIds)) {
            $foundCount++;
            echo "  ✓ Product ID {$prod['id']} found in homepage query\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Products found in homepage query: {$foundCount}/5\n";
    
    if ($foundCount === 5) {
        echo "✅ ALL PRODUCTS ARE VISIBLE ON HOMEPAGE!\n";
    } else {
        echo "⚠️  Some products may not be visible. Check status and approval_status.\n";
    }
    
    // Check cache
    $cacheDir = __DIR__ . '/../App/storage/cache';
    $cacheFiles = is_dir($cacheDir) ? count(glob($cacheDir . '/*')) : 0;
    echo "Cache files: {$cacheFiles}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

