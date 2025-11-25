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
            <a href="<?= \App\Core\View::url('products') ?>" class="inline-block bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary-dark transition-colors font-medium">
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
                <div id="wishlist-item-<?= $item['id'] ?>" class="bg-white border border-primary/10 rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition-all duration-300 group h-full wishlist-card">
                    <div class="relative">
                        <a href="<?= \App\Core\View::url('products/view/' . ($product['slug'] ?? $product['id'])) ?>" class="block">
                            <div class="relative aspect-square overflow-hidden bg-gray-50 p-2">
                                <img src="<?= htmlspecialchars($product['image_url'] ?? \App\Core\View::asset('images/products/default.jpg')) ?>" 
                                     alt="<?= htmlspecialchars($product['product_name'] ?? 'Product') ?>" 
                                     class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy">
                            </div>
                        </a>
                        
                        <!-- Remove from Wishlist Button -->
                        <button onclick="removeFromWishlist(<?= $item['id'] ?>)" 
                                class="absolute top-3 right-3 p-2 bg-white/90 hover:bg-white rounded-full shadow-md transition-all duration-200 wishlist-remove-btn">
                            <i class="fas fa-heart text-red-500 hover:text-red-600"></i>
                        </button>
                        
                        
                        <!-- Stock Badge -->
                        <?php if (isset($product['stock_quantity'])): ?>
                            <div class="absolute bottom-3 left-3">
                                <?php if ($product['stock_quantity'] < 10 && $product['stock_quantity'] > 0): ?>
                                    <span class="bg-yellow-500 text-white px-2 py-1 rounded text-xs font-bold">
                                        LOW STOCK
                                    </span>
                                <?php elseif ($product['stock_quantity'] <= 0): ?>
                                    <span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">
                                        OUT OF STOCK
                                    </span>
                                <?php else: ?>
                                    <span class="bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">
                                        IN STOCK
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-2 space-y-2 card-content">
                        <!-- Category -->
                        <div class="text-xs text-accent font-medium mb-1">
                            <?= htmlspecialchars($product['category'] ?? 'Supplement') ?>
                        </div>
                        
                        <!-- Product Name -->
                        <a href="<?= \App\Core\View::url('products/view/' . ($product['slug'] ?? $product['id'])) ?>" class="block">
                            <h3 class="text-sm font-semibold text-primary mb-2 leading-tight group-hover:text-blue-600 transition-colors min-h-[1.5rem] flex items-start product-name">
                                <?= htmlspecialchars($product['product_name'] ?? 'Unknown Product') ?>
                            </h3>
                        </a>
                        
                        <!-- Rating -->
                        <div class="flex items-center mb-2">
                            <div class="flex text-accent">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <?php if ($i < $filledStars): ?>
                                        <i class="fas fa-star text-accent text-xs"></i>
                                    <?php elseif ($halfStar && $i === $filledStars): ?>
                                        <i class="fas fa-star-half-alt text-accent text-xs"></i>
                                    <?php else: ?>
                                        <i class="fas fa-star text-gray-300 text-xs"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="text-xs text-gray-600 ml-2">
                                <?= $avgRating ?> · <?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>
                            </span>
                        </div>
                        
                        <!-- Price -->
                        <div class="mb-2">
                            <span class="text-base font-bold text-primary">
                                रु<?= number_format($currentPrice, 0) ?>
                            </span>
                            <?php if ($discountPercent > 0): ?>
                                <span class="text-xs text-gray-500 line-through ml-2">
                                    रु<?= number_format($originalPrice, 0) ?>
                                </span>
                                <span class="text-xs text-red-600 ml-1">-<?= $discountPercent ?>%</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-xs text-gray-500">
                            Tap the product to view more details or add to cart.
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 text-center">
            <a href="<?= \App\Core\View::url('products') ?>" class="inline-block border border-primary text-primary px-6 py-3 rounded-lg hover:bg-primary hover:text-white transition-colors font-medium">
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
            // Animate item removal
            item.style.opacity = '0';
            item.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                item.remove();
                
                // Check if wishlist is empty
                const remainingItems = document.querySelectorAll('[id^="wishlist-item-"]');
                if (remainingItems.length === 0) {
                    location.reload(); // Reload to show empty state
                }
            }, 300);
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