<?php 
ob_start(); 
use App\Helpers\CurrencyHelper;
?>
<?php
function getProductImageUrl($product) {
    // First check if image_url is already set by the controller
    if (!empty($product['image_url'])) {
        return $product['image_url'];
    }
    
    $mainImageUrl = '';
    if (!empty($product['images'])) {
        $primaryImage = null;
        foreach ($product['images'] as $img) {
            if ($img['is_primary']) {
                $primaryImage = $img;
                break;
            }
        }
        $imageData = $primaryImage ?: $product['images'][0];
        $mainImageUrl = filter_var($imageData['image_url'], FILTER_VALIDATE_URL)
            ? $imageData['image_url']
            : \App\Core\View::asset('uploads/images/' . $imageData['image_url']);
    } else {
        $image = $product['image'] ?? '';
        $mainImageUrl = filter_var($image, FILTER_VALIDATE_URL)
            ? $image
            : ($image ? \App\Core\View::asset('uploads/images/' . $image) : \App\Core\View::asset('images/products/default.jpg'));
    }
    return $mainImageUrl;
}

// Get default address values for pre-filling
$defaultName = $defaultAddress['recipient_name'] ?? '';
$defaultPhone = $defaultAddress['phone'] ?? '';
$defaultAddressLine = $defaultAddress['address_line1'] ?? '';
$defaultAddressLine2 = $defaultAddress['address_line2'] ?? '';
$defaultCity = $defaultAddress['city'] ?? '';
$defaultState = $defaultAddress['state'] ?? '';
$defaultPostalCode = $defaultAddress['postal_code'] ?? '';

