<?php ob_start(); ?>
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="mb-8 flex items-center justify-between">
            <a href="<?= \App\Core\View::url('admin/orders') ?>" class="text-blue-600 hover:text-blue-800 flex items-center transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Orders
            </a>
            <div class="flex items-center space-x-3 flex-wrap gap-2">
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    <?php
                    switch ($order['status']) { // Changed from order_status to status
                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                        case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                        case 'shipped': echo 'bg-purple-100 text-purple-800'; break;
                        case 'delivered': echo 'bg-green-100 text-green-800'; break;
                        case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                        case 'paid': echo 'bg-green-100 text-green-800'; break;
                        case 'unpaid': echo 'bg-red-100 text-red-800'; break;
                        default: echo 'bg-gray-100 text-gray-800'; break;
                    }
                    ?>
                ">
                    <?= htmlspecialchars(ucfirst($order['status'])) ?>
                </span>
                <form action="<?= \App\Core\View::url('admin/updateOrderStatus/' . $order['id']) ?>" method="POST" class="inline-block">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                    <select name="status" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Update Status</option>
                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="dispatched" <?= $order['status'] == 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
                        <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="paid" <?= $order['status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="unpaid" <?= $order['status'] == 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </form>
                <!-- Assign Curior Button -->
                <button onclick="openAssignCuriorModal(<?= $order['id'] ?>)" 
                        class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition-colors flex items-center gap-1">
                    <i class="fas fa-truck"></i>
                    <?= !empty($order['curior_id']) ? 'Reassign' : 'Assign' ?> Curior
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8 p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order #<?= htmlspecialchars($order['invoice']) ?></h1>
            <p class="text-gray-600 text-sm">Placed on <?= date('M j, Y', strtotime($order['created_at'])) ?> at <?= date('g:i A', strtotime($order['created_at'])) ?></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Order Details -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Details</h2>
                <div class="space-y-3 text-gray-700">
                    <?php
                    // Calculate subtotal from actual order items (using sale prices if available)
                    $subtotal = 0;
                    foreach ($orderItems as $item) {
                        $subtotal += ($item['total'] ?? 0);
                    }
                    
                    // Calculate correct total from breakdown
                    $discount = $order['discount_amount'] ?? 0;
                    $tax = $order['tax_amount'] ?? 0;
                    $delivery = $order['delivery_fee'] ?? 0;
                    $calculatedTotal = $subtotal - $discount + $tax + $delivery;
                    ?>
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span class="font-medium">रु <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Coupon Discount<?= !empty($order['coupon_code']) ? ' (' . htmlspecialchars($order['coupon_code']) . ')' : '' ?>:</span>
                            <span class="font-medium">-रु <?= number_format($discount, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($tax > 0): ?>
                        <div class="flex justify-between">
                            <span>Tax:</span>
                            <span class="font-medium">रु <?= number_format($tax, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($delivery > 0): ?>
                        <div class="flex justify-between">
                            <span>Delivery Fee:</span>
                            <span class="font-medium">रु <?= number_format($delivery, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="border-t border-gray-200 pt-3 mt-3 flex justify-between text-lg font-bold text-gray-900">
                        <span>Total:</span>
                        <span>रु <?= number_format($calculatedTotal, 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Customer Information</h2>
                <div class="space-y-2 text-gray-700">
                    <p>
                        <span class="font-medium">Name:</span>
                        <?= htmlspecialchars($order['order_customer_name'] ?? $order['user_full_name'] ?? 'N/A') ?>
                    </p>
                    <p>
                        <span class="font-medium">Phone:</span>
                        <?= htmlspecialchars($order['contact_no'] ?? $order['user_phone'] ?? 'N/A') ?>
                    </p>
                    <p>
                        <span class="font-medium">Email:</span>
                        <?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?>
                    </p>
                    <?php if ($order['user_id']): ?>
                        <p class="text-sm text-gray-500 mt-2">
                            <a href="<?= \App\Core\View::url('admin/viewUser/' . $order['user_id']) ?>" class="text-blue-600 hover:underline">View User Profile</a>
                        </p>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 mt-2">Guest Checkout</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Curior Assignment -->
            <?php if (!empty($assignedCurior)): ?>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Assigned Curior</h2>
                <div class="space-y-2 text-gray-700">
                    <p><span class="font-medium">Name:</span> <?= htmlspecialchars($assignedCurior['name']) ?></p>
                    <p><span class="font-medium">Phone:</span> <?= htmlspecialchars($assignedCurior['phone']) ?></p>
                    <?php if (!empty($assignedCurior['email'])): ?>
                        <p><span class="font-medium">Email:</span> <?= htmlspecialchars($assignedCurior['email']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Shipping Address -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Shipping Address</h2>
                <div class="space-y-2 text-gray-700">
                    <p><?= htmlspecialchars($order['recipient_name'] ?? $order['order_customer_name'] ?? 'N/A') ?></p>
                    <p><?= htmlspecialchars($order['address'] ?? 'N/A') ?></p>
                    <p><?= htmlspecialchars($order['city'] ?? 'N/A') ?>, <?= htmlspecialchars($order['state'] ?? 'N/A') ?></p>
                    <p><?= htmlspecialchars($order['country'] ?? 'N/A') ?></p>
                    <p><span class="font-medium">Phone:</span> <?= htmlspecialchars($order['phone'] ?? $order['contact_no'] ?? 'N/A') ?></p>
                </div>
            </div>
        </div>

        <!-- Payment Information & Notes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Information</h2>
                <div class="space-y-3 text-gray-700">
                    <p><span class="font-medium">Method:</span> <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></p>
                    <div class="flex items-center gap-3">
                        <span class="font-medium">Status:</span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            <?php
                            switch ($order['payment_status'] ?? '') {
                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'paid': echo 'bg-green-100 text-green-800'; break;
                                case 'failed': echo 'bg-red-100 text-red-800'; break;
                                case 'refunded': echo 'bg-blue-100 text-blue-800'; break;
                                default: echo 'bg-gray-100 text-gray-800'; break;
                            }
                            ?>
                        ">
                            <?= htmlspecialchars(ucfirst($order['payment_status'] ?? 'N/A')) ?>
                        </span>
                        <select id="paymentStatusSelect" data-order-id="<?= $order['id'] ?>" class="px-2 py-1 border border-gray-300 rounded-md text-xs focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Update Payment</option>
                            <option value="pending" <?= ($order['payment_status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= ($order['payment_status'] ?? '') == 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="failed" <?= ($order['payment_status'] ?? '') == 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="refunded" <?= ($order['payment_status'] ?? '') == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>
                    <?php if (!empty($order['transaction_id'])): ?>
                        <p><span class="font-medium">Transaction ID:</span> <?= htmlspecialchars($order['transaction_id']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['khalti_pidx'])): ?>
                        <p><span class="font-medium">Khalti PIDX:</span> <?= htmlspecialchars($order['khalti_pidx']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['khalti_transaction_id'])): ?>
                        <p><span class="font-medium">Khalti Txn ID:</span> <?= htmlspecialchars($order['khalti_transaction_id']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['esewa_reference_id'])): ?>
                        <p><span class="font-medium">eSewa Ref ID:</span> <?= htmlspecialchars($order['esewa_reference_id']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['esewa_transaction_id'])): ?>
                        <p><span class="font-medium">eSewa Txn ID:</span> <?= htmlspecialchars($order['esewa_transaction_id']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['payment_screenshot'])): ?>
                        <p class="mt-4">
                            <span class="font-medium">Payment Screenshot:</span><br>
                            <button onclick="openScreenshotModal('<?= ASSETS_URL ?>/payments/<?= htmlspecialchars($order['payment_screenshot']) ?>')" class="text-blue-600 hover:underline">View Screenshot</button>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Notes</h2>
                <div class="text-gray-700">
                    <p><?= !empty($order['order_notes']) ? nl2br(htmlspecialchars($order['order_notes'])) : 'No notes provided.' ?></p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
            <div class="overflow-x-auto -mx-6 px-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orderItems as $item): ?>
                            <?php
                            $isDigital = !empty($item['is_digital']);
                            $productType = $item['product_type_main'] ?? $item['product_type'] ?? 'Standard';
                            $colors = !empty($item['colors']) ? json_decode($item['colors'], true) : [];
                            ?>
                            <tr>
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-gray-900">
                                        <a href="<?= \App\Core\View::url('products/view/' . ($item['product_id'] ?? '')) ?>" class="text-blue-600 hover:underline">
                                            <?= htmlspecialchars($item['product_name'] ?? 'N/A') ?>
                                        </a>
                                    </div>
                                    <?php if ($isDigital): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1">
                                            <i class="fas fa-download mr-1"></i>Digital Product
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Selected Color and Size -->
                                    <div class="mt-2 space-y-1">
                                        <?php if (!empty($item['selected_color'])): ?>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-medium text-gray-600">Color:</span>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                                    <span class="w-3 h-3 rounded-full border border-gray-300 mr-1" style="background-color: <?= preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $item['selected_color']) ? htmlspecialchars($item['selected_color']) : 'transparent' ?>"></span>
                                                    <?= htmlspecialchars($item['selected_color']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($item['selected_size'])): ?>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-medium text-gray-600">Size:</span>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                    <?= htmlspecialchars($item['selected_size']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        switch(strtolower($productType)) {
                                            case 'digital': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'accessories': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'supplement': echo 'bg-green-100 text-green-800'; break;
                                            case 'vitamins': echo 'bg-yellow-100 text-yellow-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800'; break;
                                        }
                                        ?>
                                    ">
                                        <i class="fas fa-<?= $isDigital ? 'download' : 'box' ?> mr-1"></i>
                                        <?= htmlspecialchars(ucfirst($productType)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($item['quantity']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    रु <?= number_format($item['price'], 2) ?>
                                    <?php if (!empty($item['original_price']) && $item['original_price'] > $item['price']): ?>
                                        <span class="text-gray-400 line-through ml-2">रु <?= number_format($item['original_price'], 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">रु <?= number_format($item['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Assign Curior Modal -->
<div id="assignCuriorModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Assign Curior</h3>
                <button onclick="closeAssignCuriorModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="assignCuriorForm" onsubmit="assignCurior(event)">
                <input type="hidden" name="order_id" id="assignCuriorOrderId">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                
                <div class="mb-4">
                    <label for="curior_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Curior
                    </label>
                    <select name="curior_id" id="curior_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">-- Select Curior --</option>
                        <?php foreach ($curiors ?? [] as $curior): ?>
                            <?php if ($curior['status'] === 'active'): ?>
                                <option value="<?= $curior['id'] ?>" 
                                        <?= (!empty($order['curior_id']) && $order['curior_id'] == $curior['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($curior['name']) ?> - <?= htmlspecialchars($curior['phone']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (!empty($assignedCurior)): ?>
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <strong>Currently Assigned:</strong> <?= htmlspecialchars($assignedCurior['name']) ?> 
                            (<?= htmlspecialchars($assignedCurior['phone']) ?>)
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignCuriorModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-check mr-2"></i>
                        Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAssignCuriorModal(orderId) {
    document.getElementById('assignCuriorOrderId').value = orderId;
    document.getElementById('assignCuriorModal').classList.remove('hidden');
}

function closeAssignCuriorModal() {
    document.getElementById('assignCuriorModal').classList.add('hidden');
    document.getElementById('assignCuriorForm').reset();
}

function assignCurior(event) {
    event.preventDefault();
    
    const form = document.getElementById('assignCuriorForm');
    const formData = new FormData(form);
    
    fetch('<?= \App\Core\View::url('admin/orders/assignCurior') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order assigned to curior successfully!');
            closeAssignCuriorModal();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to assign order'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning the order');
    });
}

// Close modal when clicking outside
document.getElementById('assignCuriorModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAssignCuriorModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAssignCuriorModal();
    }
});
</script>

<!-- Screenshot Modal -->
<div id="screenshotModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-4xl max-h-full overflow-auto">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-semibold">Payment Screenshot</h3>
                <button onclick="closeScreenshotModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <img id="screenshotImage" src="" alt="Payment Screenshot" class="max-w-full h-auto">
            </div>
        </div>
    </div>
</div>

<script>
function openScreenshotModal(imageUrl) {
    document.getElementById('screenshotImage').src = imageUrl;
    document.getElementById('screenshotModal').classList.remove('hidden');
}

function closeScreenshotModal() {
    document.getElementById('screenshotModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('screenshotModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeScreenshotModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeScreenshotModal();
    }
});


