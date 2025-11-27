<?php 
ob_start(); 
use App\Helpers\CurrencyHelper;
?>
 

<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Success Header -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-success/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
            <p class="text-gray-600">Thank you for your order. We'll send you a confirmation email shortly.</p>
        </div>

        <!-- Order Details Card -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
            <div class="bg-accent-light px-6 py-4 border-b border-accent">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Order #<?= htmlspecialchars($data['order']['invoice']) ?></h2>
                        <p class="text-sm text-gray-600 mt-1">Placed on <?= date('F j, Y \a\t g:i A', strtotime($data['order']['created_at'])) ?></p>
                    </div>
                    <div class="mt-4 sm:mt-0 flex space-x-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $data['order']['payment_status'] === 'paid' ? 'bg-success/10 text-success-dark' : 'bg-accent/10 text-accent-dark' ?>">
                            <?= $data['order']['payment_status'] === 'paid' ? 'Paid' : ucfirst(htmlspecialchars($data['order']['status'])) ?>
                        </span>
                        <a href="<?= URLROOT ?>/receipt/download/<?= $data['order']['id'] ?>" 
                           class="inline-flex items-center justify-center px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Download
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <!-- Order Items -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Items</h3>
                    <div class="space-y-4">
                        <?php if (!empty($data['order']['items'])): ?>
                            <?php foreach ($data['order']['items'] as $item): ?>
                                                                 <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                     <div class="w-16 h-16 overflow-hidden bg-gray-100 rounded-lg">
                                         <?php 
                                         $imageUrl = '';
                                         if (!empty($item['product_image'])) {
                                             $imageUrl = filter_var($item['product_image'], FILTER_VALIDATE_URL) 
                                                 ? $item['product_image'] 
                                                 : \App\Core\View::asset('uploads/images/' . $item['product_image']);
                                         } else {
                                             $imageUrl = \App\Core\View::asset('images/products/default.jpg');
                                         }
                                         ?>
                                         <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                              alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>"
                                              class="w-16 h-16 object-cover"
                                              onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'; this.onerror=null;">
                                     </div>
                                     <div class="flex-1 min-w-0">
                                         <p class="text-sm font-medium text-gray-900 truncate">
                                             <?= htmlspecialchars($item['product_name'] ?? 'Product') ?>
                                         </p>
                                         <p class="text-sm text-gray-500">
                                             Quantity: <?= htmlspecialchars($item['quantity']) ?> × <?= CurrencyHelper::format($item['price']) ?>
                                         </p>
                                         
                                         <!-- Selected Color and Size -->
                                         <div class="mt-2 flex flex-wrap gap-2">
                                             <?php if (!empty($item['selected_color'])): ?>
                                                 <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-info/10 text-info border border-info">
                                                     <span class="w-2 h-2 rounded-full border border-gray-300 mr-1" style="background-color: <?= preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $item['selected_color']) ? htmlspecialchars($item['selected_color']) : 'transparent' ?>"></span>
                                                     Color: <?= htmlspecialchars($item['selected_color']) ?>
                                                 </span>
                                             <?php endif; ?>
                                             
                                             <?php if (!empty($item['selected_size'])): ?>
                                                 <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-success/10 text-success border border-success">
                                                     Size: <?= htmlspecialchars($item['selected_size']) ?>
                                                 </span>
                                             <?php endif; ?>
                                         </div>
                                         
                                         <?php if (!empty($item['product_category'])): ?>
                                             <p class="text-xs text-gray-400 mt-1">
                                                 Category: <?= htmlspecialchars($item['product_category']) ?>
                                             </p>
                                         <?php endif; ?>
                                     </div>
                                     <div class="text-sm font-medium text-gray-900">
                                         <?= CurrencyHelper::format($item['total']) ?>
                                     </div>
                                 </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500">No items found for this order.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Summary</h3>
                    <div class="space-y-2 text-sm mb-4">
                        <?php
                        // Calculate subtotal from items
                        $subtotal = 0;
                        foreach ($data['order']['items'] ?? [] as $item) {
                            $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                        }
                        $discountAmount = $data['order']['discount_amount'] ?? 0;
                        $taxAmount = $data['order']['tax_amount'] ?? 0;
                        $deliveryFee = $data['order']['delivery_fee'] ?? 0;
                        $calculatedTotal = $subtotal - $discountAmount + $taxAmount + $deliveryFee;
                        ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium text-gray-900"><?= CurrencyHelper::format($subtotal) ?></span>
                        </div>
                        <?php if ($discountAmount > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-success">Item Discount</span>
                                <span class="font-medium text-success">-<?= CurrencyHelper::format($discountAmount) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($taxAmount > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax (<?= number_format((($taxAmount / max(1, $subtotal - $discountAmount)) * 100), 0) ?>%)</span>
                                <span class="font-medium text-gray-900"><?= CurrencyHelper::format($taxAmount) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($deliveryFee > 0): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Delivery Fee</span>
                                <span class="font-medium text-gray-900"><?= CurrencyHelper::format($deliveryFee) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-medium text-gray-900">Total Amount</span>
                            <span class="text-xl font-bold text-primary"><?= CurrencyHelper::format($calculatedTotal) ?></span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-600 mt-3">
                        <span>Payment Method</span>
                        <span><?= htmlspecialchars($data['order']['payment_method'] ?? 'Cash on Delivery') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Shipping Information</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Delivery Address</h4>
                        <div class="text-sm text-gray-600">
                            <p class="font-medium"><?= htmlspecialchars($data['order']['customer_name']) ?></p>
                            <p><?= htmlspecialchars($data['order']['contact_no']) ?></p>
                            <p><?= htmlspecialchars($data['order']['address']) ?></p>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Estimated Delivery</h4>
                        <p class="text-sm text-gray-600">3-5 business days</p>
                        <p class="text-xs text-gray-500 mt-1">You'll receive tracking information via email</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= URLROOT ?>/orders" 
               class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-2xl font-semibold text-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                View All Orders
            </a>
            <a href="<?= URLROOT ?>/products" 
               class="inline-flex items-center justify-center px-6 py-3 bg-accent text-white rounded-2xl font-semibold text-sm hover:bg-accent-dark transition-colors shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                Continue Shopping
            </a>
        </div>

        <!-- Help Section -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-600">
                Need help with your order? 
                <a href="<?= URLROOT ?>/contact" class="text-primary hover:text-primary-dark font-medium">Contact our support team</a>
            </p>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
