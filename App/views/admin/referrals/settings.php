<?php ob_start(); ?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Referral Settings</h1>
        <a href="<?= \App\Core\View::url('admin/referrals') ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Back to Referrals
        </a>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-<?= $_SESSION['flash_type'] === 'success' ? 'green' : 'red' ?>-100 border border-<?= $_SESSION['flash_type'] === 'success' ? 'green' : 'red' ?>-400 text-<?= $_SESSION['flash_type'] === 'success' ? 'green' : 'red' ?>-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?= $_SESSION['flash_message'] ?></span>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
        <?php unset($_SESSION['flash_type']); ?>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Commission Settings</h2>
        </div>
        
        <form action="<?= \App\Core\View::url('admin/saveReferralSettings') ?>" method="post" class="p-6">
            <div class="mb-6">
                <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%)</label>
                <div class="flex items-center">
                    <input type="number" name="commission_rate" id="commission_rate" min="0" max="100" step="0.1" 
                           value="<?= htmlspecialchars($settings['commission_rate'] ?? 10) ?>"
                           class="w-32 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <span class="ml-2 text-gray-500">%</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Percentage of order total that referrers earn</p>
            </div>
            
            <div class="mb-6">
                <label for="min_withdrawal" class="block text-sm font-medium text-gray-700 mb-1">Minimum Withdrawal Amount</label>
                <div class="flex items-center">
                    <span class="mr-2 text-gray-500">₹</span>
                    <input type="number" name="min_withdrawal" id="min_withdrawal" min="0" step="1" 
                           value="<?= htmlspecialchars($settings['min_withdrawal'] ?? 100) ?>"
                           class="w-32 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <p class="text-sm text-gray-500 mt-1">Minimum amount users can withdraw</p>
            </div>
            
            <div class="mb-6">
                <label for="auto_approve" class="flex items-center">
                    <input type="checkbox" name="auto_approve" id="auto_approve" 
                           <?= isset($settings['auto_approve']) && $settings['auto_approve'] ? 'checked' : '' ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-700">Automatically approve referral earnings when order is paid</span>
                </label>
            </div>
            
            <div class="mt-6">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Withdrawal Settings</h2>
        </div>
        
        <form action="<?= \App\Core\View::url('admin/saveWithdrawalSettings') ?>" method="post" class="p-6">
            <div class="mb-6">
                <label for="processing_time" class="block text-sm font-medium text-gray-700 mb-1">Processing Time (days)</label>
                <input type="number" name="processing_time" id="processing_time" min="1" max="30" step="1" 
                       value="<?= htmlspecialchars($settings['processing_time'] ?? 3) ?>"
                       class="w-32 px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                <p class="text-sm text-gray-500 mt-1">Number of days to process withdrawal requests</p>
            </div>
            
            <div class="mb-6">
                <label for="payment_methods" class="block text-sm font-medium text-gray-700 mb-1">Available Payment Methods</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="payment_methods[]" value="bank_transfer" 
                               <?= isset($settings['payment_methods']) && in_array('bank_transfer', $settings['payment_methods']) ? 'checked' : '' ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">Bank Transfer</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="payment_methods[]" value="upi" 
                               <?= isset($settings['payment_methods']) && in_array('upi', $settings['payment_methods']) ? 'checked' : '' ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">UPI</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="payment_methods[]" value="paytm" 
                               <?= isset($settings['payment_methods']) && in_array('paytm', $settings['payment_methods']) ? 'checked' : '' ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">Paytm</span>
                    </label>
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>