<?php 
ob_start(); 
$title = $title ?? 'Create New Order';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Create New Order</h1>
            <p class="mt-1 text-sm text-gray-500">Create a new order for a customer</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= \App\Core\View::url('admin/orders') ?>" 
               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Orders
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (\App\Core\Session::hasFlash('error')): ?>
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">
                        <?= \App\Helpers\FlashHelper::getFlashMessage('error') ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= \App\Core\View::url('admin/orders/store') ?>" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Customer Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Information</h3>
                <div class="space-y-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
                        <input type="text" name="customer_name" id="customer_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Enter customer name">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="tel" name="phone" id="phone" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Enter phone number">
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                        <textarea name="address" id="address" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                                  placeholder="Enter full address"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <select name="city" id="city" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">Select City</option>
                                <?php if (!empty($deliveryCharges)): ?>
                                    <?php foreach ($deliveryCharges as $charge): ?>
                                        <option value="<?= htmlspecialchars($charge['location_name']) ?>" 
                                                data-fee="<?= $charge['charge'] ?>">
                                            <?= htmlspecialchars($charge['location_name']) ?>
                                            <?php if ($charge['charge'] == 0): ?>
                                                (Free)
                                            <?php else: ?>
                                                (रु<?= number_format($charge['charge'], 2) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <option value="other">Other Location</option>
                            </select>
                        </div>
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                            <input type="text" name="state" id="state" value="Nepal"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter state">
                        </div>
                    </div>
                    
                    <div>
                        <label for="order_notes" class="block text-sm font-medium text-gray-700 mb-1">Order Notes</label>
                        <textarea name="order_notes" id="order_notes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                                  placeholder="Any special notes for this order"></textarea>
                    </div>
                </div>
            </div>

            <!-- Product Selection -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Product Selection</h3>
                <div class="space-y-4">
                    <div id="product-selection">
                        <div class="product-item border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                                    <select name="products[0][product_id]" class="product-select w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary" required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['id'] ?>" 
                                                    data-price="<?= $product['sale_price'] ?? $product['price'] ?>"
                                                    data-name="<?= htmlspecialchars($product['product_name'] ?? '') ?>">
                                                <?= htmlspecialchars($product['product_name'] ?? 'Untitled Product') ?> - रु<?= number_format($product['sale_price'] ?? $product['price'], 2) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                                    <input type="number" name="products[0][quantity]" min="1" value="1" required
                                           class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                                    <div class="item-total px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 font-medium">
                                        रु0.00
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" onclick="addProduct()" 
                            class="w-full flex items-center justify-center px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-primary hover:text-primary transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Add Another Product
                    </button>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Order Summary</h3>
            <div class="space-y-3">
                <!-- Coupon (optional) -->
                <div>
                    <label for="coupon_code" class="block text-sm font-medium text-gray-700 mb-1">Coupon Code (optional)</label>
                    <div class="flex gap-2">
                        <input type="text" name="coupon_code" id="coupon_code"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Enter coupon code"
                               onkeyup="validateCouponLive()"
                               onblur="validateCouponLive()">
                        <button type="button" onclick="validateCouponLive()" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                            <i class="fas fa-check"></i> Apply
                        </button>
                    </div>
                    <div id="coupon-message" class="mt-1 text-xs"></div>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal:</span>
                    <span id="subtotal" class="font-medium text-gray-900">रु0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Delivery Fee:</span>
                    <span id="delivery-fee" class="font-medium text-gray-900">रु0.00</span>
                </div>
                <div class="hidden justify-between" id="coupon-row">
                    <span class="text-gray-600">Coupon Discount:</span>
                    <span id="coupon-discount" class="font-medium text-green-600">- रु0.00</span>
                </div>
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex justify-between">
                        <span class="text-lg font-medium text-gray-900">Total Amount:</span>
                        <span id="total-amount" class="text-lg font-bold text-primary">रु0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" 
                    class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-shopping-cart mr-2"></i>
                Create Order
            </button>
        </div>
    </form>
</div>

<script>
let productCount = 1;

function addProduct() {
    const container = document.getElementById('product-selection');
    const newProduct = document.createElement('div');
    newProduct.className = 'product-item border border-gray-200 rounded-lg p-4 mt-4';
    newProduct.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                <select name="products[${productCount}][product_id]" class="product-select w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary" required>
                    <option value="">Select Product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>" 
                                data-price="<?= $product['sale_price'] ?? $product['price'] ?>"
                                data-name="<?= htmlspecialchars($product['product_name'] ?? '') ?>">
                            <?= htmlspecialchars($product['product_name'] ?? 'Untitled Product') ?> - रु<?= number_format($product['sale_price'] ?? $product['price'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                <input type="number" name="products[${productCount}][quantity]" min="1" value="1" required
                       class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                <div class="flex items-center">
                    <div class="item-total flex-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 font-medium">
                        रु0.00
                    </div>
                    <button type="button" onclick="removeProduct(this)" 
                            class="ml-2 p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(newProduct);
    productCount++;
    
    // Add event listeners to new inputs
    const newSelect = newProduct.querySelector('.product-select');
    const newQuantity = newProduct.querySelector('.quantity-input');
    newSelect.addEventListener('change', calculateTotals);
    newQuantity.addEventListener('input', calculateTotals);
    newQuantity.addEventListener('change', calculateTotals);
}

function removeProduct(button) {
    button.closest('.product-item').remove();
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    
    document.querySelectorAll('.product-item').forEach(item => {
        const select = item.querySelector('.product-select');
        const quantityInput = item.querySelector('.quantity-input');
        const totalDiv = item.querySelector('.item-total');
        
        if (select.value && quantityInput.value) {
            const selectedOption = select.options[select.selectedIndex];
            const price = parseFloat(selectedOption.dataset.price) || 0;
            const quantity = parseInt(quantityInput.value) || 0;
            const itemTotal = price * quantity;
            
            totalDiv.textContent = `रु${itemTotal.toFixed(2)}`;
            subtotal += itemTotal;
        } else {
            totalDiv.textContent = 'रु0.00';
        }
    });
    
    // Update summary
    document.getElementById('subtotal').textContent = `रु${subtotal.toFixed(2)}`;
    
    // Calculate delivery fee based on city selection
    const citySelect = document.getElementById('city');
    let deliveryFee = 0;
    
    if (citySelect && citySelect.value) {
        if (citySelect.value === 'other') {
            deliveryFee = 300; // Default fee for other locations
        } else {
            const selectedOption = citySelect.options[citySelect.selectedIndex];
            if (selectedOption) {
                deliveryFee = parseFloat(selectedOption.getAttribute('data-fee')) || 0;
            }
        }
    }
    
    const deliveryFeeElement = document.getElementById('delivery-fee');
    if (deliveryFee === 0) {
        deliveryFeeElement.textContent = 'Free';
        deliveryFeeElement.className = 'font-medium text-green-600';
    } else {
        deliveryFeeElement.textContent = `रु${deliveryFee.toFixed(2)}`;
        deliveryFeeElement.className = 'font-medium text-gray-900';
    }
    
    const totalAmount = subtotal + deliveryFee;
    document.getElementById('total-amount').textContent = `रु${totalAmount.toFixed(2)}`;
}

// Add event listeners to existing inputs
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.product-select, .quantity-input').forEach(input => {
        input.addEventListener('change', calculateTotals);
        input.addEventListener('input', calculateTotals);
    });
    
    // Add city select event listener
    const citySelect = document.getElementById('city');
    if (citySelect) {
        citySelect.addEventListener('change', calculateTotals);
    }
    
    // Initial calculation
    calculateTotals();

    // Live coupon validation
    const couponInput = document.getElementById('coupon_code');
    if (couponInput) {
        let couponTimer;
        couponInput.addEventListener('input', function() {
            clearTimeout(couponTimer);
            couponTimer = setTimeout(validateCouponLive, 400);
        });
        couponInput.addEventListener('blur', validateCouponLive);
    }
});

