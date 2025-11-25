<?php ob_start(); ?>

<div class="min-h-screen bg-gray-50">
    <div class="px-3 py-3">
        <!-- Flash Messages -->
        <?php 
        $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
        if ($flashMessage): ?>
            <div class="bg-green-50 border-l-4 border-green-400 text-green-700 px-3 py-2 rounded text-sm mb-3">
                <?= $flashMessage ?>
            </div>
        <?php endif; ?>

        <?php 
        $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
        if ($flashError): ?>
            <div class="bg-red-50 border-l-4 border-red-400 text-red-700 px-3 py-2 rounded text-sm mb-3">
                <?= $flashError ?>
            </div>
        <?php endif; ?>

         <!-- Header -->
         <div class="flex items-center justify-between mb-4">
             <div class="flex items-center">
                 <img src="https://via.placeholder.com/40x40/0A3167/FFFFFF?text=NX" 
                      alt="NutriNexus" class="h-8 w-8 mr-3 rounded-full">
                 <h1 class="text-lg font-medium text-gray-900">Parcel Packing</h1>
             </div>
             <a href="<?= \App\Core\View::url('staff/logout') ?>" 
                class="text-red-600 text-sm hover:text-red-700">
                 Logout
             </a>
         </div>

         <!-- Statistics Cards -->
         <div class="grid grid-cols-3 gap-2 mb-4">
             <div class="bg-white border border-gray-200 rounded-lg p-3 text-center">
                 <div class="text-lg font-semibold text-gray-900"><?= $stats['total_orders'] ?? 0 ?></div>
                 <div class="text-xs text-gray-500">Total</div>
             </div>
             <div class="bg-white border border-gray-200 rounded-lg p-3 text-center">
                 <div class="text-lg font-semibold text-amber-600 to-package-count"><?= $stats['to_package_orders'] ?? 0 ?></div>
                 <div class="text-xs text-gray-500">To Package</div>
             </div>
             <div class="bg-white border border-gray-200 rounded-lg p-3 text-center">
                 <div class="text-lg font-semibold text-green-600 packaged-count"><?= $stats['packaged_orders'] ?? 0 ?></div>
                 <div class="text-xs text-gray-500">Packaged</div>
             </div>
         </div>

        <!-- Orders Section -->
        <div class="bg-white rounded-lg shadow-sm border-0">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-base font-medium text-gray-900">Orders to Pack</h2>
            </div>
            
            <?php if (empty($orders)): ?>
                <div class="text-center py-8">
                    <div class="text-gray-400 text-3xl mb-3">📦</div>
                    <h3 class="text-base font-medium text-gray-900 mb-1">No orders assigned</h3>
                    <p class="text-sm text-gray-500">All caught up! No orders to pack right now.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($orders as $order): ?>
                        <div class="p-4" data-order-id="<?= $order['id'] ?>">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-3">
                                    <div class="cursor-pointer" onclick="showOrderDetails(<?= $order['id'] ?>)">
                                        <div class="text-sm font-medium text-gray-900 hover:text-blue-600">#<?= $order['id'] ?></div>
                                        <div class="text-xs text-gray-500"><?= date('M d, H:i', strtotime($order['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="text-right cursor-pointer" onclick="showOrderDetails(<?= $order['id'] ?>)">
                                    <div class="text-sm font-medium text-gray-900 hover:text-blue-600">₹<?= number_format($order['total_amount'], 0) ?></div>
                                    <div class="text-xs text-gray-500 hover:text-blue-600"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <!-- Payment Status -->
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                        <?php
                                        if ($order['payment_status'] === 'paid') {
                                            echo 'bg-green-100 text-green-700';
                                        } else {
                                            echo 'bg-amber-100 text-amber-700';
                                        }
                                        ?>">
                                        <?= $order['payment_status'] === 'paid' ? 'Paid' : 'Pending Payment' ?>
                                    </span>
                                    
                                    <!-- Package Status -->
                                    <span class="package-status inline-flex px-2 py-1 text-xs font-medium rounded-full
                                        <?php
                                        if ($order['packaged_count'] > 0) {
                                            echo 'bg-orange-100 text-orange-700';
                                        } else {
                                            echo 'bg-gray-100 text-gray-700';
                                        }
                                        ?>">
                                        <?= $order['packaged_count'] > 0 ? 'Packaged' : 'To Package' ?>
                                    </span>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <?php if ($order['packaged_count'] == 0): ?>
                                        <form method="POST" action="<?= \App\Core\View::url('staff/updateOrderStatus') ?>" class="inline">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <input type="hidden" name="status" value="processing">
                                            <input type="hidden" name="action" value="packaging">
                                            <input type="hidden" name="package_count" value="1">
                                            <button type="submit" 
                                                    class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                                <i class="fas fa-box mr-2"></i>Package
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="px-4 py-2 text-sm font-medium bg-orange-100 text-orange-700 rounded-lg">
                                            <i class="fas fa-check mr-2"></i>Packaged
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- No modal needed - using simple page redirect -->

<!-- Flash messages handle notifications -->

<script>
// Simple form submission - no AJAX needed

// Simple order details - no AJAX
function showOrderDetails(orderId) {
    // Redirect to order details page
    window.location.href = '<?= \App\Core\View::url('staff/order') ?>/' + orderId;
}

// No modal functions needed

// Flash messages handle notifications - no need for toast functions

// Form submission handles everything - no need for AJAX functions

// Initialize order count for this dashboard
window.lastOrderCount = <?= count($orders) ?>;
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/staff.php'; ?>