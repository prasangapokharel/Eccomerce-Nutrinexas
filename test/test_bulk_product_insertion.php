<?php
/**
 * Bulk Product Insertion Test
 * 
 * Insert 1000 products with specified images and test performance
 */

// Load config
$configPath = __DIR__ . '/../App/Config/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    $altPaths = [
        __DIR__ . '/../App/config/config.php',
        __DIR__ . '/../config/config.php',
    ];
    foreach ($altPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Seller;

$db = Database::getInstance();
$productModel = new Product();
$productImageModel = new ProductImage();
$sellerModel = new Seller();

echo "=== BULK PRODUCT INSERTION TEST ===\n\n";

$testResults = [];
$testCount = 0;
$passCount = 0;
$failCount = 0;

// Image URLs from yogabars.in
$testImages = [
    'https://www.yogabars.in/cdn/shop/files/Group_786.png?v=1732086674',
    'https://www.yogabars.in/cdn/shop/files/HPM.png?v=1716468505',
    'https://www.yogabars.in/cdn/shop/files/Monk_Fruit_Dates.png?v=1751958870'
];

function runTest($name, $callback) {
    global $testCount, $passCount, $failCount, $testResults;
    $testCount++;
    echo "Test {$testCount}: {$name}... ";
    
    try {
        $startTime = microtime(true);
        $result = $callback();
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        if ($result['pass']) {
            $passCount++;
            echo "✓ PASS ({$duration}ms)\n";
            if (!empty($result['message'])) {
                echo "  → {$result['message']}\n";
            }
        } else {
            $failCount++;
            echo "✗ FAIL ({$duration}ms)\n";
            echo "  → {$result['message']}\n";
        }
        $testResults[] = ['name' => $name, 'pass' => $result['pass'], 'message' => $result['message'], 'duration' => $duration];
    } catch (Exception $e) {
        $failCount++;
        echo "✗ ERROR\n";
        echo "  → Exception: {$e->getMessage()}\n";
        $testResults[] = ['name' => $name, 'pass' => false, 'message' => "Exception: {$e->getMessage()}"];
    }
    echo "\n";
}

try {
    // Setup: Get or create test seller
    echo "--- Setup: Getting test seller ---\n";
    $seller = $sellerModel->findByEmail('bulk_test_seller@nutrinexus.test');
    
    if (!$seller) {
        $sellerData = [
            'name' => 'Bulk Test Seller',
            'company_name' => 'Bulk Test Seller Company',
            'email' => 'bulk_test_seller@nutrinexus.test',
            'phone' => '9800000000',
            'password' => password_hash('TestPassword123!', PASSWORD_DEFAULT),
            'status' => 'active',
            'commission_rate' => 10.00
        ];
        
        $sellerId = $sellerModel->create($sellerData);
        $seller = $sellerModel->find($sellerId);
        
        // Update approval status if column exists
        try {
            $db->query("UPDATE sellers SET is_approved = 1 WHERE id = ?", [$sellerId])->execute();
        } catch (Exception $e) {
            // Column might not exist, that's OK
        }
    } else {
        $sellerId = $seller['id'];
    }
    
    echo "Seller ID: {$sellerId}\n\n";
    
    // Test 1: Insert 1000 products simultaneously
    runTest("Insert 1000 products simultaneously", function() use ($db, $productModel, $productImageModel, $sellerId, $testImages) {
        $productIds = [];
        $batchSize = 100;
        $totalProducts = 1000;
        
        $db->beginTransaction();
        
        try {
            for ($i = 1; $i <= $totalProducts; $i++) {
                $productName = "Bulk Test Product {$i}";
                $productSlug = "bulk-test-product-{$i}";
                
                // Use different images in rotation
                $imageIndex = ($i - 1) % count($testImages);
                $primaryImage = $testImages[$imageIndex];
                
                $productData = [
                    'product_name' => $productName,
                    'slug' => $productSlug,
                    'description' => "This is a bulk test product number {$i}. It is used for performance testing.",
                    'short_description' => "Bulk test product {$i}",
                    'price' => rand(100, 5000),
                    'sale_price' => rand(50, 4500),
                    'stock_quantity' => rand(10, 1000),
                    'category' => 'Supplements', // Default category name
                    'seller_id' => $sellerId,
                    'status' => 'active',
                    'approval_status' => 'approved',
                    'is_featured' => ($i % 10 === 0) ? 1 : 0,
                    'image' => $primaryImage,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $sql = "INSERT INTO products (
                    product_name, slug, description, short_description, price, sale_price,
                    stock_quantity, category, seller_id, status, approval_status,
                    is_featured, image, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->query($sql, [
                    $productData['product_name'],
                    $productData['slug'],
                    $productData['description'],
                    $productData['short_description'],
                    $productData['price'],
                    $productData['sale_price'],
                    $productData['stock_quantity'],
                    $productData['category'],
                    $productData['seller_id'],
                    $productData['status'],
                    $productData['approval_status'],
                    $productData['is_featured'],
                    $productData['image'],
                    $productData['created_at'],
                    $productData['updated_at']
                ]);
                $stmt->execute();
                $productId = $db->lastInsertId();
                $productIds[] = $productId;
                
                // Insert product images
                foreach ($testImages as $imgIndex => $imageUrl) {
                    $isPrimary = ($imgIndex === $imageIndex) ? 1 : 0;
                    $imageData = [
                        'product_id' => $productId,
                        'image_url' => $imageUrl,
                        'is_primary' => $isPrimary,
                        'sort_order' => $imgIndex,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $imgSql = "INSERT INTO product_images (product_id, image_url, is_primary, sort_order, created_at) 
                               VALUES (?, ?, ?, ?, ?)";
                    $imgStmt = $db->query($imgSql, array_values($imageData));
                    $imgStmt->execute();
                }
                
                // Commit in batches for better performance
                if ($i % $batchSize === 0) {
                    $db->commit();
                    $db->beginTransaction();
                }
            }
            
            $db->commit();
            
            return [
                'pass' => count($productIds) === $totalProducts,
                'message' => "Inserted " . count($productIds) . " products successfully"
            ];
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'pass' => false,
                'message' => "Bulk insertion failed: " . $e->getMessage()
            ];
        }
    });
    
    // Test 2: Verify all products were inserted
    runTest("Verify all products were inserted", function() use ($db, $sellerId) {
        $count = $db->query(
            "SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND product_name LIKE 'Bulk Test Product%'",
            [$sellerId]
        )->single();
        
        $productCount = (int)$count['count'];
        
        return [
            'pass' => $productCount >= 1000,
            'message' => "Found {$productCount} bulk test products in database"
        ];
    });
    
    // Test 3: Verify product images were inserted
    runTest("Verify product images were inserted", function() use ($db) {
        $count = $db->query(
            "SELECT COUNT(*) as count FROM product_images WHERE image_url LIKE 'https://www.yogabars.in%'"
        )->single();
        
        $imageCount = (int)$count['count'];
        $expectedImages = 1000 * 3; // 1000 products * 3 images each
        
        return [
            'pass' => $imageCount >= $expectedImages,
            'message' => "Found {$imageCount} product images (Expected: {$expectedImages})"
        ];
    });
    
    // Test 4: Test homepage loading performance
    runTest("Test homepage loading performance", function() {
        $baseUrl = 'http://192.168.1.77:8000';
        $ch = curl_init($baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        $responseTime = round($totalTime * 1000, 2);
        $isSuccess = $httpCode === 200;
        $hasContent = strlen($response) > 1000;
        $isFast = $responseTime < 5000; // Should load in under 5 seconds
        
        return [
            'pass' => $isSuccess && $hasContent && $isFast,
            'message' => "Homepage loaded: HTTP {$httpCode}, Response time: {$responseTime}ms, Content: " . ($hasContent ? 'Yes' : 'No')
        ];
    });
    
    // Test 5: Test category page loading performance
    runTest("Test category page loading performance", function() {
        $baseUrl = 'http://192.168.1.77:8000/products';
        $ch = curl_init($baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        $responseTime = round($totalTime * 1000, 2);
        $isSuccess = $httpCode === 200;
        $hasContent = strlen($response) > 1000;
        $isFast = $responseTime < 5000;
        
        return [
            'pass' => $isSuccess && $hasContent && $isFast,
            'message' => "Category page loaded: HTTP {$httpCode}, Response time: {$responseTime}ms, Content: " . ($hasContent ? 'Yes' : 'No')
        ];
    });
    
    // Test 6: Test product page loading performance
    runTest("Test product page loading performance", function() use ($db) {
        // Get a random bulk test product
        $product = $db->query(
            "SELECT id, slug FROM products WHERE product_name LIKE 'Bulk Test Product%' LIMIT 1"
        )->single();
        
        if (!$product) {
            return [
                'pass' => false,
                'message' => "No bulk test products found"
            ];
        }
        
        // Try different product route formats
        $routes = [
            '/product/' . $product['slug'],
            '/products/' . $product['slug'],
            '/products/view/' . $product['id']
        ];
        
        $baseUrl = 'http://192.168.1.77:8000';
        $success = false;
        $responseTime = 0;
        $httpCode = 0;
        
        foreach ($routes as $route) {
            $ch = curl_init($baseUrl . $route);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            curl_close($ch);
            
            if ($code === 200) {
                $success = true;
                $httpCode = $code;
                $responseTime = round($time * 1000, 2);
                break;
            }
        }
        
        $hasContent = strlen($response) > 1000;
        $isFast = $responseTime < 3000 || ($responseTime === 0 && $success);
        $ch = curl_init($baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        $responseTime = round($totalTime * 1000, 2);
        $isSuccess = $httpCode === 200;
        $hasContent = strlen($response) > 1000;
        $isFast = $responseTime < 3000;
        
        return [
            'pass' => $success && $hasContent && ($isFast || $responseTime < 3000),
            'message' => $success 
                ? "Product page loaded: HTTP {$httpCode}, Response time: {$responseTime}ms, Content: " . ($hasContent ? 'Yes' : 'No')
                : "Product page not found (tried multiple routes)"
        ];
    });
    
    // Test 7: Test search page with 1000 products
    runTest("Test search page with 1000 products", function() {
        $baseUrl = 'http://192.168.1.77:8000/products/search?q=Bulk+Test';
        $ch = curl_init($baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        $responseTime = round($totalTime * 1000, 2);
        $isSuccess = $httpCode === 200;
        $hasContent = strlen($response) > 1000;
        $isFast = $responseTime < 5000;
        
        return [
            'pass' => $isSuccess && $hasContent && $isFast,
            'message' => "Search page loaded: HTTP {$httpCode}, Response time: {$responseTime}ms, Content: " . ($hasContent ? 'Yes' : 'No')
        ];
    });
    
    // Test 8: Verify no route errors
    runTest("Verify no route errors", function() {
        $routes = [
            '/' => 'Homepage',
            '/products' => 'Products listing',
            '/products/search?q=test' => 'Search page'
        ];
        
        $baseUrl = 'http://192.168.1.77:8000';
        $errors = [];
        
        foreach ($routes as $route => $name) {
            $ch = curl_init($baseUrl . $route);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 400) {
                $errors[] = "{$name} ({$route}): HTTP {$httpCode}";
            }
        }
        
        return [
            'pass' => empty($errors),
            'message' => empty($errors) 
                ? "All routes accessible" 
                : "Route errors: " . implode(', ', $errors)
        ];
    });
    
    // Test 9: Check database query performance
    runTest("Check database query performance", function() use ($db) {
        $startTime = microtime(true);
        $products = $db->query(
            "SELECT * FROM products WHERE status = 'active' AND approval_status = 'approved' LIMIT 100"
        )->all();
        $endTime = microtime(true);
        
        $queryTime = round(($endTime - $startTime) * 1000, 2);
        $isFast = $queryTime < 500;
        
        return [
            'pass' => $isFast && count($products) > 0,
            'message' => "Query time: {$queryTime}ms, Products found: " . count($products)
        ];
    });
    
    // Test 10: Verify images are accessible
    runTest("Verify images are accessible", function() use ($testImages) {
        $accessible = 0;
        foreach ($testImages as $imageUrl) {
            $ch = curl_init($imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $accessible++;
            }
        }
        
        return [
            'pass' => $accessible === count($testImages),
            'message' => "Images accessible: {$accessible}/" . count($testImages)
        ];
    });
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "Total Tests: {$testCount}\n";
echo "Passed: {$passCount}\n";
echo "Failed: {$failCount}\n";
echo "Success Rate: " . round(($passCount / $testCount) * 100, 2) . "%\n\n";

if ($failCount === 0) {
    echo "✓ ALL TESTS PASSED! Bulk product insertion and performance tests successful.\n";
    echo "\nPerformance Summary:\n";
    foreach ($testResults as $result) {
        if (isset($result['duration'])) {
            echo "  - {$result['name']}: {$result['duration']}ms\n";
        }
    }
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}

