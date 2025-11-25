<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="<?= \App\Core\View::url('orders/track') ?>" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Order Tracking
            </a>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Order Tracking</h1>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            Order #<?= htmlspecialchars($order['invoice']) ?>
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Placed on <?= date('F j, Y', strtotime($order['created_at'])) ?>
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            <?php
                            switch (strtolower($order['status'])) {
                                case 'paid':
                                case 'delivered':
                                case 'shipped':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'processing':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'pending':
                                case 'unpaid':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'cancelled':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?= ucfirst(htmlspecialchars($order['status'])) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Status Timeline</h3>
                    
                    <div class="relative">
                        <div class="absolute left-5 top-0 h-full w-0.5 bg-gray-200"></div>
                        
                        <!-- Order Placed -->
                        <div class="relative flex items-start mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600 z-10">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-base font-medium text-gray-900">Order Placed</h4>
                                <p class="text-sm text-gray-500"><?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></p>
                                <p class="text-sm text-gray-600 mt-1">Your order has been received and is being processed.</p>
                            </div>
                        </div>
                        
                        <!-- Payment Status -->
                        <?php if (in_array(strtolower($order['status']), ['paid', 'processing', 'shipped', 'delivered'])): ?>
                            <div class="relative flex items-start mb-6">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600 z-10">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">Payment Confirmed</h4>
                                    <p class="text-sm text-gray-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-gray-600 mt-1">Your payment has been confirmed and your order is being prepared.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Processing Status -->
                        <?php if (in_array(strtolower($order['status']), ['processing', 'shipped', 'delivered'])): ?>
                            <div class="relative flex items-start mb-6">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 z-10">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">Order Processing</h4>
                                    <p class="text-sm text-gray-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-gray-600 mt-1">Your order is being prepared for shipment.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Shipping Status -->
                        <?php if (in_array(strtolower($order['status']), ['shipped', 'delivered'])): ?>
                            <div class="relative flex items-start mb-6">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 z-10">
                                    <i class="fas fa-shipping-fast"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">Order Shipped</h4>
                                    <p class="text-sm text-gray-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-gray-600 mt-1">Your order has been shipped and is on its way.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Delivered Status -->
                        <?php if (strtolower($order['status']) === 'delivered'): ?>
                            <div class="relative flex items-start mb-6">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600 z-10">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">Order Delivered</h4>
                                    <p class="text-sm text-gray-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-gray-600 mt-1">Your order has been successfully delivered.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Cancelled Status -->
                        <?php if (strtolower($order['status']) === 'cancelled'): ?>
                            <div class="relative flex items-start">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600 z-10">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">Order Cancelled</h4>
                                    <p class="text-sm text-gray-500"><?= date('F j, Y, g:i a', strtotime($order['updated_at'])) ?></p>
                                    <p class="text-sm text-gray-600 mt-1">Your order has been cancelled.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pending Payment -->
                        <?php if (in_array(strtolower($order['status']), ['pending', 'unpaid'])): ?>
                            <div class="relative flex items-start">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 z-10">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-base font-medium text-gray-900">Awaiting Payment</h4>
                                    <p class="text-sm text-gray-500">Pending</p>
                                    <p class="text-sm text-gray-600 mt-1">We are waiting for your payment to be confirmed.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Shipping Address</h3>
                        <div class="text-sm text-gray-600 bg-gray-50 p-4 rounded-lg">
                            <p class="font-medium"><?= htmlspecialchars($order['customer_name']) ?></p>
                            <p class="mt-1"><?= nl2br(htmlspecialchars($order['address'])) ?></p>
                            <?php if (!empty($order['contact_no'])): ?>
                                <p class="mt-1">Phone: <?= htmlspecialchars($order['contact_no']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Order Summary</h3>
                        <div class="text-sm text-gray-600 bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between mb-2">
                                <span>Subtotal:</span>
                                <span>Rs<?= number_format($order['total_amount'] - $order['delivery_fee'], 2) ?></span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span>Shipping:</span>
                                <span>Rs<?= number_format($order['delivery_fee'], 2) ?></span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between font-medium text-gray-900">
                                    <span>Total:</span>
                                    <span>Rs<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($order['payment_method'])): ?>
                                <div class="mt-2 pt-2 border-t">
                                    <div class="flex justify-between text-xs">
                                        <span>Payment Method:</span>
                                        <span><?= htmlspecialchars($order['payment_method']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Order Items</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantity
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($orderItems)): ?>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-box text-gray-400"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    SKU: <?= htmlspecialchars($item['product_id']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">Rs<?= number_format($item['price'], 2) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($item['quantity']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">Rs<?= number_format($item['total'], 2) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
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
            <div class="bg-blue-50 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Need Help?</h3>
                <p class="text-gray-600 mb-4">
                    If you have any questions about your order, our support team is here to help.
                </p>
                <a href="<?= \App\Core\View::url('contact') ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-headset mr-2"></i>
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
