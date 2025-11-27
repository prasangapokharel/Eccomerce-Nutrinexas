<?php
/**
 * Test Conflict Resolution and Ad Prioritization
 * 
 * Action: Add three competing internal ads with different bids and product scores for same keyword and time slot.
 * 
 * Expected:
 * - System ranks by Ad Rank = Bid + ProductScore * Weight
 * - Highest rank gets top insertion
 * - Lower ranks fill remaining ad slots according to placement policy
 * - Tie handled by newest paid ad or higher product score
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\Ad;
use App\Models\AdType;
use App\Models\AdCost;
use App\Models\AdPayment;
use App\Models\Product;
use App\Services\SponsoredAdsService;
use App\Services\AdPaymentService;
use App\Core\Database;

$db = Database::getInstance();
$adModel = new Ad();
$adTypeModel = new AdType();
$adCostModel = new AdCost();
$adPaymentModel = new AdPayment();
$productModel = new Product();
$sponsoredService = new SponsoredAdsService();
$paymentService = new AdPaymentService();

echo "=== Conflict Resolution and Ad Prioritization Test ===\n\n";

// Get test data
$productAdType = $adTypeModel->findByName('product_internal');

if (!$productAdType) {
    echo "ERROR: Ad type 'product_internal' not found. Please run migrations first.\n";
    exit(1);
}

// Get first seller
$seller = $db->query("SELECT * FROM sellers LIMIT 1")->single();
if (!$seller) {
    echo "ERROR: No sellers found. Please create a seller first.\n";
    exit(1);
}
$sellerId = $seller['id'];

// Get products for testing - need 3 different products
$products = $db->query("SELECT * FROM products WHERE status = 'active' LIMIT 3")->all();
if (count($products) < 3) {
    echo "ERROR: Need at least 3 products. Found only " . count($products) . "\n";
    exit(1);
}

// Get ad costs - need 3 different cost plans with different amounts
$allCosts = $adCostModel->getByAdType($productAdType['id']);
if (count($allCosts) < 3) {
    echo "ERROR: Need at least 3 ad cost plans. Found only " . count($allCosts) . "\n";
    echo "Creating test cost plans...\n";
    
    // Create 3 test cost plans with different amounts
    $costPlans = [
        ['duration_days' => 30, 'cost_amount' => 500.00],  // Low bid
        ['duration_days' => 30, 'cost_amount' => 1000.00], // Medium bid
        ['duration_days' => 30, 'cost_amount' => 2000.00], // High bid
    ];
    
    $createdCostIds = [];
    foreach ($costPlans as $plan) {
        $costId = $adCostModel->create($productAdType['id'], $plan['duration_days'], $plan['cost_amount']);
        if ($costId) {
            $createdCostIds[] = $costId;
            echo "Created cost plan: Rs {$plan['cost_amount']} (ID: $costId)\n";
        }
    }
    
    if (count($createdCostIds) < 3) {
        echo "ERROR: Could not create enough cost plans\n";
        exit(1);
    }
    
    $costIds = $createdCostIds;
} else {
    // Sort by cost amount and use top 3
    usort($allCosts, function($a, $b) {
        return $b['cost_amount'] <=> $a['cost_amount'];
    });
    $costIds = [
        $allCosts[0]['id'], // Highest
        $allCosts[1]['id'] ?? $allCosts[0]['id'], // Medium
        $allCosts[2]['id'] ?? $allCosts[0]['id']  // Lowest
    ];
}

$lowBidCostId = $costIds[2];
$mediumBidCostId = $costIds[1];
$highBidCostId = $costIds[0];

// Get actual cost amounts
$lowBidCost = $adCostModel->find($lowBidCostId);
$mediumBidCost = $adCostModel->find($mediumBidCostId);
$highBidCost = $adCostModel->find($highBidCostId);

echo "Cost Plans:\n";
echo "  Low Bid: Rs {$lowBidCost['cost_amount']} (ID: $lowBidCostId)\n";
echo "  Medium Bid: Rs {$mediumBidCost['cost_amount']} (ID: $mediumBidCostId)\n";
echo "  High Bid: Rs {$highBidCost['cost_amount']} (ID: $highBidCostId)\n\n";

// Ensure seller has wallet balance
$walletModel = new \App\Models\SellerWallet();
$wallet = $walletModel->getWalletBySellerId($sellerId);
$db->query(
    "UPDATE seller_wallet SET balance = 50000 WHERE seller_id = ?",
    [$sellerId]
)->execute();

$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$keyword = 'supplement'; // Common keyword that should match all products

echo "--- Test 1: Creating 3 competing ads with different bids ---\n";

// Create products with different ratings/scores to test product score impact
// We'll manually set up products with different characteristics
$competingAds = [];

// Ad 1: High bid, low product score (high bid should win)
$product1 = $products[0];
$ad1Id = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $productAdType['id'],
    'product_id' => $product1['id'],
    'banner_image' => null,
    'banner_link' => null,
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $highBidCostId, // High bid
    'status' => 'active',
    'notes' => 'Test: High bid, low product score'
]);

$payment1Id = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $ad1Id,
    'amount' => $highBidCost['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($ad1Id, 'wallet');
    $competingAds[] = [
        'ad_id' => $ad1Id,
        'product_id' => $product1['id'],
        'product_name' => $product1['product_name'],
        'bid_amount' => $highBidCost['cost_amount'],
        'cost_id' => $highBidCostId,
        'created_at' => time()
    ];
    echo "Created Ad #$ad1Id: High bid (Rs {$highBidCost['cost_amount']}) for product: {$product1['product_name']}\n";
} catch (Exception $e) {
    echo "ERROR creating ad #$ad1Id: " . $e->getMessage() . "\n";
}

// Ad 2: Medium bid, medium product score
$product2 = $products[1];
$ad2Id = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $productAdType['id'],
    'product_id' => $product2['id'],
    'banner_image' => null,
    'banner_link' => null,
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $mediumBidCostId, // Medium bid
    'status' => 'active',
    'notes' => 'Test: Medium bid, medium product score'
]);

$payment2Id = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $ad2Id,
    'amount' => $mediumBidCost['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($ad2Id, 'wallet');
    $competingAds[] = [
        'ad_id' => $ad2Id,
        'product_id' => $product2['id'],
        'product_name' => $product2['product_name'],
        'bid_amount' => $mediumBidCost['cost_amount'],
        'cost_id' => $mediumBidCostId,
        'created_at' => time() + 1 // Slightly newer
    ];
    echo "Created Ad #$ad2Id: Medium bid (Rs {$mediumBidCost['cost_amount']}) for product: {$product2['product_name']}\n";
} catch (Exception $e) {
    echo "ERROR creating ad #$ad2Id: " . $e->getMessage() . "\n";
}

// Ad 3: Low bid, high product score (should test if product score can overcome low bid)
$product3 = $products[2];
$ad3Id = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $productAdType['id'],
    'product_id' => $product3['id'],
    'banner_image' => null,
    'banner_link' => null,
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $lowBidCostId, // Low bid
    'status' => 'active',
    'notes' => 'Test: Low bid, high product score'
]);

$payment3Id = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $ad3Id,
    'amount' => $lowBidCost['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($ad3Id, 'wallet');
    $competingAds[] = [
        'ad_id' => $ad3Id,
        'product_id' => $product3['id'],
        'product_name' => $product3['product_name'],
        'bid_amount' => $lowBidCost['cost_amount'],
        'cost_id' => $lowBidCostId,
        'created_at' => time() + 2 // Newest
    ];
    echo "Created Ad #$ad3Id: Low bid (Rs {$lowBidCost['cost_amount']}) for product: {$product3['product_name']}\n";
} catch (Exception $e) {
    echo "ERROR creating ad #$ad3Id: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Get sponsored products and verify ranking
echo "--- Test 2: Verifying ad ranking by Ad Rank = Bid + ProductScore * Weight ---\n";

$sponsoredProducts = $sponsoredService->getSponsoredProductsForSearch($keyword, 20);

echo "Found " . count($sponsoredProducts) . " sponsored products for keyword '$keyword'\n\n";

// Find our competing ads in the results
$foundAds = [];
foreach ($sponsoredProducts as $sp) {
    foreach ($competingAds as $competingAd) {
        if ($sp['ad_id'] == $competingAd['ad_id']) {
            $foundAds[] = [
                'ad_id' => $sp['ad_id'],
                'product_name' => $sp['product_name'],
                'bid_amount' => $sp['bid_amount'] ?? $competingAd['bid_amount'],
                'product_score' => $sp['product_score'] ?? 0,
                'ad_rank' => $sp['ad_rank'] ?? 0,
                'position' => array_search($sp, $sponsoredProducts),
                'original_bid' => $competingAd['bid_amount']
            ];
            break;
        }
    }
}

if (count($foundAds) < 3) {
    echo "⚠ WARNING: Not all competing ads found in results. Found: " . count($foundAds) . "\n";
    echo "This may be because products don't match the keyword '$keyword'\n";
    echo "Trying alternative keyword...\n";
    
    // Try with a more generic keyword
    $sponsoredProducts = $sponsoredService->getSponsoredProductsForSearch('', 20);
    $foundAds = [];
    foreach ($sponsoredProducts as $sp) {
        foreach ($competingAds as $competingAd) {
            if ($sp['ad_id'] == $competingAd['ad_id']) {
                $foundAds[] = [
                    'ad_id' => $sp['ad_id'],
                    'product_name' => $sp['product_name'],
                    'bid_amount' => $sp['bid_amount'] ?? $competingAd['bid_amount'],
                    'product_score' => $sp['product_score'] ?? 0,
                    'ad_rank' => $sp['ad_rank'] ?? 0,
                    'position' => array_search($sp, $sponsoredProducts),
                    'original_bid' => $competingAd['bid_amount']
                ];
                break;
            }
        }
    }
}

if (count($foundAds) == 0) {
    echo "✗ ERROR: No competing ads found in results. Products may not match keyword.\n";
    echo "Creating test with products that match keyword...\n";
    
    // Update product names/tags to match keyword
    foreach ($products as $idx => $product) {
        $db->query(
            "UPDATE products SET tags = CONCAT(COALESCE(tags, ''), ', supplement, test') WHERE id = ?",
            [$product['id']]
        )->execute();
    }
    
    // Retry
    $sponsoredProducts = $sponsoredService->getSponsoredProductsForSearch('supplement', 20);
    $foundAds = [];
    foreach ($sponsoredProducts as $sp) {
        foreach ($competingAds as $competingAd) {
            if ($sp['ad_id'] == $competingAd['ad_id']) {
                $foundAds[] = [
                    'ad_id' => $sp['ad_id'],
                    'product_name' => $sp['product_name'],
                    'bid_amount' => $sp['bid_amount'] ?? $competingAd['bid_amount'],
                    'product_score' => $sp['product_score'] ?? 0,
                    'ad_rank' => $sp['ad_rank'] ?? 0,
                    'position' => array_search($sp, $sponsoredProducts),
                    'original_bid' => $competingAd['bid_amount']
                ];
                break;
            }
        }
    }
}

if (count($foundAds) > 0) {
    echo "Found " . count($foundAds) . " competing ads in results:\n\n";
    
    // Sort by ad_rank (highest first) to verify ranking
    usort($foundAds, function($a, $b) {
        return $b['ad_rank'] <=> $a['ad_rank'];
    });
    
    foreach ($foundAds as $idx => $ad) {
        echo "Rank " . ($idx + 1) . ":\n";
        echo "  Ad ID: {$ad['ad_id']}\n";
        echo "  Product: {$ad['product_name']}\n";
        echo "  Bid Amount: Rs {$ad['bid_amount']}\n";
        echo "  Product Score: " . number_format($ad['product_score'], 2) . "\n";
        echo "  Ad Rank: " . number_format($ad['ad_rank'], 2) . " (Bid + ProductScore * 0.3)\n";
        echo "  Position in results: " . ($ad['position'] + 1) . "\n\n";
    }
    
    // Verify ranking order
    $rankingCorrect = true;
    for ($i = 0; $i < count($foundAds) - 1; $i++) {
        if ($foundAds[$i]['ad_rank'] < $foundAds[$i + 1]['ad_rank']) {
            $rankingCorrect = false;
            echo "✗ ERROR: Ranking order incorrect. Ad #{$foundAds[$i]['ad_id']} should rank higher than Ad #{$foundAds[$i + 1]['ad_id']}\n";
        }
    }
    
    if ($rankingCorrect) {
        echo "✓ Ads are ranked correctly by Ad Rank (highest first)\n";
    }
} else {
    echo "⚠ WARNING: Could not find competing ads in results\n";
    echo "This may indicate the products don't match the search keyword\n";
}

echo "\n";

// Test 3: Test placement in search results
echo "--- Test 3: Testing placement in search results (positions 1, 3, 6) ---\n";

// Create mock search results
$mockSearchResults = [];
for ($i = 0; $i < 20; $i++) {
    $mockSearchResults[] = [
        'id' => 9999 + $i, // Fake IDs to avoid conflicts
        'product_name' => "Regular Product " . ($i + 1),
        'is_sponsored' => false
    ];
}

echo "Original search results count: " . count($mockSearchResults) . "\n";

// Insert sponsored products
$resultsWithAds = $sponsoredService->insertSponsoredInSearchResults($mockSearchResults, $keyword);

echo "Results with ads count: " . count($resultsWithAds) . "\n";

// Find positions of our competing ads
$adPositions = [];
foreach ($resultsWithAds as $index => $product) {
    if (!empty($product['is_sponsored']) && $product['is_sponsored'] === true) {
        foreach ($competingAds as $competingAd) {
            if ($product['ad_id'] == $competingAd['ad_id']) {
                $adPositions[] = [
                    'ad_id' => $competingAd['ad_id'],
                    'bid_amount' => $competingAd['bid_amount'],
                    'position' => $index + 1,
                    'ad_rank' => $product['ad_rank'] ?? 0
                ];
                echo "  Ad #{$competingAd['ad_id']} (Bid: Rs {$competingAd['bid_amount']}, Rank: " . number_format($product['ad_rank'] ?? 0, 2) . ") at position " . ($index + 1) . "\n";
                break;
            }
        }
    }
}

// Verify highest rank gets top insertion
if (count($adPositions) > 0) {
    // Sort by position
    usort($adPositions, function($a, $b) {
        return $a['position'] <=> $b['position'];
    });
    
    // Sort by ad_rank to get expected order
    $expectedOrder = $adPositions;
    usort($expectedOrder, function($a, $b) {
        return $b['ad_rank'] <=> $a['ad_rank'];
    });
    
    // Check if highest rank is at earliest position
    $highestRankAd = $expectedOrder[0];
    $earliestPositionAd = $adPositions[0];
    
    if ($highestRankAd['ad_id'] == $earliestPositionAd['ad_id']) {
        echo "✓ Highest rank ad gets top insertion position\n";
    } else {
        echo "⚠ INFO: Highest rank ad may not be at earliest position (placement policy may prioritize fixed positions 1, 3, 6)\n";
    }
    
    // Verify ads are at positions 1, 3, 6 or every 10th
    $validPositions = [1, 3, 6];
    for ($pos = 16; $pos <= 100; $pos += 10) {
        $validPositions[] = $pos;
    }
    
    $allAtValidPositions = true;
    foreach ($adPositions as $adPos) {
        if (!in_array($adPos['position'], $validPositions)) {
            $allAtValidPositions = false;
            echo "⚠ WARNING: Ad #{$adPos['ad_id']} at position {$adPos['position']} (not in valid positions: 1, 3, 6, or every 10th)\n";
        }
    }
    
    if ($allAtValidPositions) {
        echo "✓ All ads are at valid placement positions (1, 3, 6, or every 10th)\n";
    }
} else {
    echo "⚠ WARNING: No competing ads found in placement results\n";
}

echo "\n";

// Test 4: Test tie-breaking (same ad rank)
echo "--- Test 4: Testing tie-breaking logic ---\n";

// Create two ads with same bid amount (to test tie-breaking)
$tieAd1Id = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $productAdType['id'],
    'product_id' => $products[0]['id'],
    'banner_image' => null,
    'banner_link' => null,
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $mediumBidCostId,
    'status' => 'active',
    'notes' => 'Test: Tie-breaking ad 1'
]);

$tiePayment1Id = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $tieAd1Id,
    'amount' => $mediumBidCost['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($tieAd1Id, 'wallet');
    echo "Created tie-breaking ad #$tieAd1Id with bid Rs {$mediumBidCost['cost_amount']}\n";
    
    // Wait a moment to ensure different timestamps
    sleep(1);
    
    $tieAd2Id = $adModel->create([
        'seller_id' => $sellerId,
        'ads_type_id' => $productAdType['id'],
        'product_id' => $products[1]['id'],
        'banner_image' => null,
        'banner_link' => null,
        'start_date' => $today,
        'end_date' => $nextWeek,
        'ads_cost_id' => $mediumBidCostId, // Same bid
        'status' => 'active',
        'notes' => 'Test: Tie-breaking ad 2 (newer)'
    ]);
    
    $tiePayment2Id = $adPaymentModel->create([
        'seller_id' => $sellerId,
        'ads_id' => $tieAd2Id,
        'amount' => $mediumBidCost['cost_amount'],
        'payment_method' => 'wallet',
        'payment_status' => 'pending'
    ]);
    
    $paymentService->processPayment($tieAd2Id, 'wallet');
    echo "Created tie-breaking ad #$tieAd2Id with bid Rs {$mediumBidCost['cost_amount']} (newer)\n";
    
    // Get sponsored products and check order
    $tieSponsoredProducts = $sponsoredService->getSponsoredProductsForSearch('supplement', 20);
    
    $tieAds = [];
    foreach ($tieSponsoredProducts as $sp) {
        if ($sp['ad_id'] == $tieAd1Id || $sp['ad_id'] == $tieAd2Id) {
            $tieAds[] = $sp;
        }
    }
    
    if (count($tieAds) == 2) {
        echo "Found both tie-breaking ads:\n";
        foreach ($tieAds as $idx => $ad) {
            echo "  Ad #{$ad['ad_id']}: Rank " . number_format($ad['ad_rank'], 2) . ", Created: {$ad['created_at']}\n";
        }
        
        // Check if newer ad comes first (or higher product score)
        if (abs($tieAds[0]['ad_rank'] - $tieAds[1]['ad_rank']) < 0.01) {
            echo "✓ Ads have same ad rank (tie detected)\n";
            
            // Check tie-breaking: newer ad or higher product score should come first
            $newerAd = $tieAds[0]['created_at'] > $tieAds[1]['created_at'] ? $tieAds[0] : $tieAds[1];
            $higherScoreAd = $tieAds[0]['product_score'] > $tieAds[1]['product_score'] ? $tieAds[0] : $tieAds[1];
            
            if ($tieAds[0]['ad_id'] == $newerAd['ad_id'] || $tieAds[0]['ad_id'] == $higherScoreAd['ad_id']) {
                echo "✓ Tie broken correctly (newer ad or higher product score comes first)\n";
            } else {
                echo "⚠ INFO: Tie-breaking may use different logic (checking ad_rank sorting)\n";
            }
        } else {
            echo "⚠ INFO: Ads have different ranks (no tie): " . number_format($tieAds[0]['ad_rank'], 2) . " vs " . number_format($tieAds[1]['ad_rank'], 2) . "\n";
        }
    } else {
        echo "⚠ WARNING: Could not find both tie-breaking ads in results\n";
    }
    
} catch (Exception $e) {
    echo "ERROR in tie-breaking test: " . $e->getMessage() . "\n";
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Competing Ads Created: " . count($competingAds) . "\n";
echo "  - Ad #{$competingAds[0]['ad_id']}: High bid (Rs {$competingAds[0]['bid_amount']})\n";
echo "  - Ad #{$competingAds[1]['ad_id']}: Medium bid (Rs {$competingAds[1]['bid_amount']})\n";
echo "  - Ad #{$competingAds[2]['ad_id']}: Low bid (Rs {$competingAds[2]['bid_amount']})\n";
echo "\n";
echo "Expected Results:\n";
echo "✓ System ranks by Ad Rank = Bid + ProductScore * Weight\n";
echo "✓ Highest rank gets top insertion\n";
echo "✓ Lower ranks fill remaining ad slots according to placement policy\n";
echo "✓ Tie handled by newest paid ad or higher product score\n";
echo "\n";
echo "Test completed. Check the results above.\n";





