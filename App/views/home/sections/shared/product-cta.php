<?php
/**
 * Shared Product CTA Block
 *
 * Expects $product to be defined in the parent scope.
 * Provides wishlist toggle + Buy Now / status buttons.
 */

if (!isset($product) || !is_array($product)) {
    return;
}

$productId = $product['id'] ?? 0;
$wishlistActive = !empty($product['in_wishlist']);
$stockQty = isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : null;
$isScheduled = !empty($product['is_scheduled']);
$remaining = isset($product['remaining_days']) ? (int)$product['remaining_days'] : null;
$ctaStyle = $ctaStyleOverride ?? ($ctaStyle ?? 'default');
unset($ctaStyleOverride);

$ctaClassMap = [
    'default' => 'w-full inline-flex items-center justify-center bg-white text-primary border border-primary rounded-full font-semibold py-2 px-4 shadow-sm add-to-cart-btn',
    'primary' => 'w-full inline-flex items-center justify-center bg-primary text-white rounded-full font-semibold py-2 px-4 shadow-sm add-to-cart-btn hover:bg-primary-dark transition-colors',
    'secondary' => 'w-full inline-flex items-center justify-center bg-secondary text-white rounded-full font-semibold py-2 px-4 shadow-sm add-to-cart-btn',
];
$textColorMap = [
    'default' => 'text-primary',
    'primary' => 'text-white',
    'secondary' => 'text-white',
];
$ctaClasses = $ctaClassMap[$ctaStyle] ?? $ctaClassMap['default'];
$textColorClass = $textColorMap[$ctaStyle] ?? $textColorMap['default'];
?>

<?php if ($isScheduled): ?>
    <div class="flex items-center gap-1">
        <button onclick="event.stopPropagation(); <?= $wishlistActive ? "removeFromWishlist({$productId})" : "addToWishlist({$productId})" ?>"
                class="btn-wishlist <?= $wishlistActive ? 'wishlist-active' : '' ?>"
                data-product-id="<?= $productId ?>">
            <i class="<?= $wishlistActive ? 'fas' : 'far' ?> fa-heart text-sm"></i>
        </button>
        <button disabled class="btn-sale bg-gray-300 cursor-not-allowed flex-1">
            <svg class="w-3 h-3 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <?= ($remaining && $remaining > 0) ? "Launching in {$remaining} days" : 'Coming Soon' ?>
        </button>
    </div>
<?php elseif ($stockQty === null || $stockQty > 0): ?>
    <div class="flex items-center gap-1">
        <button onclick="event.stopPropagation(); <?= $wishlistActive ? "removeFromWishlist({$productId})" : "addToWishlist({$productId})" ?>"
                class="btn-wishlist <?= $wishlistActive ? 'wishlist-active' : '' ?>"
                data-product-id="<?= $productId ?>">
            <i class="<?= $wishlistActive ? 'fas' : 'far' ?> fa-heart text-sm"></i>
        </button>

        <form action="<?= ASSETS_URL ?>/cart/add" method="post" class="add-to-cart-form flex-1" onclick="event.stopPropagation()">
            <input type="hidden" name="product_id" value="<?= $productId ?>">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="<?= $ctaClasses ?>">
                <span class="btn-text <?= $textColorClass ?>">Buy Now</span>
                <span class="btn-loading hidden">Adding...</span>
            </button>
        </form>
    </div>
<?php else: ?>
    <div class="flex items-center gap-1">
        <button onclick="event.stopPropagation(); <?= $wishlistActive ? "removeFromWishlist({$productId})" : "addToWishlist({$productId})" ?>"
                class="btn-wishlist <?= $wishlistActive ? 'wishlist-active' : '' ?>"
                data-product-id="<?= $productId ?>">
            <i class="<?= $wishlistActive ? 'fas' : 'far' ?> fa-heart text-sm"></i>
        </button>

        <button disabled class="flex-1 py-2 bg-accent text-gray-600 text-xs font-semibold rounded-2xl cursor-not-allowed shadow-sm">
            Out of Stock
        </button>
    </div>
<?php endif; ?>

