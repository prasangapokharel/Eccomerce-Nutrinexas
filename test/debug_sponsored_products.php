<?php
/**
 * Debug Sponsored Products in Search
 */

require_once __DIR__ . '/../App/Config/config.php';

// Define constants if not already defined
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}

// Autoloader
spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Services\SponsoredAdsService;

echo "=== Debug Sponsored Products ===\n\n";

$db = Database::getInstance();
$adsService = new SponsoredAdsService();

$keyword = 'yoga bar';

// Get regular products
$regularProducts = $db->query(
    "SELECT p.*, 
            COALESCE(pi.image_url, p.image) as image_url
     FROM products p
     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
     WHERE p.status = 'active'
     AND (p.product_name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)
     LIMIT 10",
    ["%{$keyword}%", "%{$keyword}%", "%{$keyword}%"]
)->all();

echo "Regular products found: " . count($regularProducts) . "\n\n";

// Insert sponsored ads
$productsWithAds = $adsService->insertSponsoredInSearchResults($regularProducts, $keyword);

echo "Products after ad insertion: " . count($productsWithAds) . "\n\n";

echo "=== Product List with Sponsored Flag ===\n";
foreach ($productsWithAds as $index => $product) {
    $isSponsored = !empty($product['is_sponsored']) ? 'YES' : 'NO';
    $adId = $product['ad_id'] ?? 'N/A';
    echo "Position {$index}: {$product['product_name']}\n";
    echo "  - is_sponsored: {$isSponsored}\n";
    echo "  - ad_id: {$adId}\n";
    echo "  - image_url: " . ($product['image_url'] ?? 'N/A') . "\n";
    echo "\n";
}

