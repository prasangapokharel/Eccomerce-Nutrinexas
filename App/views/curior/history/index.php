<?php
$page = 'history';
ob_start();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Order History & Logs</h1>
        <p class="text-gray-600 mt-2">View all completed orders, attempts, and pickups</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="">All Status</option>
                <option value="delivered" <?= ($status ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                <option value="returned" <?= ($status ?? '') === 'returned' ? 'selected' : '' ?>>Returned</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
            <select name="action" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="">All Actions</option>
                <option value="pickup_confirmed" <?= ($action ?? '') === 'pickup_confirmed' ? 'selected' : '' ?>>Pickup Confirmed</option>
                <option value="delivery_attempted" <?= ($action ?? '') === 'delivery_attempted' ? 'selected' : '' ?>>Delivery Attempted</option>
                <option value="delivered" <?= ($action ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
            </select>
        </div>
        
        <div class="md:col-span-4 flex justify-end space-x-3">
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
            <a href="<?= \App\Core\View::url('curior/history') ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-redo mr-2"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Orders History -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Completed Orders</h2>
    </div>
    <?php if (empty($orders)): ?>
        <div class="px-6 py-12 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
            <p>No orders found</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 data-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= $order['id'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">Rs <?= number_format($order['total_amount'] ?? 0, 2) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs font-semibold rounded-full
                                    <?= $order['status'] === 'delivered' ? 'bg-accent/20 text-accent-dark' : 
                                       ($order['status'] === 'cancelled' ? 'bg-error-50 text-error-dark' : 
                                       'bg-gray-100 text-gray-800') ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Activity Logs -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Activity Logs</h2>
    </div>
    <div class="divide-y divide-gray-200">
        <?php if (empty($activities)): ?>
            <div class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-history text-4xl mb-4 text-gray-300"></i>
                <p>No activity logs found</p>
            </div>
        <?php else: ?>
            <?php foreach ($activities as $activity): ?>
                <div class="px-6 py-4">
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

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

