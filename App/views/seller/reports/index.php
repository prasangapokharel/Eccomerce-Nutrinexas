<?php ob_start(); ?>
<?php $page = 'reports'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Cancelled & Returned Items Report</h1>
        <p class="text-gray-600">Track cancelled and returned orders for your products</p>
    </div>

    <!-- Period Filter -->
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label for="period" class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                <select id="period" name="period" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="7" <?= $period == '7' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="30" <?= $period == '30' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="90" <?= $period == '90' ? 'selected' : '' ?>>Last 90 Days</option>
                    <option value="365" <?= $period == '365' ? 'selected' : '' ?>>Last Year</option>
                </select>
            </div>
            <div class="flex-1">
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Filter Type</label>
                <select id="type" name="type" onchange="this.form.submit()" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="all" <?= $type == 'all' ? 'selected' : '' ?>>All Items</option>
                    <option value="cancelled" <?= $type == 'cancelled' ? 'selected' : '' ?>>Cancelled Only</option>
                    <option value="returned" <?= $type == 'returned' ? 'selected' : '' ?>>Returned Only</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Cancelled Items</p>
                    <p class="text-3xl font-bold text-red-600 mt-2"><?= number_format($summary['cancelled']['count']) ?></p>
                    <p class="text-sm text-gray-500 mt-1">Total Value: रु <?= number_format($summary['cancelled']['total_value'], 2) ?></p>
                </div>
                <div class="text-red-500 text-4xl">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Returned Items</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2"><?= number_format($summary['returned']['count']) ?></p>
                    <p class="text-sm text-gray-500 mt-1">Total Value: रु <?= number_format($summary['returned']['total_value'], 2) ?></p>
                </div>
                <div class="text-orange-500 text-4xl">
                    <i class="fas fa-undo"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancelled Items Table -->
    <?php if ($type == 'all' || $type == 'cancelled'): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Cancelled Items</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cancelled Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($cancelledItems)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                No cancelled items found for the selected period
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cancelledItems as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#<?= htmlspecialchars($item['invoice']) ?></div>
                                    <div class="text-xs text-gray-500"><?= date('M j, Y', strtotime($item['order_date'])) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($item['product_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($item['customer_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($item['customer_email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= $item['quantity'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    रु <?= number_format($item['item_total'], 2) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($item['cancel_reason'] ?? 'N/A') ?>">
                                        <?= htmlspecialchars($item['cancel_reason'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= $item['cancelled_at'] ? date('M j, Y H:i', strtotime($item['cancelled_at'])) : '-' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                        echo match($item['cancel_status'] ?? 'processing') {
                                            'refunded' => 'bg-green-100 text-green-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                            default => 'bg-yellow-100 text-yellow-800'
                                        };
                                    ?>">
                                        <?= ucfirst($item['cancel_status'] ?? 'processing') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Returned Items Table -->
    <?php if ($type == 'all' || $type == 'returned'): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Returned Items</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Returned Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($returnedItems)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No returned items found for the selected period
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($returnedItems as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#<?= htmlspecialchars($item['invoice']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($item['product_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($item['customer_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($item['customer_email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= $item['quantity'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    रु <?= number_format($item['item_total'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= date('M j, Y', strtotime($item['order_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= $item['returned_at'] ? date('M j, Y H:i', strtotime($item['returned_at'])) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>


