<?php ob_start(); ?>
<div class="min-h-screen bg-neutral-50">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
            <div class="flex items-center gap-4 mb-6">
                <a href="<?= \App\Core\View::url('user/account') ?>" class="w-10 h-10 rounded-xl border border-primary/20 flex items-center justify-center hover:bg-primary/5 transition-colors">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <path d="M14 4L6 12l8 8"/>
                    </svg>
                </a>
                <h1 class="font-heading text-3xl text-primary flex-1">Withdraw Funds</h1>
            </div>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <?php 
                    $isSuccess = ($_SESSION['flash_type'] ?? '') === 'success';
                    $flashClasses = $isSuccess
                        ? 'bg-success/10 border-success text-success'
                        : 'bg-error/10 border-error text-error';
                ?>
                <div class="<?= $flashClasses ?> border-l-4 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['flash_message'] ?></span>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
                <?php unset($_SESSION['flash_type']); ?>
            <?php endif; ?>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="bg-error/10 border-l-4 border-error text-error px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <ul class="list-disc pl-5">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 p-6">
                    <h2 class="text-lg font-medium text-primary mb-2">Available Balance</h2>
                    <div class="text-3xl font-bold text-accent">रु<?= number_format($balance['available_balance'] ?? 0, 2) ?></div>
                    <p class="text-sm text-neutral-600 mt-2">Amount available for withdrawal</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 p-6">
                    <h2 class="text-lg font-medium text-primary mb-2">Pending Withdrawals</h2>
                    <div class="text-3xl font-bold text-warning">रु<?= number_format($balance['pending_withdrawals'] ?? 0, 2) ?></div>
                    <p class="text-sm text-neutral-600 mt-2">Amount currently being processed</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 p-6">
                    <h2 class="text-lg font-medium text-primary mb-2">Total Withdrawn</h2>
                    <div class="text-3xl font-bold text-success">रु<?= number_format($balance['total_withdrawn'] ?? 0, 2) ?></div>
                    <p class="text-sm text-neutral-600 mt-2">Total amount withdrawn to date</p>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-neutral-200">
                    <h2 class="font-heading text-xl text-primary">Request Withdrawal</h2>
                </div>
                
                <form id="withdraw-form" action="<?= \App\Core\View::url('user/requestWithdrawal') ?>" method="post" class="p-6">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                    <div class="mb-6">
                        <label for="amount" class="block text-sm font-medium text-neutral-700 mb-1">Withdrawal Amount (रु)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-3 text-neutral-500">रु</span>
                            <input type="number" name="amount" id="amount" min="100" max="<?= $balance['available_balance'] ?? 0 ?>" step="1" 
                                class="input pl-8"
                                placeholder="Enter amount (minimum रु100)" required>
                        </div>
                        <p class="text-sm text-neutral-500 mt-1">Minimum withdrawal amount: रु500</p>
                    </div>
                    
                    <div class="mb-6">
                        <label for="payment_method" class="block text-sm font-medium text-neutral-700 mb-3">Payment Method</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="bank_transfer" class="sr-only" id="bank_transfer">
                                <div class="border-2 border-neutral-200 rounded-lg p-4 hover:border-primary transition-colors payment-option">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-medium text-mountain">Bank Transfer</div>
                                            <div class="text-sm text-neutral-500">Direct to your bank account</div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="esewa" class="sr-only" id="esewa">
                                <div class="border-2 border-neutral-200 rounded-lg p-4 hover:border-primary transition-colors payment-option">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-medium text-mountain">eSewa</div>
                                            <div class="text-sm text-neutral-500">Digital wallet transfer</div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="khalti" class="sr-only" id="khalti">
                                <div class="border-2 border-neutral-200 rounded-lg p-4 hover:border-primary transition-colors payment-option">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-medium text-mountain">Khalti</div>
                                            <div class="text-sm text-neutral-500">Mobile wallet transfer</div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div id="bank_details" class="mb-6 hidden">
                        <h3 class="text-md font-medium text-primary mb-4">Bank Account Details</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="account_name" class="block text-sm font-medium text-neutral-700 mb-1">Account Holder Name</label>
                                <input type="text" name="account_name" id="account_name" 
                                       class="input">
                            </div>
                            
                            <div>
                                <label for="account_number" class="block text-sm font-medium text-neutral-700 mb-1">Account Number</label>
                                <input type="text" name="account_number" id="account_number" 
                                       class="input">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-neutral-700 mb-1">Bank Name</label>
                                <input type="text" name="bank_name" id="bank_name" 
                                       class="input">
                            </div>
                            
                            <div>
                                <label for="ifsc_code" class="block text-sm font-medium text-neutral-700 mb-1">IFSC Code</label>
                                <input type="text" name="ifsc_code" id="ifsc_code" 
                                       class="input">
                            </div>
                        </div>
                    </div>
                    
                    <div id="esewa_details" class="mb-6 hidden">
                        <h3 class="text-md font-medium text-primary mb-4">eSewa Details</h3>
                        
                        <div>
                            <label for="esewa_id" class="block text-sm font-medium text-neutral-700 mb-1">eSewa ID</label>
                            <input type="text" name="esewa_id" id="esewa_id" 
                                   class="input"
                                   placeholder="98******29">
                        </div>
                    </div>
                    
                    <div id="khalti_details" class="mb-6 hidden">
                        <h3 class="text-md font-medium text-primary mb-4">Khalti Details</h3>
                        
                        <div>
                            <label for="khalti_number" class="block text-sm font-medium text-neutral-700 mb-1">Khalti Mobile Number</label>
                            <input type="text" name="khalti_number" id="khalti_number" 
                                   class="input"
                                   placeholder="10-digit mobile number">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="button" id="open-withdraw-confirm" class="btn btn-primary w-full px-6 py-3"
                                <?= ($balance['available_balance'] ?? 0) < 100 ? 'disabled' : '' ?>>
                            Request Withdrawal
                        </button>
                        <?php if (($balance['available_balance'] ?? 0) < 100): ?>
                            <p class="text-error text-sm mt-2">You need at least रु100 to request a withdrawal.</p>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-sm text-neutral-600 mt-4">
                        Note: Payment will be credited within 24 hours after approval.
                    </p>
                </form>
            </div>

            <!-- Withdraw Confirmation Drawer -->
            <div id="withdraw-confirm-drawer" class="fixed inset-0 z-50 hidden">
                <div id="withdraw-confirm-overlay" class="absolute inset-0 bg-black/40"></div>
                <div id="withdraw-confirm-content" class="fixed bottom-20 left-0 right-0 bg-white rounded-t-2xl shadow-2xl transform translate-y-full transition-transform duration-300 mx-4">
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-mountain">Confirm Withdrawal</h3>
                            <button type="button" id="close-withdraw-confirm" class="text-neutral-500 hover:text-neutral-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-neutral-600">Amount</span>
                                <span id="confirm-amount" class="text-xl font-bold text-accent">रु0.00</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-neutral-600">Payment Method</span>
                                <span id="confirm-method" class="text-base font-medium text-mountain">-</span>
                            </div>
                            <p class="text-xs text-neutral-500">Note: Payment will be credited within 24 hours after approval. You'll receive a confirmation notification.</p>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <button type="button" id="cancel-withdraw" class="btn btn-secondary px-4 py-3">Cancel</button>
                            <button type="button" id="confirm-withdraw" class="btn btn-primary px-4 py-3">Confirm & Submit</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-neutral-100 overflow-hidden mt-8">
                <div class="p-6 border-b border-neutral-200">
                    <h2 class="font-heading text-xl text-primary">Withdrawal History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Payment Method
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-neutral-200">
                            <?php if (empty($withdrawals)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-neutral-500">
                                        No withdrawal history found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($withdrawals as $withdrawal): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= date('M j, Y', strtotime($withdrawal['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            रु<?= number_format($withdrawal['amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= ucfirst(str_replace('_', ' ', $withdrawal['payment_method'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= 
                                                $withdrawal['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                ($withdrawal['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                                ($withdrawal['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) 
                                                ?>">
                                                <?= ucfirst($withdrawal['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bankDetails = document.getElementById('bank_details');
    const esewaDetails = document.getElementById('esewa_details');
    const khaltiDetails = document.getElementById('khalti_details');
    const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
    const form = document.getElementById('withdraw-form');
    const amountInput = document.getElementById('amount');
    const openBtn = document.getElementById('open-withdraw-confirm');
    const drawer = document.getElementById('withdraw-confirm-drawer');
    const drawerContent = document.getElementById('withdraw-confirm-content');
    const overlay = document.getElementById('withdraw-confirm-overlay');
    const closeBtn = document.getElementById('close-withdraw-confirm');
    const cancelBtn = document.getElementById('cancel-withdraw');
    const confirmBtn = document.getElementById('confirm-withdraw');
    const confirmAmount = document.getElementById('confirm-amount');
    const confirmMethod = document.getElementById('confirm-method');
    
    // Add click handlers for payment method options
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Hide all payment details sections
            bankDetails.classList.add('hidden');
            esewaDetails.classList.add('hidden');
            khaltiDetails.classList.add('hidden');
            
            // Remove selected styling from all options
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('border-primary', 'bg-primary/5');
                opt.classList.add('border-neutral-200');
            });
            
            // Show the selected payment details section and add styling
            if (this.value === 'bank_transfer') {
                bankDetails.classList.remove('hidden');
                this.closest('.payment-option').classList.add('border-primary', 'bg-primary/5');
                this.closest('.payment-option').classList.remove('border-neutral-200');
            } else if (this.value === 'esewa') {
                esewaDetails.classList.remove('hidden');
                this.closest('.payment-option').classList.add('border-primary', 'bg-primary/5');
                this.closest('.payment-option').classList.remove('border-neutral-200');
            } else if (this.value === 'khalti') {
                khaltiDetails.classList.remove('hidden');
                this.closest('.payment-option').classList.add('border-primary', 'bg-primary/5');
                this.closest('.payment-option').classList.remove('border-neutral-200');
            }
        });
    });

    function openConfirmDrawer() {
        drawer.classList.remove('hidden');
        drawerContent.classList.remove('translate-y-full');
        document.body.style.overflow = 'hidden';
    }

    function closeConfirmDrawer() {
        drawerContent.classList.add('translate-y-full');
        setTimeout(() => {
            drawer.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }, 300);
    }

    if (openBtn) {
        openBtn.addEventListener('click', function() {
            const amount = parseFloat(amountInput.value || '0');
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!amount || amount < 100) {
                alert('Please enter a valid amount (minimum रु100).');
                return;
            }
            if (!selectedMethod) {
                alert('Please select a payment method.');
                return;
            }

            const methodText = {
                'bank_transfer': 'Bank Transfer',
                'esewa': 'eSewa',
                'khalti': 'Khalti'
            }[selectedMethod.value] || selectedMethod.value;

            confirmAmount.textContent = 'रु' + amount.toFixed(2);
            confirmMethod.textContent = methodText;
            openConfirmDrawer();
        });
    }

    [overlay, closeBtn, cancelBtn].forEach(el => {
        if (el) {
            el.addEventListener('click', closeConfirmDrawer);
        }
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            form.submit();
        });
    }
});
</script>
<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