function gatherItemsForCoupon() {
    const items = [];
    document.querySelectorAll('.product-item').forEach(item => {
        const select = item.querySelector('.product-select');
        const qty = item.querySelector('.quantity-input');
        if (select && select.value && qty && qty.value) {
            const opt = select.options[select.selectedIndex];
            items.push({
                product_id: parseInt(select.value),
                quantity: parseInt(qty.value),
                price: parseFloat(opt.dataset.price) || 0
            });
        }
    });
    return items;
}

function validateCouponLive() {
    const code = document.getElementById('coupon_code')?.value.trim();
    const couponMessage = document.getElementById('coupon-message');
    const couponRow = document.getElementById('coupon-row');
    
    if (!code) {
        couponRow.classList.add('hidden');
        document.getElementById('coupon-discount').textContent = '- रु0.00';
        couponMessage.textContent = '';
        couponMessage.className = 'mt-1 text-xs';
        calculateTotals();
        return;
    }

    const items = gatherItemsForCoupon();
    if (items.length === 0) {
        couponMessage.textContent = 'Please add products first';
        couponMessage.className = 'mt-1 text-xs text-yellow-600';
        couponRow.classList.add('hidden');
        return;
    }

    const citySelect = document.getElementById('city');
    let deliveryFee = 0;
    if (citySelect && citySelect.value) {
        if (citySelect.value === 'other') deliveryFee = 300; else {
            const opt = citySelect.options[citySelect.selectedIndex];
            deliveryFee = parseFloat(opt.getAttribute('data-fee')) || 0;
        }
    }

    const form = new FormData();
    form.append('coupon_code', code);
    form.append('delivery_fee', deliveryFee);
    items.forEach((it, idx) => {
        form.append(`items[${idx}][product_id]`, it.product_id);
        form.append(`items[${idx}][quantity]`, it.quantity);
        form.append(`items[${idx}][price]`, it.price);
    });

    couponMessage.textContent = 'Validating...';
    couponMessage.className = 'mt-1 text-xs text-blue-600';

    fetch('<?= \App\Core\View::url('admin/validateOrderCoupon') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: form
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.discount > 0) {
            couponRow.classList.remove('hidden');
            document.getElementById('coupon-discount').textContent = `- रु${(res.discount || 0).toFixed(2)}`;
            couponMessage.textContent = `✓ Coupon applied! Discount: रु${(res.discount || 0).toFixed(2)}`;
            couponMessage.className = 'mt-1 text-xs text-green-600';
            
            // Recompute totals with discount applied
            const subtotalText = document.getElementById('subtotal').textContent.replace('रु','').replace(',','');
            const subtotal = parseFloat(subtotalText) || 0;
            const total = subtotal + deliveryFee - (res.discount || 0);
            document.getElementById('total-amount').textContent = `रु${Math.max(0,total).toFixed(2)}`;
        } else {
            couponRow.classList.add('hidden');
            couponMessage.textContent = res.message || 'Invalid coupon code';
            couponMessage.className = 'mt-1 text-xs text-red-600';
            calculateTotals();
        }
    })
    .catch(() => {
        couponRow.classList.add('hidden');
        couponMessage.textContent = 'Error validating coupon';
        couponMessage.className = 'mt-1 text-xs text-red-600';
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
