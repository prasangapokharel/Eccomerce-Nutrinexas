<?php
use App\Helpers\CurrencyHelper;

// Get active coupons
$couponModel = new \App\Models\Coupon();
$coupons = $couponModel->getPublicCoupons();
?>

<!-- Coupon Section -->
<section class="bg-white mx-2 rounded-xl mb-4">
    <div class="p-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <img src="https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/Live%20static/claimtitle.png" alt="Coupon Background" class="h-8 w-8"> Exclusive Coupons</h3>
        
    
        <?php if (!empty($coupons)): ?>
            <!-- Horizontal Coupon Cards -->
            <div class="flex space-x-3 overflow-x-auto py-2 coupon-marquee animate-marquee" id="couponMarquee">
                <?php foreach ($coupons as $coupon): ?>
                    <?php
                    $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) <= time();
                    $isActive = $coupon['is_active'] && !$isExpired;
                    $discountText = $coupon['discount_type'] === 'percentage' 
                        ? $coupon['discount_value'] . '%' 
                        : 'रु. ' . number_format($coupon['discount_value']);
                    ?>
                    
                    <div class="flex-shrink-0 w-48 h-16 relative coupon-ticket">
                        <!-- Coupon Background -->
                        <div class="absolute inset-0">
                            <img src="https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/Live%20static/coupounbg.gif" 
                                 alt="Coupon Background" 
                                 class="w-full h-full object-cover">
                        </div>
                        
                        <!-- Coupon Content -->
                        <div class="relative z-10 h-full flex items-center justify-between px-4 text-white">
                            <!-- Left Side - Discount -->
                            <div class="text-center">
                                <div class="text-lg font-bold"><?= $discountText ?></div>
                                <div class="text-xs opacity-90">OFF</div>
                            </div>
                            
                            <!-- Center - Code -->
                            <div class="text-center">
                                <div class="text-sm font-bold"><?= htmlspecialchars($coupon['code']) ?></div>
                                <div class="text-xs opacity-75">CODE</div>
                            </div>
                            
                            <!-- Right Side - Claim Button -->
                            <div class="flex items-center">
                                <?php if ($isActive && !$isExpired): ?>
                                    <button onclick="claimCoupon('<?= htmlspecialchars($coupon['code']) ?>', this)"
                                            class="bg-white text-red-600 px-3 py-1 rounded text-xs font-bold claim-btn"
                                            data-code="<?= htmlspecialchars($coupon['code']) ?>">
                                        <span class="claim-text">CLAIM</span>
                                        <span class="claimed-text hidden">COPIED!</span>
                                    </button>
                                <?php else: ?>
                                    <button disabled class="bg-gray-300 text-gray-500 px-3 py-1 rounded text-xs font-bold cursor-not-allowed">
                                        <?= $isExpired ? 'EXPIRED' : 'INACTIVE' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Duplicate Set for Seamless Marquee -->
                <?php foreach ($coupons as $coupon): ?>
                    <?php
                    $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) <= time();
                    $isActive = $coupon['is_active'] && !$isExpired;
                    $discountText = $coupon['discount_type'] === 'percentage' 
                        ? $coupon['discount_value'] . '%' 
                        : 'रु. ' . number_format($coupon['discount_value']);
                    ?>
                    
                    <div class="flex-shrink-0 w-48 h-16 relative coupon-ticket">
                        <!-- Coupon Background -->
                        <div class="absolute inset-0">
                            <img src="https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/Live%20static/coupounbg.gif" 
                                 alt="Coupon Background" 
                                 class="w-full h-full object-cover">
                        </div>
                        
                        <!-- Coupon Content -->
                        <div class="relative z-10 h-full flex items-center justify-between px-4 text-white">
                            <!-- Left Side - Discount -->
                            <div class="text-center">
                                <div class="text-lg font-bold"><?= $discountText ?></div>
                                <div class="text-xs opacity-90">OFF</div>
                            </div>
                            
                            <!-- Center - Code -->
                            <div class="text-center">
                                <div class="text-sm font-bold"><?= htmlspecialchars($coupon['code']) ?></div>
                                <div class="text-xs opacity-75">CODE</div>
                            </div>
                            
                            <!-- Right Side - Claim Button -->
                            <div class="flex items-center">
                                <?php if ($isActive && !$isExpired): ?>
                                    <button onclick="claimCoupon('<?= htmlspecialchars($coupon['code']) ?>', this)"
                                            class="bg-white text-red-600 px-3 py-1 rounded text-xs font-bold claim-btn"
                                            data-code="<?= htmlspecialchars($coupon['code']) ?>">
                                        <span class="claim-text">CLAIM</span>
                                        <span class="claimed-text hidden">COPIED!</span>
                                    </button>
                                <?php else: ?>
                                    <button disabled class="bg-gray-300 text-gray-500 px-3 py-1 rounded text-xs font-bold cursor-not-allowed">
                                        <?= $isExpired ? 'EXPIRED' : 'INACTIVE' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            </div>
        <?php else: ?>
            <!-- No Coupons Available -->
            <div class="text-center py-8">
                <div class="text-gray-500 mb-4">
                    <i class="fas fa-ticket-alt text-4xl text-gray-300"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">No coupons available</h3>
                <p class="text-gray-600">Check back later for exclusive offers!</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Hide scrollbar for coupon marquee */
