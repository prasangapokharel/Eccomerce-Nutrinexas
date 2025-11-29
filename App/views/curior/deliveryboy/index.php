<?php
$page = 'dashboard';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Delivery Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Manage deliveries for <?= htmlspecialchars($city ?? 'your city') ?></p>
        </div>
        <div class="flex gap-3">
            <a href="<?= \App\Core\View::url('deliveryboy/pickup') ?>" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark flex items-center gap-2">
                <i class="fas fa-box"></i>
                <span>Pickup Orders</span>
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending Deliveries</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($stats['pending_deliveries'] ?? 0) ?></p>
                </div>
                <div class="bg-orange-100 p-4 rounded-lg">
                    <i class="fas fa-clock text-orange-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Delivered</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($stats['delivered_count'] ?? 0) ?></p>
                </div>
                <div class="bg-green-100 p-4 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-green-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= number_format($stats['total_orders'] ?? 0) ?></p>
                </div>
                <div class="bg-blue-100 p-4 rounded-lg">
                    <i class="fas fa-shopping-cart text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Active Orders</h2>
            <p class="text-sm text-gray-500 mt-1">Orders ready for delivery</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#<?= $order['id'] ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($order['invoice'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?: $order['order_customer_name'] ?? 'Guest') ?>
                                    </div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($order['customer_email'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= number_format($order['item_count'] ?? 0) ?> items
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">रु <?= number_format($order['order_total'] ?? 0, 2) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $order['status'] ?? 'pending';
                                    $statusColors = [
                                        'confirmed' => 'bg-yellow-100 text-yellow-800',
                                        'ready_for_pickup' => 'bg-blue-100 text-blue-800',
                                        'picked_up' => 'bg-purple-100 text-purple-800',
                                        'in_transit' => 'bg-indigo-100 text-indigo-800',
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($order['created_at'])) ?><br>
                                    <span class="text-xs"><?= date('g:i A', strtotime($order['created_at'])) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <a href="<?= \App\Core\View::url('deliveryboy/order/' . $order['id']) ?>" 
                                           class="text-primary hover:text-primary-dark">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                            <form action="<?= \App\Core\View::url('deliveryboy/deliver/' . $order['id']) ?>" 
                                                  method="POST" 
                                                  class="inline"
                                                  onsubmit="return confirm('Mark this order as delivered?')">
                                                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                                                <button type="submit" 
                                                        class="text-green-600 hover:text-green-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-sm text-gray-500">No orders found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include ROOT_DIR . '/App/views/curior/layouts/main.php';
?>

