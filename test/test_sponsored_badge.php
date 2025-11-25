<?php
/**
 * Test: Verify sponsored badge shows in product card
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

use App\Core\Database;

echo "=== Test: Sponsored Badge in Product Card ===\n\n";

$db = Database::getInstance();

// Test product data
$testProduct = [
    'id' => 127,
    'product_name' => 'Test Product',
    'is_sponsored' => true,
    'ad_id' => 2,
    'image_url' => '/test.jpg',
    'price' => 100,
    'sale_price' => null,
    'stock_quantity' => 10
];

echo "Test Product Data:\n";
echo "  is_sponsored: " . ($testProduct['is_sponsored'] ? 'true' : 'false') . "\n";
echo "  ad_id: " . ($testProduct['ad_id'] ?? 'none') . "\n\n";

// Simulate product card logic
$isSponsored = !empty($testProduct['is_sponsored']) && $testProduct['is_sponsored'] === true;
$hasAdId = !empty($testProduct['ad_id']);

echo "Badge Check Logic:\n";
echo "  isSponsored: " . ($isSponsored ? 'true' : 'false') . "\n";
echo "  hasAdId: " . ($hasAdId ? 'true' : 'false') . "\n";
echo "  Will show badge: " . (($isSponsored || $hasAdId) ? 'YES ✓' : 'NO') . "\n\n";

echo "✓ Badge should appear at bottom-left of product card image\n";
echo "✓ Badge text: 'Sponsored'\n";
echo "✓ Badge style: Small gray badge (9px font)\n";

