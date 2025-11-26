<?php
$page = 'settlement';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">COD Settlements</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your COD collections and settlements</p>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="bg-gradient-to-r from-primary to-accent rounded-2xl shadow-lg p-6 text-white">
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

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header with Filters -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">Settlements</h2>
                </div>
                
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap gap-2">
                    <a href="<?= \App\Core\View::url('curior/settlements') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= !isset($status) || $status === '' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        All Status
                    </a>
                    <a href="<?= \App\Core\View::url('curior/settlements?status=pending') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Pending
                    </a>
                    <a href="<?= \App\Core\View::url('curior/settlements?status=collected') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'collected' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Collected
                    </a>
                    <a href="<?= \App\Core\View::url('curior/settlements?status=settled') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'settled' ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Settled
                    </a>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto -mx-4 px-4">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Order Details
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            COD Amount
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Collected At
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($settlements)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No settlements found</h3>
                                    <p class="text-gray-500">Settlements will appear here once COD orders are collected.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($settlements as $settlement): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center">
                                                <i class="fas fa-receipt text-primary text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                #<?= htmlspecialchars($settlement['invoice'] ?? $settlement['order_id']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Order ID: <?= $settlement['order_id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($settlement['customer_name'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        रु <?= number_format($settlement['cod_amount'] ?? 0, 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusBadges = [
                                        'pending' => 'bg-warning-50 text-warning-dark',
                                        'collected' => 'bg-accent/20 text-accent-dark',
                                        'settled' => 'bg-primary-50 text-primary-700'
                                    ];
                                    $statusBadge = $statusBadges[$settlement['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>">
                                        <?= ucfirst($settlement['status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= $settlement['collected_at'] ? date('M j, Y', strtotime($settlement['collected_at'])) : 'N/A' ?>
                                    </div>
                                    <?php if ($settlement['collected_at']): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= date('g:i A', strtotime($settlement['collected_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?= \App\Core\View::url('curior/order/view/' . $settlement['order_id']) ?>" 
                                       class="text-primary hover:text-primary-dark transition-colors"
                                       title="View Order">
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
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
