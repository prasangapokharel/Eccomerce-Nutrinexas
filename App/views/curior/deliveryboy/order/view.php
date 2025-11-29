<?php
$page = 'orders';
ob_start();
?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= \App\Core\View::url('deliveryboy/dashboard') ?>" class="text-primary hover:text-primary-dark flex items-center transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
    </a>
    <span class="px-3 py-1 rounded-full text-sm font-medium
        <?php
        switch ($order['status']) {
            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
            case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
            case 'ready_for_pickup': echo 'bg-purple-100 text-purple-800'; break;
            case 'picked_up': echo 'bg-indigo-100 text-indigo-800'; break;
            case 'in_transit': echo 'bg-orange-100 text-orange-800'; break;
            case 'delivered': echo 'bg-green-100 text-green-800'; break;
            case 'cancelled': echo 'bg-red-100 text-red-800'; break;
            default: echo 'bg-gray-100 text-gray-800'; break;
        }
        ?>
    ">
        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status']))) ?>
    </span>
</div>

<div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8 p-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Order #<?= htmlspecialchars($order['invoice'] ?? $order['id']) ?></h1>
    <p class="text-gray-600 text-sm">Placed on <?= date('M j, Y', strtotime($order['created_at'])) ?> at <?= date('g:i A', strtotime($order['created_at'])) ?></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Order Details -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Details</h2>
        <div class="space-y-3 text-gray-700">
            <div class="flex justify-between">
                <span class="text-gray-600">Subtotal:</span>
                <span class="font-medium">रु <?= number_format($order['order_total'] ?? 0, 2) ?></span>
            </div>
            <?php if (!empty($order['tax_amount']) && $order['tax_amount'] > 0): ?>
                <div class="flex justify-between">
                    <span class="text-gray-600">Tax:</span>
                    <span class="font-medium">रु <?= number_format($order['tax_amount'], 2) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                <div class="flex justify-between">
                    <span class="text-gray-600">Delivery Fee:</span>
                    <span class="font-medium">रु <?= number_format($order['delivery_fee'], 2) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                <div class="flex justify-between text-green-600">
                    <span>Discount:</span>
                    <span class="font-medium">-रु <?= number_format($order['discount_amount'], 2) ?></span>
                </div>
            <?php endif; ?>
            <div class="border-t pt-3 flex justify-between">
                <span class="text-lg font-semibold text-gray-900">Total:</span>
                <span class="text-lg font-bold text-primary">रु <?= number_format($order['total_amount'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Customer Information</h2>
        <div class="space-y-3 text-gray-700">
            <div>
                <span class="text-gray-600 block text-sm">Name:</span>
                <span class="font-medium"><?= htmlspecialchars(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?: $order['order_customer_name'] ?? 'Guest') ?></span>
            </div>
            <?php if (!empty($order['customer_email'])): ?>
                <div>
                    <span class="text-gray-600 block text-sm">Email:</span>
                    <span class="font-medium"><?= htmlspecialchars($order['customer_email']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($order['shipping_phone'])): ?>
                <div>
                    <span class="text-gray-600 block text-sm">Phone:</span>
                    <span class="font-medium"><?= htmlspecialchars($order['shipping_phone']) ?></span>
                </div>
            <?php endif; ?>
            <div>
                <span class="text-gray-600 block text-sm">Address:</span>
                <span class="font-medium">
                    <?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?><br>
                    <?= htmlspecialchars($order['shipping_city'] ?? '') ?>, <?= htmlspecialchars($order['shipping_state'] ?? '') ?><br>
                    <?= htmlspecialchars($order['shipping_postal_code'] ?? '') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Seller Information -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Seller Information</h2>
        <div class="space-y-3 text-gray-700">
            <?php if (!empty($sellerInfo)): ?>
                <div>
                    <span class="text-gray-600 block text-sm">Name:</span>
                    <span class="font-medium"><?= htmlspecialchars($sellerInfo['name'] ?? 'N/A') ?></span>
                </div>
                <?php if (!empty($sellerInfo['company_name'])): ?>
                    <div>
                        <span class="text-gray-600 block text-sm">Company:</span>
                        <span class="font-medium"><?= htmlspecialchars($sellerInfo['company_name']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($sellerInfo['address'])): ?>
                    <div>
                        <span class="text-gray-600 block text-sm">Address:</span>
                        <span class="font-medium">
                            <?= htmlspecialchars($sellerInfo['address']) ?><br>
                            <?= htmlspecialchars($sellerInfo['city'] ?? '') ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($sellerInfo['phone'])): ?>
                    <div>
                        <span class="text-gray-600 block text-sm">Phone:</span>
                        <span class="font-medium"><?= htmlspecialchars($sellerInfo['phone']) ?></span>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-gray-500">Seller information not available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Items -->
<div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($order['items'])): ?>
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if (!empty($item['product_image'])): ?>
                                        <img src="<?= htmlspecialchars($item['product_image']) ?>" 
                                             alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>"
                                             class="w-12 h-12 object-cover rounded-lg mr-3">
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($item['product_name'] ?? 'Product') ?>
                                        </div>
                                        <?php if (!empty($item['variation'])): ?>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($item['variation']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= number_format($item['quantity'] ?? 1) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                रु <?= number_format($item['price'] ?? 0, 2) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                रु <?= number_format($item['total'] ?? 0, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                            No items found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Actions -->
<?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions</h2>
        <div class="flex gap-3">
            <form action="<?= \App\Core\View::url('deliveryboy/deliver/' . $order['id']) ?>" 
                  method="POST" 
                  onsubmit="return confirm('Mark this order as delivered? This action cannot be undone.')">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                <button type="submit" 
                        class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Mark as Delivered
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include ROOT_DIR . '/App/views/curior/layouts/main.php';
?>

