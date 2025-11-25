<?php
/**
 * Script to add 5 Accessories products with color options
 * Run: php test/add_5_accessories.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define constants
define('ROOT_DIR', dirname(__DIR__));
define('URLROOT', 'http://localhost:8000');
define('ASSETS_URL', URLROOT . '/public');

// Load database config
require_once ROOT_DIR . '/App/Config/config.php';

use App\Core\Database;
use App\Models\Product;
use App\Models\ProductImage;

try {
    $db = Database::getInstance();
    $productModel = new Product();
    $imageModel = new ProductImage();
    
    echo "Starting to add 5 Accessories products...\n\n";
    
    // Default images for accessories
    $defaultImages = [
        'https://apparel.goldsgym.com/media/image/38/4b/0b/Vorschauq2YVQ1o02K49b_1142x1142@2x.jpg',
        'https://apparel.goldsgym.com/media/image/a0/c2/02/221205_GG_Classic_MEN_Heavy_Weight_Classic_Joe_Grau-1446_1142x1142@2x.jpg',
        'https://apparel.goldsgym.com/media/image/68/b3/fd/221205_GG_Classic_MEN_Heavy_Weight_Classic_Joe_Grau-1441_1142x1142@2x.jpg'
    ];
    
    // 5 Accessories products with colors
    $products = [
        [
            'product_name' => 'Classic Joe Hoodie',
            'description' => 'The Classic Joe hoodie is a long-sleeved hoodie made from organic cotton, available in grey, navy, and black. It\'s designed for fans of bodybuilding and CrossFit, as well as for cardio training, running, or everyday wear. Material blend: 80% organic cotton and 20% recycled polyester, described as "heavy, fluffy fabric." The fabric and two-part hood are designed to keep the wearer warm during workouts.',
            'short_description' => 'Premium hoodie with Classic Joe logo. Perfect for workouts and everyday wear.',
            'price' => 1200.00,
            'sale_price' => 900.00,
            'stock_quantity' => 25,
            'category' => 'Accessories',
            'subtype' => 'Clothing',
            'product_type_main' => 'Accessories',
            'product_type' => 'Hoodie',
            'colors' => json_encode(['Grey', 'Navy', 'Black']),
            'size_available' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
            'weight' => 'Heavy Weight',
            'material' => '80% Organic Cotton, 20% Recycled Polyester',
            'flavor' => null,
            'ingredients' => null,
            'is_featured' => 1,
            'is_digital' => 0,
            'status' => 'active',
            'commission_rate' => 10.00,
            'meta_title' => 'Classic Joe Hoodie - Premium Workout Hoodie | NutriNexus',
            'meta_description' => 'Get the Classic Joe hoodie in Grey, Navy, or Black. Perfect for bodybuilding, CrossFit, and everyday wear. Made from organic cotton.',
            'tags' => 'hoodie, workout, gym, clothing, classic joe',
            'cost_price' => 600.00,
            'compare_price' => 1200.00
        ],
        [
            'product_name' => 'Gold\'s Gym Classic T-Shirt',
            'description' => 'Premium cotton t-shirt with Gold\'s Gym logo. Available in multiple colors. Perfect for gym workouts, casual wear, and showing your fitness commitment.',
            'short_description' => 'Classic Gold\'s Gym t-shirt in multiple colors. Comfortable and durable.',
            'price' => 800.00,
            'sale_price' => 650.00,
            'stock_quantity' => 30,
            'category' => 'Accessories',
            'subtype' => 'Clothing',
            'product_type_main' => 'Accessories',
            'product_type' => 'T-Shirt',
            'colors' => json_encode(['White', 'Black', 'Navy', 'Red']),
            'size_available' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
            'weight' => 'Regular Fit',
            'material' => '100% Cotton',
            'flavor' => null,
            'ingredients' => null,
            'is_featured' => 1,
            'is_digital' => 0,
            'status' => 'active',
            'commission_rate' => 10.00,
            'meta_title' => 'Gold\'s Gym Classic T-Shirt - Multiple Colors | NutriNexus',
            'meta_description' => 'Premium Gold\'s Gym t-shirt available in White, Black, Navy, and Red. Perfect for workouts and casual wear.',
            'tags' => 't-shirt, gym wear, clothing, golds gym',
            'cost_price' => 400.00,
            'compare_price' => 800.00
        ],
        [
            'product_name' => 'Training Tank Top',
            'description' => 'Lightweight and breathable tank top perfect for intense workouts. Moisture-wicking fabric keeps you cool and dry during training sessions.',
            'short_description' => 'Breathable tank top for intense workouts. Moisture-wicking technology.',
            'price' => 750.00,
            'sale_price' => 600.00,
            'stock_quantity' => 20,
            'category' => 'Accessories',
            'subtype' => 'Clothing',
            'product_type_main' => 'Accessories',
            'product_type' => 'Tank Top',
            'colors' => json_encode(['Black', 'Grey', 'Blue']),
            'size_available' => json_encode(['S', 'M', 'L', 'XL']),
            'weight' => 'Lightweight',
            'material' => 'Polyester Blend',
            'flavor' => null,
            'ingredients' => null,
            'is_featured' => 1,
            'is_digital' => 0,
            'status' => 'active',
            'commission_rate' => 10.00,
            'meta_title' => 'Training Tank Top - Moisture Wicking | NutriNexus',
            'meta_description' => 'Lightweight training tank top in Black, Grey, and Blue. Perfect for intense workouts with moisture-wicking technology.',
            'tags' => 'tank top, training, workout, moisture wicking',
            'cost_price' => 350.00,
            'compare_price' => 750.00
        ],
        [
            'product_name' => 'Athletic Shorts',
            'description' => 'Comfortable athletic shorts with built-in compression. Perfect for running, gym workouts, and sports activities. Available in multiple colors and sizes.',
            'short_description' => 'Comfortable athletic shorts with compression. Perfect for workouts.',
            'price' => 950.00,
            'sale_price' => 750.00,
            'stock_quantity' => 18,
            'category' => 'Accessories',
            'subtype' => 'Clothing',
            'product_type_main' => 'Accessories',
            'product_type' => 'Shorts',
            'colors' => json_encode(['Black', 'Navy', 'Grey', 'Red']),
            'size_available' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
            'weight' => 'Medium Weight',
            'material' => 'Polyester with Compression',
            'flavor' => null,
            'ingredients' => null,
            'is_featured' => 1,
            'is_digital' => 0,
            'status' => 'active',
            'commission_rate' => 10.00,
            'meta_title' => 'Athletic Shorts - Compression Fit | NutriNexus',
            'meta_description' => 'Comfortable athletic shorts with compression in Black, Navy, Grey, and Red. Perfect for running and gym workouts.',
            'tags' => 'shorts, athletic, compression, workout',
            'cost_price' => 450.00,
            'compare_price' => 950.00
        ],
        [
            'product_name' => 'Gym Bag Pro',
            'description' => 'Spacious gym bag with multiple compartments. Perfect for carrying workout gear, clothes, and supplements. Durable material with reinforced handles.',
            'short_description' => 'Spacious gym bag with multiple compartments. Durable and functional.',
            'price' => 1500.00,
            'sale_price' => 1200.00,
            'stock_quantity' => 15,
            'category' => 'Accessories',
            'subtype' => 'Equipment',
            'product_type_main' => 'Accessories',
            'product_type' => 'Bag',
            'colors' => json_encode(['Black', 'Navy', 'Grey']),
            'size_available' => json_encode(['One Size']),
            'weight' => 'Large',
            'material' => 'Durable Nylon',
            'flavor' => null,
            'ingredients' => null,
            'is_featured' => 1,
            'is_digital' => 0,
            'status' => 'active',
            'commission_rate' => 10.00,
            'meta_title' => 'Gym Bag Pro - Spacious Workout Bag | NutriNexus',
            'meta_description' => 'Spacious gym bag in Black, Navy, and Grey. Multiple compartments for all your workout gear.',
            'tags' => 'gym bag, bag, equipment, workout gear',
            'cost_price' => 700.00,
            'compare_price' => 1500.00
        ]
    ];
    
    foreach ($products as $index => $productData) {
        echo "Adding product " . ($index + 1) . ": {$productData['product_name']}...\n";
        
        // Generate slug
        $slug = $productModel->generateSlug($productData['product_name']);
        $productData['slug'] = $slug;
        
        // Add product
        $productId = $productModel->create($productData);
        
        if ($productId) {
            echo "  ✓ Product created with ID: $productId\n";
            
            // Add images
            foreach ($defaultImages as $imgIndex => $imageUrl) {
                $imageData = [
                    'product_id' => $productId,
                    'image_url' => $imageUrl,
                    'is_primary' => $imgIndex === 0 ? 1 : 0,
                    'sort_order' => $imgIndex
                ];
                $imageModel->create($imageData);
            }
            echo "  ✓ Images added\n";
            echo "  ✓ Colors: " . implode(', ', json_decode($productData['colors'])) . "\n";
            echo "  ✓ Sizes: " . implode(', ', json_decode($productData['size_available'])) . "\n";
            echo "\n";
        } else {
            echo "  ✗ Failed to create product\n\n";
        }
    }
    
    echo "✅ All 5 Accessories products added successfully!\n";
    echo "\nProducts are now available on:\n";
    echo "- Homepage (Latest Products section)\n";
    echo "- Products page\n";
    echo "- Product view pages with color and size options\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

