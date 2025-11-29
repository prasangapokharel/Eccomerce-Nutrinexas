<?php ob_start(); ?>
<?php $page = 'wallet'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Request Withdrawal</h1>
        <a href="<?= \App\Core\View::url('seller/withdraw-requests') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back
        </a>
    </div>

    <!-- Wallet Balance -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Available Balance</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">रु <?= number_format($wallet['balance'] ?? 0, 2) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="<?= \App\Core\View::url('seller/withdraw-requests/create') ?>" method="POST" id="withdrawForm">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="space-y-4">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Withdrawal Amount (रु) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="amount" 
                           name="amount" 
                           step="0.01" 
                           min="100"
                           max="<?= $wallet['balance'] ?? 0 ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                           placeholder="Enter amount"
                           required>
                    <p class="text-xs text-gray-500 mt-1">Minimum withdrawal: रु 100</p>
                </div>

                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">
                        Payment Method <span class="text-red-500">*</span>
                    </label>
                    <select id="payment_method" 
                            name="payment_method" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                            required
                            onchange="toggleBankAccount()">
                        <option value="bank_transfer" selected>Bank Transfer</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Only bank transfer is supported for Nepal</p>
                </div>

                <div id="bankAccountSection">
                    <label for="bank_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Bank Account <span class="text-red-500">*</span>
                    </label>
                    <select id="bank_account_id" 
                            name="bank_account_id" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                            required>
                        <option value="">Select Bank Account</option>
                        <?php foreach ($bankAccounts as $account): ?>
                            <option value="<?= $account['id'] ?>" <?= $account['is_default'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($account['bank_name']) ?> - 
                                <?= htmlspecialchars($account['account_holder_name'] ?? '') ?> - 
                                ****<?= substr($account['account_number'], -4) ?>
                                <?= $account['is_default'] ? ' (Default)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mt-2 flex items-center gap-2">
                        <a href="<?= \App\Core\View::url('seller/bank-account') ?>" class="text-sm text-primary hover:underline">
                            <i class="fas fa-plus-circle mr-1"></i>Add New Bank Account
                        </a>
                    </div>
                </div>

                <div>
                    <label for="account_details" class="block text-sm font-medium text-gray-700 mb-2">
                        Additional Account Details (Optional)
                    </label>
                    <textarea id="account_details" 
                              name="account_details" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                              placeholder="Enter any additional bank account details if needed"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Optional: Add any additional information about your bank account</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="<?= \App\Core\View::url('seller/withdraw-requests') ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleBankAccount() {
    // Always show bank account section for bank transfer (only supported method)
    const bankSection = document.getElementById('bankAccountSection');
    bankSection.style.display = 'block';
}

document.getElementById('withdrawForm').addEventListener('submit', function(e) {
    const amount = parseFloat(document.getElementById('amount').value);
    const balance = <?= $wallet['balance'] ?? 0 ?>;
    const pending = <?= $wallet['pending_withdrawals'] ?? 0 ?>;
    const available = balance - pending;
    const bankAccountId = document.getElementById('bank_account_id').value;
    
    if (!bankAccountId) {
        e.preventDefault();
        alert('Please select a bank account');
        return false;
    }
    
    if (amount > available) {
        e.preventDefault();
        alert('Insufficient balance. Available: रु ' + available.toFixed(2));
        return false;
    }
    
    if (amount < 100) {
        e.preventDefault();
        alert('Minimum withdrawal amount is रु 100');
        return false;
    }
    
    if (amount <= 0) {
        e.preventDefault();
        alert('Please enter a valid amount');
        return false;
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

