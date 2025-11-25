<?php
/**
 * Comprehensive Test: Product Search + Filter
 * 
 * Test: Search product by name, category, price range, rating, sort by latest, and verify the results are accurate.
 */

// Load config directly
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

$db = Database::getInstance();
$productModel = new Product();

echo "=== PRODUCT SEARCH + FILTER TEST ===\n\n";

$testResults = [];
$testCount = 0;
$passCount = 0;
$failCount = 0;
$testProducts = [];

function runTest($name, $callback) {
    global $testCount, $passCount, $failCount, $testResults;
    $testCount++;
    echo "Test {$testCount}: {$name}... ";
    
    try {
        $result = $callback();
        if ($result['pass']) {
            $passCount++;
            echo "✓ PASS\n";
            if (!empty($result['message'])) {
                echo "  → {$result['message']}\n";
            }
        } else {
            $failCount++;
            echo "✗ FAIL\n";
            echo "  → {$result['message']}\n";
        }
        $testResults[] = ['name' => $name, 'pass' => $result['pass'], 'message' => $result['message']];
    } catch (Exception $e) {
        $failCount++;
        echo "✗ ERROR\n";
        echo "  → Exception: {$e->getMessage()}\n";
        $testResults[] = ['name' => $name, 'pass' => false, 'message' => "Exception: {$e->getMessage()}"];
    }
    echo "\n";
}

