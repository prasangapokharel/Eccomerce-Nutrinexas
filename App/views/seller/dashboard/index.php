<?php ob_start(); ?>
<?php $page = 'dashboard'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Seller Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening with your store today.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="flex flex-wrap gap-4 lg:gap-6">
        <!-- Total Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_orders'] ?? 0) ?></h3>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Pending Orders</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= number_format($stats['pending_orders'] ?? 0) ?></h3>
                </div>
            </div>
        </div>

        <!-- Completed Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Completed Orders</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= number_format($stats['completed_orders'] ?? 0) ?></h3>
                </div>
            </div>
        </div>

        <!-- Total Earnings -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Earnings</p>
                    <h3 class="text-2xl font-bold text-gray-900">रु <?= number_format($stats['total_earnings'] ?? $stats['total_revenue'] ?? 0, 0) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="flex flex-wrap gap-4 lg:gap-6">
        <!-- Wallet Balance -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Wallet Balance</p>
                    <h3 class="text-xl font-bold text-gray-900">रु <?= number_format($stats['wallet_balance'] ?? 0, 0) ?></h3>
                </div>
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-coins text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Products Count -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Products Count</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= number_format($stats['products_count'] ?? $stats['total_products'] ?? 0) ?></h3>
                </div>
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                    <i class="fas fa-boxes text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-gradient-to-r from-primary to-primary-dark rounded-xl shadow-sm p-6 text-white flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-white/80">Quick Actions</p>
                    <h3 class="text-lg font-bold">Manage Store</h3>
                </div>
                <div class="p-3 rounded-xl bg-white/20">
                    <i class="fas fa-bolt text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="flex flex-col xl:flex-row gap-6">
        <!-- Recent Orders - Takes 2 columns -->
        <div class="xl:flex-[2] bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                    <a href="<?= \App\Core\View::url('seller/orders') ?>" class="text-sm text-primary hover:text-primary-dark font-medium">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-hidden">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-shopping-cart text-3xl text-gray-300 mb-2"></i>
                                    <p>No orders found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">#<?= htmlspecialchars($order['invoice'] ?? $order['id']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($order['customer_name'] ?? ($order['first_name'] . ' ' . $order['last_name']) ?? 'Unknown Customer') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">रु <?= number_format($order['total_amount'] ?? $order['seller_total'] ?? 0, 2) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php
                                            switch ($order['status']) {
                                                case 'delivered':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                case 'processing':
                                                case 'confirmed':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                case 'shipped':
                                                    echo 'bg-purple-100 text-purple-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="<?= \App\Core\View::url('seller/orders/detail/' . $order['id']) ?>" class="text-primary hover:text-primary-dark">
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
        
        <!-- Top Products - Takes 1 column -->
        <div class="xl:flex-1 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Top Products</h2>
                    <a href="<?= \App\Core\View::url('seller/products') ?>" class="text-sm text-primary hover:text-primary-dark font-medium">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <div class="p-6">
                <?php if (empty($topProducts)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-box text-3xl text-gray-300 mb-2"></i>
                        <p class="text-gray-500">No sales data available</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($topProducts as $product): ?>
                            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <?= htmlspecialchars($product['product_name'] ?? 'N/A') ?>
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        <?= number_format($product['total_sold'] ?? 0) ?> sold
                                    </p>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <p class="text-sm font-semibold text-gray-900">रु <?= number_format($product['total_revenue'] ?? 0, 0) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
