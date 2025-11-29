<?php
/**
 * Product Card Partial
 *
 * Reusable product presentation for all home sections.
 *
 * Required variables before include:
 * - $product (array)
 * - $pricingHelper (callable) already defined in parent view
 *
 * Optional:
 * - $cardOptions (array) with keys:
 *      'theme' => 'flash' | 'primary' | 'light'
 *      'topRightBadge' => ['label' => 'Most Sold', 'icon' => '<svg ...>']
 *      'topLeftBadges' => callable/custom partial (defaults to discount + stock)
 *      'ctaStyle' => overrides theme default
 */

if (empty($product) || !is_array($product)) {
    return;
}

$cardOptions = $cardOptions ?? [];
$themeKey = $cardOptions['theme'] ?? 'light';
$themes = [
    'flash' => [
        'card' => 'block bg-sale border border-gray-100 rounded-2xl overflow-hidden relative product-card productclip text-white',
        'image' => 'relative aspect-square bg-sale p-2 rounded-2xl overflow-hidden',
        'title' => 'text-sm font-medium text-white mb-1 truncate',
        'ratingText' => 'text-xs text-gray-300',
        'priceWrap' => 'text-base font-bold text-accent',
        'priceStrike' => 'text-xs text-gray-200 line-through',
        'savings' => 'text-xs text-success font-semibold',
        'cta' => 'default',
    ],
    'primary' => [
        'card' => 'block bg-primary text-white border border-primary/20 rounded-2xl overflow-hidden relative product-card',
        'image' => 'relative aspect-square bg-primary/30 p-2 rounded-2xl overflow-hidden',
        'title' => 'text-sm font-semibold text-white mb-2 truncate',
        'ratingText' => 'text-xs text-white/70',
        'priceWrap' => 'text-lg font-bold text-white',
        'priceStrike' => 'text-xs text-white/70 line-through',
        'savings' => 'text-xs text-success font-semibold',
        'cta' => 'primary',
    ],
    'light' => [
        'card' => 'block bg-white border border-gray-100 rounded-2xl overflow-hidden relative product-card hover:shadow-md transition-shadow',
        'image' => 'relative aspect-square bg-gray-50 p-2 rounded-2xl overflow-hidden',
        'title' => 'text-sm font-semibold text-foreground mb-2 truncate',
        'ratingText' => 'text-xs text-gray-600',
        'priceWrap' => 'text-lg font-bold text-primary',
        'priceStrike' => 'text-xs text-gray-500 line-through',
        'savings' => 'text-xs text-success font-semibold',
        'cta' => 'default',
    ],
];

$theme = $themes[$themeKey] ?? $themes['light'];
$showCta = array_key_exists('showCta', $cardOptions) ? (bool)$cardOptions['showCta'] : false;
$cardClassExtra = trim($cardOptions['cardClass'] ?? '');
$pricing = $pricingHelper($product);
$hasSale = $pricing['hasSale'];
$originalPrice = $pricing['original'];
$currentPrice = $pricing['current'];
$discountPercent = $pricing['discountPercent'];
$mainImageUrl = $product['image_url'] ?? \App\Core\View::asset('images/products/default.jpg');
$rating = isset($product['avg_rating']) && $product['avg_rating'] > 0 ? floatval($product['avg_rating']) : 0;
$reviewCount = isset($product['review_count']) ? (int)$product['review_count'] : 0;
$ctaStyle = $cardOptions['ctaStyle'] ?? $theme['cta'];
$cardUrl = \App\Core\View::url('products/view/' . ($product['slug'] ?? $product['id']));
$topRightBadge = $cardOptions['topRightBadge'] ?? null;

// Check if product is sponsored for tracking
$isSponsored = !empty($product['is_sponsored']) && $product['is_sponsored'] === true;
$hasAdId = !empty($product['ad_id']);
$adId = $isSponsored || $hasAdId ? ($product['ad_id'] ?? null) : null;
?>

