<?php
$page = 'returns';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Returns & RTO</h1>
    <p class="text-gray-600 mt-2">Manage return orders and RTO (Return to Origin)</p>
</div>

<?php if (empty($orders)): ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-undo text-gray-400 text-5xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No returns assigned</h3>
        <p class="text-gray-500">You don't have any return orders at the moment</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($orders as $order): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Order #<?= $order['id'] ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium
                        <?= $order['status'] === 'returned' ? 'bg-accent/20 text-accent-dark' : 
                           ($order['status'] === 'return_in_transit' ? 'bg-primary-50 text-primary-700' : 
                           ($order['status'] === 'return_picked_up' ? 'bg-primary-50 text-primary-700' : 
                           'bg-warning-50 text-warning-dark')) ?>">
                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Amount</p>
                        <p class="font-semibold text-gray-900">Rs <?= number_format($order['total_amount'] ?? 0, 2) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold text-gray-900"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="font-semibold text-gray-900"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3 mt-4">
                    <?php if ($order['status'] === 'return_requested'): ?>
                        <button onclick="acceptReturn(<?= $order['id'] ?>)" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                            <i class="fas fa-check mr-2"></i>Accept Return Pickup
                        </button>
                    <?php elseif ($order['status'] === 'return_picked_up'): ?>
                        <button onclick="updateReturnTransit(<?= $order['id'] ?>)" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                            <i class="fas fa-truck mr-2"></i>Mark In Transit
                        </button>
                    <?php elseif ($order['status'] === 'return_in_transit'): ?>
                        <button onclick="completeReturn(<?= $order['id'] ?>)" 
                                class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent-dark transition-colors">
                            <i class="fas fa-check-circle mr-2"></i>Complete Return
                        </button>
                    <?php endif; ?>
                    <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-eye mr-2"></i>View Details
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

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

