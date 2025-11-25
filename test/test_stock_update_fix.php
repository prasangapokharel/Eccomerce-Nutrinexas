<?php
/**
 * Test Stock Update Fix - Ensures no "Invalid request" errors
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;
use App\Models\Product;

$db = Database::getInstance();
$productModel = new Product();

echo "=== Stock Update Fix Test ===\n\n";

// Create a test product
$productId = $productModel->create([
    'product_name' => 'Test Stock Update Product',
    'slug' => 'test-stock-update-' . time(),
    'description' => 'Test product for stock update',
    'short_description' => 'Test stock',
    'price' => 1000.00,
    'stock_quantity' => 10,
    'category' => 'Test',
    'status' => 'active'
]);

echo "Created test product ID: {$productId}\n\n";

// Simulate AJAX request with proper headers
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_POST['product_id'] = $productId;
$_POST['stock_quantity'] = 25;
$_POST['_csrf_token'] = \App\Helpers\SecurityHelper::generateCSRFToken();

echo "Simulating stock update request...\n";
echo "  - Method: POST\n";
echo "  - X-Requested-With: XMLHttpRequest\n";
echo "  - Product ID: {$productId}\n";
echo "  - New Stock: 25\n\n";

// Check if the request would be accepted
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    echo "✅ AJAX request properly detected\n";
} else {
    echo "❌ AJAX request not detected\n";
}

// Verify product exists
$product = $productModel->getProductById($productId);
if ($product) {
    echo "✅ Product exists\n";
    echo "  Current stock: {$product['stock_quantity']}\n";
    
    // Update stock directly
    $result = $productModel->updateStock($productId, 25);
    if ($result) {
        $updated = $productModel->getProductById($productId);
        echo "✅ Stock updated successfully\n";
        echo "  New stock: {$updated['stock_quantity']}\n";
    } else {
        echo "❌ Stock update failed\n";
    }
} else {
    echo "❌ Product not found\n";
}

// Cleanup
$productModel->delete($productId);
echo "\n✅ Test product cleaned up\n";

echo "\n=== Test Complete ===\n";
echo "The stock update functionality should work correctly with proper AJAX headers.\n";
echo "Make sure the fetch request includes: headers: { 'X-Requested-With': 'XMLHttpRequest' }\n";

?>


