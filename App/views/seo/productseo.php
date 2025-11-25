<?php
// Dynamic SEO Meta Tags Setup for Product Pages
// Place this code at the top of your product view page, before including meta.php

// Get product data safely with fallbacks
$productName = htmlspecialchars($product['product_name'] ?? 'Premium Supplement');
$productDescription = htmlspecialchars($product['description'] ?? $product['short_description'] ?? 'High-quality supplement for optimal health and performance.');
$productCategory = htmlspecialchars($product['category'] ?? 'Supplement');

// Truncate description for meta description (160 characters max)
$truncatedDescription = strlen($productDescription) > 160 
    ? substr($productDescription, 0, 157) . '...' 
    : $productDescription;

// Get price information
$currentPrice = '';
$originalPrice = '';
if (isset($product['sale_price']) && $product['sale_price'] && $product['sale_price'] < $product['price']) {
    $currentPrice = number_format($product['sale_price'], 2);
    $originalPrice = number_format($product['price'], 2);
    $discountPercent = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
} else {
    $currentPrice = number_format($product['price'] ?? 0, 2);
}

// Get main image URL (your existing logic)
$mainImageUrl = '';
if (!empty($product['images'])) {
    $primaryImage = null;
    foreach ($product['images'] as $img) {
        if ($img['is_primary']) {
            $primaryImage = $img;
            break;
        }
    }
    $imageData = $primaryImage ?: $product['images'][0];
    $mainImageUrl = filter_var($imageData['image_url'], FILTER_VALIDATE_URL)
        ? $imageData['image_url']
        : \App\Core\View::asset('uploads/images/' . $imageData['image_url']);
} else {
    $image = $product['image'] ?? '';
    $mainImageUrl = filter_var($image, FILTER_VALIDATE_URL)
        ? $image
        : ($image ? \App\Core\View::asset('uploads/images/' . $image) : \App\Core\View::asset('images/products/default.jpg'));
}

// Set up SEO variables for meta.php
$page_title = $productName . " - " . $productCategory . " | NutriNexas Nepal";
$meta_description = $truncatedDescription . " Shop authentic supplements at NutriNexas Nepal with fast delivery.";
$meta_keywords = $productName . ", " . $productCategory . " Nepal, supplements Nepal, " . strtolower($productName) . " price, buy " . strtolower($productName) . " online Nepal, NutriNexas, protein Nepal, supplements Kathmandu";
$page_type = "product";
$og_image = $mainImageUrl;
$canonical_url = "https://nutrinexas.shop" . $_SERVER['REQUEST_URI'];

// Product-specific variables
$product_name = $productName;
$product_price = $currentPrice;
$product_currency = "NPR";
$product_availability = (isset($product['stock_quantity']) && $product['stock_quantity'] > 0) ? "InStock" : "OutOfStock";
$product_condition = "NewCondition";
$product_brand = $product['brand'] ?? "NutriNexas";
$product_sku = $product['sku'] ?? $product['id'] ?? "";
$product_gtin = $product['gtin'] ?? "";
$product_rating = number_format($averageRating ?? 4.5, 1);
$product_review_count = $reviewCount ?? 0;

// Include the meta tags
include APPROOT . '/views/seo/meta.php';
?>
