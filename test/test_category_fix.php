<?php
/**
 * Test: Verify category page fix
 */

require_once __DIR__ . '/../App/Config/config.php';

if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Services\SponsoredAdsService;

echo "=== Test: Category Page Fix ===\n\n";

$sponsoredAdsService = new SponsoredAdsService();

try {
    echo "Testing getSponsoredProductsForCategory('Supplements', NULL, 15)...\n";
    $products = $sponsoredAdsService->getSponsoredProductsForCategory('Supplements', NULL, 15);
    echo "✓ Success! Found " . count($products) . " sponsored products\n\n";
    
    if (count($products) > 0) {
        echo "Sample products:\n";
        foreach (array_slice($products, 0, 3) as $product) {
            echo "  - Product #{$product['id']}: {$product['product_name']} (Ad #{$product['ad_id']})\n";
        }
    }
    
    echo "\n✓ Category page should now work without errors!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

