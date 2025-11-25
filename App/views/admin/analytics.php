<?php ob_start(); ?>
<?php
// Analytics data is passed from controller - use defaults if not provided
$revenueStats = $revenueStats ?? ['total_orders' => 0, 'total_revenue' => 0, 'today_revenue' => 0, 'month_revenue' => 0, 'avg_order_value' => 0];
$orderStatusBreakdown = $orderStatusBreakdown ?? [];
$paymentMethodBreakdown = $paymentMethodBreakdown ?? [];
$topProducts = $topProducts ?? [];
$recentOrders = $recentOrders ?? [];
$customerStats = $customerStats ?? ['total_customers' => 0, 'new_today' => 0, 'new_month' => 0];
$salesTrend = $salesTrend ?? [];
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Business insights and performance metrics</p>
        </div>
        <div class="flex items-center gap-3">
            <select id="dateRange" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last Year</option>
            </select>
        </div>
    </div>

    <!-- Revenue Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">
                        Rs<?= number_format($revenueStats['total_revenue'] ?? 0, 2) ?>
                    </h3>
                </div>
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-rupee-sign text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">From <?= $revenueStats['total_orders'] ?? 0 ?> orders</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Today's Revenue</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">
                        Rs<?= number_format($revenueStats['today_revenue'] ?? 0, 2) ?>
                    </h3>
                </div>
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-calendar-day text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Today's sales</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">This Month</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">
                        Rs<?= number_format($revenueStats['month_revenue'] ?? 0, 2) ?>
                    </h3>
                </div>
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                    <i class="fas fa-calendar-alt text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Monthly revenue</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Avg Order Value</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-1">
                        Rs<?= number_format($revenueStats['avg_order_value'] ?? 0, 2) ?>
                    </h3>
                </div>
                <div class="p-3 rounded-xl bg-orange-50 text-orange-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Average per order</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sales Trend Chart -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Trend (Last 7 Days)</h3>
            <div class="h-64 flex items-end justify-between gap-2">
                <?php 
                if (!empty($salesTrend)) {
                    $maxRevenue = max(array_column($salesTrend, 'revenue')) ?: 1;
                    foreach ($salesTrend as $day): 
                        $height = ($day['revenue'] / $maxRevenue) * 100;
                ?>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full bg-primary rounded-t-lg transition-all hover:bg-primary-dark" 
                                 style="height: <?= $height ?>%" 
                                 title="Rs<?= number_format($day['revenue'], 2) ?>">
                            </div>
                            <p class="text-xs text-gray-500 mt-2"><?= date('M j', strtotime($day['date'])) ?></p>
                            <p class="text-xs font-medium text-gray-700"><?= $day['order_count'] ?></p>
                        </div>
                    <?php endforeach; 
                } else { ?>
                    <div class="w-full text-center text-gray-500 py-8">No sales data available</div>
                <?php } ?>
            </div>
        </div>

        <!-- Order Status Breakdown -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Status Breakdown</h3>
            <div class="space-y-3">
                <?php foreach ($orderStatusBreakdown as $status): ?>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700 capitalize"><?= $status['status'] ?></span>
                            <span class="text-sm font-bold text-gray-900"><?= $status['count'] ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" 
                                 style="width: <?= !empty($revenueStats['total_orders']) ? ($status['count'] / $revenueStats['total_orders']) * 100 : 0 ?>%">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Payment Methods & Top Products -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payment Methods -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Methods</h3>
            <div class="space-y-3">
                <?php if (!empty($paymentMethodBreakdown)): ?>
                    <?php foreach ($paymentMethodBreakdown as $method): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($method['name'] ?? 'Unknown') ?></p>
                                <p class="text-sm text-gray-500"><?= $method['count'] ?? 0 ?> orders</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">Rs<?= number_format($method['revenue'] ?? 0, 2) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-gray-500 py-4">No payment method data</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Selling Products</h3>
            <div class="space-y-3">
                <?php foreach ($topProducts as $index => $product): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($product['product_name']) ?></p>
                                <p class="text-sm text-gray-500"><?= $product['total_sold'] ?> sold</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">Rs<?= number_format($product['revenue'] ?? 0, 2) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Customer Stats & Recent Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Customer Statistics -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Statistics</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-600">Total Customers</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $customerStats['total_customers'] ?? 0 ?></p>
                    </div>
                    <i class="fas fa-users text-3xl text-blue-600"></i>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-green-50 rounded-lg">
                        <p class="text-xs text-gray-600">New Today</p>
                        <p class="text-xl font-bold text-gray-900"><?= $customerStats['new_today'] ?? 0 ?></p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-lg">
                        <p class="text-xs text-gray-600">New This Month</p>
                        <p class="text-xl font-bold text-gray-900"><?= $customerStats['new_month'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Orders</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                <?php foreach ($recentOrders as $order): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($order['invoice']) ?></p>
                            <p class="text-sm text-gray-500">
                                <?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-gray-900">Rs<?= number_format($order['total_amount'] ?? 0, 2) ?></p>
                            <p class="text-xs text-gray-500"><?= date('M j, Y', strtotime($order['created_at'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__FILE__) . '/layouts/admin.php'; ?>

