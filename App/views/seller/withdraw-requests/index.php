<?php ob_start(); ?>
<?php $page = 'wallet'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Withdrawal Requests</h1>
        <a href="<?= \App\Core\View::url('seller/withdraw-requests/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus icon-spacing"></i> New Request
        </a>
    </div>

    <!-- Wallet Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Available Balance</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">रु <?= number_format($wallet['balance'] ?? 0, 2) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Pending Withdrawals</p>
                <p class="text-xl font-semibold text-yellow-600 mt-1">रु <?= number_format($wallet['pending_withdrawals'] ?? 0, 2) ?></p>
            </div>
        </div>
    </div>

    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <i class="fas fa-money-bill-wave empty-state-icon"></i>
            <h3 class="empty-state-title">No withdrawal requests</h3>
            <p class="empty-state-text">Create your first withdrawal request</p>
            <a href="<?= \App\Core\View::url('seller/withdraw-requests/create') ?>" class="btn btn-primary">
                Request Withdrawal
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">All Requests</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($requests as $request): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?= $request['id'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    रु <?= number_format($request['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= ucfirst(str_replace('_', ' ', $request['payment_method'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                        'completed' => 'bg-green-100 text-green-800'
                                    ];
                                    $statusColor = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= ucfirst($request['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= \App\Core\View::url('seller/withdraw-requests/detail/' . $request['id']) ?>" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

