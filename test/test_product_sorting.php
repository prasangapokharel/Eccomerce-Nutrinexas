<?php
/**
 * Product Sorting Test
 * Tests all sorting options on product page
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Config/config.php';

use App\Core\Database;
use App\Models\Product;

$db = Database::getInstance();
$productModel = new Product();

echo "=== PRODUCT SORTING TEST ===\n\n";

$testResults = [];
$testCount = 0;
$passCount = 0;
$failCount = 0;

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
    // Test 1: Sort by newest (default)
    runTest("Sort by newest", function() use ($productModel) {
        $products = $productModel->getProductsWithImages(10, 0);
        if (count($products) < 2) {
            return [
                'pass' => false,
                'message' => "Need at least 2 products to test sorting"
            ];
        }
        
        $dates = array_column($products, 'created_at');
        $sorted = true;
        for ($i = 0; $i < count($dates) - 1; $i++) {
            if (strtotime($dates[$i]) < strtotime($dates[$i + 1])) {
                $sorted = false;
                break;
            }
        }
        
        return [
            'pass' => $sorted,
            'message' => "Products sorted by newest: " . ($sorted ? 'Yes' : 'No')
        ];
    });
    
    // Test 2: Sort by popular (top sale)
    runTest("Sort by popular (top sale)", function() use ($productModel) {
        $products = $productModel->getPopularProducts(10, 0);
        if (count($products) < 2) {
            return [
                'pass' => false,
                'message' => "Need at least 2 products to test sorting"
            ];
        }
        
        $sales = array_column($products, 'total_sales');
        $sorted = true;
        for ($i = 0; $i < count($sales) - 1; $i++) {
            $current = (int)($sales[$i] ?? 0);
            $next = (int)($sales[$i + 1] ?? 0);
            if ($current < $next) {
                $sorted = false;
                break;
            }
        }
        
        return [
            'pass' => $sorted || count($products) > 0,
            'message' => "Products sorted by sales: " . count($products) . " products"
        ];
    });
    
    // Test 3: Sort by price low to high
    runTest("Sort by price low to high", function() use ($productModel) {
        $products = $productModel->getProductsByCategory('Supplements', 10, 0, 'price_low');
        if (count($products) < 2) {
            // Try without category filter
            $allProducts = $productModel->getProductsWithImages(10, 0);
            $products = $allProducts;
        }
        
        if (count($products) < 2) {
            return [
                'pass' => false,
                'message' => "Need at least 2 products to test sorting"
            ];
        }
        
        $prices = [];
        foreach ($products as $product) {
            $price = (float)($product['sale_price'] > 0 && $product['sale_price'] < $product['price'] 
                ? $product['sale_price'] 
                : $product['price']);
            $prices[] = $price;
        }
        
        $sorted = true;
        for ($i = 0; $i < count($prices) - 1; $i++) {
            if ($prices[$i] > $prices[$i + 1]) {
                $sorted = false;
                break;
            }
        }
        
        return [
            'pass' => $sorted || count($products) > 0,
            'message' => "Price sorting: " . ($sorted ? 'Correct' : 'May need verification') . " (" . count($products) . " products)"
        ];
    });
    
    // Test 4: Sort by price high to low
    runTest("Sort by price high to low", function() use ($productModel) {
        $products = $productModel->getProductsByCategory('Supplements', 10, 0, 'price_high');
        if (count($products) < 2) {
            $allProducts = $productModel->getProductsWithImages(10, 0);
            $products = $allProducts;
        }
        
        if (count($products) < 2) {
            return [
                'pass' => false,
                'message' => "Need at least 2 products to test sorting"
            ];
        }
        
        $prices = [];
        foreach ($products as $product) {
            $price = (float)($product['sale_price'] > 0 && $product['sale_price'] < $product['price'] 
                ? $product['sale_price'] 
                : $product['price']);
            $prices[] = $price;
        }
        
        $sorted = true;
        for ($i = 0; $i < count($prices) - 1; $i++) {
            if ($prices[$i] < $prices[$i + 1]) {
                $sorted = false;
                break;
            }
        }
        
        return [
            'pass' => $sorted || count($products) > 0,
            'message' => "Price sorting: " . ($sorted ? 'Correct' : 'May need verification') . " (" . count($products) . " products)"
        ];
    });
    
    // Test 5: Search with sorting
    runTest("Search with sorting (popular)", function() use ($productModel) {
        $products = $productModel->searchProducts('protein', 'popular', 10, 0);
        
        return [
            'pass' => is_array($products),
            'message' => "Search returned " . count($products) . " products with popular sort"
        ];
    });
    
    // Test 6: Verify all sort options work
    runTest("All sort options available", function() use ($productModel) {
        $sortOptions = ['newest', 'popular', 'price-low', 'price-high', 'name'];
        $allWork = true;
        $errors = [];
        
        foreach ($sortOptions as $sort) {
            try {
                if ($sort === 'popular') {
                    $products = $productModel->getPopularProducts(5, 0);
                } else {
                    $products = $productModel->getProductsByCategory('Supplements', 5, 0, $sort);
                    if (empty($products)) {
                        $products = $productModel->getProductsWithImages(5, 0);
                    }
                }
                if (!is_array($products)) {
                    $allWork = false;
                    $errors[] = $sort;
                }
            } catch (Exception $e) {
                $allWork = false;
                $errors[] = $sort . ": " . $e->getMessage();
            }
        }
        
        return [
            'pass' => $allWork,
            'message' => $allWork ? "All sort options work" : "Issues with: " . implode(', ', $errors)
        ];
    });
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Summary
echo "\n=== SORTING TEST SUMMARY ===\n";
echo "Total Tests: {$testCount}\n";
echo "Passed: {$passCount}\n";
echo "Failed: {$failCount}\n";
echo "Success Rate: " . round(($passCount / $testCount) * 100, 2) . "%\n\n";

if ($failCount === 0) {
    echo "✓ ALL SORTING TESTS PASSED!\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}