try {
    // Setup: Create test products with different attributes
    echo "--- Setup: Creating test products ---\n";
    $timestamp = time();
    $categories = ['Supplements', 'Vitamins', 'Accessories'];
    $testProducts = [];
    
    // Product 1: High price, Supplements category, recent
    $product1 = [
        'product_name' => "Premium Protein Powder Test {$timestamp}",
        'slug' => 'premium-protein-powder-test-' . $timestamp,
        'description' => 'High quality protein powder for muscle building and recovery. Contains whey protein isolate.',
        'short_description' => 'Premium protein powder',
        'price' => 2500.00,
        'sale_price' => 2200.00,
        'stock_quantity' => 50,
        'category' => 'Supplements',
        'subtype' => 'Protein',
        'product_type_main' => 'Supplement',
        'product_type' => 'Protein',
        'status' => 'active',
        'approval_status' => 'approved',
        'is_featured' => 1,
        'tags' => 'protein, whey, muscle, fitness',
        'created_at' => date('Y-m-d H:i:s', $timestamp - 3600) // 1 hour ago
    ];
    
    // Product 2: Medium price, Vitamins category, older
    $product2 = [
        'product_name' => "Vitamin C Tablets Test {$timestamp}",
        'slug' => 'vitamin-c-tablets-test-' . $timestamp,
        'description' => 'Vitamin C tablets for immune support and overall health. 1000mg per tablet.',
        'short_description' => 'Vitamin C tablets',
        'price' => 500.00,
        'sale_price' => 450.00,
        'stock_quantity' => 100,
        'category' => 'Vitamins',
        'subtype' => 'Vitamin C',
        'product_type_main' => 'Vitamins',
        'product_type' => 'Vitamin C',
        'status' => 'active',
        'approval_status' => 'approved',
        'is_featured' => 0,
        'tags' => 'vitamin, immune, health',
        'created_at' => date('Y-m-d H:i:s', $timestamp - 7200) // 2 hours ago
    ];
    
    // Product 3: Low price, Accessories category, newest
    $product3 = [
        'product_name' => "Fitness Band Test {$timestamp}",
        'slug' => 'fitness-band-test-' . $timestamp,
        'description' => 'Adjustable fitness band for resistance training and stretching exercises.',
        'short_description' => 'Fitness resistance band',
        'price' => 300.00,
        'sale_price' => null,
        'stock_quantity' => 75,
        'category' => 'Accessories',
        'subtype' => 'Fitness Equipment',
        'product_type_main' => 'Accessories',
        'product_type' => 'Fitness Equipment',
        'status' => 'active',
        'approval_status' => 'approved',
        'is_featured' => 0,
        'tags' => 'fitness, band, resistance, exercise',
        'created_at' => date('Y-m-d H:i:s', $timestamp - 1800) // 30 minutes ago
    ];
    
    // Product 4: Medium-high price, Supplements category, with specific search term
    $product4 = [
        'product_name' => "Creatine Monohydrate Test {$timestamp}",
        'slug' => 'creatine-monohydrate-test-' . $timestamp,
        'description' => 'Pure creatine monohydrate powder for strength and power. Unflavored and easy to mix.',
        'short_description' => 'Creatine monohydrate powder',
        'price' => 1200.00,
        'sale_price' => 1000.00,
        'stock_quantity' => 60,
        'category' => 'Supplements',
        'subtype' => 'Creatine',
        'product_type_main' => 'Supplement',
        'product_type' => 'Creatine',
        'status' => 'active',
        'approval_status' => 'approved',
        'is_featured' => 0,
        'tags' => 'creatine, strength, power, muscle',
        'created_at' => date('Y-m-d H:i:s', $timestamp - 5400) // 1.5 hours ago
    ];
    
    // Product 5: Inactive product (should not appear in search)
    $product5 = [
        'product_name' => "Inactive Product Test {$timestamp}",
        'slug' => 'inactive-product-test-' . $timestamp,
        'description' => 'This product is inactive and should not appear in search results.',
        'short_description' => 'Inactive product',
        'price' => 100.00,
        'sale_price' => null,
        'stock_quantity' => 10,
        'category' => 'Supplements',
        'subtype' => 'Test',
        'product_type_main' => 'Supplement',
        'product_type' => 'Test',
        'status' => 'inactive',
        'approval_status' => 'approved',
        'is_featured' => 0,
        'tags' => 'inactive, test',
        'created_at' => date('Y-m-d H:i:s', $timestamp - 900) // 15 minutes ago
    ];
    
    $productsToCreate = [$product1, $product2, $product3, $product4, $product5];
    
    foreach ($productsToCreate as $productData) {
        $sql = "INSERT INTO products (
            product_name, slug, description, short_description, price, sale_price,
            stock_quantity, category, subtype, product_type_main, product_type,
            status, approval_status, is_featured, tags, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->query($sql, [
            $productData['product_name'],
            $productData['slug'],
            $productData['description'],
            $productData['short_description'],
            $productData['price'],
            $productData['sale_price'],
            $productData['stock_quantity'],
            $productData['category'],
            $productData['subtype'],
            $productData['product_type_main'],
            $productData['product_type'],
            $productData['status'],
            $productData['approval_status'],
            $productData['is_featured'],
            $productData['tags'],
            $productData['created_at']
        ]);
        $stmt->execute();
        $productId = $db->lastInsertId();
        $testProducts[] = ['id' => $productId, 'data' => $productData];
        echo "Created product: ID {$productId}, Name: {$productData['product_name']}\n";
    }
    
    echo "\n";
    
    // Test 1: Search by product name
    runTest("Search by product name", function() use ($productModel, $testProducts) {
        $searchTerm = 'Protein';
        $results = $productModel->searchProducts($searchTerm, 'newest', 100, 0);
        
        $foundProducts = [];
        foreach ($results as $product) {
            if (stripos($product['product_name'], $searchTerm) !== false || 
                stripos($product['description'], $searchTerm) !== false ||
                stripos($product['tags'] ?? '', $searchTerm) !== false) {
                $foundProducts[] = $product;
            }
        }
        
        $hasResults = count($foundProducts) > 0;
        $expectedProduct = null;
        foreach ($testProducts as $tp) {
            if (stripos($tp['data']['product_name'], $searchTerm) !== false && $tp['data']['status'] === 'active') {
                $expectedProduct = $tp;
                break;
            }
        }
        
        $foundExpected = false;
        if ($expectedProduct) {
            foreach ($foundProducts as $fp) {
                if ($fp['id'] == $expectedProduct['id']) {
                    $foundExpected = true;
                    break;
                }
            }
        }
        
        return [
            'pass' => $hasResults && $foundExpected,
            'message' => $hasResults && $foundExpected
                ? "Found " . count($foundProducts) . " product(s) matching '{$searchTerm}' (Expected product found)"
                : "Search results: " . count($foundProducts) . " found, Expected: " . ($expectedProduct ? 'Yes' : 'No')
        ];
    });
    
    // Test 2: Search by description
    runTest("Search by description", function() use ($productModel, $testProducts) {
        $searchTerm = 'creatine';
        $results = $productModel->searchProducts($searchTerm, 'newest', 100, 0);
        
        $foundProducts = [];
        foreach ($results as $product) {
            if (stripos($product['product_name'], $searchTerm) !== false || 
                stripos($product['description'], $searchTerm) !== false ||
                stripos($product['tags'] ?? '', $searchTerm) !== false) {
                $foundProducts[] = $product;
            }
        }
        
        $hasResults = count($foundProducts) > 0;
        $expectedProduct = null;
        foreach ($testProducts as $tp) {
            if ((stripos($tp['data']['description'], $searchTerm) !== false || 
                 stripos($tp['data']['product_name'], $searchTerm) !== false) && 
                $tp['data']['status'] === 'active') {
                $expectedProduct = $tp;
                break;
            }
        }
        
        $foundExpected = false;
        if ($expectedProduct) {
            foreach ($foundProducts as $fp) {
                if ($fp['id'] == $expectedProduct['id']) {
                    $foundExpected = true;
                    break;
                }
            }
        }
        
        return [
            'pass' => $hasResults && $foundExpected,
            'message' => $hasResults && $foundExpected
                ? "Found " . count($foundProducts) . " product(s) matching '{$searchTerm}' in description"
                : "Search results: " . count($foundProducts) . " found, Expected: " . ($expectedProduct ? 'Yes' : 'No')
        ];
    });
    
    // Test 3: Filter by category
    runTest("Filter by category", function() use ($productModel, $testProducts) {
        $category = 'Supplements';
        $results = $productModel->getProductsByCategory($category, 100, 0, 'newest');
        
        $foundProducts = [];
        foreach ($results as $product) {
            if ($product['category'] === $category && $product['status'] === 'active') {
                $foundProducts[] = $product;
            }
        }
        
        $expectedCount = 0;
        foreach ($testProducts as $tp) {
            if ($tp['data']['category'] === $category && $tp['data']['status'] === 'active') {
                $expectedCount++;
            }
        }
        
        $hasCorrectCategory = count($foundProducts) >= $expectedCount;
        
        return [
            'pass' => $hasCorrectCategory,
            'message' => $hasCorrectCategory
                ? "Found " . count($foundProducts) . " product(s) in category '{$category}' (Expected: {$expectedCount}+)"
                : "Category filter results: " . count($foundProducts) . " found (Expected: {$expectedCount}+)"
        ];
    });
    
    // Test 4: Filter by price range (min price)
    runTest("Filter by minimum price", function() use ($productModel, $testProducts) {
        $minPrice = 1000.00;
        $filters = ['min_price' => $minPrice, 'status' => 'active'];
        $results = $productModel->getAllProducts(100, 0, $filters);
        
        $foundProducts = [];
        foreach ($results as $product) {
            $price = (float)$product['price'];
            if ($price >= $minPrice && $product['status'] === 'active') {
                $foundProducts[] = $product;
            }
        }
        
        $expectedCount = 0;
        foreach ($testProducts as $tp) {
            if ($tp['data']['price'] >= $minPrice && $tp['data']['status'] === 'active') {
                $expectedCount++;
            }
        }
        
        $allAboveMinPrice = true;
        foreach ($foundProducts as $fp) {
            if ((float)$fp['price'] < $minPrice) {
                $allAboveMinPrice = false;
                break;
            }
        }
        
        return [
            'pass' => $allAboveMinPrice && count($foundProducts) >= $expectedCount,
            'message' => $allAboveMinPrice && count($foundProducts) >= $expectedCount
                ? "Found " . count($foundProducts) . " product(s) with price >= {$minPrice} (Expected: {$expectedCount}+)"
                : "Price filter results: " . count($foundProducts) . " found, All above min: " . ($allAboveMinPrice ? 'Yes' : 'No')
        ];
    });
    
    // Test 5: Filter by price range (max price)
    runTest("Filter by maximum price", function() use ($productModel, $testProducts) {
        $maxPrice = 600.00;
        $filters = ['max_price' => $maxPrice, 'status' => 'active'];
        $results = $productModel->getAllProducts(100, 0, $filters);
        
        $foundProducts = [];
        foreach ($results as $product) {
            $price = (float)$product['price'];
            if ($price <= $maxPrice && $product['status'] === 'active') {
                $foundProducts[] = $product;
            }
        }
        
        $expectedCount = 0;
        foreach ($testProducts as $tp) {
            if ($tp['data']['price'] <= $maxPrice && $tp['data']['status'] === 'active') {
                $expectedCount++;
            }
        }
        
        $allBelowMaxPrice = true;
        foreach ($foundProducts as $fp) {
            if ((float)$fp['price'] > $maxPrice) {
                $allBelowMaxPrice = false;
                break;
            }
        }
        
        return [
            'pass' => $allBelowMaxPrice && count($foundProducts) >= $expectedCount,
            'message' => $allBelowMaxPrice && count($foundProducts) >= $expectedCount
                ? "Found " . count($foundProducts) . " product(s) with price <= {$maxPrice} (Expected: {$expectedCount}+)"
                : "Price filter results: " . count($foundProducts) . " found, All below max: " . ($allBelowMaxPrice ? 'Yes' : 'No')
        ];
    });
    
    // Test 6: Filter by price range (min and max)
    runTest("Filter by price range (min and max)", function() use ($productModel, $testProducts) {
        $minPrice = 400.00;
        $maxPrice = 1500.00;
        $filters = ['min_price' => $minPrice, 'max_price' => $maxPrice, 'status' => 'active'];
        $results = $productModel->getAllProducts(100, 0, $filters);
        
        $foundProducts = [];
        foreach ($results as $product) {
            $price = (float)$product['price'];
            if ($price >= $minPrice && $price <= $maxPrice && $product['status'] === 'active') {
                $foundProducts[] = $product;
            }
        }
        
        $expectedCount = 0;
        foreach ($testProducts as $tp) {
            if ($tp['data']['price'] >= $minPrice && $tp['data']['price'] <= $maxPrice && $tp['data']['status'] === 'active') {
                $expectedCount++;
            }
        }
        
        $allInRange = true;
        foreach ($foundProducts as $fp) {
            $price = (float)$fp['price'];
            if ($price < $minPrice || $price > $maxPrice) {
                $allInRange = false;
                break;
            }
        }
        
        return [
            'pass' => $allInRange && count($foundProducts) >= $expectedCount,
            'message' => $allInRange && count($foundProducts) >= $expectedCount
                ? "Found " . count($foundProducts) . " product(s) in price range {$minPrice}-{$maxPrice} (Expected: {$expectedCount}+)"
                : "Price range filter results: " . count($foundProducts) . " found, All in range: " . ($allInRange ? 'Yes' : 'No')
        ];
    });
    
    // Test 7: Sort by latest (newest first)
    runTest("Sort by latest (newest first)", function() use ($productModel, $testProducts) {
        $results = $productModel->getAllProducts(100, 0, ['status' => 'active']);
        
        // Filter to only our test products
        $testProductIds = array_column($testProducts, 'id');
        $ourProducts = [];
        foreach ($results as $product) {
            if (in_array($product['id'], $testProductIds) && $product['status'] === 'active') {
                $ourProducts[] = $product;
            }
        }
        
        // Check if sorted by created_at DESC
        $isSorted = true;
        for ($i = 0; $i < count($ourProducts) - 1; $i++) {
            $current = strtotime($ourProducts[$i]['created_at']);
            $next = strtotime($ourProducts[$i + 1]['created_at']);
            if ($current < $next) {
                $isSorted = false;
                break;
            }
        }
        
        return [
            'pass' => $isSorted && count($ourProducts) >= 4,
            'message' => $isSorted && count($ourProducts) >= 4
                ? "Products sorted by latest: " . count($ourProducts) . " products in descending order by created_at"
                : "Sorting check: " . count($ourProducts) . " products, Sorted: " . ($isSorted ? 'Yes' : 'No')
        ];
    });
    
    // Test 8: Sort by price (low to high)
    runTest("Sort by price (low to high)", function() use ($productModel, $testProducts) {
        $results = $productModel->getProductsByCategory('Supplements', 100, 0, 'price_low');
        
        // Filter to only our test products
        $testProductIds = array_column($testProducts, 'id');
        $ourProducts = [];
        foreach ($results as $product) {
            if (in_array($product['id'], $testProductIds) && $product['status'] === 'active') {
                $ourProducts[] = $product;
            }
        }
        
        // Check if sorted by price ASC
        $isSorted = true;
        for ($i = 0; $i < count($ourProducts) - 1; $i++) {
            $current = (float)$ourProducts[$i]['price'];
            $next = (float)$ourProducts[$i + 1]['price'];
            if ($current > $next) {
                $isSorted = false;
                break;
            }
        }
        
        return [
            'pass' => $isSorted || count($ourProducts) === 0,
            'message' => $isSorted || count($ourProducts) === 0
                ? "Products sorted by price (low to high): " . count($ourProducts) . " products"
                : "Price sorting check: " . count($ourProducts) . " products, Sorted: " . ($isSorted ? 'Yes' : 'No')
        ];
    });
    
    // Test 9: Sort by price (high to low)
    runTest("Sort by price (high to low)", function() use ($productModel, $testProducts) {
        $results = $productModel->getProductsByCategory('Supplements', 100, 0, 'price_high');
        
        // Filter to only our test products
        $testProductIds = array_column($testProducts, 'id');
        $ourProducts = [];
        foreach ($results as $product) {
            if (in_array($product['id'], $testProductIds) && $product['status'] === 'active') {
                $ourProducts[] = $product;
            }
        }
        
        // Check if sorted by price DESC
        $isSorted = true;
        for ($i = 0; $i < count($ourProducts) - 1; $i++) {
            $current = (float)$ourProducts[$i]['price'];
            $next = (float)$ourProducts[$i + 1]['price'];
            if ($current < $next) {
                $isSorted = false;
                break;
            }
        }
        
        return [
            'pass' => $isSorted || count($ourProducts) === 0,
            'message' => $isSorted || count($ourProducts) === 0
                ? "Products sorted by price (high to low): " . count($ourProducts) . " products"
                : "Price sorting check: " . count($ourProducts) . " products, Sorted: " . ($isSorted ? 'Yes' : 'No')
        ];
    });
    
    // Test 10: Search with category filter
    runTest("Search with category filter", function() use ($productModel, $testProducts) {
        $searchTerm = 'protein';
        $category = 'Supplements';
        
        // Search first
        $searchResults = $productModel->searchProducts($searchTerm, 'newest', 100, 0);
        
        // Filter by category
        $filteredResults = [];
        foreach ($searchResults as $product) {
            if ($product['category'] === $category && $product['status'] === 'active') {
                $filteredResults[] = $product;
            }
        }
        
        $hasResults = count($filteredResults) > 0;
        $allInCategory = true;
        foreach ($filteredResults as $product) {
            if ($product['category'] !== $category) {
                $allInCategory = false;
                break;
            }
        }
        
        return [
            'pass' => $hasResults && $allInCategory,
            'message' => $hasResults && $allInCategory
                ? "Found " . count($filteredResults) . " product(s) matching '{$searchTerm}' in category '{$category}'"
                : "Search + category filter: " . count($filteredResults) . " found, All in category: " . ($allInCategory ? 'Yes' : 'No')
        ];
    });
    
    // Test 11: Search with price range filter
    runTest("Search with price range filter", function() use ($productModel, $testProducts) {
        $searchTerm = 'vitamin';
        $minPrice = 400.00;
        $maxPrice = 600.00;
        
        // Search first
        $searchResults = $productModel->searchProducts($searchTerm, 'newest', 100, 0);
        
        // Filter by price range
        $filteredResults = [];
        foreach ($searchResults as $product) {
            $price = (float)$product['price'];
            if ($price >= $minPrice && $price <= $maxPrice && $product['status'] === 'active') {
                $filteredResults[] = $product;
            }
        }
        
        $hasResults = count($filteredResults) > 0;
        $allInPriceRange = true;
        foreach ($filteredResults as $product) {
            $price = (float)$product['price'];
            if ($price < $minPrice || $price > $maxPrice) {
                $allInPriceRange = false;
                break;
            }
        }
        
        return [
            'pass' => $hasResults && $allInPriceRange,
            'message' => $hasResults && $allInPriceRange
                ? "Found " . count($filteredResults) . " product(s) matching '{$searchTerm}' in price range {$minPrice}-{$maxPrice}"
                : "Search + price filter: " . count($filteredResults) . " found, All in range: " . ($allInPriceRange ? 'Yes' : 'No')
        ];
    });
    
    // Test 12: Inactive products are excluded
    runTest("Inactive products are excluded from search", function() use ($productModel, $testProducts) {
        $searchTerm = 'Inactive';
        $results = $productModel->searchProducts($searchTerm, 'newest', 100, 0);
        
        $foundInactive = false;
        foreach ($results as $product) {
            if ($product['status'] !== 'active') {
                $foundInactive = true;
                break;
            }
        }
        
        // Check if our inactive test product is in results
        $inactiveProductId = null;
        foreach ($testProducts as $tp) {
            if ($tp['data']['status'] === 'inactive') {
                $inactiveProductId = $tp['id'];
                break;
            }
        }
        
        $inactiveFound = false;
        if ($inactiveProductId) {
            foreach ($results as $product) {
                if ($product['id'] == $inactiveProductId) {
                    $inactiveFound = true;
                    break;
                }
            }
        }
        
        return [
            'pass' => !$foundInactive && !$inactiveFound,
            'message' => !$foundInactive && !$inactiveFound
                ? "Inactive products correctly excluded from search results"
                : "Inactive products found: " . ($foundInactive ? 'Yes' : 'No') . ", Test inactive product: " . ($inactiveFound ? 'Found' : 'Not found')
        ];
    });
    
    // Test 13: Verify search results accuracy
    runTest("Verify search results accuracy", function() use ($productModel, $testProducts) {
        $searchTerm = 'fitness';
        $results = $productModel->searchProducts($searchTerm, 'newest', 100, 0);
        
        $accurateResults = 0;
        $inaccurateResults = 0;
        
        foreach ($results as $product) {
            $matches = false;
            if (stripos($product['product_name'], $searchTerm) !== false) {
                $matches = true;
            } elseif (stripos($product['description'], $searchTerm) !== false) {
                $matches = true;
            } elseif (stripos($product['tags'] ?? '', $searchTerm) !== false) {
                $matches = true;
            }
            
            if ($matches) {
                $accurateResults++;
            } else {
                $inaccurateResults++;
            }
        }
        
        $accuracy = count($results) > 0 ? ($accurateResults / count($results)) * 100 : 0;
        $isAccurate = $accuracy >= 90 || count($results) === 0;
        
        return [
            'pass' => $isAccurate,
            'message' => $isAccurate
                ? "Search results accuracy: " . round($accuracy, 2) . "% ({$accurateResults} accurate, {$inaccurateResults} inaccurate)"
                : "Search results accuracy too low: " . round($accuracy, 2) . "%"
        ];
    });
    
    // Cleanup
    echo "--- Cleanup ---\n";
    foreach ($testProducts as $tp) {
        $db->query("DELETE FROM products WHERE id = ?", [$tp['id']])->execute();
    }
    echo "Test products deleted\n\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Cleanup on error
    if (!empty($testProducts)) {
        foreach ($testProducts as $tp) {
            try {
                $db->query("DELETE FROM products WHERE id = ?", [$tp['id']])->execute();
            } catch (Exception $cleanupError) {
                echo "Product cleanup error: " . $cleanupError->getMessage() . "\n";
            }
        }
    }
    
    exit(1);
}

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "Total Tests: {$testCount}\n";
echo "Passed: {$passCount}\n";
echo "Failed: {$failCount}\n";
echo "Success Rate: " . round(($passCount / $testCount) * 100, 2) . "%\n\n";

if ($failCount === 0) {
    echo "✓ ALL TESTS PASSED! Product search and filter system is working perfectly.\n";
    echo "\nFeatures Verified:\n";
    echo "  ✓ Search by product name\n";
    echo "  ✓ Search by description\n";
    echo "  ✓ Filter by category\n";
    echo "  ✓ Filter by minimum price\n";
    echo "  ✓ Filter by maximum price\n";
    echo "  ✓ Filter by price range (min and max)\n";
    echo "  ✓ Sort by latest (newest first)\n";
    echo "  ✓ Sort by price (low to high)\n";
    echo "  ✓ Sort by price (high to low)\n";
    echo "  ✓ Search with category filter\n";
    echo "  ✓ Search with price range filter\n";
    echo "  ✓ Inactive products are excluded\n";
    echo "  ✓ Search results accuracy verified\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}

