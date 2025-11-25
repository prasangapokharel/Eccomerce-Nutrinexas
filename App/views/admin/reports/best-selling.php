<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Best Selling Products</h1>
            <p class="text-gray-600 mt-2">Track your top-performing products</p>
        </div>
        <div class="flex items-center space-x-4">
            <select id="periodFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>All Time</option>
                <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>This Week</option>
                <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>This Month</option>
                <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>This Year</option>
            </select>
        </div>
    </div>

    <?php if (!empty($products)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Top <?= $limit ?> Products</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Sold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $index => $product): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full <?= $index < 3 ? 'bg-yellow-100 text-yellow-800 font-bold' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $index + 1 ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['product_name']) ?></div>
                                    <div class="text-sm text-gray-500">Rs <?= number_format($product['product_price'] ?? 0, 2) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="font-semibold"><?= number_format($product['total_sold']) ?></span> units
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="font-semibold text-green-600">Rs <?= number_format($product['total_revenue'], 2) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($product['order_count']) ?> orders
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-chart-line text-6xl text-gray-300 mb-4"></i>
            <p class="text-lg font-medium text-gray-900">No sales data available</p>
            <p class="text-sm text-gray-500 mt-2">Sales data will appear here once orders are placed</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('periodFilter').addEventListener('change', function() {
    const period = this.value;
    window.location.href = '<?= \App\Core\View::url('admin/reports/best-selling') ?>?period=' + period;
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>


