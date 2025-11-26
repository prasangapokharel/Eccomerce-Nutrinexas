<?php
$page = 'pickup';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Pickup Management</h1>
            <p class="mt-1 text-sm text-gray-500">Orders waiting for pickup from seller location</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header -->
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Pickup Orders</h2>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto -mx-4 px-4">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Order Details
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Seller Location
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date & Time
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-check-circle text-4xl text-accent mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No pickups pending</h3>
                                    <p class="text-gray-500">All orders have been picked up</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center">
                                                <i class="fas fa-receipt text-primary text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                #<?= htmlspecialchars($order['invoice'] ?? $order['id']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Order ID: <?= $order['id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($order['customer_name'] ?? $order['order_customer_name'] ?? $order['user_full_name'] ?? 'N/A') ?>
                                    </div>
                                    <?php if (!empty($order['customer_email'] ?? $order['email'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($order['customer_email'] ?? $order['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($order['seller_address']) || !empty($order['seller_name'])): ?>
                                        <div class="text-sm font-medium text-gray-900">
                                            <i class="fas fa-store text-primary mr-1"></i>
                                            <?= htmlspecialchars($order['seller_company'] ?? $order['seller_name'] ?? 'N/A') ?>
                                        </div>
                                        <?php if (!empty($order['seller_address'])): ?>
                                            <div class="text-xs text-gray-600 mt-1">
                                                <?= htmlspecialchars($order['seller_address']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($order['seller_city'])): ?>
                                            <div class="text-xs text-gray-500">
                                                <i class="fas fa-map-marker-alt text-accent mr-1"></i>
                                                <?= htmlspecialchars($order['seller_city']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($order['seller_phone'])): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-phone text-primary mr-1"></i>
                                                <?= htmlspecialchars($order['seller_phone']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Location not available</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= date('g:i A', strtotime($order['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        रु <?= number_format($order['total_amount'] ?? 0, 2) ?>
                                    </div>
                                    <?php if ($order['payment_method_id'] == 1): ?>
                                        <span class="inline-block mt-1 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">COD</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-50 text-warning-dark">
                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button onclick="markPicked(<?= $order['id'] ?>)" 
                                                class="text-primary hover:text-primary-dark transition-colors p-1 rounded hover:bg-primary-50" 
                                                title="Mark Picked">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                        <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
                                           class="text-gray-600 hover:text-gray-900 transition-colors p-1 rounded hover:bg-gray-100" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mark Picked Modal -->
<div id="pickupModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Mark Order as Picked</h3>
        <form id="pickupForm" enctype="multipart/form-data">
            <input type="hidden" name="order_id" id="pickupOrderId">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Pickup Proof (Optional)</label>
                <input type="file" name="pickup_proof" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Add any notes about the pickup"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closePickupModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <i class="fas fa-check-circle mr-2"></i>Confirm Pickup
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function markPicked(orderId) {
    document.getElementById('pickupOrderId').value = orderId;
    const modal = document.getElementById('pickupModal');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}

function closePickupModal() {
    const modal = document.getElementById('pickupModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.getElementById('pickupForm').reset();
}

document.getElementById('pickupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?= \App\Core\View::url('curior/pickup/mark-picked') ?>', {
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
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

// Close modal on outside click
document.getElementById('pickupModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePickupModal();
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
