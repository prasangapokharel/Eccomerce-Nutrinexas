<?php
$page = 'orders';
ob_start();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Assigned Orders</h1>
        <p class="text-gray-600 mt-2">All orders assigned to you for delivery</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex items-center space-x-4 flex-wrap gap-4">
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
            <option value="">All Status</option>
            <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="processing" <?= ($status ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
            <option value="picked_up" <?= ($status ?? '') === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
            <option value="in_transit" <?= ($status ?? '') === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
            <option value="shipped" <?= ($status ?? '') === 'shipped' ? 'selected' : '' ?>>Shipped</option>
            <option value="delivered" <?= ($status ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        
        <select name="sort" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
            <option value="newest" <?= ($sort ?? 'newest') === 'newest' ? 'selected' : '' ?>>Sort by Newest</option>
            <option value="oldest" <?= ($sort ?? '') === 'oldest' ? 'selected' : '' ?>>Sort by Oldest</option>
        </select>
        
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
        
        <a href="<?= \App\Core\View::url('curior/orders') ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
            <i class="fas fa-redo mr-2"></i>Reset
        </a>
    </form>
</div>

<!-- Orders Table -->
<?php if (empty($orders)): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500">
        <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
        <p>No orders found</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
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
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

