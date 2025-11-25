<?php
$page = 'pickup';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Pickup Management</h1>
    <p class="text-gray-600 mt-2">Orders waiting for pickup from seller location</p>
</div>

<?php if (empty($orders)): ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-check-circle text-accent text-5xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No pickups pending</h3>
        <p class="text-gray-500">All orders have been picked up</p>
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
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-warning-50 text-warning-dark">
                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-500">Amount</p>
                        <p class="font-semibold text-gray-900">Rs <?= number_format($order['total_amount'] ?? 0, 2) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Payment</p>
                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-3 mt-4">
                    <button onclick="markPicked(<?= $order['id'] ?>)" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        <i class="fas fa-check-circle mr-2"></i>Mark Picked
                    </button>
                    <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-eye mr-2"></i>View Details
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Mark Picked Modal -->
<div id="pickupModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" style="display: none;">
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
                <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2"></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closePickupModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    Confirm Pickup
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
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

