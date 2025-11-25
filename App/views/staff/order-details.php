<?php ob_start(); ?>

<div class="min-h-screen bg-white pb-20 fixed">
    <!-- Header -->
    <div class="bg-white shadow-sm">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="<?= \App\Core\View::asset('images/logo/logo.svg') ?>" 
                         alt="NutriNexus" class="h-8 w-8 mr-3 rounded-full">
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900">Order #<?= $order['id'] ?></h1>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></p>
                    </div>
                </div>
                <a href="<?= \App\Core\View::url('staff/dashboard') ?>" 
                   class="btn-secondary text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Back
                </a>
            </div>
        </div>
    </div>

    <div class="px-4 py-4">
        <!-- Order Status Card -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Order Status</div>
                    <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                        <?php
                        switch ($order['status']) {
                            case 'pending':
                                echo 'bg-amber-100 text-amber-700';
                                break;
                            case 'packed':
                                echo 'bg-green-100 text-green-700';
                                break;
                            case 'processing':
                                echo 'bg-blue-100 text-blue-700';
                                break;
                            case 'dispatched':
                                echo 'bg-purple-100 text-purple-700';
                                break;
                            default:
                                echo 'bg-gray-100 text-gray-700';
                        }
                        ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="text-lg font-semibold text-gray-900">₹<?= number_format($order['total_amount'], 0) ?></div>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-900 mb-3">Customer Information</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Name:</span>
                    <span class="text-sm text-gray-900"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Email:</span>
                    <span class="text-sm text-gray-900"><?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Payment:</span>
                    <span class="text-sm text-gray-900"><?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-500">Date:</span>
                    <span class="text-sm text-gray-900"><?= date('M d, H:i', strtotime($order['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <h3 class="text-sm font-medium text-gray-900 mb-3">Order Items</h3>
            <div class="space-y-3">
                <?php foreach ($order['items'] as $item): ?>
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0">
                            <img class="h-12 w-12 rounded-lg object-cover border border-gray-200" 
                                 src="<?= !empty($item['product_image']) && file_exists('public/uploads/images/' . $item['product_image']) ? \App\Core\View::asset('uploads/images/' . $item['product_image']) : \App\Core\View::asset('images/products/default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                                 onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="text-xs text-gray-500">Qty: <?= $item['quantity'] ?> × ₹<?= number_format($item['price'], 0) ?></div>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">
                            ₹<?= number_format($item['price'] * $item['quantity'], 0) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4">
        <div class="flex space-x-3">
            <?php if ($order['status'] === 'pending'): ?>
                <button onclick="updateOrderStatus(<?= $order['id'] ?>, 'packed', 'packaging')" 
                        class="flex-1 btn-success">
                    <i class="fas fa-box mr-2"></i>
                    Mark as Packed
                </button>
            <?php elseif ($order['status'] === 'packed'): ?>
                <button onclick="updateOrderStatus(<?= $order['id'] ?>, 'processing', '')" 
                        class="flex-1 btn-primary">
                    <i class="fas fa-cog mr-2"></i>
                    Mark as Processing
                </button>
                <button onclick="updateOrderStatus(<?= $order['id'] ?>, 'dispatched', '')" 
                        class="flex-1 btn-warning">
                    <i class="fas fa-truck mr-2"></i>
                    Mark as Dispatched
                </button>
            <?php elseif ($order['status'] === 'processing'): ?>
                <button onclick="updateOrderStatus(<?= $order['id'] ?>, 'dispatched', '')" 
                        class="flex-1 btn-warning">
                    <i class="fas fa-truck mr-2"></i>
                    Mark as Dispatched
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= \App\Core\View::asset('layouts/shared-scripts.js') ?>"></script>


<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/staff.php'; ?>