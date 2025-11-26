<?php
$page = 'delivery';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Delivery Management</h1>
            <p class="mt-1 text-sm text-gray-500">Orders ready for delivery (picked up and in transit)</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header with Filters -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">Delivery Orders</h2>
                </div>
                
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap gap-2">
                    <a href="<?= \App\Core\View::url('curior/delivery') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= !isset($status) || $status === '' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        All Orders
                    </a>
                    <a href="<?= \App\Core\View::url('curior/delivery?status=picked_up') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'picked_up' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Picked Up
                    </a>
                    <a href="<?= \App\Core\View::url('curior/delivery?status=in_transit') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'in_transit' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Out for Delivery
                    </a>
                </div>
            </div>
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
                            Delivery Address
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
                                    <i class="fas fa-truck text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No orders ready for delivery</h3>
                                    <p class="text-gray-500">Orders will appear here after they are picked up from sellers.</p>
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
                                        <?= htmlspecialchars($order['customer_name'] ?? $order['order_customer_name'] ?? 'N/A') ?>
                                    </div>
                                    <?php if (!empty($order['contact_no'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($order['contact_no']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($order['address'] ?? 'N/A') ?>
                                    </div>
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?= $order['status'] === 'in_transit' ? 'bg-primary-50 text-primary-700' : 'bg-warning-50 text-warning-dark' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
                                           class="text-gray-600 hover:text-gray-900 transition-colors p-1 rounded hover:bg-gray-100" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($order['status'] === 'picked_up'): ?>
                                            <button onclick="markOutForDelivery(<?= $order['id'] ?>)" 
                                                    class="text-primary hover:text-primary-dark transition-colors p-1 rounded hover:bg-primary-50" 
                                                    title="Out for Delivery">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($order['status'] === 'in_transit'): ?>
                                            <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
                                               class="text-success hover:text-success-dark transition-colors p-1 rounded hover:bg-success-50" 
                                               title="Mark Delivered">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
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

<!-- Out for Delivery Modal -->
<div id="outForDeliveryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Mark as Out for Delivery</h3>
        <form id="outForDeliveryForm" onsubmit="submitOutForDelivery(event)">
            <input type="hidden" name="order_id" id="outForDeliveryOrderId">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="mb-4">
                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                    Current Location (Optional)
                </label>
                <input type="text" 
                       id="location" 
                       name="location" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Enter your current location">
            </div>
            
            <div class="mb-4">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Notes (Optional)
                </label>
                <textarea id="notes" 
                          name="notes" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                          placeholder="Add any notes about this delivery"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" 
                        onclick="closeOutForDeliveryModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    <i class="fas fa-truck mr-2"></i>Mark Out for Delivery
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function markOutForDelivery(orderId) {
    document.getElementById('outForDeliveryOrderId').value = orderId;
    document.getElementById('outForDeliveryModal').style.display = 'flex';
}

function closeOutForDeliveryModal() {
    document.getElementById('outForDeliveryModal').style.display = 'none';
    document.getElementById('outForDeliveryForm').reset();
}

function submitOutForDelivery(event) {
    event.preventDefault();
    
    const form = document.getElementById('outForDeliveryForm');
    const formData = new FormData(form);
    
    fetch('<?= \App\Core\View::url('curior/delivery/out-for-delivery') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order marked as out for delivery!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update order status'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Close modal on outside click
document.getElementById('outForDeliveryModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeOutForDeliveryModal();
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
