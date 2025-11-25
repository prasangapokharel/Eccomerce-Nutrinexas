<?php
/**
 * Fix accessories products images - assign appropriate images per product type
 */

require_once __DIR__ . '/../vendor/autoload.php';

define('ROOT_DIR', dirname(__DIR__));
define('URLROOT', 'http://localhost:8000');
define('ASSETS_URL', URLROOT . '/public');

require_once ROOT_DIR . '/App/Config/config.php';

use App\Core\Database;
use App\Models\Product;
use App\Models\ProductImage;

try {
    $db = Database::getInstance();
    $productModel = new Product();
    $imageModel = new ProductImage();
    
    echo "Fixing accessories products images...\n\n";
    
    // Product-specific images (using placeholder/stock images for now)
    // In production, these should be actual product images
    $productImages = [
        'Classic Joe Hoodie' => [
            'https://apparel.goldsgym.com/media/image/38/4b/0b/Vorschauq2YVQ1o02K49b_1142x1142@2x.jpg',
            'https://apparel.goldsgym.com/media/image/a0/c2/02/221205_GG_Classic_MEN_Heavy_Weight_Classic_Joe_Grau-1446_1142x1142@2x.jpg',
            'https://apparel.goldsgym.com/media/image/68/b3/fd/221205_GG_Classic_MEN_Heavy_Weight_Classic_Joe_Grau-1441_1142x1142@2x.jpg'
        ],
        'Gold\'s Gym Classic T-Shirt' => [
            'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&h=800&fit=crop',
            'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&h=800&fit=crop',
            'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&h=800&fit=crop'
        ],
        'Training Tank Top' => [
            'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop',
            'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=800&h=800&fit=crop',
            'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop'
        ],
        'Athletic Shorts' => [
            'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=800&h=800&fit=crop',
            'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=800&h=800&fit=crop',
            'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=800&h=800&fit=crop'
        ],
        'Gym Bag Pro' => [
            'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop',
            'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=800&h=800&fit=crop',
            'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&h=800&fit=crop'
        ]
    ];
    
    // Get all accessories products
    $sql = "SELECT * FROM products WHERE category = 'Accessories' AND status = 'active'";
    $products = $db->query($sql)->all();
    
    foreach ($products as $product) {
        $productName = $product['product_name'];
        
        if (!isset($productImages[$productName])) {
            continue;
        }
        
        echo "Updating images for: {$productName} (ID: {$product['id']})...\n";
        
        // Delete existing images
        $existingImages = $imageModel->getByProductId($product['id']);
        foreach ($existingImages as $img) {
            $imageModel->delete($img['id']);
        }
        
        // Add new images
        foreach ($productImages[$productName] as $index => $imageUrl) {
            $imageData = [
                'product_id' => $product['id'],
                'image_url' => $imageUrl,
                'is_primary' => $index === 0 ? 1 : 0,
                'sort_order' => $index
            ];
            $imageModel->create($imageData);
        }
        
        echo "  ✓ Images updated\n\n";
    }
    
    echo "✅ All accessories products images fixed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

