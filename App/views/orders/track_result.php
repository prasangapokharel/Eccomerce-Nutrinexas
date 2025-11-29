<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="<?= \App\Core\View::url('orders/track') ?>" class="inline-flex items-center gap-2 text-primary hover:text-primary-dark font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Order Tracking
            </a>
        </div>
        
        <h1 class="text-2xl font-semibold text-foreground mb-8">Order Tracking</h1>
        
        <?php if (empty($order)): ?>
            <div class="bg-error/10 border border-error/30 rounded-2xl p-6 text-center">
                <svg class="w-12 h-12 text-error mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-xl font-semibold text-error mb-2">Order Not Found</h2>
                <p class="text-neutral-600 mb-4">The order you're looking for doesn't exist or the invoice number is incorrect.</p>
                <a href="<?= \App\Core\View::url('orders/track') ?>" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2.5 rounded-2xl font-medium hover:bg-primary-dark">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Tracking
                </a>
            </div>
        <?php else: ?>
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden mb-8">
            <div class="p-6 border-b border-neutral-200">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-foreground">
                            Order #<?= htmlspecialchars($order['invoice'] ?? 'N/A') ?>
                        </h2>
                        <p class="mt-1 text-sm text-neutral-500">
                            Placed on <?= !empty($order['created_at']) ? date('F j, Y', strtotime($order['created_at'])) : 'N/A' ?>
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            <?php
                            $status = strtolower($order['status'] ?? 'pending');
                            switch ($status) {
                                case 'paid':
                                case 'delivered':
                                case 'shipped':
                                    echo 'bg-success/10 text-success border border-success/30';
                                    break;
                                case 'processing':
                                    echo 'bg-info/10 text-info border border-info/30';
                                    break;
                                case 'pending':
                                case 'unpaid':
                                    echo 'bg-warning/10 text-warning border border-warning/30';
                                    break;
                                case 'cancelled':
                                    echo 'bg-error/10 text-error border border-error/30';
                                    break;
                                default:
                                    echo 'bg-neutral-100 text-neutral-800 border border-neutral-300';
                            }
                            ?>">
                            <?= ucfirst(htmlspecialchars($order['status'] ?? 'Pending')) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-foreground mb-4">Order Status Timeline</h3>
                    
                    <div class="relative order-timeline pl-3">
                        
                        <!-- Order Placed -->
                        <div class="timeline-step relative flex items-start mb-6">
                            <div class="timeline-icon flex items-center justify-center w-10 h-10 rounded-full bg-success/10 text-success z-10 border-2 border-success/30">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-base font-semibold text-foreground">Order Placed</h4>
                                <p class="text-sm text-neutral-500"><?= !empty($order['created_at']) ? date('F j, Y, g:i a', strtotime($order['created_at'])) : 'N/A' ?></p>
                                <p class="text-sm text-neutral-600 mt-1">Your order has been received and is being processed.</p>
                            </div>
                        </div>
                        
                        <!-- Payment Status -->
                        <?php if (in_array(strtolower($order['status'] ?? 'pending'), ['paid', 'processing', 'shipped', 'delivered'])): ?>
                            <div class="timeline-step relative flex items-start mb-6">
                            <div class="timeline-icon flex items-center justify-center w-10 h-10 rounded-full bg-success/10 text-success z-10 border-2 border-success/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-semibold text-foreground">Payment Confirmed</h4>
                                    <p class="text-sm text-neutral-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-neutral-600 mt-1">Your payment has been confirmed and your order is being prepared.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Processing Status -->
                        <?php if (in_array(strtolower($order['status'] ?? 'pending'), ['processing', 'shipped', 'delivered'])): ?>
                            <div class="timeline-step relative flex items-start mb-6">
                            <div class="timeline-icon flex items-center justify-center w-10 h-10 rounded-full bg-info/10 text-info z-10 border-2 border-info/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-semibold text-foreground">Order Processing</h4>
                                    <p class="text-sm text-neutral-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-neutral-600 mt-1">Your order is being prepared for shipment.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Shipping Status -->
                        <?php if (in_array(strtolower($order['status'] ?? 'pending'), ['shipped', 'delivered'])): ?>
                            <div class="timeline-step relative flex items-start mb-6">
                            <div class="timeline-icon flex items-center justify-center w-10 h-10 rounded-full bg-info/10 text-info z-10 border-2 border-info/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-semibold text-foreground">Order Shipped</h4>
                                    <p class="text-sm text-neutral-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-neutral-600 mt-1">Your order has been shipped and is on its way.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Delivered Status -->
                        <?php if (strtolower($order['status'] ?? '') === 'delivered'): ?>
                            <div class="relative flex items-start mb-6">
                            <div class="timeline-icon flex items-center justify-center w-10 h-10 rounded-full bg-success/10 text-success z-10 border-2 border-success/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4-8-4V7m16 0L12 3 4 7"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-semibold text-foreground">Order Delivered</h4>
                                    <p class="text-sm text-neutral-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-neutral-600 mt-1">Your order has been successfully delivered.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Cancelled Status -->
                        <?php if (strtolower($order['status'] ?? '') === 'cancelled'): ?>
                            <div class="timeline-step relative flex items-start">
                            <div class="timeline-icon flex items-center justify-center w-10 h-10 rounded-full bg-error/10 text-error z-10 border-2 border-error/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-semibold text-foreground">Order Cancelled</h4>
                                    <p class="text-sm text-neutral-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-neutral-600 mt-1">Your order has been cancelled.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pending Payment -->
                        <?php if (in_array(strtolower($order['status'] ?? 'pending'), ['pending', 'unpaid'])): ?>
                            <div class="timeline-step relative flex items-start">
                            <div class="timeline-icon flex items-center justify-center w-10 h-10 rounded-full bg-warning/10 text-warning z-10 border-2 border-warning/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-semibold text-foreground">Awaiting Payment</h4>
                                    <p class="text-sm text-neutral-500">Pending</p>
                                    <p class="text-sm text-neutral-600 mt-1">We are waiting for your payment to be confirmed.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground mb-2">Shipping Address</h3>
                            <div class="text-sm text-neutral-600 bg-neutral-50 p-4 rounded-2xl border border-neutral-200">
                            <p class="font-semibold text-foreground"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></p>
                            <p class="mt-1"><?= nl2br(htmlspecialchars($order['address'] ?? 'N/A')) ?></p>
                            <?php if (!empty($order['contact_no'])): ?>
                                <p class="mt-1">Phone: <?= htmlspecialchars($order['contact_no']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-foreground mb-2">Order Summary</h3>
                        <div class="text-sm text-neutral-600 bg-neutral-50 p-4 rounded-2xl border border-neutral-200">
                            <div class="flex justify-between mb-2">
                                <span>Subtotal:</span>
                                <span class="font-medium text-foreground">रु<?= number_format(($order['total_amount'] ?? 0) - ($order['delivery_fee'] ?? 0), 2) ?></span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>Shipping:</span>
                                <span class="font-medium text-foreground">रु<?= number_format($order['delivery_fee'] ?? 0, 2) ?></span>
                            </div>
                            <div class="border-t border-neutral-200 pt-2 mt-2">
                                <div class="flex justify-between font-semibold text-foreground">
                                    <span>Total:</span>
                                    <span>रु<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($order['payment_method'])): ?>
                                <div class="mt-2 pt-2 border-t border-neutral-200">
                                    <div class="flex justify-between text-xs">
                                        <span>Payment Method:</span>
                                        <span class="font-medium"><?= htmlspecialchars($order['payment_method']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
            <div class="p-6 border-b border-neutral-200">
                <h2 class="text-xl font-semibold text-foreground">Order Items</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Quantity
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        <?php if (!empty($orderItems)): ?>
                            <?php foreach ($orderItems as $item): ?>
                                <?php
                                // Get product image
                                $productImage = !empty($item['image_url']) ? $item['image_url'] : (!empty($item['product_image']) ? $item['product_image'] : (!empty($item['image']) ? $item['image'] : \App\Core\View::asset('images/products/default.jpg')));
                                if (!filter_var($productImage, FILTER_VALIDATE_URL) && strpos($productImage, '/') !== 0) {
                                    $productImage = \App\Core\View::asset('uploads/images/' . $productImage);
                                }
                                ?>
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-16 w-16 rounded-lg overflow-hidden border border-neutral-200 bg-neutral-100">
                                                <img src="<?= htmlspecialchars($productImage) ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>"
                                                     class="w-full h-full object-cover"
                                                     onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-foreground">
                                                    <?= htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']) ?>
                                                </div>
                                                <div class="text-sm text-neutral-500">
                                                    Product ID: <?= htmlspecialchars($item['product_id']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-foreground">रु<?= number_format($item['price'], 2) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-foreground">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-800">
                                                <?= htmlspecialchars($item['quantity']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-foreground">रु<?= number_format($item['total'], 2) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-neutral-500">
                                    No items found for this order.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Support Section -->
        <div class="mt-8 text-center">
            <div class="bg-info/10 rounded-2xl p-6 border border-info/20">
                <h3 class="text-lg font-semibold text-foreground mb-2">Need Help?</h3>
                <p class="text-neutral-600 mb-4">
                    If you have any questions about your order, our support team is here to help.
                </p>
                <a href="<?= \App\Core\View::url('contact') ?>" class="inline-flex items-center gap-2 bg-info text-white px-4 py-2.5 rounded-2xl font-medium hover:bg-info-dark">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Contact Support
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.order-timeline {
    position: relative;
}
.order-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 24px;
    width: 2px;
    background: #e5e7eb;
}
.timeline-step {
    position: relative;
    padding-left: 0.75rem;
}
.timeline-step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 24px;
    top: 40px;
    bottom: -10px;
    width: 2px;
    background: #e5e7eb;
}
.timeline-icon {
    position: relative;
    z-index: 10;
    background-color: #fff;
}
@media (max-width: 640px) {
    .order-timeline::before,
    .timeline-step:not(:last-child)::after {
        left: 20px;
    }
}
</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
