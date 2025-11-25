<?php
$page = 'dashboard';
ob_start();
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calendar-day text-primary text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Today's Orders</dt>
                        <dd class="text-lg font-medium text-gray-900"><?= count($todayOrders ?? []) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-truck-loading text-warning text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Pickups</dt>
                        <dd class="text-lg font-medium text-gray-900"><?= count($pendingPickups ?? []) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-shipping-fast text-primary text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                        <dd class="text-lg font-medium text-gray-900"><?= count($inProgress ?? []) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-accent text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                        <dd class="text-lg font-medium text-gray-900"><?= count($completed ?? []) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- COD Pending Card -->
<?php if (($codPending ?? 0) > 0): ?>
<div class="bg-gradient-to-r from-primary to-accent rounded-lg shadow-lg p-6 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-white/90 text-sm mb-1">COD Pending Collection</p>
            <p class="text-3xl font-bold">Rs <?= number_format($codPending, 2) ?></p>
        </div>
        <div class="bg-white/20 rounded-full p-4">
            <i class="fas fa-money-bill-wave text-3xl"></i>
        </div>
    </div>
    <a href="<?= \App\Core\View::url('curior/settlements') ?>" class="mt-4 inline-block text-sm underline hover:text-white/90">
        View Settlements →
    </a>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <a href="<?= \App\Core\View::url('curior/pickup') ?>" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-warning-50 rounded-full p-3 mr-4">
                <i class="fas fa-box-open text-warning text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Pickup Management</h3>
                <p class="text-sm text-gray-500"><?= count($pendingPickups ?? []) ?> orders waiting</p>
            </div>
        </div>
    </a>
    
    <a href="<?= \App\Core\View::url('curior/orders') ?>" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-primary-50 rounded-full p-3 mr-4">
                <i class="fas fa-list text-primary text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">All Orders</h3>
                <p class="text-sm text-gray-500">View all assigned orders</p>
            </div>
        </div>
    </a>
    
    <a href="<?= \App\Core\View::url('curior/returns') ?>" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-error-50 rounded-full p-3 mr-4">
                <i class="fas fa-undo text-error text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Returns & RTO</h3>
                <p class="text-sm text-gray-500">Manage returns</p>
            </div>
        </div>
    </a>
</div>

<!-- Orders Table -->
<div class="bg-white shadow overflow-hidden sm:rounded-md" id="orders">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Your Orders</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Orders assigned to you for delivery</p>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No orders assigned</h3>
            <p class="text-gray-500">You don't have any orders assigned to you at the moment.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 data-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?= $order['id'] ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($order['customer_name'] ?? $order['order_customer_name'] ?? 'N/A') ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rs <?= number_format($order['total_amount'] ?? 0, 2) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $order['status'] === 'delivered' ? 'bg-accent/20 text-accent-dark' : 
                                       ($order['status'] === 'shipped' || $order['status'] === 'in_transit' ? 'bg-primary-50 text-primary-700' : 
                                       ($order['status'] === 'dispatched' || $order['status'] === 'picked_up' ? 'bg-primary-50 text-primary-700' : 
                                       ($order['status'] === 'processing' ? 'bg-warning-50 text-warning-dark' : 'bg-gray-100 text-gray-800'))) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2 items-center">
                                    <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
                                       class="text-primary hover:text-primary-dark transition-colors p-1 rounded hover:bg-primary-50" 
                                       title="View Order">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= \App\Core\View::url('receipt/previewReceipt/' . $order['id']) ?>" 
                                       target="_blank"
                                       class="text-accent hover:text-accent-dark transition-colors p-1 rounded hover:bg-accent/10" 
                                       title="View Receipt">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

