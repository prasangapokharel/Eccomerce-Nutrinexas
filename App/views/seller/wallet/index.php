<?php ob_start(); ?>
<?php $page = 'wallet'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">My Wallet</h1>
    </div>

    <!-- Wallet Balance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl shadow border border-primary/10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Available Balance</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">रु <?= number_format($wallet['balance'] ?? 0, 2) ?></p>
                </div>
                <div class="p-3 bg-primary/10 rounded-xl">
                    <i class="fas fa-wallet text-primary text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow border border-primary/10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Earnings</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">रु <?= number_format($wallet['total_earnings'] ?? 0, 2) ?></p>
                </div>
                <div class="p-3 bg-accent/10 rounded-xl">
                    <i class="fas fa-chart-line text-accent text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow border border-primary/10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Withdrawals</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">रु <?= number_format($wallet['total_withdrawals'] ?? 0, 2) ?></p>
                </div>
                <div class="p-3 bg-primary/10 rounded-xl">
                    <i class="fas fa-money-bill-wave text-primary text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow border border-primary/10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending Withdrawals</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">रु <?= number_format($wallet['pending_withdrawals'] ?? 0, 2) ?></p>
                </div>
                <div class="p-3 bg-accent/10 rounded-xl">
                    <i class="fas fa-clock text-accent text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col md:flex-row gap-3">
        <a href="<?= \App\Core\View::url('seller/withdraw-requests/create') ?>" class="btn flex items-center justify-center gap-2 bg-primary text-white rounded-full px-6 py-3 font-semibold shadow hover:bg-primary/90 transition">
            <i class="fas fa-money-bill-wave"></i>
            Request Withdrawal
        </a>
        <a href="<?= \App\Core\View::url('seller/wallet/transactions') ?>" class="btn flex items-center justify-center gap-2 bg-accent text-white rounded-full px-6 py-3 font-semibold shadow hover:bg-accent/90 transition">
            <i class="fas fa-list"></i>
            View All Transactions
        </a>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-2xl shadow border border-primary/10 overflow-hidden">
        <div class="px-6 py-4 border-b border-primary/10 bg-primary/5">
            <h2 class="text-xl font-semibold text-gray-900">Recent Transactions</h2>
        </div>
        
        <?php if (empty($transactions)): ?>
            <div class="text-center py-12">
                <i class="fas fa-exchange-alt text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions yet</h3>
                <p class="text-gray-500">Transactions will appear here once you receive payments</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-primary/10">
                    <thead class="bg-primary/5">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance After</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-primary/5">
                        <?php foreach (array_slice($transactions, 0, 10) as $transaction): ?>
                            <tr class="hover:bg-primary/5">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('M j, Y g:i A', strtotime($transaction['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $transaction['type'] === 'credit' ? 'bg-primary/10 text-primary' : 'bg-accent/10 text-accent' ?>">
                                        <?= ucfirst($transaction['type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= htmlspecialchars($transaction['description'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold <?= $transaction['type'] === 'credit' ? 'text-primary' : 'text-accent' ?>">
                                    <?= $transaction['type'] === 'credit' ? '+' : '-' ?>रु <?= number_format($transaction['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    रु <?= number_format($transaction['balance_after'] ?? 0, 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?= $transaction['status'] === 'completed'
                                            ? 'bg-primary/10 text-primary'
                                            : ($transaction['status'] === 'pending'
                                                ? 'bg-accent/10 text-accent'
                                                : 'bg-destructive/10 text-destructive') ?>">
                                        <?= ucfirst($transaction['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

