<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening with your store today.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fas fa-plus mr-2"></i>
                Quick Actions
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
        <!-- Total Products -->
        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-box text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Products</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= number_format($totalProducts) ?></h3>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +12% from last month
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= number_format($totalOrders) ?></h3>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +8% from last month
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Users</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= number_format($totalUsers) ?></h3>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +15% from last month
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Sales -->
        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600">
                    <i class="fas fa-rupee-sign text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Sales</p>
                    <h3 class="text-2xl font-bold text-gray-900">Rs<?= number_format($totalSales, 0) ?></h3>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-arrow-up mr-1"></i>
                        +23% from last month
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        <!-- Total Coupons -->
        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Coupons</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= number_format($totalCoupons ?? 0) ?></h3>
                </div>
                <div class="p-3 rounded-xl bg-orange-50 text-orange-600">
                    <i class="fas fa-tags text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Active Coupons -->
        <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Coupons</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= number_format($activeCoupons ?? 0) ?></h3>
                </div>
                <div class="p-3 rounded-xl bg-teal-50 text-teal-600">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-gradient-to-r from-primary to-primary-dark rounded-xl shadow-sm p-6 text-white">
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
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Recent Orders - Takes 2 columns -->
        <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                    <a href="<?= \App\Core\View::url('admin/orders') ?>" class="text-sm text-primary hover:text-primary-dark font-medium">
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
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= $order['invoice'] ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">Rs<?= number_format($order['total_amount'], 2) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php
                                            switch ($order['status']) {
                                                case 'paid':
                                                case 'delivered':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'unpaid':
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                case 'processing':
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
                                        <a href="<?= \App\Core\View::url('admin/viewOrder/' . $order['id']) ?>" class="text-primary hover:text-primary-dark">
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
        
        <!-- Low Stock Products - Takes 1 column -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Low Stock Alert</h2>
                    <a href="<?= \App\Core\View::url('admin/products') ?>" class="text-sm text-primary hover:text-primary-dark font-medium">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <div class="p-6">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-3xl text-green-300 mb-2"></i>
                        <p class="text-gray-500">All products are well stocked!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="flex items-center space-x-3 p-3 bg-red-50 rounded-lg border border-red-100">
                           
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <?= htmlspecialchars($product['product_name']) ?>
                                    </p>
                                    <p class="text-xs text-red-600">
                                        Only <?= $product['stock_quantity'] ?> left in stock
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="<?= \App\Core\View::url('admin/editProduct/' . $product['id']) ?>" 
                                       class="text-xs bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">
                                        Restock
                                    </a>
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
<?php include dirname(dirname(__FILE__)) . '/admin/layouts/admin.php'; ?>