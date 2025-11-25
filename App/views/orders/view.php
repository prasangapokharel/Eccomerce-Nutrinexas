<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="<?= URLROOT ?>/orders" class="text-primary hover:text-primary-dark">
            <i class="fas fa-arrow-left mr-2"></i> Back to Orders
        </a>
    </div>
    
    <div class="bg-white rounded-none shadow-md overflow-hidden">
        <!-- Order header and status section remains the same -->
        
        <!-- Order Items Section - Updated with product images -->
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-gray-900">Order Items</h2>
                <div class="text-sm text-gray-500">
                    <?= isset($orderItems) ? count($orderItems) : 0 ?> item(s)
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantity
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!isset($orderItems) || empty($orderItems)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    No items found for this order.
                                </td>
                            </tr>
                        <?php else: ?>
                        <?php foreach ($orderItems as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <!-- Product Image -->
                                        <div class="flex-shrink-0 h-16 w-16 rounded-md overflow-hidden border border-gray-200">
                                            <img src="<?= !empty($item['product_image']) ? $item['product_image'] : \App\Core\View::asset('images/products/default.jpg') ?>" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                 class="h-full w-full object-cover object-center"
                                                 loading="lazy"
                                                 onerror="this.onerror=null;this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                Product ID: <?= $item['product_id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Rs<?= number_format($item['price'], 2) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?= $item['quantity'] ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Rs<?= number_format($item['total'], 2) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <!-- Rest of the table footer remains the same -->
                </table>
            </div>
        </div>
        
        <!-- Additional Actions section -->
        <?php 
        $cancellableStatuses = ['pending', 'confirmed', 'processing', 'unpaid'];
        if (isset($order) && in_array($order['status'], $cancellableStatuses)): 
        ?>
            <div class="p-6 border-t border-gray-200">
                <button onclick="openCancelDrawer(<?= $order['id'] ?>)"
                        class="w-full px-4 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-colors">
                    <i class="fas fa-times mr-2"></i> Cancel Order
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

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
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>