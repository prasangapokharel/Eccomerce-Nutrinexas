<?php
/**
 * Verify Ads for Products
 */

require_once __DIR__ . '/../App/Config/config.php';

// Autoloader
spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

$db = Database::getInstance();

echo "=== Verifying Ads for Products ===\n\n";

// Get products with "yoga bar" in name
$products = $db->query(
    "SELECT id, product_name FROM products WHERE product_name LIKE '%yoga bar%' LIMIT 10"
)->all();

echo "Products found: " . count($products) . "\n\n";

foreach ($products as $product) {
    $ads = $db->query(
        "SELECT a.id, a.status, a.start_date, a.end_date, at.name as ad_type
         FROM ads a
         INNER JOIN ads_types at ON a.ads_type_id = at.id
         WHERE a.product_id = ?",
        [$product['id']]
    )->all();
    
    echo "Product #{$product['id']}: {$product['product_name']}\n";
    if (empty($ads)) {
        echo "  ✗ No ads found\n";
    } else {
        foreach ($ads as $ad) {
            $isActive = ($ad['status'] === 'active' && 
                        date('Y-m-d') >= $ad['start_date'] && 
                        date('Y-m-d') <= $ad['end_date']);
            echo "  - Ad #{$ad['id']}: Status={$ad['status']}, Type={$ad['ad_type']}, Active=" . ($isActive ? 'YES' : 'NO') . "\n";
        }
    }
    echo "\n";
}

