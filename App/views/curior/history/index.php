<?php
$page = 'history';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Order History & Logs</h1>
            <p class="mt-1 text-sm text-gray-500">View all completed orders, attempts, and pickups</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom ?? '') ?>" 
                       class="input native-input">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo ?? '') ?>" 
                       class="input native-input">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="input native-input">
                    <option value="">All Status</option>
                    <option value="delivered" <?= (isset($status) && $status === 'delivered') ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= (isset($status) && $status === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    <option value="returned" <?= (isset($status) && $status === 'returned') ? 'selected' : '' ?>>Returned</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                <select name="action" class="input native-input">
                    <option value="">All Actions</option>
                    <option value="pickup_confirmed" <?= (isset($action) && $action === 'pickup_confirmed') ? 'selected' : '' ?>>Pickup Confirmed</option>
                    <option value="delivery_attempted" <?= (isset($action) && $action === 'delivery_attempted') ? 'selected' : '' ?>>Delivery Attempted</option>
                    <option value="delivered" <?= (isset($action) && $action === 'delivered') ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
            
            <div class="md:col-span-4 flex justify-end space-x-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="<?= \App\Core\View::url('curior/history') ?>" class="btn btn-outline">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Orders History Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Completed Orders</h2>
        </div>
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
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
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
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                                    <p class="text-gray-500">Completed orders will appear here.</p>
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
                                    <div class="text-sm font-medium text-gray-900">
                                        रु <?= number_format($order['total_amount'] ?? 0, 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusBadges = [
                                        'delivered' => 'bg-accent/20 text-accent-dark',
                                        'cancelled' => 'bg-error-50 text-error-dark',
                                        'returned' => 'bg-warning-50 text-warning-dark'
                                    ];
                                    $statusBadge = $statusBadges[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
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
                                    <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
                                       class="text-primary hover:text-primary-dark transition-colors"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Activity Logs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Activity Logs</h2>
        </div>
        <div class="divide-y divide-gray-100">
            <?php if (empty($activities)): ?>
                <div class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No activity logs found</h3>
                        <p class="text-gray-500">Activity logs will appear here.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-circle text-xs text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= ucfirst(str_replace('_', ' ', $activity['action'])) ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Order #<?= $activity['order_id'] ?? 'N/A' ?>
                                            <?php if (!empty($activity['invoice'])): ?>
                                                - <?= htmlspecialchars($activity['invoice']) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= date('M d, Y H:i', strtotime($activity['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
