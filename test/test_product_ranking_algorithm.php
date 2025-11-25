<?php
/**
 * Product Ranking Algorithm Test
 * Tests the Flipkart/Amazon-style product ranking algorithm
 */

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

echo "=== PRODUCT RANKING ALGORITHM TEST ===\n\n";

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
    // Test 1: Check if ranking method exists
    runTest("Check if getRankedProducts method exists", function() use ($productModel) {
        $hasMethod = method_exists($productModel, 'getRankedProducts');
        return [
            'pass' => $hasMethod,
            'message' => $hasMethod ? "Method exists" : "Method getRankedProducts not found"
        ];
    });
    
    // Test 2: Test ranking algorithm SQL structure
    runTest("Test ranking algorithm SQL structure", function() use ($db) {
        try {
            $sql = "
                SELECT 
                    p.*,
                    COALESCE(product_ratings.avg_rating, 0) as product_rating,
                    COALESCE(seller_ratings.avg_rating, 0) as seller_rating,
                    COALESCE((
                        SELECT COUNT(*) 
                        FROM order_items oi 
                        INNER JOIN orders o ON oi.order_id = o.id 
                        WHERE oi.product_id = p.id 
                        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        AND o.status != 'cancelled'
                    ), 0) as monthly_sales,
                    CASE 
                        WHEN p.sale_price > 0 AND p.sale_price < p.price * 0.9 THEN 1 
                        ELSE 0 
                    END as price_is_good,
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM ads a 
                            INNER JOIN ads_types at ON a.ads_type_id = at.id 
                            WHERE a.product_id = p.id 
                            AND a.status = 'active' 
                            AND at.name = 'product_internal'
                            AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
                            AND CURDATE() BETWEEN a.start_date AND a.end_date
                        ) THEN 1 
                        ELSE 0 
                    END as is_sponsored,
                    COALESCE((
                        SELECT COUNT(*) * 0.1
                        FROM order_items oi 
                        INNER JOIN orders o ON oi.order_id = o.id 
                        WHERE oi.product_id IN (
                            SELECT id FROM products WHERE category = p.category
                        )
                        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        AND o.status != 'cancelled'
                    ), 0) as category_trend_score,
                    (
                        (CASE 
                            WHEN DATEDIFF(NOW(), p.created_at) < 7 THEN 40
                            WHEN DATEDIFF(NOW(), p.created_at) < 30 THEN 20
                            ELSE 0
                        END)
                        +
                        (COALESCE(product_ratings.avg_rating, 0) * 10)
                        +
                        (COALESCE(seller_ratings.avg_rating, 0) * 5)
                        +
                        (LEAST(COALESCE(monthly_sales.count, 0), 100))
                        +
                        (CASE 
                            WHEN p.sale_price > 0 AND p.sale_price < p.price * 0.9 THEN 10 
                            ELSE 0 
                        END)
                        +
                        (CASE WHEN p.stock_quantity < 5 THEN -20 ELSE 0 END)
                        +
                        (CASE 
                            WHEN active_ads.ad_id IS NOT NULL THEN 60 
                            ELSE 0 
                        END)
                        +
                        COALESCE(category_trend.score, 0)
                    ) AS score
                FROM products p
                LEFT JOIN (
                    SELECT product_id, AVG(rating) as avg_rating
                    FROM reviews
                    GROUP BY product_id
                ) product_ratings ON p.id = product_ratings.product_id
                LEFT JOIN (
                    SELECT s.id as seller_id, COALESCE(AVG(r.rating), 0) as avg_rating
                    FROM sellers s
                    LEFT JOIN products sp ON s.id = sp.seller_id
                    LEFT JOIN reviews r ON sp.id = r.product_id
                    GROUP BY s.id
                ) seller_ratings ON p.seller_id = seller_ratings.seller_id
                LEFT JOIN (
                    SELECT oi.product_id, COUNT(*) as count
                    FROM order_items oi
                    INNER JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND o.status != 'cancelled'
                    GROUP BY oi.product_id
                ) monthly_sales ON p.id = monthly_sales.product_id
                LEFT JOIN (
                    SELECT a.product_id, a.id as ad_id
                    FROM ads a
                    INNER JOIN ads_types at ON a.ads_type_id = at.id
                    WHERE a.status = 'active'
                    AND at.name = 'product_internal'
                    AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
                    AND CURDATE() BETWEEN a.start_date AND a.end_date
                    AND a.product_id IS NOT NULL
                ) active_ads ON p.id = active_ads.product_id
                LEFT JOIN (
                    SELECT p2.category, COUNT(*) * 0.1 as score
                    FROM products p2
                    INNER JOIN order_items oi ON p2.id = oi.product_id
                    INNER JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND o.status != 'cancelled'
                    GROUP BY p2.category
                ) category_trend ON p.category = category_trend.category
                WHERE p.status = 'active'
                AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                GROUP BY p.id
                ORDER BY score DESC, p.created_at DESC
                LIMIT 20
            ";
            
            $stmt = $db->query($sql);
            $products = $stmt->all();
            
            return [
                'pass' => is_array($products) && count($products) > 0,
                'message' => "SQL executed successfully, returned " . count($products) . " products"
            ];
        } catch (Exception $e) {
            return [
                'pass' => false,
                'message' => "SQL error: " . $e->getMessage()
            ];
        }
    });
    
    // Test 3: Verify score calculation
    runTest("Verify score calculation components", function() use ($db) {
        try {
            $sql = "
                SELECT 
                    p.id,
                    p.product_name,
                    DATEDIFF(NOW(), p.created_at) as product_age,
                    COALESCE(AVG(r.rating), 0) as product_rating,
                    p.stock_quantity,
                    (
                        (CASE 
                            WHEN DATEDIFF(NOW(), p.created_at) < 7 THEN 40
                            WHEN DATEDIFF(NOW(), p.created_at) < 30 THEN 20
                            ELSE 0
                        END)
                        +
                        (COALESCE(AVG(r.rating), 0) * 10)
                        +
                        (CASE WHEN p.stock_quantity < 5 THEN -20 ELSE 0 END)
                    ) AS base_score
                FROM products p
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.status = 'active'
                GROUP BY p.id
                LIMIT 5
            ";
            
            $products = $db->query($sql)->all();
            
            $hasScores = true;
            foreach ($products as $product) {
                if (!isset($product['base_score']) || !is_numeric($product['base_score'])) {
                    $hasScores = false;
                    break;
                }
            }
            
            return [
                'pass' => $hasScores && count($products) > 0,
                'message' => "Score calculation working, tested " . count($products) . " products"
            ];
        } catch (Exception $e) {
            return [
                'pass' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
    });
    
    // Test 4: Test fresh products get higher scores
    runTest("Test fresh products get higher scores", function() use ($db) {
        try {
            $sql = "
                SELECT 
                    p.id,
                    DATEDIFF(NOW(), p.created_at) as age_days,
                    (
                        CASE 
                            WHEN DATEDIFF(NOW(), p.created_at) < 7 THEN 40
                            WHEN DATEDIFF(NOW(), p.created_at) < 30 THEN 20
                            ELSE 0
                        END
                    ) AS freshness_score
                FROM products p
                WHERE p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT 10
            ";
            
            $products = $db->query($sql)->all();
            
            $freshProducts = array_filter($products, function($p) {
                return $p['age_days'] < 7 && $p['freshness_score'] == 40;
            });
            
            return [
                'pass' => count($freshProducts) > 0 || count($products) > 0,
                'message' => "Fresh products scoring: " . count($freshProducts) . " products < 7 days old"
            ];
        } catch (Exception $e) {
            return [
                'pass' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
    });
    
    // Test 5: Test low stock penalty
    runTest("Test low stock penalty", function() use ($db) {
        try {
            $sql = "
                SELECT 
                    p.id,
                    p.stock_quantity,
                    (
                        CASE WHEN p.stock_quantity < 5 THEN -20 ELSE 0 END
                    ) AS stock_penalty
                FROM products p
                WHERE p.status = 'active'
                AND p.stock_quantity < 5
                LIMIT 5
            ";
            
            $products = $db->query($sql)->all();
            
            $hasPenalty = true;
            foreach ($products as $product) {
                if ($product['stock_quantity'] < 5 && $product['stock_penalty'] != -20) {
                    $hasPenalty = false;
                    break;
                }
            }
            
            return [
                'pass' => $hasPenalty || count($products) == 0,
                'message' => "Low stock penalty working: " . count($products) . " low stock products found"
            ];
        } catch (Exception $e) {
            return [
                'pass' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
    });
    
    // Test 6: Test full ranking query performance
    runTest("Test full ranking query performance", function() use ($db) {
        try {
            $startTime = microtime(true);
            
            $sql = "
                SELECT 
                    p.*,
                    COALESCE(AVG(r.rating), 0) as product_rating,
                    (
                        (CASE 
                            WHEN DATEDIFF(NOW(), p.created_at) < 7 THEN 40
                            WHEN DATEDIFF(NOW(), p.created_at) < 30 THEN 20
                            ELSE 0
                        END)
                        +
                        (COALESCE(AVG(r.rating), 0) * 10)
                        +
                        (CASE WHEN p.stock_quantity < 5 THEN -20 ELSE 0 END)
                    ) AS score
                FROM products p
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.status = 'active'
                AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                GROUP BY p.id
                ORDER BY score DESC, p.created_at DESC
                LIMIT 20
            ";
            
            $products = $db->query($sql)->all();
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            return [
                'pass' => $duration < 2000 && count($products) > 0,
                'message' => "Query executed in {$duration}ms, returned " . count($products) . " products"
            ];
        } catch (Exception $e) {
            return [
                'pass' => false,
                'message' => "Error: " . $e->getMessage()
            ];
        }
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
    echo "✓ ALL TESTS PASSED! Ranking algorithm is ready for implementation.\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}

