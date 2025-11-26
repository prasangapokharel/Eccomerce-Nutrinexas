<?php
/**
 * Latest Products Grid
 *
 * Uses $products and $pricingHelper to render recently added items.
 */

$latestProducts = array_slice($products ?? [], 0, 7);

// Get internal product ad
$internalProductAd = null;
$db = \App\Core\Database::getInstance();
$adProduct = $db->query(
    "SELECT p.*, a.id as ad_id,
            (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image_url
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     INNER JOIN products p ON a.product_id = p.id
     WHERE at.name = 'product_internal'
     AND a.status = 'active'
     AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
     AND CURDATE() BETWEEN a.start_date AND a.end_date
     AND a.product_id IS NOT NULL
     AND p.status = 'active'
     AND p.approval_status = 'approved'
     ORDER BY RAND()
     LIMIT 1",
    []
)->single();

if ($adProduct) {
    // Get image URL
    if (!empty($adProduct['primary_image_url'])) {
        $adProduct['image_url'] = filter_var($adProduct['primary_image_url'], FILTER_VALIDATE_URL) 
            ? $adProduct['primary_image_url'] 
            : \App\Core\View::asset('uploads/images/' . $adProduct['primary_image_url']);
    } else {
        $adProduct['image_url'] = \App\Core\View::asset('images/products/default.jpg');
    }
    
    // Add review stats for product card display
    $reviewModel = new \App\Models\Review();
    $adProduct['avg_rating'] = $reviewModel->getAverageRating($adProduct['id']);
    $adProduct['review_count'] = $reviewModel->getReviewCount($adProduct['id']);
    
    $adProduct['is_sponsored'] = true;
    $internalProductAd = $adProduct;
}

if (empty($latestProducts) && empty($internalProductAd)) {
    return;
}
?>

<div class="bg-white mx-2 rounded-xl shadow-sm mb-0 border border-primary/10">
    <div class="flex items-center justify-between p-3 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-primary">Latest Products</h3>
        <a href="<?= \App\Core\View::url('products') ?>" class="inline-flex items-center gap-1 text-accent font-semibold text-sm hover:text-accent/80 transition-colors">
            <span>View All</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <div class="p-0 sm:p-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
            <?php 
            // Mix 7 products + 1 ad
            $displayProducts = [];
            $productCount = 0;
            foreach ($latestProducts as $product) {
                if ($productCount < 7) {
                    $displayProducts[] = $product;
                    $productCount++;
                }
            }
            
            // Insert ad randomly after 2nd, 3rd, or 4th product
            if (!empty($internalProductAd)) {
                $insertPosition = min(rand(2, 4), count($displayProducts));
                array_splice($displayProducts, $insertPosition, 0, [$internalProductAd]);
            }
            
            foreach ($displayProducts as $product):
                $cardOptions = [
                    'theme' => 'light',
                    'showCta' => false,
                ];
                
                // Add AD badge for sponsored products
                if (!empty($product['is_sponsored']) || !empty($product['ad_id'])) {
                    $cardOptions['topRightBadge'] = ['label' => 'AD'];
                }
                
                include __DIR__ . '/shared/product-card.php';
            endforeach; ?>
        </div>
    </div>
</div>

