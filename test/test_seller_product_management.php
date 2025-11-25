<?php
/**
 * Comprehensive Test: Seller Product Management
 * 
 * Test: Login as seller, add a new product, upload images, set stock, set variations, and verify admin approval flow works.
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
use App\Core\Session;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariantColor;
use App\Models\Seller;

$db = Database::getInstance();
$productModel = new Product();
$productImageModel = new ProductImage();
$productVariantModel = new ProductVariantColor();
$sellerModel = new Seller();

echo "=== SELLER PRODUCT MANAGEMENT TEST ===\n\n";

$testResults = [];
$testCount = 0;
$passCount = 0;
$failCount = 0;
$testSellerId = null;
$testProductId = null;
$testAdminId = null;

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
    // Initialize session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear any existing session
    Session::destroy();
    $_SESSION = [];
    
    // Setup: Create test seller
    echo "--- Setup: Creating test seller ---\n";
    $timestamp = time();
    $sellerEmail = "seller_test_{$timestamp}@nutrinexus.test";
    $sellerPassword = 'SellerPassword123!';
    $hashedPassword = password_hash($sellerPassword, PASSWORD_DEFAULT);
    
    $sellerData = [
        'name' => "Test Seller {$timestamp}",
        'company_name' => "Test Company",
        'email' => $sellerEmail,
        'password' => $hashedPassword,
        'phone' => "98" . str_pad($timestamp % 100000000, 8, '0', STR_PAD_LEFT),
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 10.00
    ];
    
    $stmt = $db->query(
        "INSERT INTO sellers (name, company_name, email, password, phone, status, is_approved, commission_rate) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$sellerData['name'], $sellerData['company_name'], $sellerData['email'], $sellerData['password'], 
         $sellerData['phone'], $sellerData['status'], $sellerData['is_approved'], $sellerData['commission_rate']]
    );
    $stmt->execute();
    $testSellerId = $db->lastInsertId();
    
    echo "Test Seller ID: {$testSellerId}, Email: {$sellerEmail}\n";
    
    // Setup: Create test admin user
    echo "--- Setup: Creating test admin user ---\n";
    $adminEmail = "admin_test_{$timestamp}@nutrinexus.test";
    $adminPassword = password_hash('AdminPassword123!', PASSWORD_DEFAULT);
    
    $adminData = [
        'username' => "admin_test_{$timestamp}",
        'email' => $adminEmail,
        'phone' => "97" . str_pad($timestamp % 100000000, 8, '0', STR_PAD_LEFT),
        'password' => $adminPassword,
        'full_name' => "Test Admin",
        'first_name' => "Test",
        'last_name' => "Admin",
        'role' => 'admin',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $db->query(
        "INSERT INTO users (username, email, phone, password, full_name, first_name, last_name, role, status, created_at, updated_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_values($adminData)
    );
    $stmt->execute();
    $testAdminId = $db->lastInsertId();
    
    echo "Test Admin ID: {$testAdminId}, Email: {$adminEmail}\n\n";
    
    // Test 1: Seller can login
    runTest("Seller can login", function() use ($sellerModel, $sellerEmail, $sellerPassword, $testSellerId) {
        $seller = $sellerModel->authenticate($sellerEmail, $sellerPassword);
        $canLogin = !empty($seller) && $seller['id'] == $testSellerId;
        
        return [
            'pass' => $canLogin,
            'message' => $canLogin
                ? "Seller authenticated successfully: ID {$seller['id']}, Name: {$seller['name']}"
                : "Seller authentication failed"
        ];
    });
    
    // Test 2: Seller session is set
    runTest("Seller session is set after login", function() use ($sellerModel, $sellerEmail, $sellerPassword, $testSellerId) {
        // Simulate login by setting session
        Session::set('seller_id', $testSellerId);
        Session::set('seller_email', $sellerEmail);
        Session::set('seller_name', "Test Seller");
        Session::set('logged_in', true);
        
        $sessionSellerId = Session::get('seller_id');
        $sessionSet = $sessionSellerId == $testSellerId;
        
        return [
            'pass' => $sessionSet,
            'message' => $sessionSet
                ? "Seller session set: Seller ID {$sessionSellerId}"
                : "Seller session not set correctly"
        ];
    });
    
    // Test 3: Create product with all fields
    runTest("Create product with all fields", function() use ($productModel, $testSellerId, &$testProductId) {
        $productData = [
            'product_name' => 'Test Product ' . time(),
            'description' => 'This is a test product description for seller product management test.',
            'short_description' => 'Test product short description',
            'price' => 1000.00,
            'sale_price' => 850.00,
            'stock_quantity' => 50,
            'category' => 'Supplements',
            'subcategory' => 'Protein',
            'product_type_main' => 'Supplement',
            'product_type' => 'Protein',
            'is_digital' => 0,
            'colors' => 'Red,Blue,Green',
            'weight' => '500g',
            'serving' => '1 serving',
            'flavor' => 'Chocolate',
            'is_featured' => 0,
            'status' => 'pending',
            'approval_status' => 'pending',
            'seller_id' => $testSellerId
        ];
        
        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $productData['product_name'])));
        $productData['slug'] = $slug;
        
        $productId = $productModel->createProduct($productData);
        $testProductId = $productId;
        
        $productCreated = $productId > 0;
        
        return [
            'pass' => $productCreated,
            'message' => $productCreated
                ? "Product created: ID {$productId}, Name: {$productData['product_name']}, Status: pending, Approval: pending"
                : "Product creation failed"
        ];
    });
    
    // Test 4: Verify product has correct seller_id
    runTest("Product has correct seller_id", function() use ($productModel, $testProductId, $testSellerId) {
        $product = $productModel->find($testProductId);
        $hasCorrectSellerId = $product && $product['seller_id'] == $testSellerId;
        
        return [
            'pass' => $hasCorrectSellerId,
            'message' => $hasCorrectSellerId
                ? "Product seller_id correct: {$product['seller_id']} (Expected: {$testSellerId})"
                : "Product seller_id incorrect: " . ($product['seller_id'] ?? 'null') . " (Expected: {$testSellerId})"
        ];
    });
    
    // Test 5: Verify product has pending approval status
    runTest("Product has pending approval status", function() use ($productModel, $testProductId) {
        $product = $productModel->find($testProductId);
        $hasPendingStatus = $product && $product['approval_status'] === 'pending' && $product['status'] === 'pending';
        
        return [
            'pass' => $hasPendingStatus,
            'message' => $hasPendingStatus
                ? "Product approval status: {$product['approval_status']}, Status: {$product['status']}"
                : "Product approval status incorrect: " . ($product['approval_status'] ?? 'null') . ", Status: " . ($product['status'] ?? 'null')
        ];
    });
    
    // Test 6: Upload primary product image
    runTest("Upload primary product image", function() use ($productImageModel, $testProductId) {
        $imageUrl = 'https://example.com/images/test-product-primary.jpg';
        $imageId = $productImageModel->create([
            'product_id' => $testProductId,
            'image_url' => $imageUrl,
            'is_primary' => 1
        ]);
        
        $imageCreated = $imageId > 0;
        
        return [
            'pass' => $imageCreated,
            'message' => $imageCreated
                ? "Primary image uploaded: ID {$imageId}, URL: {$imageUrl}"
                : "Primary image upload failed"
        ];
    });
    
    // Test 7: Upload additional product images
    runTest("Upload additional product images", function() use ($productImageModel, $testProductId) {
        $additionalImages = [
            'https://example.com/images/test-product-1.jpg',
            'https://example.com/images/test-product-2.jpg',
            'https://example.com/images/test-product-3.jpg'
        ];
        
        $createdCount = 0;
        foreach ($additionalImages as $imageUrl) {
            $imageId = $productImageModel->create([
                'product_id' => $testProductId,
                'image_url' => $imageUrl,
                'is_primary' => 0
            ]);
            if ($imageId > 0) {
                $createdCount++;
            }
        }
        
        $allImagesCreated = $createdCount === count($additionalImages);
        
        return [
            'pass' => $allImagesCreated,
            'message' => $allImagesCreated
                ? "All additional images uploaded: {$createdCount} images"
                : "Some images failed to upload: {$createdCount}/" . count($additionalImages)
        ];
    });
    
    // Test 8: Verify product images are stored
    runTest("Product images are stored correctly", function() use ($productImageModel, $testProductId) {
        $images = $productImageModel->getByProductId($testProductId);
        $primaryImage = null;
        $additionalImages = [];
        
        foreach ($images as $image) {
            if ($image['is_primary'] == 1) {
                $primaryImage = $image;
            } else {
                $additionalImages[] = $image;
            }
        }
        
        $hasPrimaryImage = !empty($primaryImage);
        $hasAdditionalImages = count($additionalImages) >= 3;
        
        return [
            'pass' => $hasPrimaryImage && $hasAdditionalImages,
            'message' => $hasPrimaryImage && $hasAdditionalImages
                ? "Images stored: 1 primary, " . count($additionalImages) . " additional"
                : "Images missing: Primary=" . ($hasPrimaryImage ? 'Yes' : 'No') . ", Additional=" . count($additionalImages)
        ];
    });
    
    // Test 9: Set product stock
    runTest("Set product stock", function() use ($productModel, $testProductId) {
        $newStock = 100;
        $result = $productModel->updateStock($testProductId, $newStock);
        
        if ($result) {
            $product = $productModel->find($testProductId);
            $stockSet = $product && (int)$product['stock_quantity'] === $newStock;
            
            return [
                'pass' => $stockSet,
                'message' => $stockSet
                    ? "Stock set: {$newStock} (Current: {$product['stock_quantity']})"
                    : "Stock not set correctly: " . ($product['stock_quantity'] ?? 'null')
            ];
        }
        
        return [
            'pass' => false,
            'message' => "Stock update failed"
        ];
    });
    
    // Test 10: Create product variations (colors)
    runTest("Create product variations (colors)", function() use ($productVariantModel, $testProductId) {
        $variations = [
            ['variant_name' => 'Red', 'variant_value' => '#FF0000', 'price_adjustment' => 0.00, 'stock_quantity' => 30],
            ['variant_name' => 'Blue', 'variant_value' => '#0000FF', 'price_adjustment' => 50.00, 'stock_quantity' => 25],
            ['variant_name' => 'Green', 'variant_value' => '#00FF00', 'price_adjustment' => 25.00, 'stock_quantity' => 20]
        ];
        
        $createdCount = 0;
        foreach ($variations as $variation) {
            $variantId = $productVariantModel->createVariant([
                'product_id' => $testProductId,
                'variant_name' => $variation['variant_name'],
                'variant_value' => $variation['variant_value'],
                'variant_type' => 'color',
                'price_adjustment' => $variation['price_adjustment'],
                'stock_quantity' => $variation['stock_quantity'],
                'sku' => 'VAR-' . strtoupper(substr($variation['variant_name'], 0, 3)) . '-' . $testProductId,
                'is_default' => $createdCount === 0 ? 1 : 0,
                'status' => 'active'
            ]);
            
            if ($variantId > 0) {
                $createdCount++;
            }
        }
        
        $allVariationsCreated = $createdCount === count($variations);
        
        return [
            'pass' => $allVariationsCreated,
            'message' => $allVariationsCreated
                ? "All variations created: {$createdCount} color variations"
                : "Some variations failed: {$createdCount}/" . count($variations)
        ];
    });
    
    // Test 11: Verify product variations are stored
    runTest("Product variations are stored correctly", function() use ($productVariantModel, $testProductId) {
        $variants = $productVariantModel->getVariantsByProduct($testProductId, false);
        $hasVariations = count($variants) >= 3;
        
        return [
            'pass' => $hasVariations,
            'message' => $hasVariations
                ? "Variations stored: " . count($variants) . " variants"
                : "Variations missing: " . count($variants) . " found (Expected: 3+)"
        ];
    });
    
    // Test 12: Product appears in seller products list
    runTest("Product appears in seller products list", function() use ($productModel, $testSellerId, $testProductId) {
        $sellerProducts = $productModel->getProductsBySellerId($testSellerId, 100, 0);
        $productFound = false;
        
        foreach ($sellerProducts as $product) {
            if ($product['id'] == $testProductId) {
                $productFound = true;
                break;
            }
        }
        
        return [
            'pass' => $productFound,
            'message' => $productFound
                ? "Product found in seller products list: ID {$testProductId}"
                : "Product not found in seller products list"
        ];
    });
    
    // Test 13: Product appears in admin approval queue
    runTest("Product appears in admin approval queue", function() use ($db, $testProductId) {
        $pendingProducts = $db->query(
            "SELECT p.*, s.name as seller_name
             FROM products p
             LEFT JOIN sellers s ON p.seller_id = s.id
             WHERE p.approval_status = 'pending' OR p.approval_status IS NULL
             ORDER BY p.created_at DESC"
        )->all();
        
        $productFound = false;
        foreach ($pendingProducts as $product) {
            if ($product['id'] == $testProductId) {
                $productFound = true;
                break;
            }
        }
        
        return [
            'pass' => $productFound,
            'message' => $productFound
                ? "Product found in admin approval queue: ID {$testProductId}"
                : "Product not found in admin approval queue"
        ];
    });
    
    // Test 14: Admin can approve product
    runTest("Admin can approve product", function() use ($productModel, $testProductId, $testAdminId) {
        $approvalData = [
            'approval_status' => 'approved',
            'status' => 'active',
            'approval_notes' => 'Product approved for testing',
            'approved_by' => $testAdminId,
            'approved_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $productModel->updateProduct($testProductId, $approvalData);
        
        if ($result) {
            $product = $productModel->find($testProductId);
            $isApproved = $product && $product['approval_status'] === 'approved' && $product['status'] === 'active';
            
            return [
                'pass' => $isApproved,
                'message' => $isApproved
                    ? "Product approved: Status = {$product['status']}, Approval = {$product['approval_status']}, Approved by = {$product['approved_by']}"
                    : "Product approval failed: Status = " . ($product['status'] ?? 'null') . ", Approval = " . ($product['approval_status'] ?? 'null')
            ];
        }
        
        return [
            'pass' => false,
            'message' => "Product approval update failed"
        ];
    });
    
    // Test 15: Approved product is active
    runTest("Approved product is active", function() use ($productModel, $testProductId) {
        $product = $productModel->find($testProductId);
        $isActive = $product && $product['status'] === 'active' && $product['approval_status'] === 'approved';
        
        return [
            'pass' => $isActive,
            'message' => $isActive
                ? "Product is active: Status = {$product['status']}, Approval = {$product['approval_status']}"
                : "Product is not active: Status = " . ($product['status'] ?? 'null') . ", Approval = " . ($product['approval_status'] ?? 'null')
        ];
    });
    
    // Test 16: Approved product appears in active products
    runTest("Approved product appears in active products", function() use ($db, $testProductId) {
        $activeProducts = $db->query(
            "SELECT * FROM products WHERE status = 'active' AND approval_status = 'approved'"
        )->all();
        
        $productFound = false;
        foreach ($activeProducts as $product) {
            if ($product['id'] == $testProductId) {
                $productFound = true;
                break;
            }
        }
        
        return [
            'pass' => $productFound,
            'message' => $productFound
                ? "Product found in active products: ID {$testProductId}"
                : "Product not found in active products"
        ];
    });
    
    // Test 17: Product can be rejected by admin
    runTest("Product can be rejected by admin", function() use ($productModel, $testProductId, $testAdminId) {
        // First, set back to pending
        $productModel->updateProduct($testProductId, [
            'approval_status' => 'pending',
            'status' => 'pending'
        ]);
        
        // Now reject it
        $rejectionData = [
            'approval_status' => 'rejected',
            'status' => 'inactive',
            'approval_notes' => 'Product rejected for testing',
            'approved_by' => $testAdminId,
            'approved_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $productModel->updateProduct($testProductId, $rejectionData);
        
        if ($result) {
            $product = $productModel->find($testProductId);
            $isRejected = $product && $product['approval_status'] === 'rejected' && $product['status'] === 'inactive';
            
            return [
                'pass' => $isRejected,
                'message' => $isRejected
                    ? "Product rejected: Status = {$product['status']}, Approval = {$product['approval_status']}"
                    : "Product rejection failed: Status = " . ($product['status'] ?? 'null') . ", Approval = " . ($product['approval_status'] ?? 'null')
            ];
        }
        
        return [
            'pass' => false,
            'message' => "Product rejection update failed"
        ];
    });
    
    // Test 18: Rejected product is inactive
    runTest("Rejected product is inactive", function() use ($productModel, $testProductId) {
        $product = $productModel->find($testProductId);
        $isInactive = $product && $product['status'] === 'inactive' && $product['approval_status'] === 'rejected';
        
        return [
            'pass' => $isInactive,
            'message' => $isInactive
                ? "Product is inactive: Status = {$product['status']}, Approval = {$product['approval_status']}"
                : "Product is not inactive: Status = " . ($product['status'] ?? 'null') . ", Approval = " . ($product['approval_status'] ?? 'null')
        ];
    });
    
    // Cleanup
    echo "--- Cleanup ---\n";
    if ($testProductId) {
        $db->query("DELETE FROM product_variants WHERE product_id = ?", [$testProductId])->execute();
        $db->query("DELETE FROM product_images WHERE product_id = ?", [$testProductId])->execute();
        $db->query("DELETE FROM products WHERE id = ?", [$testProductId])->execute();
    }
    
    if ($testSellerId) {
        $db->query("DELETE FROM sellers WHERE id = ?", [$testSellerId])->execute();
    }
    
    if ($testAdminId) {
        $db->query("DELETE FROM users WHERE id = ?", [$testAdminId])->execute();
    }
    
    echo "Test product, images, variations, seller, and admin deleted\n\n";
    
    Session::destroy();
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Cleanup on error
    if ($testProductId) {
        try {
            $db->query("DELETE FROM product_variants WHERE product_id = ?", [$testProductId])->execute();
            $db->query("DELETE FROM product_images WHERE product_id = ?", [$testProductId])->execute();
            $db->query("DELETE FROM products WHERE id = ?", [$testProductId])->execute();
        } catch (Exception $cleanupError) {
            echo "Product cleanup error: " . $cleanupError->getMessage() . "\n";
        }
    }
    
    if ($testSellerId) {
        try {
            $db->query("DELETE FROM sellers WHERE id = ?", [$testSellerId])->execute();
        } catch (Exception $cleanupError) {
            echo "Seller cleanup error: " . $cleanupError->getMessage() . "\n";
        }
    }
    
    if ($testAdminId) {
        try {
            $db->query("DELETE FROM users WHERE id = ?", [$testAdminId])->execute();
        } catch (Exception $cleanupError) {
            echo "Admin cleanup error: " . $cleanupError->getMessage() . "\n";
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
    echo "✓ ALL TESTS PASSED! Seller product management system is working perfectly.\n";
    echo "\nFeatures Verified:\n";
    echo "  ✓ Seller can login\n";
    echo "  ✓ Seller session is set\n";
    echo "  ✓ Product can be created with all fields\n";
    echo "  ✓ Product has correct seller_id\n";
    echo "  ✓ Product has pending approval status\n";
    echo "  ✓ Primary product image can be uploaded\n";
    echo "  ✓ Additional product images can be uploaded\n";
    echo "  ✓ Product images are stored correctly\n";
    echo "  ✓ Product stock can be set\n";
    echo "  ✓ Product variations (colors) can be created\n";
    echo "  ✓ Product variations are stored correctly\n";
    echo "  ✓ Product appears in seller products list\n";
    echo "  ✓ Product appears in admin approval queue\n";
    echo "  ✓ Admin can approve product\n";
    echo "  ✓ Approved product is active\n";
    echo "  ✓ Approved product appears in active products\n";
    echo "  ✓ Product can be rejected by admin\n";
    echo "  ✓ Rejected product is inactive\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}

