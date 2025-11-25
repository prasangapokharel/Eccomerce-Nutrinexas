<?php
/**
 * Create Test Products and Ads for Testing
 * 
 * Creates 20 test products with provided images and sets up ads
 */

require_once __DIR__ . '/../App/Config/config.php';

// Define constants if not already defined
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}

// Autoloader
spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Models\Product;
use App\Models\Ad;
use App\Models\ProductImage;

echo "=== Creating Test Products and Ads ===\n\n";

$db = Database::getInstance();
$productModel = new Product();
$adModel = new Ad();
$productImageModel = new ProductImage();

// Test products data with provided images
$testProducts = [
    [
        'name' => 'Yoga Bar Korean Fire Energy Bar',
        'image' => 'https://www.yogabars.in/cdn/shop/files/Korean_Fire.png?v=1759726617',
        'price' => 199,
        'sale_price' => 149,
        'category' => 'Supplements',
        'description' => 'High protein energy bar with Korean Fire flavor. Perfect for fitness enthusiasts.',
        'tags' => 'energy bar, protein, korean fire, yoga bar'
    ],
    [
        'name' => 'Yoga Bar Dark Chocolate Energy Bar',
        'image' => 'https://www.yogabars.in/cdn/shop/files/DarkChocolate_571e6ce9-cdd3-458c-8f6a-fabc87ff37af.png?v=1759834420',
        'price' => 199,
        'sale_price' => 149,
        'category' => 'Supplements',
        'description' => 'Rich dark chocolate energy bar packed with protein and nutrients.',
        'tags' => 'energy bar, protein, dark chocolate, yoga bar'
    ],
    [
        'name' => 'Yoga Bar Pack of 6 Energy Bars',
        'image' => 'https://www.yogabars.in/cdn/shop/files/pack_of_6.png?v=1748845748&width=1445',
        'price' => 999,
        'sale_price' => 799,
        'category' => 'Supplements',
        'description' => 'Value pack of 6 energy bars. Mix of flavors available.',
        'tags' => 'energy bar, protein, pack, yoga bar, bundle'
    ],
    [
        'name' => 'Yoga Bar Power Up Coffee Crush',
        'image' => 'https://www.yogabars.in/cdn/shop/files/Power_Up_Coffee_Crush_Monocarton_FOP.png?v=1740984694&width=1445',
        'price' => 249,
        'sale_price' => 199,
        'category' => 'Supplements',
        'description' => 'Coffee flavored energy bar with extra caffeine boost for your workouts.',
        'tags' => 'energy bar, protein, coffee, power up, caffeine'
    ],
];

// Create 20 products (repeat the 4 products with variations)
$createdProducts = [];
$sellerId = 2; // Use existing seller ID

for ($i = 1; $i <= 20; $i++) {
    $baseProduct = $testProducts[($i - 1) % 4];
    $productName = $baseProduct['name'] . ' - Variant ' . $i;
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $productName)));
    
    $productData = [
        'product_name' => $productName,
        'slug' => $slug . '-' . $i,
        'description' => $baseProduct['description'] . ' (Variant ' . $i . ')',
        'short_description' => $baseProduct['description'],
        'price' => $baseProduct['price'] + ($i * 10), // Vary price
        'sale_price' => $baseProduct['sale_price'] + ($i * 5),
        'stock_quantity' => 100,
        'category' => $baseProduct['category'],
        'subtype' => 'Energy Bars',
        'image' => $baseProduct['image'],
        'tags' => $baseProduct['tags'],
        'status' => 'active',
        'approval_status' => 'approved',
        'seller_id' => $sellerId,
        'is_featured' => ($i <= 5) ? 1 : 0,
    ];
    
    try {
        $productId = $productModel->createProduct($productData);
        
        if ($productId) {
            // Add product image
            $productImageModel->addImage($productId, $baseProduct['image'], true, 0);
            
            $createdProducts[] = [
                'id' => $productId,
                'name' => $productName,
                'image' => $baseProduct['image']
            ];
            
            echo "✓ Created product #{$productId}: {$productName}\n";
        } else {
            echo "✗ Failed to create product: {$productName}\n";
        }
    } catch (Exception $e) {
        echo "✗ Error creating product {$productName}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Creating Ads for Products ===\n\n";

// Get ad type ID for product_internal
$adType = $db->query(
    "SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1"
)->single();

if (!$adType) {
    echo "⚠ Warning: ads_types 'product_internal' not found. Creating it...\n";
    $db->query(
        "INSERT INTO ads_types (name, description) VALUES ('product_internal', 'Product Internal Ad')"
    )->execute();
    $adTypeId = $db->lastInsertId();
} else {
    $adTypeId = $adType['id'];
}

// Get ad cost ID
$adCost = $db->query(
    "SELECT id FROM ads_costs ORDER BY id LIMIT 1"
)->single();

if (!$adCost) {
    echo "⚠ Warning: No ads_costs found. Creating default...\n";
    $db->query(
        "INSERT INTO ads_costs (duration_days, cost_amount) VALUES (30, 1000)"
    )->execute();
    $adCostId = $db->lastInsertId();
} else {
    $adCostId = $adCost['id'];
}

// Create ads for first 10 products
$adsCreated = 0;
foreach (array_slice($createdProducts, 0, 10) as $product) {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+30 days'));
    
    try {
        $adData = [
            'seller_id' => $sellerId,
            'ads_type_id' => $adTypeId,
            'product_id' => $product['id'],
            'banner_image' => $product['image'],
            'banner_link' => '/products/view/' . $product['id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'ads_cost_id' => $adCostId,
            'status' => 'active',
            'notes' => 'Test ad for ' . $product['name']
        ];
        
        $adId = $adModel->create($adData);
        
        if ($adId) {
            echo "✓ Created ad #{$adId} for product: {$product['name']}\n";
            $adsCreated++;
        } else {
            echo "✗ Failed to create ad for product: {$product['name']}\n";
        }
    } catch (Exception $e) {
        echo "✗ Error creating ad for {$product['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Products created: " . count($createdProducts) . "\n";
echo "Ads created: {$adsCreated}\n";
echo "\n=== Test Search ===\n";
echo "Search for: 'yoga bar', 'energy bar', 'korean fire', 'coffee'\n";
echo "You should see 'Sponsored' labels on ads in search results!\n";

