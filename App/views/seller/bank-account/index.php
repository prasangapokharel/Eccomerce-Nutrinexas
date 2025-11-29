<?php ob_start(); ?>
<?php $page = 'bank-account'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Bank Account Management</h1>
        <p class="text-gray-600">Manage your bank accounts for withdrawals</p>
    </div>

    <!-- Show Existing Account Details -->
    <?php if (!empty($hasAccount) && !empty($existingAccount)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Bank Account Details</h2>
            
            <div class="border border-gray-200 rounded-lg p-6 bg-blue-50 border-blue-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                        <p class="text-gray-900 font-semibold"><?= htmlspecialchars($existingAccount['bank_name']) ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name</label>
                        <p class="text-gray-900 font-semibold"><?= htmlspecialchars($existingAccount['account_holder_name']) ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                        <p class="text-gray-900 font-semibold"><?= htmlspecialchars($existingAccount['account_number']) ?></p>
                    </div>
                    
                    <?php if (!empty($existingAccount['branch_name'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                        <p class="text-gray-900 font-semibold"><?= htmlspecialchars($existingAccount['branch_name']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <span class="inline-flex items-center px-3 py-1 bg-primary text-white text-sm rounded-full">
                            <?= $existingAccount['is_default'] ? 'Default Account' : 'Active Account' ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($existingAccount['created_at'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Added On</label>
                        <p class="text-gray-600 text-sm"><?= date('F j, Y', strtotime($existingAccount['created_at'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <?= $defaultAccount ? 'Update Bank Account' : 'Add Bank Account' ?>
        </h2>
        
        <?php if (!empty($hasAccount)): ?>
            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    You already have a bank account saved. You can update it below or contact support to add additional accounts.
                </p>
            </div>
        <?php endif; ?>
        
        <form action="<?= \App\Core\View::url('seller/bank-account') ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <input type="hidden" name="action" value="<?= $defaultAccount ? 'update' : 'create' ?>">
            <?php if ($defaultAccount): ?>
                <input type="hidden" name="account_id" value="<?= $defaultAccount['id'] ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Bank Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="bank_name" name="bank_name" 
                           value="<?= htmlspecialchars($defaultAccount['bank_name'] ?? '') ?>" required
                           class="input native-input"
                           placeholder="e.g., Nabil Bank, Nepal Investment Bank">
                </div>

                <div>
                    <label for="account_holder_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Account Holder Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="account_holder_name" name="account_holder_name" 
                           value="<?= htmlspecialchars($defaultAccount['account_holder_name'] ?? '') ?>" required
                           class="input native-input">
                </div>

                <div>
                    <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Account Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="account_number" name="account_number" 
                           value="<?= htmlspecialchars($defaultAccount['account_number'] ?? '') ?>" required
                           class="input native-input">
                </div>

                <div>
                    <label for="branch_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Branch Name
                    </label>
                    <input type="text" id="branch_name" name="branch_name" 
                           value="<?= htmlspecialchars($defaultAccount['branch_name'] ?? '') ?>"
                           class="input native-input"
                           placeholder="e.g., Kathmandu Branch">
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_default" value="1" 
                               <?= $defaultAccount && $defaultAccount['is_default'] ? 'checked' : (!empty($bankAccounts) ? '' : 'checked') ?>
                               class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Set as default bank account</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="submit" class="btn btn-primary">
                    <?= $defaultAccount ? 'Update Account' : 'Add Account' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Bank Accounts List -->
    <?php if (!empty($bankAccounts)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Saved Bank Accounts</h2>
            
            <div class="space-y-4">
                <?php foreach ($bankAccounts as $account): ?>
                    <div class="border border-gray-200 rounded-lg p-4 <?= $account['is_default'] ? 'bg-blue-50 border-blue-200' : '' ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?= htmlspecialchars($account['bank_name']) ?>
                                    </h3>
                                    <?php if ($account['is_default']): ?>
                                        <span class="px-2 py-1 bg-primary text-white text-xs rounded-full">Default</span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1 text-sm text-gray-600">
                                    <p><strong>Account Holder:</strong> <?= htmlspecialchars($account['account_holder_name']) ?></p>
                                    <p><strong>Account Number:</strong> <?= htmlspecialchars($account['account_number']) ?></p>
                                    <?php if (!empty($account['branch_name'])): ?>
                                        <p><strong>Branch:</strong> <?= htmlspecialchars($account['branch_name']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <form action="<?= \App\Core\View::url('seller/bank-account/delete/' . $account['id']) ?>" 
                                      method="POST" 
                                      onsubmit="return confirm('Are you sure you want to delete this bank account?')">
                                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

