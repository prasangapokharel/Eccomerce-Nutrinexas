<?php
ob_start();
use App\Helpers\CurrencyHelper;
?>

<div class="min-h-screen bg-neutral-50 py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-3">Available Coupons</h1>
            <p class="text-neutral-600 max-w-2xl mx-auto">
                Unlock amazing deals on premium supplements! Copy the code and save on your next purchase.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="mb-8 max-w-2xl mx-auto">
            <div class="relative">
                <input
                    type="text"
                    id="couponSearch"
                    placeholder="Search coupons by code or description..."
                    class="input pl-12"
                >
                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <?php if (empty($coupons)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-neutral-100 p-12 text-center max-w-lg mx-auto">
            <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-mountain mb-2">No Coupons Available</h2>
            <p class="text-neutral-600 mb-6">New exclusive deals coming soon. Check back later!</p>
            <a href="<?= \App\Core\View::url('products') ?>" class="btn btn-primary inline-flex px-6 py-3">Browse Products</a>
        </div>
        <?php else: ?>
        <!-- Coupons Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12" id="couponsGrid">
            <?php foreach ($coupons as $coupon): ?>
                <?php
                $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) <= time();
                $isActive = $coupon['is_active'] && !$isExpired;
                $discountText = $coupon['discount_type'] === 'percentage' 
                    ? $coupon['discount_value'] . '% OFF' 
                    : CurrencyHelper::format($coupon['discount_value']) . ' OFF';
                ?>
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden coupon-card" 
                     data-code="<?= strtolower(htmlspecialchars($coupon['code'])) ?>"
                     data-description="<?= strtolower(htmlspecialchars($coupon['description'] ?? '')) ?>">
                    <!-- Coupon Header -->
                    <div class="bg-primary p-6 text-white">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="text-xs font-semibold uppercase tracking-wide mb-2 opacity-90">Coupon Code</div>
                                <h3 class="text-2xl font-bold tracking-wider"><?= htmlspecialchars($coupon['code']) ?></h3>
                            </div>
                            <div class="text-right">
                                <div class="text-3xl font-bold"><?= $discountText ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Coupon Body -->
                    <div class="p-5">
                        <!-- Description -->
                        <?php if (!empty($coupon['description'])): ?>
                            <p class="text-neutral-700 text-sm mb-4"><?= htmlspecialchars($coupon['description']) ?></p>
                        <?php endif; ?>
                        
                        <!-- Conditions -->
                        <div class="space-y-2 mb-4">
                            <?php if ($coupon['min_order_amount']): ?>
                                <div class="flex items-center text-sm text-neutral-600 bg-neutral-50 px-3 py-2 rounded-lg">
                                    <span class="font-medium">Min order: <?= CurrencyHelper::format($coupon['min_order_amount']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($coupon['usage_limit_per_user']): ?>
                                <div class="flex items-center text-sm text-neutral-600 bg-neutral-50 px-3 py-2 rounded-lg">
                                    <span class="font-medium">Limit: <?= $coupon['usage_limit_per_user'] ?> per user</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($coupon['expires_at']): ?>
                                <div class="flex items-center text-sm text-neutral-600 bg-neutral-50 px-3 py-2 rounded-lg">
                                    <span class="font-medium">Expires: <?= date('M j, Y', strtotime($coupon['expires_at'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="mb-4">
                            <?php if ($isExpired): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    Expired
                                </span>
                            <?php elseif (!$isActive): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-mountain">
                                    Inactive
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    Active Now
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Copy Button -->
                        <?php if ($isActive && !$isExpired): ?>
                            <button onclick="copyCouponCode('<?= htmlspecialchars($coupon['code']) ?>')" 
                                    class="btn btn-primary w-full py-3">
                                Copy Code
                            </button>
                        <?php else: ?>
                            <button disabled class="w-full rounded-lg border border-neutral-200 bg-neutral-100 px-4 py-3 font-semibold text-neutral-500">
                                <?= $isExpired ? 'Coupon Expired' : 'Not Available' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- How to Use Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-primary mb-3">How to Redeem Your Coupon</h2>
                <p class="text-neutral-600">Follow these simple steps to save on your purchase</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-white font-bold text-xl">1</span>
                    </div>
                    <h3 class="text-lg font-semibold text-mountain mb-2">Copy Code</h3>
                    <p class="text-neutral-600 text-sm">Click the button to copy your coupon code</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-white font-bold text-xl">2</span>
                    </div>
                    <h3 class="text-lg font-semibold text-mountain mb-2">Shop Products</h3>
                    <p class="text-neutral-600 text-sm">Browse and add your favorite supplements to cart</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-white font-bold text-xl">3</span>
                    </div>
                    <h3 class="text-lg font-semibold text-mountain mb-2">Apply & Save</h3>
                    <p class="text-neutral-600 text-sm">Paste code at checkout and enjoy your discount</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyCouponCode(code) {
    const textarea = document.createElement('textarea');
    textarea.value = code;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    
    textarea.select();
    textarea.setSelectionRange(0, 99999);
    
    try {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(code).then(function() {
                showToast('Coupon code copied: ' + code, 'success');
            }).catch(function() {
                const successful = document.execCommand('copy');
                if (successful) {
                    showToast('Coupon code copied: ' + code, 'success');
                }
            });
        } else {
            const successful = document.execCommand('copy');
            if (successful) {
                showToast('Coupon code copied: ' + code, 'success');
            }
        }
    } catch (err) {
        console.error('Copy failed:', err);
    } finally {
        document.body.removeChild(textarea);
    }
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `fixed top-20 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-600' : 'bg-red-600'
    } text-white max-w-sm`;
    toast.innerHTML = `<div class="flex items-center gap-3"><span class="font-medium">${message}</span></div>`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('couponSearch');
    const couponsGrid = document.getElementById('couponsGrid');
    
    if (searchInput && couponsGrid) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const couponCards = couponsGrid.querySelectorAll('.coupon-card');
            
            couponCards.forEach(function(card) {
                const code = card.getAttribute('data-code') || '';
                const description = card.getAttribute('data-description') || '';
                
                if (code.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php 
$content = ob_get_clean(); 
include dirname(dirname(__FILE__)) . '/layouts/main.php'; 
?>
