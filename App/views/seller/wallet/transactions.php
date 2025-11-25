<?php ob_start(); ?>
<?php $page = 'wallet'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Wallet Transactions</h1>
        <a href="<?= \App\Core\View::url('seller/wallet') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back to Wallet
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">All Transactions</h2>
        </div>
        
        <?php if (empty($transactions)): ?>
            <div class="text-center py-12">
                <i class="fas fa-exchange-alt text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions found</h3>
                <p class="text-gray-500">Transactions will appear here once you receive payments</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
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
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($transactions as $transaction): ?>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?= $transaction['type'] === 'credit' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $transaction['type'] === 'credit' ? '+' : '-' ?>रु <?= number_format($transaction['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    रु <?= number_format($transaction['balance_after'] ?? 0, 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $transaction['status'] === 'completed' ? 'bg-green-100 text-green-800' : ($transaction['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                        <?= ucfirst($transaction['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Showing <?= ($currentPage - 1) * 25 + 1 ?> to <?= min($currentPage * 25, $total) ?> of <?= $total ?> transactions
                    </div>
                    <div class="flex gap-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?>" class="btn btn-secondary">Previous</a>
                        <?php endif; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?>" class="btn btn-secondary">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

