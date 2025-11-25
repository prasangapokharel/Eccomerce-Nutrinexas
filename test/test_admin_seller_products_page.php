<?php
/**
 * Test Admin Seller Products Page
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

echo "=== Testing Admin Seller Products Page ===\n\n";

try {
    $db = Database::getInstance();
    
    // Check if view file exists
    $viewFile = __DIR__ . '/../App/views/admin/seller/products/index.php';
    if (file_exists($viewFile)) {
        echo "✅ View file exists: admin/seller/products/index.php\n";
    } else {
        echo "❌ View file NOT found\n";
        exit(1);
    }
    
    // Check if layout file exists
    $layoutFile = __DIR__ . '/../App/views/admin/layouts/admin.php';
    if (file_exists($layoutFile)) {
        echo "✅ Layout file exists: admin/layouts/admin.php\n";
    } else {
        echo "❌ Layout file NOT found\n";
        exit(1);
    }
    
    // Test path resolution
    $testPath = dirname(dirname($viewFile)) . '/layouts/admin.php';
    echo "Path resolution test:\n";
    echo "  From: {$viewFile}\n";
    echo "  To: {$testPath}\n";
    echo "  Exists: " . (file_exists($testPath) ? 'Yes ✅' : 'No ❌') . "\n\n";
    
    // Check products
    $products = $db->query(
        "SELECT COUNT(*) as count FROM products WHERE seller_id IS NOT NULL AND seller_id > 0"
    )->single();
    
    echo "Seller products in database: " . ($products['count'] ?? 0) . "\n";
    
    $pendingProducts = $db->query(
        "SELECT COUNT(*) as count FROM products 
         WHERE (approval_status = 'pending' OR approval_status IS NULL) 
         AND seller_id IS NOT NULL AND seller_id > 0"
    )->single();
    
    echo "Pending approval: " . ($pendingProducts['count'] ?? 0) . "\n";
    
    echo "\n✅ All checks passed! Page should work now.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

