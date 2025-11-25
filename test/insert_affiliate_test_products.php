<?php
/**
 * Insert 20 Test Products with Affiliate Commissions
 * 
 * Creates products with higher prices and random affiliate commission percentages
 * All products will be approved and active
 */

// Load only database and models, not full application
require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Define constants if not defined
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', '/assets');
}

use App\Core\Database;
use App\Models\Product;
use App\Models\ProductImage;

$db = Database::getInstance();
$productModel = new Product();
$productImageModel = new ProductImage();

// Test images
$testImages = [
    'https://www.yogabars.in/cdn/shop/files/Group_786.png?v=1732086674',
    'https://www.yogabars.in/cdn/shop/files/HPM.png?v=1716468505',
    'https://www.yogabars.in/cdn/shop/files/Monk_Fruit_Dates.png?v=1751958870',
];

// Categories
$categories = ['Supplements', 'Vitamins', 'Protein', 'Wellness', 'Fitness'];

// Product names
$productNames = [
    'Premium Whey Protein Isolate',
    'Advanced Multivitamin Complex',
    'Omega-3 Fish Oil Capsules',
    'Pre-Workout Energy Boost',
    'Post-Workout Recovery Formula',
    'Collagen Peptides Powder',
    'Vitamin D3 + K2 Supplement',
    'Probiotics Digestive Health',
    'Creatine Monohydrate Powder',
    'BCAA Amino Acids',
    'Green Tea Extract Capsules',
    'Turmeric Curcumin Complex',
    'Magnesium Glycinate Tablets',
    'Zinc + Vitamin C Boost',
    'Ashwagandha Stress Support',
    'Glucosamine Joint Support',
    'Iron + Folate Supplement',
    'B-Complex Energy Formula',
    'CoQ10 Heart Health',
    'Melatonin Sleep Support'
];

$inserted = 0;
$errors = [];

try {
    $db->beginTransaction();
    
    for ($i = 0; $i < 20; $i++) {
        // Random affiliate commission (5% to 25%)
        $affiliateCommission = rand(5, 25);
        
        // Higher prices (Rs. 1000 to Rs. 5000)
        $basePrice = rand(1000, 5000);
        $salePrice = rand(500, $basePrice - 100); // Random sale price
        
        // Random category
        $category = $categories[array_rand($categories)];
        
        // Product data
        $productData = [
            'product_name' => $productNames[$i],
            'slug' => 'affiliate-test-product-' . ($i + 1),
            'description' => 'Premium quality ' . strtolower($productNames[$i]) . ' for optimal health and wellness. This product is specially formulated with high-quality ingredients to support your fitness and health goals.',
            'short_description' => 'Premium quality supplement for optimal health',
            'price' => $basePrice,
            'sale_price' => $salePrice,
            'stock_quantity' => rand(50, 200),
            'category' => $category,
            'status' => 'active',
            'approval_status' => 'approved',
            'affiliate_commission' => $affiliateCommission,
            'is_featured' => rand(0, 1),
            'seller_id' => 0, // Admin products
        ];
        
        // Insert product
        $productId = $productModel->create($productData);
        
        if ($productId) {
            // Add product image
            $imageUrl = $testImages[array_rand($testImages)];
            $imageData = [
                'product_id' => $productId,
                'image_url' => $imageUrl,
                'is_primary' => 1
            ];
            
            $productImageModel->create($imageData);
            
            $inserted++;
            echo "✓ Created product #{$productId}: {$productNames[$i]} (Commission: {$affiliateCommission}%, Price: Rs. {$basePrice})\n";
        } else {
            $errors[] = "Failed to create product: {$productNames[$i]}";
        }
    }
    
    $db->commit();
    
    echo "\n=== Summary ===\n";
    echo "Successfully inserted: {$inserted} products\n";
    if (!empty($errors)) {
        echo "Errors: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }
    echo "\nAll products are approved and active.\n";
    echo "Visit: http://192.168.1.77:8000/affiliate/products\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