// Override with cookies if available (for guest users)
if (isset($_COOKIE['guest_recipient_name'])) {
    $defaultName = $_COOKIE['guest_recipient_name'];
}
if (isset($_COOKIE['guest_phone'])) {
    $defaultPhone = $_COOKIE['guest_phone'];
}
if (isset($_COOKIE['guest_address_line1'])) {
    $defaultAddressLine = $_COOKIE['guest_address_line1'];
}
if (isset($_COOKIE['guest_address_line2'])) {
    $defaultAddressLine2 = $_COOKIE['guest_address_line2'];
}
if (isset($_COOKIE['guest_city'])) {
    $defaultCity = $_COOKIE['guest_city'];
}
if (isset($_COOKIE['guest_state'])) {
    $defaultState = $_COOKIE['guest_state'];
}
if (isset($_COOKIE['guest_postal_code'])) {
    $defaultPostalCode = $_COOKIE['guest_postal_code'];
}
?>
<div class="bg-gray-50 min-h-screen">
    <div class="max-w-md mx-auto bg-white min-h-screen">
        
        <!-- Progress Indicator -->
        <!-- <div class="bg-white border-b border-gray-200 px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-semibold">
                        1
                    </div>
                    <span class="text-sm font-medium text-gray-900">Checkout</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-sm font-semibold">
                        2
                    </div>
                    <span class="text-sm text-gray-500">Payment</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-sm font-semibold">
                        3
                    </div>
                    <span class="text-sm text-gray-500">Complete</span>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex space-x-2">
                    <div class="flex-1 h-2 bg-primary rounded-full"></div>
                    <div class="flex-1 h-2 bg-gray-200 rounded-full"></div>
                    <div class="flex-1 h-2 bg-gray-200 rounded-full"></div>
                </div>
            </div>
        </div> -->

        <form id="checkout-form" class="space-y-0" method="POST" action="<?= \App\Core\View::url('checkout/process') ?>" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <!-- Shipping Information -->
            <div class="px-4 py-4 border-b border-gray-200">
                <h2 class="h4-semibold text-gray-900 mb-4">Shipping Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="recipient_name" id="recipient_name" required
                               value="<?= htmlspecialchars($defaultName) ?>"
                               class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Enter your full name">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="tel" name="phone" id="phone" required
                               value="<?= htmlspecialchars($defaultPhone) ?>"
                               class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Enter your phone number">
                    </div>
                    
                    <div>
                        <label for="address_line1" class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                        <input type="text" name="address_line1" id="address_line1" required
                               value="<?= htmlspecialchars($defaultAddressLine) ?>"
                               class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Street address, P.O. Box, company name">
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                            <div class="relative">
                                <input type="text" name="city" id="city" list="city-list" required
                                       value="<?= htmlspecialchars($defaultCity) ?>"
                                       class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary"
                                       placeholder="Type to search city">
                                <datalist id="city-list">
                                    <?php foreach ($deliveryCharges as $charge): ?>
                                        <option value="<?= htmlspecialchars($charge['location_name']) ?>"></option>
                                    <?php endforeach; ?>
                                    <option value="other">Other Location</option>
                                </datalist>
                                <!-- Custom suggestions for full cross-device support -->
                                <div id="city-suggest-box" class="absolute left-0 right-10 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto hidden z-40"></div>
                                <button type="button" onclick="detectLocation()" 
                                        class="absolute right-2 top-1/2 transform -translate-y-1/2 text-primary hover:text-primary-dark">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        
                    </div>
                </div>
                
                <input type="hidden" name="country" value="Nepal">
            </div>

            <!-- Optional Account Creation -->
            <?php if (!$userId): ?>
                <div class="px-4 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Options</h2>
                    
                    <div class="p-3 bg-primary/10 border border-primary/20 rounded-lg mb-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="create_account" id="create_account" class="h-4 w-4 text-primary rounded focus:ring-primary">
                            <span class="ml-3 text-sm text-gray-700">Create account for faster checkout</span>
                        </label>
                    </div>

                    <div id="account-fields" class="space-y-4 hidden">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" id="email"
                                   class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter your email">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" id="password"
                                   class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Create a password">
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Payment Method -->
            <div class="px-4 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Method</h2>
                
                <div class="space-y-3">
                    <?php foreach ($paymentGateways as $gateway): ?>
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg bg-white cursor-pointer">
                            <input type="radio" name="gateway_id" value="<?= $gateway['id'] ?>" class="mr-3 w-4 h-4 text-primary focus:ring-primary" required <?= ($gateway['slug'] ?? '') === 'cod' ? 'data-cod="1"' : '' ?>>
                            <div class="flex items-center flex-1">
                                <?php 
                                $iconUrl = '';
                                if (!empty($gateway['logo'])) {
                                    $iconUrl = filter_var($gateway['logo'], FILTER_VALIDATE_URL) 
                                        ? $gateway['logo'] 
                                        : \App\Core\View::asset('uploads/gateways/' . $gateway['logo']);
                                } else {
                                    switch ($gateway['slug']) {
                                        case 'khalti':
                                            $iconUrl = \App\Core\View::asset('images/gateways/khalti.svg');
                                            break;
                                        case 'esewa':
                                            $iconUrl = \App\Core\View::asset('images/gateways/esewa.svg');
                                            break;
                                        case 'mypay':
                                            $iconUrl = \App\Core\View::asset('images/gateways/mypay.svg');
                                            break;
                                        case 'bank_transfer':
                                            $iconUrl = \App\Core\View::asset('images/gateways/bank.svg');
                                            break;
                                        case 'cod':
                                            $iconUrl = \App\Core\View::asset('images/gateways/cod.svg');
                                            break;
                                        default:
                                            $iconUrl = \App\Core\View::asset('images/gateways/default.svg');
                                    }
                                }
                                ?>
                                
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3 overflow-hidden">
                                    <?php if ($iconUrl): ?>
                                        <img src="<?= htmlspecialchars($iconUrl) ?>" 
                                             alt="<?= htmlspecialchars($gateway['name']) ?>" 
                                             class="w-full h-full object-contain"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <?php endif; ?>
                                    
                                    <div class="w-full h-full rounded-lg flex items-center justify-center <?= $gateway['type'] === 'cod' ? 'bg-green-100' : ($gateway['type'] === 'manual' ? 'bg-blue-100' : 'bg-purple-100') ?>" 
                                         style="<?= $iconUrl ? 'display: none;' : '' ?>">
                                        <?php if ($gateway['type'] === 'cod'): ?>
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        <?php elseif ($gateway['type'] === 'manual'): ?>
                                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex-1">
                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($gateway['name']) ?></span>
                                    <?php 
                                    $description = '';
                                    $trustBadge = '';
                                    switch ($gateway['slug']) {
                                        case 'khalti':
                                            $description = 'Pay with Khalti digital wallet';
                                            $trustBadge = 'Instant';
                                            break;
                                        case 'esewa':
                                            $description = 'Pay with eSewa digital wallet';
                                            $trustBadge = 'Instant';
                                            break;
                                        case 'cod':
                                            $description = 'Pay when your order arrives';
                                            $trustBadge = 'Free';
                                            break;
                                        case 'bank_transfer':
                                            $description = 'Transfer to our bank account';
                                            $trustBadge = 'Manual';
                                            break;
                                        default:
                                            $description = $gateway['description'] ?? 'Secure payment method';
                                            $trustBadge = 'Secure';
                                    }
                                    ?>
                                    <p class="text-xs text-gray-600"><?= htmlspecialchars($description) ?></p>
                                    <?php if ($gateway['type'] === 'cod'): ?>
                                        <!-- <p class="text-xs text-green-600 font-medium">✓ No extra charges</p> -->
                                    <?php elseif ($gateway['type'] === 'digital'): ?>
                                        <!-- <p class="text-xs text-blue-600 font-medium">✓ 100% secure</p> -->
                                    <?php endif; ?>
                                </div>
                                
                                <!-- <div class="text-right">
                                    <div class="text-green-600 text-sm font-medium">
                                        <?= $trustBadge ?>
                                    </div>
                                    <?php if ($gateway['type'] === 'digital'): ?>
                                        <div class="text-xs text-gray-500">SSL Protected</div>
                                    <?php endif; ?>
                                </div> -->
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bank Transfer Details -->
            <div id="bank-details" class="px-4 py-4 border-b border-gray-200 hidden">
                <div class="bg-primary/10 border border-primary/20 rounded-lg p-4">
                    <h3 class="font-semibold text-primary mb-3">Bank Transfer Details</h3>
                    <?php
                    $bankGateway = null;
                    foreach ($paymentGateways as $gateway) {
                        if ($gateway['slug'] === 'bank_transfer') {
                            $bankGateway = $gateway;
                            break;
                        }
                    }
                    if ($bankGateway) {
                        $bankParams = json_decode($bankGateway['parameters'], true) ?? [];
                    ?>
                        <div class="grid grid-cols-2 gap-3 text-sm text-primary mb-4">
                            <?php if (!empty($bankParams['bank_name'])): ?>
                                <div>
                                    <p class="font-medium">Bank:</p>
                                    <p><?= htmlspecialchars($bankParams['bank_name']) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($bankParams['account_number'])): ?>
                                <div>
                                    <p class="font-medium">Account:</p>
                                    <p><?= htmlspecialchars($bankParams['account_number']) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($bankParams['account_name'])): ?>
                                <div>
                                    <p class="font-medium">Name:</p>
                                    <p><?= htmlspecialchars($bankParams['account_name']) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($bankParams['branch'])): ?>
                                <div>
                                    <p class="font-medium">Branch:</p>
                                    <p><?= htmlspecialchars($bankParams['branch']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                    
                    <div class="space-y-3">
                        <div>
                            <label for="transaction_id" class="block text-sm font-medium text-gray-700 mb-1">Transaction ID *</label>
                            <input type="text" name="transaction_id" id="transaction_id"
                                   class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Enter transaction ID">
                        </div>
                        <div>
                            <label for="payment_screenshot" class="block text-sm font-medium text-gray-700 mb-1">Payment Screenshot *</label>
                            <input type="file" name="payment_screenshot" id="payment_screenshot" accept="image/*"
                                   class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 file:mr-3 file:py-2 file:px-3 file:bg-primary file:text-white file:font-medium file:rounded file:border-0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Notes -->
            <div class="px-4 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Notes (Optional)</h2>
                <textarea name="order_notes" id="order_notes" rows="3"
                          class="w-full px-2 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-primary focus:border-primary"
                          placeholder="Any special instructions for your order..."></textarea>
            </div>

            <!-- Order Summary -->
            <div class="px-4 py-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                
                <div class="space-y-3 mb-4">
                    <?php foreach ($cartItems as $item): ?>
                        <div id="checkout-item-<?= $item['id'] ?>" class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-12 h-12 overflow-hidden bg-gray-100 rounded-lg">
                                <?php $imageUrl = htmlspecialchars(getProductImageUrl($item['product'])); ?>
                                <img src="<?= $imageUrl ?>"
                                     alt="<?= htmlspecialchars($item['product']['product_name']) ?>"
                                     class="w-12 h-12 object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($item['product']['product_name']) ?></p>
                                <p class="text-xs text-gray-500">Qty: <?= $item['quantity'] ?> × <?= CurrencyHelper::format($item['product']['sale_price'] ?? $item['product']['price']) ?></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <p class="text-sm font-medium text-primary"><?= CurrencyHelper::format($item['subtotal']) ?></p>
                                <button type="button"
                                        class="text-gray-400 hover:text-red-600"
                                        aria-label="Remove item"
                                        onclick="removeCheckoutItem(<?= $item['id'] ?>, <?= $item['product']['id'] ?>, 'checkout-item-<?= $item['id'] ?>')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Coupon Section -->
                <div class="mb-4 p-3 bg-primary/5 border border-primary/20 rounded-lg" id="coupon-section">
                    <div class="flex items-center mb-2">
                        <svg class="w-4 h-4 text-primary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <span class="font-medium text-primary text-sm">Have a coupon?</span>
                    </div>
                    <?php if (isset($appliedCoupon) && $appliedCoupon): ?>
                        <div id="applied-coupon" class="flex items-center justify-between p-2 bg-accent/10 border border-accent/30 rounded">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-accent mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-accent"><?= htmlspecialchars($appliedCoupon['code']) ?></p>
                                    <p class="text-xs text-accent/70">Discount: <?= CurrencyHelper::format($couponDiscount) ?></p>
                                </div>
                            </div>
                            <button type="button" id="remove-coupon-btn" class="text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    <?php else: ?>
                        <div id="coupon-form">
                            <div class="flex space-x-2">
                                <input type="text" id="coupon-code" placeholder="Enter coupon code"
                                       class="flex-1 px-3 py-2 border border-primary/30 rounded text-sm focus:ring-2 focus:ring-primary focus:border-primary uppercase"
                                       style="text-transform: uppercase;">
                                <button type="button" id="apply-coupon-btn"
                                        class="px-3 py-2 bg-primary text-white text-sm font-medium rounded hover:bg-primary-dark">
                                    Apply
                                </button>
                            </div>
                            <div id="coupon-message" class="mt-2 text-sm hidden"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Totals -->
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal (<?= count($cartItems) ?> items)</span>
                        <span class="font-medium text-gray-900"><?= CurrencyHelper::format($total) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax (<?= (new \App\Models\Setting())->get('tax_rate', 13) ?>%)</span>
                        <span class="font-medium text-gray-900"><?= CurrencyHelper::format($tax) ?></span>
                    </div>
                    <?php if (isset($couponDiscount) && $couponDiscount > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-green-600">Coupon Discount</span>
                            <span class="font-medium text-green-600">-<?= CurrencyHelper::format($couponDiscount) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Delivery Fee</span>
                        <span id="delivery-fee" class="font-medium text-gray-900">रु0.00</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2">
                        <div class="flex justify-between">
                            <span class="text-lg font-semibold text-gray-900">Total</span>
                            <span id="final-total" class="text-xl font-bold text-primary"><?= CurrencyHelper::format($finalTotal) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Place Order Button -->
            <div class="px-4 pb-20">
                <!-- Spacer for sticky button -->
            </div>
        </form>
    </div>
    
    <!-- Trust Signals -->
    <!-- <div class="fixed bottom-20 left-0 right-0 bg-white border-t border-gray-100 p-4 z-40">
        <div class="flex items-center justify-center space-x-6 text-xs text-gray-600">
            <div class="flex items-center space-x-1">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <span>100% Secure</span>
            </div>
            <div class="flex items-center space-x-1">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span>SSL Protected</span>
            </div>
            <div class="flex items-center space-x-1">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <span>Privacy Protected</span>
            </div>
        </div>
    </div> -->

    <!-- Sticky Place Order Button -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 shadow-lg z-50">
        <div class="grid grid-cols-2 gap-3">
            <button type="button" id="cod-quick-btn" class="w-full bg-accent hover:bg-accent-dark border border-accent text-white px-6 py-3 rounded-2xl font-semibold text-sm flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                COD
            </button>
            <button type="submit" form="checkout-form" id="place-order-btn" class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-2xl font-semibold text-sm flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                </svg>
                Place Order
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkout-form');
    const placeOrderBtn = document.getElementById('place-order-btn');
    const codQuickBtn = document.getElementById('cod-quick-btn');
    const addressInput = document.getElementById('address_line1');
    const phoneInput = document.getElementById('phone');
    const nameInput = document.getElementById('recipient_name');
    const cityInput = document.getElementById('city');
    const citySuggestBox = document.getElementById('city-suggest-box');

    const deliveryMap = <?php
        $map = [];
        foreach ($deliveryCharges as $charge) {
            $map[$charge['location_name']] = (float) $charge['charge'];
        }
        echo json_encode($map, JSON_UNESCAPED_UNICODE);
    ?>;

    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days*24*60*60*1000));
        document.cookie = name + "=" + encodeURIComponent(value) + ";expires=" + d.toUTCString() + ";path=/";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length));
        }
        return null;
    }
    
    // Payment method selection
    const paymentMethods = document.querySelectorAll('input[name="gateway_id"]');
    const bankDetails = document.getElementById('bank-details');
    const transactionId = document.getElementById('transaction_id');
    const paymentScreenshot = document.getElementById('payment_screenshot');

    paymentMethods.forEach(function(method) {
        method.addEventListener('change', function() {
            const gatewayName = this.closest('label').querySelector('span').textContent;
            if (gatewayName.toLowerCase().includes('bank transfer')) {
                bankDetails.classList.remove('hidden');
                transactionId.setAttribute('required', 'required');
                paymentScreenshot.setAttribute('required', 'required');
            } else {
                bankDetails.classList.add('hidden');
                transactionId.removeAttribute('required');
                paymentScreenshot.removeAttribute('required');
            }
        });
    });

    // Quick select COD and submit immediately if minimal fields valid
    function minimalValid() {
        return (
            nameInput && nameInput.value.trim().length > 0 &&
            phoneInput && /^\d{9,15}$/.test(phoneInput.value.trim()) &&
            addressInput && addressInput.value.trim().length > 0 &&
            cityInput && cityInput.value.trim().length > 0
        );
    }

    if (codQuickBtn) {
        codQuickBtn.addEventListener('click', function() {
            let codSelected = false;
            paymentMethods.forEach(function(method) {
                if (method.dataset.cod === '1') {
                    method.checked = true;
                    codSelected = true;
                }
            });
            if (!codSelected) {
                paymentMethods.forEach(function(method) {
                    const txt = method.closest('label').innerText.toLowerCase();
                    if (txt.includes('cash on delivery') || txt.includes('cod')) {
                        method.checked = true;
                        codSelected = true;
                    }
                });
            }
            codQuickBtn.classList.add('ring-2','ring-accent-light');
            setTimeout(()=>codQuickBtn.classList.remove('ring-2','ring-accent-light'),800);
            if (minimalValid()) {
                confirmed = true;
                checkoutForm.submit();
            } else {
                errorToast('Please fill Name, Phone, Address, City');
            }
        });
    }

    // Optional account creation fields toggle
    const createAccountCheckbox = document.getElementById('create_account');
    const accountFields = document.getElementById('account-fields');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    if (createAccountCheckbox) {
        createAccountCheckbox.addEventListener('change', function() {
            if (this.checked) {
                accountFields.classList.remove('hidden');
                emailInput.setAttribute('required', 'required');
                passwordInput.setAttribute('required', 'required');
            } else {
                accountFields.classList.add('hidden');
                emailInput.removeAttribute('required');
                passwordInput.removeAttribute('required');
            }
        });
    }

    // Coupon functionality
    const couponCode = document.getElementById('coupon-code');
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const removeCouponBtn = document.getElementById('remove-coupon-btn');
    const couponMessage = document.getElementById('coupon-message');
    
    if (couponCode) {
        couponCode.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            
            if (applyCouponBtn) {
                if (this.value.trim().length > 0) {
                    applyCouponBtn.disabled = false;
                    applyCouponBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    applyCouponBtn.disabled = true;
                    applyCouponBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        });
        
        couponCode.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyCoupon();
            }
        });
    }
    
    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', applyCoupon);
        
        if (couponCode && couponCode.value.trim().length === 0) {
            applyCouponBtn.disabled = true;
            applyCouponBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
    
    if (removeCouponBtn) {
        removeCouponBtn.addEventListener('click', removeCoupon);
    }

    function applyCoupon() {
        const code = couponCode.value.trim();
        if (!code) {
            showCouponMessage('Please enter a coupon code', 'error');
            return;
        }

        applyCouponBtn.disabled = true;
        applyCouponBtn.textContent = 'Applying...';
        couponCode.disabled = true;

        fetch('<?= ASSETS_URL ?>/checkout/validateCoupon', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCouponDisplay(data.coupon, data.discount, data.final_amount);
                showCouponMessage('Coupon applied successfully!', 'success');
                couponCode.value = '';
            } else {
                showCouponMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCouponMessage('Failed to apply coupon. Please try again.', 'error');
        })
        .finally(() => {
            applyCouponBtn.disabled = false;
            applyCouponBtn.textContent = 'Apply';
            couponCode.disabled = false;
        });
    }

    function removeCoupon() {
        removeCouponBtn.disabled = true;
        removeCouponBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';

        fetch('<?= ASSETS_URL ?>/checkout/removeCoupon', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                removeCouponDisplay(data.final_amount);
                showCouponMessage('Coupon removed successfully!', 'success');
            } else {
                showCouponMessage(data.message || 'Failed to remove coupon', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCouponMessage('Failed to remove coupon. Please try again.', 'error');
        })
        .finally(() => {
            removeCouponBtn.disabled = false;
            removeCouponBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
        });
    }

    function showCouponMessage(message, type) {
        couponMessage.textContent = message;
        couponMessage.className = `mt-2 text-sm ${type === 'error' ? 'text-red-600' : 'text-green-600'}`;
        couponMessage.classList.remove('hidden');
        setTimeout(() => {
            couponMessage.classList.add('hidden');
        }, 5000);
    }

    function updateCouponDisplay(coupon, discount, finalAmount) {
        const couponForm = document.getElementById('coupon-form');
        if (couponForm) {
            couponForm.style.display = 'none';
        }

        const couponSection = document.getElementById('coupon-section');
        const appliedCouponHtml = `
            <div id="applied-coupon" class="flex items-center justify-between p-2 bg-accent/10 border border-accent/30 rounded">
                <div class="flex items-center">
                    <svg class="w-4 h-4 text-accent mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-accent">${coupon.code}</p>
                        <p class="text-xs text-accent/70">Discount: रु${parseFloat(discount).toFixed(2)}</p>
                    </div>
                </div>
                <button type="button" id="remove-coupon-btn" class="text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        const header = couponSection.querySelector('.flex.items-center.mb-2');
        header.insertAdjacentHTML('afterend', appliedCouponHtml);

        const newRemoveBtn = document.getElementById('remove-coupon-btn');
        if (newRemoveBtn) {
            newRemoveBtn.addEventListener('click', removeCoupon);
        }

        updateOrderTotals(discount, finalAmount);
    }

    function removeCouponDisplay(finalAmount) {
        const appliedCoupon = document.getElementById('applied-coupon');
        if (appliedCoupon) {
            appliedCoupon.remove();
        }

        const couponForm = document.getElementById('coupon-form');
        if (couponForm) {
            couponForm.style.display = 'block';
        }

        updateOrderTotals(0, finalAmount);
    }

    function updateOrderTotals(discount, finalAmount) {
        let couponDiscountLine = document.querySelector('.coupon-discount-line');
        
        if (discount > 0) {
            if (!couponDiscountLine) {
                const totalsSection = document.querySelector('.space-y-2.text-sm');
                const taxLine = totalsSection.querySelector('.flex.justify-between:nth-child(2)');
                
                if (taxLine) {
                    const couponHtml = `
                        <div class="flex justify-between coupon-discount-line">
                            <span class="text-green-600">Coupon Discount</span>
                            <span class="font-medium text-green-600">-रु${parseFloat(discount).toFixed(2)}</span>
                        </div>
                    `;
                    taxLine.insertAdjacentHTML('afterend', couponHtml);
                }
            } else {
                const discountAmount = couponDiscountLine.querySelector('.font-medium');
                if (discountAmount) {
                    discountAmount.textContent = `-रु${parseFloat(discount).toFixed(2)}`;
                }
            }
        } else {
            if (couponDiscountLine) {
                couponDiscountLine.remove();
            }
        }

        const finalTotalElement = document.getElementById('final-total');
        if (finalTotalElement) {
            finalTotalElement.textContent = `रु${parseFloat(finalAmount).toFixed(2)}`;
        }
    }

    // Delivery fee calculation
    const deliveryFeeElement = document.getElementById('delivery-fee');
    let currentDeliveryFee = 0;

    // Save name to cookies
    if (nameInput) {
        const savedName = getCookie('guest_recipient_name');
        if (savedName && !nameInput.value) {
            nameInput.value = savedName;
        }
        nameInput.addEventListener('input', function() {
            setCookie('guest_recipient_name', this.value, 30);
        });
    }

    // Save phone to cookies
    if (phoneInput) {
        const savedPhone = getCookie('guest_phone');
        if (savedPhone && !phoneInput.value) {
            phoneInput.value = savedPhone;
        }
        phoneInput.addEventListener('input', function() {
            setCookie('guest_phone', this.value, 30);
        });
    }

    if (addressInput) {
        const savedAddress = getCookie('guest_address_line1');
        if (savedAddress && !addressInput.value) {
            addressInput.value = savedAddress;
        }
        addressInput.addEventListener('input', function() {
            setCookie('guest_address_line1', this.value, 30);
        });
    }

    if (cityInput) {
        const savedCity = getCookie('guest_city');
        if (savedCity) {
            cityInput.value = savedCity;
        }
        // Build interactive suggestions for broader device support (iOS included)
        const locations = Object.keys(deliveryMap || {});
        function renderSuggestions(list) {
            if (!citySuggestBox) return;
            if (!list.length) {
                citySuggestBox.classList.add('hidden');
                citySuggestBox.innerHTML = '';
                return;
            }
            citySuggestBox.innerHTML = list.map(loc => `
                <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-100" data-city="${loc}">${loc}</button>
            `).join('');
            citySuggestBox.classList.remove('hidden');
        }
        function filterLocations(q) {
            const term = (q || '').toLowerCase();
            return locations.filter(loc => loc.toLowerCase().includes(term)).slice(0, 20);
        }
        cityInput.addEventListener('input', function() {
            const list = filterLocations(this.value);
            renderSuggestions(list);
            updateDeliveryFee();
            setCookie('guest_city', this.value, 30);
        });
        cityInput.addEventListener('focus', function(){
            const list = filterLocations(this.value);
            renderSuggestions(list);
        });
        cityInput.addEventListener('blur', function(){
            // Delay hiding to allow clicks
            setTimeout(()=>{ if (citySuggestBox) citySuggestBox.classList.add('hidden'); }, 150);
        });
        if (citySuggestBox) {
            citySuggestBox.addEventListener('click', function(e){
                const btn = e.target.closest('button[data-city]');
                if (!btn) return;
                cityInput.value = btn.dataset.city;
                updateDeliveryFee();
                setCookie('guest_city', cityInput.value, 30);
                citySuggestBox.classList.add('hidden');
                cityInput.blur();
            });
        }
    }
    
    function updateDeliveryFee() {
        if (!cityInput) return;
        const city = (cityInput.value || '').trim();
        if (!city) return;
        if (city.toLowerCase() === 'other') {
            currentDeliveryFee = 300;
            deliveryFeeElement.textContent = 'रु300.00';
            deliveryFeeElement.className = 'font-medium text-gray-900';
        } else {
            const fee = deliveryMap[city];
            currentDeliveryFee = typeof fee !== 'undefined' ? parseFloat(fee) : 0;
            if (currentDeliveryFee === 0) {
                deliveryFeeElement.textContent = 'Free';
                deliveryFeeElement.className = 'font-medium text-green-600';
            } else {
                deliveryFeeElement.textContent = `रु${currentDeliveryFee.toFixed(2)}`;
                deliveryFeeElement.className = 'font-medium text-gray-900';
            }
        }
        updateFinalTotal();
    }
    
    function updateFinalTotal() {
        const subtotal = <?= $total ?>;
        const tax = <?= $tax ?>;
        const couponDiscount = <?= $couponDiscount ?? 0 ?>;
        const finalAmount = subtotal + tax + currentDeliveryFee - couponDiscount;
        
        const finalTotalElement = document.getElementById('final-total');
        if (finalTotalElement) {
            finalTotalElement.textContent = `रु${finalAmount.toFixed(2)}`;
        }
    }
    
    if (cityInput) {
        cityInput.addEventListener('input', function() {
            updateDeliveryFee();
            setCookie('guest_city', this.value, 30);
        });
        updateDeliveryFee();
    }

// Auto-location detection
function detectLocation() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by this browser.');
        return;
    }
    
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    button.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    button.disabled = true;
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Use reverse geocoding to get city name
            fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=en`)
                .then(response => response.json())
                .then(data => {
                    const city = data.city || data.locality || data.principalSubdivision;
                    if (city) {
                        // Try to match with available delivery locations
                        const cityInput = document.getElementById('city');
                        const locations = Object.keys(deliveryMap || {});
                        let matched = locations.find(loc => loc.toLowerCase().includes(city.toLowerCase()) || city.toLowerCase().includes(loc.toLowerCase()));
                        if (matched) {
                            cityInput.value = matched;
                            updateDeliveryFee();
                            showLocationAlert('success', `Location detected: ${city}. Delivery fee updated.`);
                        } else {
                            showLocationAlert('info', `Location detected: ${city}. Please type your city and select from suggestions.`);
                        }
                    } else {
                        showLocationAlert('warning', 'Could not determine city from location. Please select manually.');
                    }
                })
                .catch(error => {
                    console.error('Geocoding error:', error);
                    showLocationAlert('error', 'Could not determine city from location. Please select manually.');
                })
                .finally(() => {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
        },
        function(error) {
            console.error('Geolocation error:', error);
            let message = 'Could not detect location. ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message += 'Location access denied.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message += 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    message += 'Location request timed out.';
                    break;
                default:
                    message += 'Unknown error occurred.';
                    break;
            }
            showLocationAlert('error', message);
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    );
}

function showLocationAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
        type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' :
        type === 'info' ? 'bg-blue-100 border border-blue-400 text-blue-700' :
        type === 'warning' ? 'bg-primary/10 border border-primary/30 text-primary' :
        'bg-red-100 border border-red-400 text-red-700'
    }`;
    alertDiv.innerHTML = `
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    ${type === 'success' ? 
                        '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>' :
                        type === 'info' ?
                        '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>' :
                        '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>'
                    }
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">${message}</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Remove alert after 4 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 4000);
}

    // Basic validation and review modal
    const errorToast = (msg) => {
        const el = document.createElement('div');
        el.className = 'fixed bottom-20 left-1/2 -translate-x-1/2 bg-red-600 text-white px-4 py-2 rounded-lg shadow z-50';
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(()=>el.remove(), 2500);
    };

    let confirmed = false;
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            if (confirmed) {
                placeOrderBtn.disabled = true;
                placeOrderBtn.innerHTML = `
                    <div class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full mr-2"></div>
                    Processing Order...
                `;
                checkoutForm.dataset.submitted = 'true';
                return;
            }
            e.preventDefault();
            // Basic validations
            if (!nameInput.value.trim()) { errorToast('Please enter your full name'); return; }
            if (!phoneInput.value.trim() || !/^\d{9,15}$/.test(phoneInput.value.trim())) { errorToast('Enter a valid phone number'); return; }
            if (!addressInput.value.trim()) { errorToast('Please enter your address'); return; }
            if (!cityInput || !cityInput.value.trim()) { errorToast('Please enter your city'); return; }
            // Show review modal
            openReviewModal();
        });
    }

    function openReviewModal() {
        let modal = document.getElementById('order-review-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'order-review-modal';
            modal.className = 'fixed inset-0 bg-black/40 flex items-end md:items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white w-full md:max-w-lg rounded-t-2xl md:rounded-2xl shadow-lg">
                    <div class="p-4 border-b">
                        <h3 class="text-lg font-semibold">Confirm Your Order</h3>
                        <p class="text-xs text-gray-500">Please review your details before placing the order.</p>
                    </div>
                    <div class="p-4 space-y-3 text-sm">
                        <div>
                            <p class="text-gray-600">Name</p>
                            <p class="font-medium" id="rv-name"></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Phone</p>
                            <p class="font-medium" id="rv-phone"></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Address</p>
                            <p class="font-medium" id="rv-address"></p>
                        </div>
                        <div class="flex justify-between pt-2 border-t">
                            <span class="text-gray-600">Total</span>
                            <span class="text-primary font-bold" id="rv-total"></span>
                        </div>
                    </div>
                    <div class="p-4 grid grid-cols-2 gap-3">
                        <button type="button" class="px-3 py-2 rounded-2xl border" id="rv-edit">Edit</button>
                        <button type="button" class="px-3 py-2 rounded-2xl  bg-primary hover:bg-primary-dark text-white" id="rv-confirm">Confirm</button>
                    </div>
                </div>`;
            document.body.appendChild(modal);
        }
        // Populate
        document.getElementById('rv-name').textContent = nameInput.value.trim();
        document.getElementById('rv-phone').textContent = phoneInput.value.trim();
        document.getElementById('rv-address').textContent = addressInput.value.trim();
        document.getElementById('rv-total').textContent = document.getElementById('final-total')?.textContent || '';
        // Wire buttons
        modal.querySelector('#rv-edit').onclick = () => { modal.remove(); };
        modal.querySelector('#rv-confirm').onclick = () => {
            confirmed = true;
            modal.remove();
            checkoutForm.submit();
        };
    }
});
</script>

<script>
// Lightweight removal handler for checkout summary items
window.removeCheckoutItem = function(cartItemId, productId, rowId) {
    const btn = event && event.currentTarget ? event.currentTarget : null;
    if (btn) { btn.disabled = true; }

    fetch('<?= ASSETS_URL ?>/cart/remove', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `cart_item_id=${cartItemId}&product_id=${productId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data && data.success) {
            const row = document.getElementById(rowId);
            if (row) row.remove();
            // Reload to ensure totals and payment sections reflect latest cart state
            window.location.reload();
        } else {
            alert((data && data.message) || 'Failed to remove item');
        }
    })
    .catch(err => {
        console.error('Remove item error:', err);
        alert('Failed to remove item');
    })
    .finally(() => { if (btn) btn.disabled = false; });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>