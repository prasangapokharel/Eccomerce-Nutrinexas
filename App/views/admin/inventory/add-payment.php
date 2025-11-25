<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Add New Payment</h1>
            <p class="text-gray-600 mt-2">Record a payment for a purchase</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/inventory/payments') ?>" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Payments
        </a>
    </div>

    <!-- Flash Messages -->
    <?php 
    $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
    if ($flashMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $flashMessage ?>
        </div>
    <?php endif; ?>

    <?php 
    $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
    if ($flashError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $flashError ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form method="POST" action="<?= \App\Core\View::url('admin/inventory/add-payment') ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Purchase Selection -->
                <div class="md:col-span-2">
                    <label for="purchase_id" class="block text-sm font-medium text-gray-700 mb-2">Purchase *</label>
                    <select name="purchase_id" id="purchase_id" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Select a purchase</option>
                        <?php if (!empty($purchases)): ?>
                            <?php foreach ($purchases as $purchase): ?>
                                <option value="<?= $purchase['purchase_id'] ?>" 
                                        data-total="<?= $purchase['total_amount'] ?>"
                                        data-paid="<?= $purchase['paid_amount'] ?? 0 ?>"
                                        data-remaining="<?= $purchase['remaining_amount'] ?? $purchase['total_amount'] ?>">
                                    <?= htmlspecialchars($purchase['product_name']) ?> 
                                    - Rs <?= number_format($purchase['total_amount'], 2) ?>
                                    (<?= htmlspecialchars($purchase['supplier_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Payment Method -->
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                    <select name="payment_method" id="payment_method" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="digital_wallet">Digital Wallet</option>
                    </select>
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (Rs) *</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <!-- Reference Number -->
                <div>
                    <label for="reference_number" class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text" name="reference_number" id="reference_number"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                           placeholder="Transaction ID, Check number, etc.">
                </div>

                <!-- Payment Date -->
                <div>
                    <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-2">Payment Date *</label>
                    <input type="datetime-local" name="payment_date" id="payment_date" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                              placeholder="Additional notes about this payment..."></textarea>
                </div>
            </div>

            <!-- Purchase Info Display -->
            <div id="purchaseInfo" class="mt-6 p-4 bg-gray-50 rounded-lg hidden">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Purchase Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Total Amount:</span>
                        <span id="totalAmount" class="text-gray-900"></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Already Paid:</span>
                        <span id="paidAmount" class="text-gray-900"></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Remaining:</span>
                        <span id="remainingAmount" class="text-gray-900"></span>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 mt-8">
                <a href="<?= \App\Core\View::url('admin/inventory/payments') ?>" 
                   class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Update purchase info when purchase is selected
document.getElementById('purchase_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const purchaseInfo = document.getElementById('purchaseInfo');
    
    if (this.value) {
        const total = parseFloat(selectedOption.getAttribute('data-total'));
        const paid = parseFloat(selectedOption.getAttribute('data-paid'));
        const remaining = parseFloat(selectedOption.getAttribute('data-remaining'));
        
        document.getElementById('totalAmount').textContent = 'Rs ' + total.toFixed(2);
        document.getElementById('paidAmount').textContent = 'Rs ' + paid.toFixed(2);
        document.getElementById('remainingAmount').textContent = 'Rs ' + remaining.toFixed(2);
        
        // Set max amount to remaining amount
        document.getElementById('amount').max = remaining;
        document.getElementById('amount').placeholder = 'Max: Rs ' + remaining.toFixed(2);
        
        purchaseInfo.classList.remove('hidden');
    } else {
        purchaseInfo.classList.add('hidden');
        document.getElementById('amount').max = '';
        document.getElementById('amount').placeholder = '';
    }
});

// Set current date and time as default
document.getElementById('payment_date').value = new Date().toISOString().slice(0, 16);
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>