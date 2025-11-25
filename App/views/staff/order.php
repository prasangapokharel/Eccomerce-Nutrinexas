<?php ob_start(); ?>

<div class="min-h-screen bg-gray-50">
    <div class="px-3 py-3">
        <!-- Flash Messages -->
        <?php 
        $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
        if ($flashMessage): ?>
            <div class="bg-green-50 border-l-4 border-green-400 text-green-700 px-3 py-2 rounded text-sm mb-3">
                <?= $flashMessage ?>
            </div>
        <?php endif; ?>

        <?php 
        $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
        if ($flashError): ?>
            <div class="bg-red-50 border-l-4 border-red-400 text-red-700 px-3 py-2 rounded text-sm mb-3">
                <?= $flashError ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border-0 mb-4">
            <div class="px-4 py-3 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <a href="<?= \App\Core\View::url('staff/dashboard') ?>" class="text-gray-500 hover:text-gray-700 mr-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="text-lg font-semibold text-gray-900">Order Details</h1>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">
                            <i class="fas fa-clock mr-1"></i>
                            <?= date('M d, Y - H:i') ?>
                        </span>
                        <a href="<?= \App\Core\View::url('staff/logout') ?>" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($order) && $order): ?>
            <!-- Order Information -->
            <div class="bg-white rounded-lg shadow-sm border-0 mb-4">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Order #<?= $order['id'] ?></h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Customer</label>
                            <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Status</label>
                            <p class="text-lg font-semibold text-gray-900"><?= ucfirst($order['status']) ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Total Amount</label>
                            <p class="text-lg font-semibold text-gray-900">₹<?= number_format($order['total_amount'], 2) ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Payment Status</label>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                <?php
                                if ($order['payment_status'] === 'paid') {
                                    echo 'bg-green-100 text-green-700';
                                } else {
                                    echo 'bg-amber-100 text-amber-700';
                                }
                                ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-lg shadow-sm border-0 mb-4">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>
                </div>
                <div class="p-4">
                    <?php if (isset($order['items']) && !empty($order['items'])): ?>
                        <div class="space-y-3">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg border">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></p>
                                        <p class="text-sm text-gray-500">Price: ₹<?= number_format($item['price'], 2) ?> each</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900">Quantity: <?= $item['quantity'] ?></p>
                                        <p class="text-sm text-gray-500">Total: ₹<?= number_format($item['total'], 2) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No items found for this order.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Delivery Address -->
            <div class="bg-white rounded-lg shadow-sm border-0 mb-4">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Delivery Address</h2>
                </div>
                <div class="p-4">
                    <p class="text-gray-900"><?= htmlspecialchars($order['address']) ?></p>
                </div>
            </div>

            <!-- Package Action -->
            <?php if ($order['packaged_count'] == 0): ?>
                <div class="bg-white rounded-lg shadow-sm border-0">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">Package Order</h2>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="<?= \App\Core\View::url('staff/updateOrderStatus') ?>">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="status" value="processing">
                            <input type="hidden" name="action" value="packaging">
                            <input type="hidden" name="package_count" value="1">
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-700">Mark this order as packaged?</p>
                                    <p class="text-sm text-gray-500">This will update the order status to "processing" and set package count to 1.</p>
                                </div>
                                <button type="submit" 
                                        class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                    <i class="fas fa-box mr-2"></i>Package Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-sm border-0">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">Order Status</h2>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-700">This order has been packaged.</p>
                                <p class="text-sm text-gray-500">Package count: <?= $order['packaged_count'] ?></p>
                            </div>
                            <span class="px-4 py-2 bg-orange-100 text-orange-700 rounded-lg">
                                <i class="fas fa-check mr-2"></i>Packaged
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm border-0">
                <div class="p-8 text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Order not found</h3>
                    <p class="text-gray-500 mb-4">The order you're looking for doesn't exist or you don't have permission to view it.</p>
                    <a href="<?= \App\Core\View::url('staff/dashboard') ?>" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(__FILE__) . '/../layouts/staff.php';
?>


