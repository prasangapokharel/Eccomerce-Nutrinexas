<?php
namespace App\Helpers;
use App\Models\ProductImage;

class Image
{
    private $productImageModel;

    public function __construct()
    {
        $this->productImageModel = new ProductImage();
    }

    /**
     * Get product image URL with fallback logic
     *
     * @param array $product
     * @return string
     */

protected function getProductImageUrl($product)
{
    // 1. Check if product has direct image URL
    if (!empty($product['image'])) {
        return $product['image'];
    }
    
    // 2. Check for primary image from product_images table
    $primaryImage = $this->productImageModel->getPrimaryImage($product['product_id']);
    if ($primaryImage && !empty($primaryImage['image_url'])) {
        return $primaryImage['image_url'];
    }
    
    // 3. Fallback to default image
    return \App\Core\View::asset('images/products/default.jpg');
}


}




?>