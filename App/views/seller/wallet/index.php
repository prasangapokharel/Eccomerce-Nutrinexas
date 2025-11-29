<?php ob_start(); ?>
<?php $page = 'wallet'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">My Wallet</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your earnings and withdrawals</p>
        </div>
    </div>

    <!-- Wallet Balance Cards -->
    <div class="flex flex-wrap gap-4 lg:gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-primary/10 text-primary">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Available Balance</p>
                    <h3 class="text-2xl font-bold text-gray-900">रु <?= number_format($wallet['balance'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-accent/10 text-accent">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Earnings</p>
                    <h3 class="text-2xl font-bold text-gray-900">रु <?= number_format($wallet['total_earnings'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-primary/10 text-primary">
                    <i class="fas fa-money-bill-wave text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Withdrawals</p>
                    <h3 class="text-2xl font-bold text-gray-900">रु <?= number_format($wallet['total_withdrawals'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex-1 min-w-[220px]">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-accent/10 text-accent">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <p class="text-sm font-medium text-gray-500">Pending Withdrawals</p>
                    <h3 class="text-2xl font-bold text-gray-900">रु <?= number_format($wallet['pending_withdrawals'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <button type="button" 
                onclick="window.location.href='<?= \App\Core\View::url('seller/withdraw-requests/create') ?>'" 
                class="btn btn-primary">
            <i class="fas fa-money-bill-wave mr-2"></i>
            Request Withdrawal
        </button>
        <button type="button" 
                onclick="window.location.href='<?= \App\Core\View::url('seller/wallet/transactions') ?>'" 
                class="btn btn-outline">
            <i class="fas fa-list mr-2"></i>
            View All Transactions
        </button>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
        </div>
        
        <?php if (empty($transactions)): ?>
            <div class="flex flex-col items-center justify-center py-12 px-6">
                <div class="p-4 bg-gray-50 rounded-full mb-4">
                    <i class="fas fa-exchange-alt text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions yet</h3>
                <p class="text-sm text-gray-500 text-center">Transactions will appear here once you receive payments</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance After</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach (array_slice($transactions, 0, 10) as $transaction): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('M j, Y g:i A', strtotime($transaction['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $transaction['type'] === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= ucfirst($transaction['type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= htmlspecialchars($transaction['description'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold <?= $transaction['type'] === 'credit' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $transaction['type'] === 'credit' ? '+' : '-' ?>रु <?= number_format($transaction['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    रु <?= number_format($transaction['balance_after'] ?? 0, 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?= $transaction['status'] === 'completed'
                                            ? 'bg-green-100 text-green-800'
                                            : ($transaction['status'] === 'pending'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-red-100 text-red-800') ?>">
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

