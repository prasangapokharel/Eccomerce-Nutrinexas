<?php
$title = 'Edit Coupon';
ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">Edit Coupon</h1>
                <p class="text-gray-600 mt-1">Update coupon information</p>
            </div>
            
            <form action="<?= \App\Core\View::url('admin/coupons/edit/' . $coupon['id']) ?>" method="POST" class="p-6 space-y-6">
                <!-- General Errors -->
                <?php if (isset($errors['general'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Coupon Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Coupon Code *</label>
                    <input type="text" id="code" name="code" 
                           value="<?= htmlspecialchars($data['code'] ?? $coupon['code'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary uppercase"
                           style="text-transform: uppercase;"
                           required>
                    <?php if (isset($errors['code'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['code']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Discount Type and Value -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-2">Discount Type *</label>
                        <select id="discount_type" name="discount_type" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" 
                                required onchange="updateDiscountLabel()">
                            <option value="percentage" <?= ($data['discount_type'] ?? $coupon['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                            <option value="fixed" <?= ($data['discount_type'] ?? $coupon['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount (Rs)</option>
                        </select>
                        <?php if (isset($errors['discount_type'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['discount_type']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-2">
                            <span id="discount_label">
                                <?= ($data['discount_type'] ?? $coupon['discount_type'] ?? '') === 'percentage' ? 'Discount Percentage (%) *' : 'Discount Amount (Rs) *' ?>
                            </span>
                        </label>
                        <input type="number" id="discount_value" name="discount_value" 
                               value="<?= htmlspecialchars($data['discount_value'] ?? $coupon['discount_value'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                               step="0.01" min="0.01" required>
                        <?php if (isset($errors['discount_value'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['discount_value']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Constraints -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="min_order_amount" class="block text-sm font-medium text-gray-700 mb-2">Minimum Order Amount (Rs)</label>
                        <input type="number" id="min_order_amount" name="min_order_amount" 
                               value="<?= htmlspecialchars($data['min_order_amount'] ?? $coupon['min_order_amount'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                               step="0.01" min="0">
                        <?php if (isset($errors['min_order_amount'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['min_order_amount']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="max_discount_amount" class="block text-sm font-medium text-gray-700 mb-2">Maximum Discount Amount (Rs)</label>
                        <input type="number" id="max_discount_amount" name="max_discount_amount" 
                               value="<?= htmlspecialchars($data['max_discount_amount'] ?? $coupon['max_discount_amount'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                               step="0.01" min="0">
                        <p class="text-gray-500 text-sm mt-1">For percentage discounts only</p>
                    </div>
                </div>
                
                <!-- Usage Limits -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="usage_limit_per_user" class="block text-sm font-medium text-gray-700 mb-2">Usage Limit Per User</label>
                        <input type="number" id="usage_limit_per_user" name="usage_limit_per_user" 
                               value="<?= htmlspecialchars($data['usage_limit_per_user'] ?? $coupon['usage_limit_per_user'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                               min="1">
                        <?php if (isset($errors['usage_limit_per_user'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['usage_limit_per_user']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="usage_limit_global" class="block text-sm font-medium text-gray-700 mb-2">Global Usage Limit</label>
                        <input type="number" id="usage_limit_global" name="usage_limit_global" 
                               value="<?= htmlspecialchars($data['usage_limit_global'] ?? $coupon['usage_limit_global'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                               min="1">
                        <?php if (isset($errors['usage_limit_global'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['usage_limit_global']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Expiry Date -->
                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">Expiry Date & Time</label>
                    <input type="datetime-local" id="expires_at" name="expires_at" 
                           value="<?= !empty($data['expires_at'] ?? $coupon['expires_at']) ? date('Y-m-d\TH:i', strtotime($data['expires_at'] ?? $coupon['expires_at'])) : '' ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <?php if (isset($errors['expires_at'])): ?>
                        <p class="text-red-500 text-sm mt-1"><?= htmlspecialchars($errors['expires_at']) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Applicable Products -->
                <div>
                    <label for="applicable_products" class="block text-sm font-medium text-gray-700 mb-2">Applicable Products</label>
                    <select id="applicable_products" name="applicable_products[]" multiple
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                            size="6">
                        <?php if (isset($products) && !empty($products)): ?>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>"
                                        <?= (isset($coupon['applicable_products']) && in_array($product['id'], json_decode($coupon['applicable_products'], true) ?? [])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($product['product_name']) ?> - Rs<?= number_format($product['price'], 2) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="text-gray-500 text-sm mt-1">Leave empty to apply to all products. Hold Ctrl/Cmd to select multiple products.</p>
                </div>
                
                <!-- Status -->
                <div class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                           <?= ($data['is_active'] ?? $coupon['is_active'] ?? false) ? 'checked' : '' ?>>
                    <label for="is_active" class="ml-2 text-sm text-gray-700">Active (coupon can be used)</label>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end space-x-4 pt-6">
                    <a href="<?= \App\Core\View::url('admin/coupons') ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-primary text-white rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Update Coupon
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateDiscountLabel() {
    const discountType = document.getElementById('discount_type').value;
    const label = document.getElementById('discount_label');
    
    if (discountType === 'percentage') {
        label.textContent = 'Discount Percentage (%) *';
    } else {
        label.textContent = 'Discount Amount (Rs) *';
    }
}

// Auto-uppercase coupon code
document.getElementById('code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Initialize the discount label on page load
document.addEventListener('DOMContentLoaded', function() {
    updateDiscountLabel();
});
</script>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/admin.php';
?>