.coupon-marquee::-webkit-scrollbar {
    display: none;
}

.coupon-marquee {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Marquee Animation */
@keyframes marquee {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.animate-marquee {
    animation: marquee 20s linear infinite;
}

.animate-marquee:hover {
    animation-play-state: paused;
}

/* Coupon ticket styling */
.coupon-ticket {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Claim button - clean design */
.claim-btn {
    position: relative;
    border: none;
    outline: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.claim-btn:hover {
    background-color: #f8f9fa !important;
}

.claim-btn:focus {
    outline: none;
    box-shadow: none;
}

/* Claimed state - simple color change */
.claim-btn.claimed {
    background-color: #10b981 !important;
    color: white !important;
}

/* Text transition - simple show/hide */
.claim-text, .claimed-text {
    transition: opacity 0.2s ease;
}

.claim-btn.claimed .claim-text {
    opacity: 0;
}

.claim-btn.claimed .claimed-text {
    opacity: 1;
}
</style>

<script>
// Make sure function is globally accessible
window.claimCoupon = function(code, button) {
    console.log('=== CLAIM COUPON FUNCTION CALLED ===');
    console.log('Code:', code);
    console.log('Button:', button);
    
    // Test alert to confirm function is called
    alert('Copying code: ' + code);
    
    // Prevent multiple clicks
    if (button.classList.contains('claimed')) {
        console.log('Button already claimed, ignoring click');
        return;
    }
    
    // Function to update button state
    function updateButtonState() {
        button.classList.add('claimed');
        
        const claimText = button.querySelector('.claim-text');
        const claimedText = button.querySelector('.claimed-text');
        
        if (claimText && claimedText) {
            claimText.style.display = 'none';
            claimedText.style.display = 'inline';
        }
        
        // Reset after 3 seconds
        setTimeout(function() {
            button.classList.remove('claimed');
            if (claimText && claimedText) {
                claimText.style.display = 'inline';
                claimedText.style.display = 'none';
            }
        }, 3000);
    }
    
    // Try modern clipboard API first
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(function() {
            console.log('Successfully copied to clipboard:', code);
            updateButtonState();
            showToast('Coupon code copied: ' + code, 'success');
        }).catch(function(err) {
            console.error('Clipboard API failed:', err);
            // Fall back to legacy method
            copyWithLegacyMethod(code, button);
        });
    } else {
        // Use legacy method for non-HTTPS or older browsers
        copyWithLegacyMethod(code, button);
    }
}

function copyWithLegacyMethod(code, button) {
    try {
        // Create a temporary textarea
        const textArea = document.createElement('textarea');
        textArea.value = code;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        
        // Select and copy
        textArea.focus();
        textArea.select();
        textArea.setSelectionRange(0, 99999); // For mobile devices
        
        const successful = document.execCommand('copy');
        document.body.removeChild(textArea);
        
        if (successful) {
            console.log('Successfully copied with legacy method:', code);
            
            // Update button state
            button.classList.add('claimed');
            
            const claimText = button.querySelector('.claim-text');
            const claimedText = button.querySelector('.claimed-text');
            
            if (claimText && claimedText) {
                claimText.style.display = 'none';
                claimedText.style.display = 'inline';
            }
            
            // Reset after 3 seconds
            setTimeout(function() {
                button.classList.remove('claimed');
                if (claimText && claimedText) {
                    claimText.style.display = 'inline';
                    claimedText.style.display = 'none';
                }
            }, 3000);
            
            showToast('Coupon code copied: ' + code, 'success');
        } else {
            throw new Error('execCommand failed');
        }
        
    } catch (err) {
        console.error('Legacy copy failed:', err);
        showToast('Failed to copy. Code: ' + code, 'error');
    }
}

// Marquee is handled by CSS animation

// Toast notification function
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    }`;
    toast.textContent = message;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}
</script>
