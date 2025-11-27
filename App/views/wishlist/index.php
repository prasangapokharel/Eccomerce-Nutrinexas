<?php 
ob_start(); 
use App\Helpers\CurrencyHelper;
?>

<div class="container mx-auto px-3 py-5 md:py-8">
    <h1 class="text-2xl md:text-3xl font-bold text-primary mb-4 border-b border-gray-200 pb-3">My Wishlist</h1>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?= htmlspecialchars($_SESSION['flash_message']) ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    
    <?php if (empty($wishlistItems)): ?>
        <div class="bg-white border border-primary/10 shadow-md rounded-2xl p-6 text-center">
            <div class="text-gray-500 mb-4">
                <i class="far fa-heart text-5xl text-gray-300"></i>
            </div>
            <h2 class="text-xl font-semibold mb-2">Your wishlist is empty</h2>
            <p class="text-gray-600 mb-6">Add items to your wishlist to keep track of products you're interested in.</p>
            <a href="<?= \App\Core\View::url('products') ?>" class="inline-block bg-primary text-white px-4 py-2.5 rounded-2xl font-medium hover:bg-primary-dark">
                Browse Products
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3 lg:gap-4">
            <?php foreach ($wishlistItems as $item): ?>
                <?php 
                // Skip items missing critical fields
                if (!isset($item['product']) || !isset($item['product']['id'])) {
                    continue;
                }
                
                $product = $item['product'];
                $currentPrice = $product['sale_price'] ?? $product['price'] ?? 0;
                $originalPrice = $product['price'] ?? 0;
                $discountPercent = 0;
                
                if (isset($product['sale_price']) && $product['sale_price'] && $product['sale_price'] < $originalPrice) {
                    $discountPercent = round((($originalPrice - $currentPrice) / $originalPrice) * 100);
                }
                
                $reviewStats = $product['review_stats'] ?? [];
                $avgRating = isset($reviewStats['average_rating']) ? round($reviewStats['average_rating'], 1) : 0;
                $reviewCount = $reviewStats['total_reviews'] ?? 0;
                $filledStars = floor($avgRating);
                $halfStar = ($avgRating - $filledStars) >= 0.5;
                ?>
                <div id="wishlist-item-<?= $item['id'] ?>" class="bg-white border border-neutral-200 rounded-2xl shadow-sm overflow-hidden hover:shadow-md group h-full wishlist-card">
                    <div class="relative">
                        <a href="<?= \App\Core\View::url('products/view/' . ($product['slug'] ?? $product['id'])) ?>" class="block">
                            <div class="relative aspect-square overflow-hidden bg-gray-50 p-2">
                                <img src="<?= htmlspecialchars($product['image_url'] ?? \App\Core\View::asset('images/products/default.jpg')) ?>" 
                                     alt="<?= htmlspecialchars($product['product_name'] ?? 'Product') ?>" 
                                     class="w-full h-full object-contain"
                                     loading="lazy">
                            </div>
                        </a>
                        
                        <!-- Remove from Wishlist Button -->
                        <button onclick="removeFromWishlist(<?= $item['id'] ?>)" 
                                class="absolute top-2 right-2 p-1.5 bg-white/80 hover:bg-white rounded-full shadow-sm wishlist-remove-btn">
                            <svg class="w-4 h-4 text-error" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>
                        
                        
                        <!-- Stock Badge -->
                        <?php if (isset($product['stock_quantity'])): ?>
                            <div class="absolute bottom-3 left-3">
                                <?php if ($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0): ?>
                                    <span class="bg-warning text-white px-2 py-1 rounded-lg text-xs font-semibold">
                                        LOW STOCK
                                    </span>
                                <?php elseif ($product['stock_quantity'] <= 0): ?>
                                    <span class="bg-error text-white px-2 py-1 rounded-lg text-xs font-semibold">
                                        OUT OF STOCK
                                    </span>
                                <?php else: ?>
                                    <span class="bg-success text-white px-2 py-1 rounded-lg text-xs font-semibold">
                                        IN STOCK
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-2 space-y-2 card-content">
                        <!-- Category -->
                        <div class="text-xs text-neutral-600 font-medium mb-1">
                            <?= htmlspecialchars($product['category'] ?? 'Supplement') ?>
                        </div>
                        
                        <!-- Product Name -->
                        <a href="<?= \App\Core\View::url('products/view/' . ($product['slug'] ?? $product['id'])) ?>" class="block">
                            <h3 class="text-sm font-semibold text-foreground mb-2 leading-tight hover:text-primary min-h-[1.5rem] flex items-start product-name">
                                <?= htmlspecialchars($product['product_name'] ?? 'Unknown Product') ?>
                            </h3>
                        </a>
                        
                        <!-- Rating -->
                        <div class="flex items-center mb-2">
                            <div class="flex text-warning">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <?php if ($i < $filledStars): ?>
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    <?php elseif ($halfStar && $i === $filledStars): ?>
                                        <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-3 h-3 text-neutral-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="text-xs text-neutral-600 ml-2">
                                <?= $avgRating ?> · <?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>
                            </span>
                        </div>
                        
                        <!-- Price -->
                        <div class="mb-2">
                            <span class="text-base font-bold text-primary">
                                रु<?= number_format($currentPrice, 0) ?>
                            </span>
                            <?php if ($discountPercent > 0): ?>
                                <span class="text-xs text-neutral-500 line-through ml-2">
                                    रु<?= number_format($originalPrice, 0) ?>
                                </span>
                                <span class="text-xs text-success ml-1">-<?= $discountPercent ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 text-center">
            <a href="<?= \App\Core\View::url('products') ?>" class="inline-block border border-primary text-primary bg-transparent px-4 py-2.5 rounded-2xl font-medium hover:bg-primary/10">
                Continue Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(wishlistId) {
    if (!confirm('Are you sure you want to remove this item from your wishlist?')) {
        return;
    }
    
    const item = document.getElementById(`wishlist-item-${wishlistId}`);
    const removeBtn = item.querySelector('.wishlist-remove-btn');
    
    // Show loading state
    removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-red-500"></i>';
    removeBtn.disabled = true;
    
    fetch('<?= ASSETS_URL ?>/wishlist/remove/' + wishlistId, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            // Remove item immediately
            item.remove();
            
            // Check if wishlist is empty
            const remainingItems = document.querySelectorAll('[id^="wishlist-item-"]');
            if (remainingItems.length === 0) {
                location.reload(); // Reload to show empty state
            }
        } else if (data && data.error) {
            alert(data.error);
            // Reset button state
            removeBtn.innerHTML = '<i class="fas fa-heart text-red-500"></i>';
            removeBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove item from wishlist');
        // Reset button state
        removeBtn.innerHTML = '<i class="fas fa-heart text-red-500"></i>';
        removeBtn.disabled = false;
    });
}
</script>

<style>
/* Truncate long product names with ellipsis */
.product-name {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Uniform card sizes and tighter mobile layout */
.wishlist-card {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.wishlist-card .card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* Reduce gaps on small screens */
@media (max-width: 640px) {
    .container .grid { gap: 0.5rem; }
}
</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>