<div class="<?= trim($theme['card'] . ' ' . $cardClassExtra) ?>" 
     onclick="redirectToProduct('<?= $cardUrl ?>', <?= $adId ? $adId : 'null' ?>)"
     <?php if ($adId): ?>data-ad-id="<?= $adId ?>"<?php endif; ?>>
    <div class="<?= $theme['image'] ?>">
        <?php 
        $isVideo = \App\Helpers\MediaHelper::isVideo($mainImageUrl);
        if ($isVideo): 
        ?>
            <video src="<?= htmlspecialchars($mainImageUrl) ?>"
                   class="w-full h-full object-cover rounded-2xl"
                   muted
                   loop
                   playsinline
                   preload="metadata"
                   poster="<?= \App\Core\View::asset('images/products/default.jpg') ?>"
                   <?php if ($adId): ?>
                   onloadedmetadata="<?= \App\Helpers\AdTrackingHelper::getReachTrackingJS($adId) ?>"
                   <?php endif; ?>>
            </video>
        <?php else: ?>
            <img src="<?= htmlspecialchars($mainImageUrl) ?>"
                 alt="<?= htmlspecialchars($product['product_name'] ?? 'Product') ?>"
                 class="w-full h-full object-cover rounded-2xl"
                 loading="lazy"
                 <?php if ($adId): ?>
                 onload="<?= \App\Helpers\AdTrackingHelper::getReachTrackingJS($adId) ?>"
                 <?php endif; ?>
                 onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
        <?php endif; ?>

        <div class="absolute top-1.5 left-1.5 z-10 flex flex-col gap-1">
            <?php if ($discountPercent > 0): ?>
                <span class="bg-primary text-white px-1.5 py-0.5 rounded-full text-xs font-semibold shadow-sm">
                    -<?= $discountPercent ?>%
                </span>
            <?php endif; ?>

            <?php if (isset($product['stock_quantity'])): ?>
                <?php if ($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0): ?>
                    <span class="bg-orange-500 text-white px-1.5 py-0.5 rounded-full text-xs font-semibold shadow-sm">
                        LOW
                    </span>
                <?php elseif ($product['stock_quantity'] <= 0): ?>
                    <span class="bg-gray-500 text-white px-1.5 py-0.5 rounded-full text-xs font-semibold shadow-sm">
                        OUT
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php 
        // Show "Sponsored" badge if product has active ad
        $isSponsored = !empty($product['is_sponsored']) && $product['is_sponsored'] === true;
        $hasAdId = !empty($product['ad_id']);
        if ($isSponsored || $hasAdId): ?>
            <div class="absolute bottom-2 left-2 z-10">
                <span class="bg-gray-700/80 text-white px-1.5 py-0.5 rounded text-[10px] font-medium shadow-sm" style="font-size: 9px; letter-spacing: 0.3px;">
                    Sponsored
                </span>
            </div>
        <?php endif; ?>

        <?php 
        // Wishlist heart icon - always show in top-right (same as More Products section)
        $productId = $product['id'] ?? 0;
        $wishlistActive = !empty($product['in_wishlist']);
        ?>
        <button onclick="event.stopPropagation(); <?= $wishlistActive ? "removeFromWishlist({$productId})" : "addToWishlist({$productId})" ?>" 
                class="absolute top-2 right-2 btn-wishlist <?= $wishlistActive ? 'wishlist-active' : '' ?> z-10"
                data-product-id="<?= $productId ?>">
            <i class="<?= $wishlistActive ? 'fas' : 'far' ?> fa-heart"></i>
        </button>

        <?php if (!empty($topRightBadge['label'])): ?>
            <div class="absolute top-2 right-12 bg-primary text-white px-2 py-1 rounded-full text-xs font-bold shadow inline-flex items-center gap-1 z-10">
                <?php if (!empty($topRightBadge['icon'])): ?>
                    <?= $topRightBadge['icon'] ?>
                <?php endif; ?>
                <span><?= htmlspecialchars($topRightBadge['label']) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="p-3">
        <h4 class="<?= $theme['title'] ?>">
            <?= htmlspecialchars($product['product_name'] ?? 'Product Name') ?>
        </h4>

        <div class="flex items-center gap-1 mb-2">
            <div class="flex items-center">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star text-xs <?= $i <= $rating ? 'text-yellow-400' : ($i - 0.5 <= $rating ? 'text-yellow-300' : 'text-gray-300') ?>"></i>
                <?php endfor; ?>
            </div>
            <span class="<?= $theme['ratingText'] ?>">(<?= $reviewCount ?>)</span>
        </div>

        <div class="mb-2">
            <?php if ($hasSale && $discountPercent > 0): ?>
                <div class="flex items-baseline gap-2 mb-1">
                    <span class="<?= $theme['priceWrap'] ?>">रु<?= number_format($currentPrice, 0) ?></span>
                    <span class="<?= $theme['priceStrike'] ?>">रु<?= number_format($originalPrice, 0) ?></span>
                </div>
                <div class="<?= $theme['savings'] ?>">
                    Save रु<?= number_format($originalPrice - $currentPrice, 0) ?> (<?= $discountPercent ?>% OFF)
                </div>
            <?php else: ?>
                <span class="<?= $theme['priceWrap'] ?>">रु<?= number_format($currentPrice, 0) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($showCta): ?>
            <?php
            $ctaStyleOverride = $ctaStyle;
            include __DIR__ . '/product-cta.php';
            unset($ctaStyleOverride);
            ?>
        <?php endif; ?>

        <?php if (!empty($cardOptions['bottomSection'])): ?>
            <div class="px-3 pb-3">
                <?= $cardOptions['bottomSection'] ?>
            </div>
        <?php endif; ?>
    </div>
</div>

