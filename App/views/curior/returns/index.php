<?php
$page = 'returns';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Returns & RTO</h1>
            <p class="mt-1 text-sm text-gray-500">Manage return orders and RTO (Return to Origin)</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header -->
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Return Orders</h2>
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
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-undo text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No returns assigned</h3>
                                    <p class="text-gray-500">You don't have any return orders at the moment</p>
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
                                        <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?>
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
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusBadges = [
                                        'return_requested' => 'bg-warning-50 text-warning-dark',
                                        'return_picked_up' => 'bg-primary-50 text-primary-700',
                                        'return_in_transit' => 'bg-primary-50 text-primary-700',
                                        'returned' => 'bg-accent/20 text-accent-dark'
                                    ];
                                    $statusBadge = $statusBadges[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>">
                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <?php if ($order['status'] === 'return_requested'): ?>
                                            <button onclick="acceptReturn(<?= $order['id'] ?>)" 
                                                    class="text-primary hover:text-primary-dark transition-colors p-1 rounded hover:bg-primary-50" 
                                                    title="Accept Return Pickup">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        <?php elseif ($order['status'] === 'return_picked_up'): ?>
                                            <button onclick="updateReturnTransit(<?= $order['id'] ?>)" 
                                                    class="text-primary hover:text-primary-dark transition-colors p-1 rounded hover:bg-primary-50" 
                                                    title="Mark In Transit">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                        <?php elseif ($order['status'] === 'return_in_transit'): ?>
                                            <button onclick="completeReturn(<?= $order['id'] ?>)" 
                                                    class="text-success hover:text-success-dark transition-colors p-1 rounded hover:bg-success-50" 
                                                    title="Complete Return">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        <?php endif; ?>
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

<script>
function acceptReturn(orderId) {
    if (!confirm('Accept return pickup for this order?')) return;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('curior/order/return/pickup') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Return pickup accepted!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to accept return'));
        }
    });
}

function updateReturnTransit(orderId) {
    const location = prompt('Enter current location:');
    if (!location) return;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('location', location);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('curior/order/return/transit') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Return transit updated!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update transit'));
        }
    });
}

function completeReturn(orderId) {
    if (!confirm('Complete return drop-off to seller?')) return;
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('curior/order/return/complete') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Return completed successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to complete return'));
        }
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
