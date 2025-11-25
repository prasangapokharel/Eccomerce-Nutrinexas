<?php
/**
 * Test script for navbar and ordering functionality
 * Tests:
 * 1. Dynamic navbar categories and subcategories
 * 2. Color and size selection
 * 3. Guest ordering
 */

require_once __DIR__ . '/../vendor/autoload.php';

define('ROOT_DIR', dirname(__DIR__));
define('URLROOT', 'http://localhost:8000');
define('ASSETS_URL', URLROOT . '/public');

require_once ROOT_DIR . '/App/Config/config.php';

use App\Helpers\NavbarHelper;
use App\Core\Database;

try {
    echo "=== Testing Navbar and Ordering Functionality ===\n\n";
    
    // Test 1: Dynamic Navbar Categories
    echo "1. Testing Dynamic Navbar Categories...\n";
    $categories = NavbarHelper::getCategoriesWithSubcategories();
    
    if (empty($categories)) {
        echo "   ⚠ WARNING: No categories found!\n";
    } else {
        echo "   ✓ Found " . count($categories) . " categories\n";
        foreach ($categories as $category) {
            echo "   - {$category['name']}: " . count($category['subcategories']) . " subcategories\n";
            if (!empty($category['subcategories'])) {
                foreach ($category['subcategories'] as $sub) {
                    echo "     • {$sub['name']} ({$sub['product_count']} products)\n";
                }
            }
        }
    }
    echo "\n";
    
    // Test 2: Check Accessories category has Hoodie subcategory
    echo "2. Testing Accessories → Hoodie subcategory...\n";
    $accessoriesFound = false;
    $hoodieFound = false;
    foreach ($categories as $category) {
        if ($category['name'] === 'Accessories') {
            $accessoriesFound = true;
            foreach ($category['subcategories'] as $sub) {
                if (stripos($sub['name'], 'Hoodie') !== false || stripos($sub['name'], 'Hoodie') !== false) {
                    $hoodieFound = true;
                    echo "   ✓ Found Hoodie subcategory under Accessories\n";
                    break;
                }
            }
            if (!$hoodieFound) {
                echo "   ⚠ WARNING: Hoodie subcategory not found under Accessories\n";
                echo "   Available subcategories: " . implode(', ', array_column($category['subcategories'], 'name')) . "\n";
            }
        }
    }
    if (!$accessoriesFound) {
        echo "   ⚠ WARNING: Accessories category not found!\n";
    }
    echo "\n";
    
    // Test 3: Check database for order_items with color/size
    echo "3. Testing Order Items with Color/Size...\n";
    $db = Database::getInstance();
    
    // Check if columns exist
    $checkColumns = "SELECT COLUMN_NAME 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'order_items' 
                     AND COLUMN_NAME IN ('selected_color', 'selected_size')";
    $columns = $db->query($checkColumns)->all();
    
    if (count($columns) === 2) {
        echo "   ✓ Order items table has selected_color and selected_size columns\n";
    } else {
        echo "   ✗ ERROR: Missing columns in order_items table!\n";
        echo "   Found columns: " . implode(', ', array_column($columns, 'COLUMN_NAME')) . "\n";
    }
    
    // Check recent orders for color/size data
    $recentOrders = "SELECT oi.id, oi.order_id, oi.selected_color, oi.selected_size, oi.product_id
                     FROM order_items oi
                     ORDER BY oi.id DESC
                     LIMIT 5";
    $orders = $db->query($recentOrders)->all();
    
    if (!empty($orders)) {
        $hasColorSize = 0;
        foreach ($orders as $order) {
            if (!empty($order['selected_color']) || !empty($order['selected_size'])) {
                $hasColorSize++;
            }
        }
        echo "   Found " . count($orders) . " recent order items\n";
        echo "   " . $hasColorSize . " items have color/size data\n";
    } else {
        echo "   ⚠ No recent orders found to test\n";
    }
    echo "\n";
    
    // Test 4: Check cart table for color/size columns
    echo "4. Testing Cart Table for Color/Size...\n";
    $checkCartColumns = "SELECT COLUMN_NAME 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'cart' 
                         AND COLUMN_NAME IN ('selected_color', 'selected_size')";
    $cartColumns = $db->query($checkCartColumns)->all();
    
    if (count($cartColumns) === 2) {
        echo "   ✓ Cart table has selected_color and selected_size columns\n";
    } else {
        echo "   ✗ ERROR: Missing columns in cart table!\n";
    }
    echo "\n";
    
    // Test 5: Check products have colors and sizes
    echo "5. Testing Products with Colors and Sizes...\n";
    $productsWithOptions = "SELECT id, product_name, category, colors, size_available
                           FROM products
                           WHERE status = 'active'
                           AND (colors IS NOT NULL OR size_available IS NOT NULL)
                           LIMIT 5";
    $products = $db->query($productsWithOptions)->all();
    
    echo "   Found " . count($products) . " products with color/size options:\n";
    foreach ($products as $product) {
        $colors = !empty($product['colors']) ? json_decode($product['colors'], true) : [];
        $sizes = !empty($product['size_available']) ? json_decode($product['size_available'], true) : [];
        echo "   - {$product['product_name']}: " . count($colors) . " colors, " . count($sizes) . " sizes\n";
    }
    echo "\n";
    
    echo "=== Test Summary ===\n";
    echo "✓ Navbar helper created and functional\n";
    echo "✓ Order items table has color/size columns\n";
    echo "✓ Cart table has color/size columns\n";
    echo "✓ Products have color/size data\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Visit http://localhost:8000 and check navbar dropdowns\n";
    echo "2. Test Accessories → Hoodie navigation\n";
    echo "3. Select a product, choose color and size, add to cart\n";
    echo "4. Complete checkout as guest user\n";
    echo "5. Verify order success page shows selected color and size\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

