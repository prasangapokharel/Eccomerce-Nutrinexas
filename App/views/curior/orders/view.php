<?php
$page = 'orders';
ob_start();
?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= \App\Core\View::url('curior/dashboard') ?>" class="text-primary hover:text-primary-dark flex items-center transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
    </a>
    <span class="px-3 py-1 rounded-full text-sm font-medium
        <?php
        switch ($order['status']) {
            case 'pending': echo 'bg-warning-50 text-warning-dark'; break;
            case 'processing': echo 'bg-primary-50 text-primary-700'; break;
            case 'picked_up': echo 'bg-primary-50 text-primary-700'; break;
            case 'in_transit': echo 'bg-primary-50 text-primary-700'; break;
            case 'shipped': echo 'bg-primary-50 text-primary-700'; break;
            case 'delivered': echo 'bg-accent/20 text-accent-dark'; break;
            case 'cancelled': echo 'bg-error-50 text-error-dark'; break;
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
            <?php
            // Calculate subtotal from order items using sale_price
            $subtotal = 0;
            if (!empty($order['items'])) {
                foreach ($order['items'] as $item) {
                    $itemPrice = !empty($item['sale_price']) && $item['sale_price'] > 0 
                        ? $item['sale_price'] 
                        : ($item['price'] ?? 0);
                    $subtotal += $itemPrice * ($item['quantity'] ?? 1);
                }
            } else {
                // Fallback calculation
                $subtotal = ($order['total_amount'] ?? 0) - ($order['tax_amount'] ?? 0) - ($order['delivery_fee'] ?? 0) + ($order['discount_amount'] ?? 0);
                $subtotal = max(0, $subtotal);
            }
            ?>
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span class="font-medium">Rs <?= number_format($subtotal, 2) ?></span>
            </div>
            <?php if (($order['tax_amount'] ?? 0) > 0): ?>
                <div class="flex justify-between">
                    <span>Tax:</span>
                    <span class="font-medium">Rs <?= number_format($order['tax_amount'] ?? 0, 2) ?></span>
                </div>
            <?php endif; ?>
            <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                    <div class="flex justify-between text-accent">
                    <span>Discount:</span>
                    <span class="font-medium">-Rs <?= number_format($order['discount_amount'], 2) ?></span>
                </div>
            <?php endif; ?>
            <?php if (($order['delivery_fee'] ?? 0) > 0): ?>
                <div class="flex justify-between">
                    <span>Delivery Fee:</span>
                    <span class="font-medium">Rs <?= number_format($order['delivery_fee'] ?? 0, 2) ?></span>
                </div>
            <?php endif; ?>
            <div class="border-t border-gray-200 pt-3 mt-3 flex justify-between text-lg font-bold text-gray-900">
                <span>Total:</span>
                <span>Rs <?= number_format($order['total_amount'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Customer Information</h2>
        <div class="space-y-3 text-gray-700">
            <div>
                <p class="text-sm text-gray-500">Name</p>
                <p class="font-medium"><?= htmlspecialchars($order['customer_name'] ?? $order['order_customer_name'] ?? 'N/A') ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Email</p>
                <p class="font-medium"><?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Phone</p>
                <p class="font-medium"><?= htmlspecialchars($order['contact_no'] ?? 'N/A') ?></p>
            </div>
        </div>
    </div>

    <!-- Delivery Address -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Delivery Address</h2>
        <div class="text-gray-700">
            <p class="font-medium"><?= htmlspecialchars($order['address'] ?? 'N/A') ?></p>
        </div>
    </div>

    <!-- Pickup Location (Seller Address) -->
    <?php if (!empty($sellerInfo)): ?>
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Pickup Location</h2>
        <div class="space-y-3 text-gray-700">
            <div>
                <p class="text-sm text-gray-500">Seller</p>
                <p class="font-medium"><?= htmlspecialchars($sellerInfo['company_name'] ?? $sellerInfo['name'] ?? 'N/A') ?></p>
            </div>
            <?php if (!empty($sellerInfo['address'])): ?>
            <div>
                <p class="text-sm text-gray-500">Address</p>
                <p class="font-medium"><?= htmlspecialchars($sellerInfo['address']) ?></p>
                <?php if (!empty($sellerInfo['city'])): ?>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($sellerInfo['city']) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($sellerInfo['phone'])): ?>
            <div>
                <p class="text-sm text-gray-500">Phone</p>
                <p class="font-medium"><?= htmlspecialchars($sellerInfo['phone']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Order Items -->
<div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
    <?php if (!empty($order['items'])): ?>
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
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></div>
                                <?php if (!empty($item['selected_color']) || !empty($item['selected_size'])): ?>
                                    <div class="text-sm text-gray-500">
                                        <?php if (!empty($item['selected_color'])): ?>
                                            Color: <?= htmlspecialchars($item['selected_color']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item['selected_size'])): ?>
                                            Size: <?= htmlspecialchars($item['selected_size']) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $item['quantity'] ?? 0 ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                Rs <?= number_format(
                                    (!empty($item['sale_price']) && $item['sale_price'] > 0) 
                                        ? $item['sale_price'] 
                                        : ($item['price'] ?? 0), 
                                    2
                                ) ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                Rs <?= number_format(
                                    (!empty($item['sale_price']) && $item['sale_price'] > 0) 
                                        ? ($item['sale_price'] * ($item['quantity'] ?? 1)) 
                                        : ($item['total'] ?? 0), 
                                    2
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-500">No items found</p>
    <?php endif; ?>
</div>

<!-- Order Actions -->
<div class="bg-white rounded-2xl shadow-lg p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Actions</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php if (in_array($order['status'], ['processing', 'confirmed', 'shipped'])): ?>
            <button onclick="scanAndPickup(<?= $order['id'] ?>)" 
                    class="px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-qrcode mr-2"></i>Scan & Pickup
            </button>
        <?php endif; ?>
        
        <?php if (in_array($order['status'], ['picked_up', 'shipped'])): ?>
            <button onclick="updateTransit(<?= $order['id'] ?>)" 
                    class="px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-truck mr-2"></i>Mark In Transit
            </button>
        <?php endif; ?>
        
        <?php if (in_array($order['status'], ['in_transit', 'picked_up', 'shipped'])): ?>
            <button onclick="attemptDelivery(<?= $order['id'] ?>)" 
                    class="px-4 py-3 bg-warning text-white rounded-lg hover:bg-warning-dark transition-colors">
                <i class="fas fa-exclamation-triangle mr-2"></i>Attempt Delivery
            </button>
        <?php endif; ?>
        
        <?php if (in_array($order['status'], ['in_transit', 'picked_up', 'shipped'])): ?>
            <button onclick="confirmDelivery(<?= $order['id'] ?>)" 
                    class="px-4 py-3 bg-accent text-white rounded-lg hover:bg-accent-dark transition-colors">
                <i class="fas fa-check mr-2"></i>Confirm Delivery
            </button>
        <?php endif; ?>
        
        <?php if (!empty($order['payment_method_id']) && $order['payment_method_id'] == 1 && !empty($order['payment_status']) && $order['payment_status'] === 'pending'): ?>
            <button onclick="collectCOD(<?= $order['id'] ?>, <?= $order['total_amount'] ?>)" 
                    class="px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-money-bill-wave mr-2"></i>Collect COD
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Delivery Modal -->
<div id="deliveryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirm Delivery</h3>
        <form id="deliveryForm" enctype="multipart/form-data">
            <input type="hidden" name="order_id" id="deliveryOrderId">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">OTP (if applicable)</label>
                <input type="text" name="otp" placeholder="Enter OTP or leave blank"
                       class="input native-input">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Customer Signature</label>
                <select name="signature" class="input native-input">
                    <option value="yes">Yes, signature provided</option>
                    <option value="no">No signature</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Delivery Proof (Max 300KB)</label>
                <input type="file" name="delivery_proof" accept="image/*" required
                       class="input native-input">
                <p class="text-xs text-gray-500 mt-1">Image will be compressed automatically</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea name="notes" rows="3" placeholder="Additional delivery notes"
                          class="input native-input"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeliveryModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent-dark">
                    <i class="fas fa-check mr-2"></i>Confirm Delivery
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function scanAndPickup(orderId) {
    const orderCode = prompt('Scan or enter order code/invoice:', '');
    if (!orderCode) return;
    
    if (!confirm('Confirm pickup from seller location?')) return;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('scan_code', orderCode);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('curior/order/pickup') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Pickup confirmed successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to confirm pickup'));
        }
    });
}

function updateTransit(orderId) {
    const location = prompt('Enter current location:');
    if (!location) return;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('location', location);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('curior/order/transit') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order status updated to in-transit!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update status'));
        }
    });
}

function attemptDelivery(orderId) {
    const reason = prompt('Reason for delivery attempt (e.g., Customer unavailable):');
    if (!reason) return;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('reason', reason);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('curior/order/attempt') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Delivery attempt logged!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to log attempt'));
        }
    });
}

function confirmDelivery(orderId) {
    document.getElementById('deliveryOrderId').value = orderId;
    const modal = document.getElementById('deliveryModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex', 'items-center', 'justify-center');
    modal.style.display = 'flex';
}

function closeDeliveryModal() {
    const modal = document.getElementById('deliveryModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.getElementById('deliveryForm').reset();
}

document.getElementById('deliveryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?= \App\Core\View::url('curior/order/deliver') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order delivered successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to confirm delivery'));
        }
    });
});

function collectCOD(orderId, amount) {
    const codAmount = prompt('Enter COD amount collected:', amount);
    if (!codAmount || isNaN(codAmount) || codAmount <= 0) return;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('cod_amount', codAmount);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('curior/order/cod') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('COD collected successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to collect COD'));
        }
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

