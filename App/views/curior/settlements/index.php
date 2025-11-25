<?php
$page = 'settlement';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">COD Settlements</h1>
    <p class="text-gray-600 mt-2">Manage your COD collections and settlements</p>
</div>

<!-- Summary Card -->
<div class="bg-gradient-to-r from-primary to-accent rounded-2xl shadow-lg p-6 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-white/90 text-sm mb-1">Total COD Collected</p>
            <p class="text-3xl font-bold">Rs <?= number_format($totalCollected ?? 0, 2) ?></p>
        </div>
        <div class="bg-white/20 rounded-full p-4">
            <i class="fas fa-money-bill-wave text-3xl"></i>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="flex items-center space-x-4">
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
            <option value="">All Status</option>
            <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="collected" <?= ($status ?? '') === 'collected' ? 'selected' : '' ?>>Collected</option>
            <option value="settled" <?= ($status ?? '') === 'settled' ? 'selected' : '' ?>>Settled</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
    </form>
</div>

<!-- Settlements Table -->
<?php if (empty($settlements)): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500">
        <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
        <p>No settlements found</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 data-table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">COD Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collected At</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($settlements as $settlement): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?= $settlement['order_id'] ?>
                                <?php if (!empty($settlement['invoice'])): ?>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($settlement['invoice']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($settlement['customer_name'] ?? 'N/A') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                Rs <?= number_format($settlement['cod_amount'] ?? 0, 2) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?= $settlement['status'] === 'collected' ? 'bg-accent/20 text-accent-dark' : 
                                       ($settlement['status'] === 'settled' ? 'bg-primary-50 text-primary-700' : 
                                       'bg-warning-50 text-warning-dark') ?>">
                                    <?= ucfirst($settlement['status'] ?? 'pending') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= $settlement['collected_at'] ? date('M d, Y H:i', strtotime($settlement['collected_at'])) : 'N/A' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="<?= \App\Core\View::url('curior/order/view/' . $settlement['order_id']) ?>" 
                                   class="text-primary hover:text-primary-dark transition-colors">
                                    <i class="fas fa-eye"></i> View Order
                                </a>
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

