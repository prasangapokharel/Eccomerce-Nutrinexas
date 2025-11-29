<?php ob_start(); ?>
<?php $page = 'analytics'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Analytics & Reports</h1>
            <p class="mt-1 text-sm text-gray-500">Track your sales performance and insights</p>
        </div>
    </div>

    <!-- Period Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex flex-wrap gap-2">
            <a href="?period=day" class="px-4 py-2 rounded-lg text-sm font-medium <?= $period === 'day' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700' ?>">
                Today
            </a>
            <a href="?period=week" class="px-4 py-2 rounded-lg text-sm font-medium <?= $period === 'week' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700' ?>">
                Last 7 Days
            </a>
            <a href="?period=month" class="px-4 py-2 rounded-lg text-sm font-medium <?= $period === 'month' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700' ?>">
                Last 30 Days
            </a>
            <a href="?period=year" class="px-4 py-2 rounded-lg text-sm font-medium <?= $period === 'year' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700' ?>">
                Last Year
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="flex flex-wrap gap-4 lg:gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-dollar-sign text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-gray-900">रु <?= number_format($orderStats['total_revenue'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= number_format($orderStats['total_orders'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                    <i class="fas fa-chart-bar text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Avg Order Value</p>
                    <h3 class="text-2xl font-bold text-gray-900">रु <?= number_format($orderStats['avg_order_value'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-orange-50 text-orange-600">
                    <i class="fas fa-percentage text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Conversion Rate</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= number_format($conversionRate['rate'] ?? 0, 2) ?>%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Traffic Insights -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Traffic Insights</h2>
        <div class="flex flex-wrap gap-4 lg:gap-6">
            <div class="bg-gray-50 rounded-lg p-4 flex-1 min-w-[200px]">
                <p class="text-sm font-medium text-gray-500">Unique Visitors</p>
                <p class="text-xl font-bold text-gray-900 mt-1"><?= number_format($trafficInsights['unique_visitors'] ?? 0) ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 flex-1 min-w-[200px]">
                <p class="text-sm font-medium text-gray-500">Total Visits</p>
                <p class="text-xl font-bold text-gray-900 mt-1"><?= number_format($trafficInsights['total_visits'] ?? 0) ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 flex-1 min-w-[200px]">
                <p class="text-sm font-medium text-gray-500">Bounce Rate</p>
                <p class="text-xl font-bold text-gray-900 mt-1"><?= number_format($trafficInsights['bounce_rate'] ?? 0, 1) ?>%</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 flex-1 min-w-[200px]">
                <p class="text-sm font-medium text-gray-500">Avg Session Duration</p>
                <p class="text-xl font-bold text-gray-900 mt-1"><?= $trafficInsights['avg_session_duration'] ?? '0:00' ?></p>
            </div>
        </div>
    </div>

    <!-- Sales Chart Data (for future chart implementation) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Sales Trend</h2>
        <div class="text-center py-12 text-gray-500">
            <p>Sales data available for chart visualization</p>
            <p class="text-sm mt-2"><?= count($salesData) ?> data points</p>
        </div>
    </div>

    <!-- Best Selling Products -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Best Selling Products</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Units Sold</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($bestSelling)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">No sales data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bestSelling as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($product['product_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= number_format($product['total_sold']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    रु <?= number_format($product['total_revenue'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cancelled & Returned Report -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Cancelled & Returned Items Summary</h2>
            <a href="<?= \App\Core\View::url('seller/reports') ?>" class="text-sm text-primary font-medium">
                View Detailed Report <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="flex flex-wrap gap-4 lg:gap-6">
            <div class="border border-red-200 rounded-xl p-4 bg-red-50 flex-1 min-w-[250px]">
                <h3 class="text-md font-semibold text-red-900 mb-2">Cancelled Orders</h3>
                <p class="text-2xl font-bold text-red-900"><?= number_format($cancelledReturned['cancelled']['count']) ?></p>
                <p class="text-sm text-red-700 mt-1">Total Value: रु <?= number_format($cancelledReturned['cancelled']['total'], 2) ?></p>
            </div>
            <div class="border border-orange-200 rounded-xl p-4 bg-orange-50 flex-1 min-w-[250px]">
                <h3 class="text-md font-semibold text-orange-900 mb-2">Returned Orders</h3>
                <p class="text-2xl font-bold text-orange-900"><?= number_format($cancelledReturned['returned']['count']) ?></p>
                <p class="text-sm text-orange-700 mt-1">Total Value: रु <?= number_format($cancelledReturned['returned']['total'], 2) ?></p>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