// Payment Status Update via AJAX
document.addEventListener('DOMContentLoaded', function() {
    const paymentStatusSelect = document.getElementById('paymentStatusSelect');
    if (paymentStatusSelect) {
        const originalValue = paymentStatusSelect.value;
        const orderId = paymentStatusSelect.getAttribute('data-order-id');
        
        paymentStatusSelect.addEventListener('change', function() {
            const newStatus = this.value;
            if (!newStatus) {
                this.value = originalValue;
                return;
            }
            
            // Disable select during request
            this.disabled = true;
            const statusBadge = this.closest('.space-y-3').querySelector('.px-2.py-1.rounded-full');
            
            const formData = new FormData();
            formData.append('payment_status', newStatus);
            formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
            
            fetch('<?= \App\Core\View::url("admin/updateOrderPaymentStatus/") ?>' + orderId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.disabled = false;
                
                if (data.success) {
                    // Update status badge
                    if (statusBadge) {
                        statusBadge.textContent = data.payment_status_display || newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        
                        // Update badge color
                        statusBadge.className = 'px-2 py-1 rounded-full text-xs font-medium';
                        switch(newStatus) {
                            case 'pending':
                                statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                                break;
                            case 'paid':
                                statusBadge.classList.add('bg-green-100', 'text-green-800');
                                break;
                            case 'failed':
                                statusBadge.classList.add('bg-red-100', 'text-red-800');
                                break;
                            case 'refunded':
                                statusBadge.classList.add('bg-blue-100', 'text-blue-800');
                                break;
                            default:
                                statusBadge.classList.add('bg-gray-100', 'text-gray-800');
                        }
                    }
                    
                    // Show success message
                    showFlashMessage('success', data.message || 'Payment status updated successfully');
                } else {
                    // Revert to original value on error
                    this.value = originalValue;
                    showFlashMessage('error', data.message || 'Failed to update payment status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.disabled = false;
                this.value = originalValue;
                showFlashMessage('error', 'An error occurred while updating payment status');
            });
        });
    }
});

// Flash message function
function showFlashMessage(type, message) {
    const flashDiv = document.createElement('div');
    flashDiv.className = `fixed top-4 left-1/2 transform -translate-x-1/2 z-50 max-w-md w-full mx-4`;
    flashDiv.innerHTML = `
        <div class="${type === 'success' ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300'} border-2 rounded-lg p-4 shadow-xl flex items-start animate-slide-down">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 ${type === 'success' ? 'text-green-500' : 'text-red-500'}" fill="currentColor" viewBox="0 0 20 20">
                    ${type === 'success' 
                        ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
                        : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
                    }
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-semibold ${type === 'success' ? 'text-green-800' : 'text-red-800'}">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 ${type === 'success' ? 'text-green-500' : 'text-red-500'} hover:opacity-75 transition-opacity">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    `;
    document.body.appendChild(flashDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        flashDiv.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
        flashDiv.style.opacity = '0';
        flashDiv.style.transform = 'translateX(-50%) translateY(-20px)';
        setTimeout(() => flashDiv.remove(), 300);
    }, 5000);
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
