<?php ob_start(); ?>
<?php $page = 'wallet'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Withdrawal Request #<?= $request['id'] ?></h1>
        <a href="<?= \App\Core\View::url('seller/withdraw-requests') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Request Details</h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-500">Amount</label>
                    <p class="text-lg font-bold text-gray-900">रु <?= number_format($request['amount'], 2) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Payment Method</label>
                    <p class="text-gray-900"><?= ucfirst(str_replace('_', ' ', $request['payment_method'])) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Status</label>
                    <p>
                        <?php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'processing' => 'bg-blue-100 text-blue-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'completed' => 'bg-green-100 text-green-800'
                        ];
                        $statusColor = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                            <?= ucfirst($request['status']) ?>
                        </span>
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Requested At</label>
                    <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?></p>
                </div>
                <?php if ($request['processed_at']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Processed At</label>
                        <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($request['processed_at'])) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($request['admin_comment'])): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Admin Comment</label>
                        <p class="text-gray-900"><?= htmlspecialchars($request['admin_comment']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Bank Account Information</h2>
            <?php if (!empty($request['bank_account_id']) && !empty($request['account_holder_name'])): ?>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Account Holder</label>
                        <p class="text-gray-900 font-medium"><?= htmlspecialchars($request['account_holder_name']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Bank Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($request['bank_name'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Account Number</label>
                        <p class="text-gray-900 font-mono"><?= htmlspecialchars($request['account_number'] ?? 'N/A') ?></p>
                    </div>
                    <?php if (!empty($request['branch_name'])): ?>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Branch Name</label>
                            <p class="text-gray-900"><?= htmlspecialchars($request['branch_name']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($request['account_type'])): ?>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Type</label>
                            <p class="text-gray-900"><?= htmlspecialchars($request['account_type']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($request['ifsc_code'])): ?>
                        <div>
                            <label class="text-sm font-medium text-gray-500">IFSC Code</label>
                            <p class="text-gray-900 font-mono"><?= htmlspecialchars($request['ifsc_code']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (!empty($request['account_details'])): ?>
                <div>
                    <label class="text-sm font-medium text-gray-500">Account Details</label>
                    <p class="text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($request['account_details']) ?></p>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-yellow-600 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <p class="text-sm text-yellow-800">Bank account information not available. This may be an old request.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (in_array($request['status'], ['rejected', 'failed', 'cancelled'])): ?>
                <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 mr-2 flex-shrink-0 mt-0.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-red-800">Request <?= ucfirst($request['status']) ?></p>
                            <?php if (!empty($request['admin_comment'])): ?>
                                <p class="text-sm text-red-700 mt-1"><?= htmlspecialchars($request['admin_comment']) ?></p>
                            <?php else: ?>
                                <p class="text-sm text-red-700 mt-1">The amount has been returned to your wallet balance.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

