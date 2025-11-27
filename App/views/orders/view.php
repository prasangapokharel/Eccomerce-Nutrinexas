<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="<?= \App\Core\View::url('orders') ?>" class="inline-flex items-center gap-2 text-primary hover:text-primary-dark font-medium">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Orders
        </a>
    </div>
    
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-neutral-200">
        <!-- Order header and status section remains the same -->
        
        <!-- Order Items Section - Updated with product images -->
        <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-foreground">Order Items</h2>
                <div class="text-sm text-neutral-500">
                    <?= isset($orderItems) ? count($orderItems) : 0 ?> item(s)
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Quantity
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        <?php if (!isset($orderItems) || empty($orderItems)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-neutral-500">
                                    No items found for this order.
                                </td>
                            </tr>
                        <?php else: ?>
                        <?php foreach ($orderItems as $item): ?>
                            <tr class="hover:bg-neutral-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <!-- Product Image -->
                                        <div class="flex-shrink-0 h-16 w-16 rounded-lg overflow-hidden border border-neutral-200 bg-neutral-100">
                                            <?php 
                                            $imageUrl = !empty($item['image_url']) ? $item['image_url'] : (!empty($item['product_image']) ? $item['product_image'] : \App\Core\View::asset('images/products/default.jpg'));
                                            if (!filter_var($imageUrl, FILTER_VALIDATE_URL) && strpos($imageUrl, '/') !== 0) {
                                                $imageUrl = \App\Core\View::asset('uploads/images/' . $imageUrl);
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                 class="h-full w-full object-cover object-center"
                                                 loading="lazy"
                                                 onerror="this.onerror=null;this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-foreground">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </div>
                                            <div class="text-sm text-neutral-500">
                                                Product ID: <?= $item['product_id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-foreground">रु<?= number_format($item['price'], 2) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-foreground">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-800">
                                            <?= $item['quantity'] ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-foreground">रु<?= number_format($item['total'], 2) ?></div>
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
            <div class="p-6 border-t border-neutral-200">
                <button onclick="openCancelDrawer(<?= $order['id'] ?>)"
                        class="w-full bg-error text-white px-4 py-2.5 rounded-2xl font-medium hover:bg-error-dark flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel Order
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
                <h3 class="text-lg font-semibold text-foreground">Cancel Order</h3>
                <button onclick="closeCancelDrawer()" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="cancelOrderForm" onsubmit="submitCancelOrder(event)">
                <input type="hidden" id="cancelOrderId" name="order_id">
                <div class="mb-4">
                    <label for="cancelReason" class="block text-sm font-medium text-foreground mb-2">
                        Reason for Cancellation <span class="text-error">*</span>
                    </label>
                    <textarea id="cancelReason" 
                              name="reason" 
                              rows="4" 
                              required
                              class="input w-full px-4 py-3 rounded-2xl resize-none"
                              placeholder="Please provide a reason for cancelling this order..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeCancelDrawer()"
                            class="flex-1 border border-primary text-primary bg-transparent px-4 py-2.5 rounded-2xl font-medium hover:bg-primary/10">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-error text-white px-4 py-2.5 rounded-2xl font-medium hover:bg-error-dark">
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