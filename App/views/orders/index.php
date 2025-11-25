<?php ob_start(); ?>

<div class="min-h-screen bg-gray-50">

    <div class="container mx-auto px-4 py-6 max-w-4xl">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="bg-green-50 border-l-4 border-green-400 text-green-800 px-3 py-2 rounded-lg mb-4 shadow-sm">
                <div class="flex items-center">
                    <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium text-xs"><?= $_SESSION['flash_message'] ?></span>
                </div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="bg-white rounded-xl shadow-lg p-8 text-center mt-8 max-w-md mx-auto">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">No orders yet</h2>
                <p class="text-accent text-xs mb-4">You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="<?= \App\Core\View::url('products') ?>" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-xl font-semibold text-xs">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="space-y-2">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'processing' => 'bg-blue-100 text-blue-800',
                        'shipped' => 'bg-purple-100 text-purple-800',
                        'delivered' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                    ];
                    $statusColor = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center p-3">
                            <!-- Order Icon -->
                            <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 mr-3">
                                <img src="<?= ASSETS_URL ?>/images/icons/order.png" 
                                     alt="Order" 
                                     class="w-full h-full object-contain p-2"
                                     onerror="this.src='<?= ASSETS_URL ?>/images/icons/orders.png'">
                            </div>
                            
                            <!-- Order Details -->
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-gray-900 truncate">
                                    Order #<?= $order['id'] ?>
                                </h4>
                                <p class="text-xs text-gray-500"><?= date('M j, Y', strtotime($order['created_at'])) ?></p>
                                <p class="text-sm font-semibold text-primary">रु <?= number_format($order['total_amount'], 0) ?></p>
                            </div>
                            
                            <!-- Order Status & Actions -->
                            <div class="text-right flex flex-col items-end">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $statusColor ?> mb-2">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                                <!-- Download Receipt Button -->
                                <a href="<?= \App\Core\View::url('receipt/' . $order['id']) ?>" 
                                   class="inline-flex items-center px-2 py-1 text-xs text-primary hover:text-blue-800 transition-colors"
                                   title="Download Receipt">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                                <!-- Cancel Button (if order can be cancelled) -->
                                <?php 
                                $cancellableStatuses = ['pending', 'confirmed', 'processing', 'unpaid'];
                                if (in_array($order['status'], $cancellableStatuses)): 
                                ?>
                                    <button onclick="openCancelDrawer(<?= $order['id'] ?>)"
                                            class="mt-2 inline-flex items-center px-3 py-1 text-xs rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors"
                                            title="Cancel Order">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Cancel
                                    </button>
                                <?php endif; ?>
                                <!-- One-click Reorder -->
                                <a href="<?= \App\Core\View::url('orders/reorder/' . $order['id']) ?>"
                                   class="mt-2 inline-flex items-center px-3 py-1 text-xs rounded-lg bg-primary text-white hover:bg-green-700 transition-colors"
                                   title="Reorder these items">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
</svg>

                                    Reorder
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Custom scrollbar for mobile */
@media (max-width: 1023px) {
    .overflow-x-auto::-webkit-scrollbar {
        height: 4px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
    }
}

/* Status badge animations */
.status-badge {
    transition: all 0.2s ease-in-out;
}

/* Order card hover effects for desktop only */
@media (min-width: 1024px) {
    .order-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
}
</style>

<!-- Cancel Order Bottom Drawer -->
<div id="cancelDrawer" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeCancelDrawer()"></div>
    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl transform transition-transform duration-300 ease-out" id="cancelDrawerContent">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Cancel Order</h3>
                <button onclick="closeCancelDrawer()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="cancelOrderForm" onsubmit="submitCancelOrder(event)">
                <input type="hidden" id="cancelOrderId" name="order_id">
                <div class="mb-4">
                    <label for="cancelReason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for Cancellation <span class="text-red-500">*</span>
                    </label>
                    <textarea id="cancelReason" 
                              name="reason" 
                              rows="4" 
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary resize-none"
                              placeholder="Please provide a reason for cancelling this order..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeCancelDrawer()"
                            class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-3 bg-red-500 text-white rounded-xl font-medium hover:bg-red-600 transition-colors">
                        Submit Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentCancelOrderId = null;

function openCancelDrawer(orderId) {
    currentCancelOrderId = orderId;
    document.getElementById('cancelOrderId').value = orderId;
    document.getElementById('cancelReason').value = '';
    document.getElementById('cancelDrawer').classList.remove('hidden');
    document.getElementById('cancelDrawerContent').classList.remove('translate-y-full');
    document.body.style.overflow = 'hidden';
}

function closeCancelDrawer() {
    document.getElementById('cancelDrawer').classList.add('hidden');
    document.getElementById('cancelDrawerContent').classList.add('translate-y-full');
    document.body.style.overflow = '';
    currentCancelOrderId = null;
}

function submitCancelOrder(event) {
    event.preventDefault();
    
    const orderId = document.getElementById('cancelOrderId').value;
    const reason = document.getElementById('cancelReason').value.trim();
    
    if (!reason) {
        alert('Please provide a reason for cancellation');
        return;
    }
    
    const formData = new FormData();
    formData.append('reason', reason);
    
    fetch('<?= \App\Core\View::url('orders/cancel/') ?>' + orderId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Order cancellation request submitted successfully');
            closeCancelDrawer();
            location.reload();
        } else {
            alert(data.message || 'Failed to cancel order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Orders page loaded');
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>