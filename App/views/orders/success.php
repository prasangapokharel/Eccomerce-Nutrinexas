<?php ob_start(); ?>
<?php
// Get main image URL function (enhanced with debugging)
function getProductImageUrl($product) {
    $mainImageUrl = '';
    if (!empty($product['images'])) {
        // Use primary image or first image
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
        // Debugging: Log the selected image URL
        error_log("Primary image URL for product ID {$product['id']}: " . $mainImageUrl);
    } else {
        // Fallback to old image field
        $image = $product['image'] ?? '';
        $mainImageUrl = filter_var($image, FILTER_VALIDATE_URL) 
            ? $image 
            : ($image ? \App\Core\View::asset('uploads/images/' . $image) : \App\Core\View::asset('images/products/default.jpg'));
        error_log("Fallback image URL for product ID {$product['id']}: " . $mainImageUrl);
    }
    return $mainImageUrl;
}

// Check if we have order data
if (!isset($order) || !$order) {
    // Try to get from data array if it exists
    $order = $data['order'] ?? null;
    $orderItems = $data['orderItems'] ?? [];
} else {
    // Extract order items from the order data
    $orderItems = $order['items'] ?? [];
}

// If still no order data, redirect to home
if (!$order) {
    header('Location: ' . \App\Core\View::url(''));
    exit;
}
?>
<div class="container mx-auto px-4 py-8 md:py-12 min-h-screen flex items-center justify-center bg-gray-50">
    <div class="bg-white rounded-lg shadow-lg p-6 md:p-8 max-w-3xl w-full">
        <!-- Success Header -->
        <div class="flex flex-col items-center mb-6 md:mb-8">
            <!-- Success Animation -->
            <div class="rounded-full bg-green-100 p-3 mb-4 animate-pulse">
                <svg class="w-12 h-12 md:w-16 md:h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-2xl md:text-4xl font-bold text-gray-900 text-center mb-2 md:mb-3">Thank You!</h1>
            <p class="text-gray-600 text-base md:text-lg text-center max-w-lg">Your order has been placed successfully. We'll send a confirmation email shortly.</p>
            <div class="mt-3 text-sm text-gray-700 bg-gray-50 px-3 py-2 rounded-md">
                <span class="font-medium">Order ID:</span> #<?= htmlspecialchars($order['invoice'] ?? $order['id'] ?? 'N/A') ?>
            </div>
        </div>

        <!-- Order Details -->
        <div class="mb-6 md:mb-8">
            <h2 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m-9 8h10l1 12H4L5 9z"></path>
                </svg>
                Order Summary
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 bg-gray-50 p-4 md:p-6 rounded-lg">
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Order Number</p>
                        <p class="text-gray-900 font-medium break-all">#<?= htmlspecialchars($order['invoice'] ?? $order['id'] ?? 'N/A') ?></p>
                    </div>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Order Date</p>
                        <p class="text-gray-900 font-medium"><?= date('F j, Y', strtotime($order['created_at'] ?? 'now')) ?></p>
                    </div>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Payment Method</p>
                        <p class="text-gray-900 font-medium">
                            <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total Amount</p>
                        <p class="text-gray-900 font-semibold">Rs<?= number_format($order['final_amount'] ?? $order['total_amount'] ?? 0, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="mb-6 md:mb-8">
            <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-3 md:mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                Order Items
            </h3>
            <div class="space-y-4 bg-white border border-gray-100 rounded-lg overflow-hidden">
                <?php if (!empty($orderItems)): ?>
                    <?php foreach ($orderItems as $index => $item): ?>
                        <div class="flex justify-between items-center p-4 <?= $index !== count($orderItems) - 1 ? 'border-b border-gray-100' : '' ?>">
                            <div class="flex items-center">
                                <!-- Product Image -->
                                <div class="w-16 h-16 rounded-lg overflow-hidden bg-gray-100 mr-4 flex-shrink-0">
                                    <?php 
                                    $imageUrl = !empty($item['image']) 
                                        ? (filter_var($item['image'], FILTER_VALIDATE_URL) ? htmlspecialchars($item['image']) : ASSETS_URL . '/uploads/images/' . htmlspecialchars($item['image']))
                                        : ASSETS_URL . '/images/products/default.jpg';
                                    ?>
                                    <img src="<?= $imageUrl ?>" 
                                         alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>" 
                                         class="w-full h-full object-cover"
                                         onerror="this.onerror=null; this.src='<?= ASSETS_URL ?>/images/products/default.jpg';">
                                </div>
                                <div>
                                    <p class="text-gray-900 font-medium"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></p>
                                    <p class="text-gray-500 text-sm">Quantity: <?= $item['quantity'] ?></p>
                                    <p class="text-gray-500 text-sm md:hidden mt-1">Rs<?= number_format($item['total'] ?? $item['price'] * $item['quantity'], 2) ?></p>
                                </div>
                            </div>
                            <p class="text-gray-900 font-medium hidden md:block">Rs<?= number_format($item['total'] ?? $item['price'] * $item['quantity'], 2) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-gray-500">No order items found</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estimated Delivery -->
        <?php
            $expectedDate = null;
            if (!empty($order['expected_delivery_date'])) {
                $expectedDate = date('F j, Y', strtotime($order['expected_delivery_date']));
            } elseif (!empty($order['created_at'])) {
                $expectedDate = date('F j, Y', strtotime($order['created_at'] . ' +3 days'));
            }
        ?>
        <div class="mb-6 md:mb-8 bg-blue-50 p-4 rounded-lg flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"></path>
                <path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="1"></path>
            </svg>
            <div>
                <p class="text-blue-800 font-medium">Estimated Delivery</p>
                <?php if ($expectedDate): ?>
                    <p class="text-blue-600 text-sm">Expected delivery by <span class="font-semibold"><?= $expectedDate ?></span></p>
                <?php else: ?>
                    <p class="text-blue-600 text-sm">Your order will be delivered within 3-5 business days</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4 mt-6 md:mt-8">
            <a href="<?= URLROOT ?>/products" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 font-medium py-3 px-6 rounded-lg transition duration-300 flex items-center justify-center">
                Continue Shopping
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </a>
            <a href="<?= URLROOT ?>/orders" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition duration-300 flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                View Orders
            </a>
        </div>
    </div>
</div>

<script>
    // Add confetti effect on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Simple confetti effect
        const confettiColors = ['#34D399', '#3B82F6', '#F59E0B', '#EC4899'];
        const confettiContainer = document.createElement('div');
        confettiContainer.style.position = 'fixed';
        confettiContainer.style.top = '0';
        confettiContainer.style.left = '0';
        confettiContainer.style.width = '100%';
        confettiContainer.style.height = '100%';
        confettiContainer.style.pointerEvents = 'none';
        confettiContainer.style.zIndex = '1000';
        document.body.appendChild(confettiContainer);
        
        // Create confetti pieces
        for (let i = 0; i < 100; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.style.position = 'absolute';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.backgroundColor = confettiColors[Math.floor(Math.random() * confettiColors.length)];
                confetti.style.borderRadius = '50%';
                confetti.style.opacity = Math.random() * 0.7 + 0.3;
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-20px';
                
                const animationDuration = Math.random() * 3 + 2;
                confetti.style.animation = `fall ${animationDuration}s linear forwards`;
                
                confettiContainer.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, animationDuration * 1000);
            }, i * 50);
        }
        
        // Add keyframe animation
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes fall {
                0% {
                    transform: translateY(-20px) rotate(0deg);
                }
                100% {
                    transform: translateY(100vh) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);
        
        // Remove confetti container after animation
        setTimeout(() => {
            confettiContainer.remove();
            style.remove();
        }, 6000);
    });
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
