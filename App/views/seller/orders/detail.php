<?php ob_start(); ?>
<?php $page = 'orders'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Order Details #<?= $order['id'] ?></h1>
        <a href="<?= \App\Core\View::url('seller/orders') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back to Orders
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Information -->
        <div style="grid-column: span 2;" class="space-y-6">
            <!-- Order Items -->
            <div class="card">
                <h2 class="card-title">Order Items</h2>
                <div class="space-y-4">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="flex items-center justify-between p-4" style="border: 1px solid var(--gray-200); border-radius: 0.5rem;">
                            <div class="flex items-center gap-4">
                                <div class="img-container">
                                    <img src="<?= htmlspecialchars($item['product_image'] ?? \App\Core\View::asset('images/products/default.jpg')) ?>" 
                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                         onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></p>
                                    <p class="text-sm text-gray-600">Quantity: <?= $item['quantity'] ?></p>
                                    <p class="text-sm text-gray-600">Price: रु <?= number_format($item['price'], 2) ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">रु <?= number_format($item['total'], 2) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="card">
                <h2 class="card-title">Shipping Address</h2>
                <div class="text-gray-700">
                    <p class="font-medium"><?= htmlspecialchars($order['customer_name']) ?></p>
                    <p><?= htmlspecialchars($order['address'] ?? '') ?></p>
                    <p>Phone: <?= htmlspecialchars($order['contact_no'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="space-y-6">
            <div class="card">
                <h2 class="card-title">Order Summary</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium">रु <?= number_format($order['seller_subtotal'] ?? 0, 2) ?></span>
                    </div>
                    <?php if (!empty($order['seller_discount']) && $order['seller_discount'] > 0): ?>
                        <div class="flex justify-between" style="color: #059669;">
                            <span>Discount</span>
                            <span>-रु <?= number_format($order['seller_discount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order['seller_tax']) && $order['seller_tax'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax</span>
                            <span>रु <?= number_format($order['seller_tax'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order['seller_delivery_fee']) && $order['seller_delivery_fee'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Delivery Fee</span>
                            <span>रु <?= number_format($order['seller_delivery_fee'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div style="border-top: 1px solid var(--gray-200); padding-top: 0.75rem;" class="flex justify-between">
                        <span class="font-semibold text-gray-900">Total</span>
                        <span class="font-bold text-lg text-gray-900">रु <?= number_format($order['seller_total'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <?php if ($order['status'] === 'pending'): ?>
                <div class="card">
                    <h2 class="card-title">Quick Actions</h2>
                    <div class="space-y-2">
                        <form action="<?= \App\Core\View::url('seller/orders/accept/' . $order['id']) ?>" method="POST" class="mb-2">
                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                            <button type="submit" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-check mr-2"></i>Accept Order
                            </button>
                        </form>
                        <button onclick="showRejectModal()" class="btn btn-danger" style="width: 100%;">
                            <i class="fas fa-times mr-2"></i>Reject Order
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Print Actions -->
            <div class="card">
                <h2 class="card-title">Print</h2>
                <div class="space-y-3">
                    <a href="<?= \App\Core\View::url('orders/receipt/' . $order['id']) ?>" 
                       target="_blank"
                       class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-file-invoice mr-2"></i>Print Invoice
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders/print-shipping-label/' . $order['id']) ?>" 
                       target="_blank"
                       class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors font-medium">
                        <i class="fas fa-truck mr-2"></i>Print Shipping Label
                    </a>
                </div>
            </div>

            <!-- Update Status -->
            <div class="card">
                <h2 class="card-title">Update Status</h2>
                <form action="<?= \App\Core\View::url('seller/orders/update-status/' . $order['id']) ?>" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                    <div class="mb-4">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Order Status</label>
                        <select name="status" id="status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>New Order</option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing / Packing</option>
                            <option value="ready_for_pickup" <?= $order['status'] === 'ready_for_pickup' ? 'selected' : '' ?>>Ready for Pickup</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-save mr-2"></i>Update Status
                    </button>
                </form>
            </div>

            <!-- Order Info -->
            <div class="card">
                <h2 class="card-title">Order Information</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Order Date:</span>
                        <span class="text-gray-900"><?= date('M j, Y h:i A', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Payment Status:</span>
                        <span class="badge <?= $order['payment_status'] === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                            <?= ucfirst($order['payment_status']) ?>
                        </span>
                    </div>
                    <?php if (!empty($order['transaction_id'])): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Transaction ID:</span>
                            <span class="text-gray-900"><?= htmlspecialchars($order['transaction_id']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Order Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Order</h3>
        <form action="<?= \App\Core\View::url('seller/orders/reject/' . $order['id']) ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <div class="mb-4">
                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Rejection</label>
                <textarea id="rejection_reason" 
                          name="rejection_reason" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                          placeholder="Enter reason for rejecting this order..."
                          required></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="hideRejectModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Reject Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal() {
    document.getElementById('rejectModal').style.display = 'flex';
}

function hideRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideRejectModal();
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
