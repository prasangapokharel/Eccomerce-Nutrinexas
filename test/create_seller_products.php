<?php
/**
 * Create 5 Test Products for Seller
 * This script creates products as a seller, then approves them as admin
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Product;
use App\Models\Seller;
use App\Models\ProductImage;

echo "=== Creating Seller Products Test ===\n\n";

try {
    $db = Database::getInstance();
    $productModel = new Product();
    $sellerModel = new Seller();
    $imageModel = new ProductImage();
    
    // Step 1: Get or create a test seller
    echo "Step 1: Getting/Creating test seller...\n";
    $seller = $sellerModel->findByEmail('test-seller@nutrinexus.com');
    
    if (!$seller) {
        // Create test seller
        $sellerData = [
            'name' => 'Test Seller',
            'email' => 'test-seller@nutrinexus.com',
            'phone' => '9841234567',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'company_name' => 'Test Seller Company',
            'address' => 'Kathmandu, Nepal',
            'status' => 'active',
            'is_approved' => 1,
            'commission_rate' => 10.00
        ];
        
        $sellerId = $sellerModel->create($sellerData);
        if ($sellerId) {
            $seller = $sellerModel->find($sellerId);
            echo "  ✓ Test seller created (ID: {$sellerId})\n";
        } else {
            throw new Exception("Failed to create test seller");
        }
    } else {
        // Approve seller if not approved
        if (!$seller['is_approved']) {
            $sellerModel->update($seller['id'], ['is_approved' => 1, 'status' => 'active']);
            echo "  ✓ Test seller approved\n";
        }
        echo "  ✓ Test seller found (ID: {$seller['id']})\n";
    }
    
    $sellerId = $seller['id'];
    
    // Step 2: Create 5 products
    echo "\nStep 2: Creating 5 products...\n";
    
    $products = [
        [
            'product_name' => 'Korean Fire Energy Bar',
            'description' => 'Spicy and flavorful Korean Fire Energy Bar packed with natural ingredients. Perfect for a quick energy boost during workouts or busy days.',
            'short_description' => 'Spicy Korean Fire Energy Bar - Natural Energy Boost',
            'price' => 250.00,
            'sale_price' => 199.00,
            'stock_quantity' => 100,
            'category' => 'Energy Bars',
            'subcategory' => 'Protein Bars',
            'product_type_main' => 'Supplement',
            'product_type' => 'Energy Bar',
            'is_digital' => 0,
            'colors' => 'Red',
            'weight' => '50g',
            'serving' => '1 bar',
            'flavor' => 'Korean Fire',
            'is_featured' => 1,
            'status' => 'pending',
            'approval_status' => 'pending',
            'seller_id' => $sellerId,
            'image_url' => 'https://www.yogabars.in/cdn/shop/files/Korean_Fire.png?v=1759726617'
        ],
        [
            'product_name' => 'Dark Chocolate Energy Bar',
            'description' => 'Rich and indulgent Dark Chocolate Energy Bar made with premium dark chocolate. A perfect combination of taste and nutrition.',
            'short_description' => 'Premium Dark Chocolate Energy Bar - Rich & Indulgent',
            'price' => 280.00,
            'sale_price' => 229.00,
            'stock_quantity' => 150,
            'category' => 'Energy Bars',
            'subcategory' => 'Chocolate Bars',
            'product_type_main' => 'Supplement',
            'product_type' => 'Energy Bar',
            'is_digital' => 0,
            'colors' => 'Brown',
            'weight' => '50g',
            'serving' => '1 bar',
            'flavor' => 'Dark Chocolate',
            'is_featured' => 1,
            'status' => 'pending',
            'approval_status' => 'pending',
            'seller_id' => $sellerId,
            'image_url' => 'https://www.yogabars.in/cdn/shop/files/DarkChocolate_571e6ce9-cdd3-458c-8f6a-fabc87ff37af.png?v=1759834420'
        ],
        [
            'product_name' => 'Energy Bar Pack of 6',
            'description' => 'Value pack of 6 assorted energy bars. Perfect for stocking up on your favorite flavors. Includes variety of flavors for different tastes.',
            'short_description' => 'Value Pack of 6 Assorted Energy Bars',
            'price' => 1200.00,
            'sale_price' => 999.00,
            'stock_quantity' => 50,
            'category' => 'Energy Bars',
            'subcategory' => 'Combo Packs',
            'product_type_main' => 'Supplement',
            'product_type' => 'Energy Bar',
            'is_digital' => 0,
            'colors' => 'Mixed',
            'weight' => '300g',
            'serving' => '6 bars',
            'flavor' => 'Assorted',
            'is_featured' => 0,
            'status' => 'pending',
            'approval_status' => 'pending',
            'seller_id' => $sellerId,
            'image_url' => 'https://www.yogabars.in/cdn/shop/files/pack_of_6.png?v=1748845748&width=1445'
        ],
        [
            'product_name' => 'Triple Energy Bar Combo',
            'description' => 'Premium triple combo pack featuring our best-selling energy bars. Great value for money with three delicious flavors.',
            'short_description' => 'Triple Combo Pack - 3 Best-Selling Energy Bars',
            'price' => 750.00,
            'sale_price' => 649.00,
            'stock_quantity' => 75,
            'category' => 'Energy Bars',
            'subcategory' => 'Combo Packs',
            'product_type_main' => 'Supplement',
            'product_type' => 'Energy Bar',
            'is_digital' => 0,
            'colors' => 'Mixed',
            'weight' => '150g',
            'serving' => '3 bars',
            'flavor' => 'Assorted',
            'is_featured' => 1,
            'status' => 'pending',
            'approval_status' => 'pending',
            'seller_id' => $sellerId,
            'image_url' => 'https://www.yogabars.in/cdn/shop/files/3_898fa5bd-e2f2-4ae5-84d6-57a739a66ad2.jpg?v=1748845748&width=1445'
        ],
        [
            'product_name' => 'Power Up Coffee Crush Energy Bar',
            'description' => 'Energizing Coffee Crush Energy Bar with real coffee flavor. Perfect for coffee lovers who need an energy boost. Contains natural caffeine from coffee.',
            'short_description' => 'Coffee Crush Energy Bar - Coffee Lovers Favorite',
            'price' => 270.00,
            'sale_price' => 219.00,
            'stock_quantity' => 120,
            'category' => 'Energy Bars',
            'subcategory' => 'Coffee Bars',
            'product_type_main' => 'Supplement',
            'product_type' => 'Energy Bar',
            'is_digital' => 0,
            'colors' => 'Brown',
            'weight' => '50g',
            'serving' => '1 bar',
            'flavor' => 'Coffee Crush',
            'is_featured' => 1,
            'status' => 'pending',
            'approval_status' => 'pending',
            'seller_id' => $sellerId,
            'image_url' => 'https://www.yogabars.in/cdn/shop/files/Power_Up_Coffee_Crush_Monocarton_FOP.png?v=1740984694&width=1445'
        ]
    ];
    
    $createdProducts = [];
    foreach ($products as $index => $productData) {
        $imageUrl = $productData['image_url'];
        unset($productData['image_url']);
        
        // Generate slug using Product model method
        $slug = $productModel->generateSlug($productData['product_name']);
        $productData['slug'] = $slug;
        
        // Create product
        $productId = $productModel->createProduct($productData);
        
        if ($productId) {
            // Add image
            $imageModel->create([
                'product_id' => $productId,
                'image_url' => $imageUrl,
                'is_primary' => 1
            ]);
            
            $createdProducts[] = $productId;
            echo "  ✓ Product " . ($index + 1) . " created (ID: {$productId}) - {$productData['product_name']}\n";
        } else {
            echo "  ✗ Failed to create product " . ($index + 1) . "\n";
        }
    }
    
    echo "\nStep 3: Approving products as admin...\n";
    $adminId = 1; // Assuming admin user ID is 1
    
    foreach ($createdProducts as $productId) {
        $result = $productModel->updateProduct($productId, [
            'approval_status' => 'approved',
            'status' => 'active',
            'approved_by' => $adminId,
            'approved_at' => date('Y-m-d H:i:s'),
            'approval_notes' => 'Approved for testing'
        ]);
        
        if ($result) {
            echo "  ✓ Product ID {$productId} approved\n";
        } else {
            echo "  ✗ Failed to approve product ID {$productId}\n";
        }
    }
    
    echo "\nStep 4: Clearing cache...\n";
    $cacheDir = __DIR__ . '/../App/storage/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $deleted++;
            }
        }
        echo "  ✓ Cache cleared ({$deleted} files deleted)\n";
    } else {
        echo "  ⚠ Cache directory not found\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "✅ Created " . count($createdProducts) . " products\n";
    echo "✅ Approved " . count($createdProducts) . " products\n";
    echo "✅ Cache cleared\n";
    echo "\nProducts should now be visible on the homepage!\n";
    echo "Seller ID: {$sellerId}\n";
    echo "Product IDs: " . implode(', ', $createdProducts) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

