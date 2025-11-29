<?php 
ob_start(); 
use App\Helpers\CurrencyHelper;
use App\Helpers\PaymentGatewayHelper;
use App\Models\Setting;

// Get website URL from settings table
$settingModel = new Setting();
$baseUrl = $settingModel->get('website_url', URLROOT);

// Get dynamic Khalti logo
$khaltiLogo = PaymentGatewayHelper::getGatewayLogo('Khalti');
?>
<div class="container mx-auto px-4 py-12">
    <div class="max-w-3xl mx-auto bg-white cardclip shadow-md p-8">
        <div class="flex flex-col items-center mb-8">
            <div class="rounded-full bg-primary/10 p-3 mb-4">
                <img src="<?= htmlspecialchars($khaltiLogo) ?>" alt="Khalti" class="w-16 h-16" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <svg class="w-16 h-16 text-primary hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">Complete Your Payment</h1>
            <p class="text-gray-600 text-lg">Secure payment with Khalti. Click the button below to proceed.</p>
        </div>

        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
            <div class="bg-neutral-50 p-6 rounded-2xl border border-neutral-200">
                <div class="flex justify-between mb-4 pb-3 border-b border-neutral-200">
                    <span class="text-neutral-600">Order Number:</span>
                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($order['invoice']) ?></span>
                </div>
                
                <?php
                // Recalculate totals using same logic as checkout and eSewa to ensure accuracy
                // Get order items to calculate subtotal
                $orderItemModel = new \App\Models\OrderItem();
                $orderItems = $orderItemModel->getByOrderId($order['id']);
                $itemCount = count($orderItems);
                
                // Calculate subtotal from order items (same as checkout)
                $subtotal = 0;
                foreach ($orderItems as $item) {
                    $itemPrice = ($item['price'] ?? 0);
                    $itemQuantity = ($item['quantity'] ?? 1);
                    $subtotal += $itemPrice * $itemQuantity;
                }
                
                // Get stored values
                $discountAmount = round($order['discount_amount'] ?? 0, 2);
                $deliveryFee = round($order['delivery_fee'] ?? 0, 2);
                $taxRate = (new \App\Models\Setting())->get('tax_rate', 12);
                
                // Recalculate using same service as checkout
                $totals = \App\Services\OrderCalculationService::calculateTotals(
                    $subtotal,
                    $discountAmount,
                    $deliveryFee,
                    $taxRate
                );
                
                // Use recalculated values for display
                $taxAmount = round($totals['tax'], 2);
                $totalAmount = round($totals['total'], 2);
                ?>
                
                <div class="space-y-3 text-sm text-neutral-600">
                    <div class="flex justify-between">
                        <span>Subtotal (<?= $itemCount ?> items)</span>
                        <span class="font-semibold text-gray-900"><?= CurrencyHelper::format($subtotal) ?></span>
                    </div>
                    
                    <?php if ($discountAmount > 0): ?>
                        <div class="flex justify-between text-success">
                            <span>Coupon Discount</span>
                            <span class="font-semibold">-<?= CurrencyHelper::format($discountAmount) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-between">
                        <span>Tax (<?= $taxRate ?>%)</span>
                        <span class="font-semibold text-gray-900"><?= CurrencyHelper::format($taxAmount) ?></span>
                    </div>
                    
                    <?php if ($deliveryFee > 0): ?>
                        <div class="flex justify-between">
                            <span>Delivery Fee</span>
                            <span class="font-semibold text-gray-900"><?= CurrencyHelper::format($deliveryFee) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="border-t border-neutral-200 pt-3 flex items-center justify-between">
                        <span class="text-base font-semibold text-gray-900">Total</span>
                        <span class="text-xl font-bold text-primary"><?= CurrencyHelper::format($totalAmount) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading overlay -->
        <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white p-5 rounded-lg flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary mb-3"></div>
                <p class="text-gray-800 font-medium">Processing payment...</p>
            </div>
        </div>

        <!-- Payment success animation -->
        <div id="paymentSuccessAnimation" class="fixed inset-0 bg-black bg-opacity-50 flex flex-col items-center justify-center z-50 hidden">
            <div class="bg-white p-8 rounded-lg flex flex-col items-center">
                <div class="bg-success/10 rounded-full p-3 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h2>
                <p class="text-gray-600 mb-4">Your order has been confirmed.</p>
            </div>
        </div>

        <!-- Payment error display -->
        <div id="paymentError" class="bg-error/10 border border-error text-error rounded-lg p-4 mb-6 hidden">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-error" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium" id="errorMessage"></p>
                </div>
            </div>
        </div>

        <div class="flex justify-center">
            <button id="payment-button" class="w-full px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg flex items-center justify-center">
                <span class="mr-2">Pay with Khalti</span>
                <img src="<?= htmlspecialchars($khaltiLogo) ?>" alt="Khalti" class="h-6 w-auto" onerror="this.style.display='none';">
            </button>
        </div>

        <div class="mt-6 text-center">
            <a href="<?= URLROOT ?>/orders" class="text-gray-600 hover:text-gray-900">
                Cancel and return to orders
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentButton = document.getElementById('payment-button');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const paymentSuccessAnimation = document.getElementById('paymentSuccessAnimation');
    const paymentError = document.getElementById('paymentError');
    const errorMessage = document.getElementById('errorMessage');
    
    // Variables to store payment information
    let paymentPidx = '';
    let paymentCheckInterval = null;
    
    function showError(message) {
        paymentError.classList.remove('hidden');
        errorMessage.textContent = message || 'Payment failed. Please try again.';
        paymentButton.disabled = false;
        paymentButton.innerHTML = '<span class="mr-2">Pay with Khalti</span><img src="<?= htmlspecialchars($khaltiLogo) ?>" alt="Khalti" class="h-6 w-auto">';
    }
    
    function initiatePayment() {
        // Show loading overlay
        loadingOverlay.classList.remove('hidden');
        paymentError.classList.add('hidden');
        paymentButton.disabled = true;
        
        fetch('<?= $baseUrl ?>/checkout/initiateKhalti/<?= $order['id'] ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Hide loading overlay
            loadingOverlay.classList.add('hidden');
            
            if (data.success && data.payment_url) {
                // Store payment ID for status checking
                paymentPidx = data.pidx;
                
                // Open Khalti payment page
                window.location.href = data.payment_url;
                
                // Start checking payment status
                startPaymentStatusCheck();
            } else {
                showError(data.message || 'Payment initiation failed');
                console.error('Payment error:', data);
            }
        })
        .catch(error => {
            // Hide loading overlay
            loadingOverlay.classList.add('hidden');
            
            console.error('Error:', error);
            showError('An error occurred. Please try again.');
        });
    }
    
    // Function to start checking payment status
    function startPaymentStatusCheck() {
        // Clear any existing interval
        if (paymentCheckInterval) {
            clearInterval(paymentCheckInterval);
        }
        
        // Set interval to check status every 5 seconds
        paymentCheckInterval = setInterval(checkPaymentStatus, 5000);
    }
    
    // Function to check payment status
    function checkPaymentStatus() {
        if (!paymentPidx) return;
        
                fetch('<?= $baseUrl ?>/checkout/verifyKhalti', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                pidx: paymentPidx
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.status === 'completed') {
                    // Payment successful
                    clearInterval(paymentCheckInterval);
                    
                    // Show success animation
                    paymentSuccessAnimation.classList.remove('hidden');
                    
                    // Then redirect to success page
                    setTimeout(() => {
                        window.location.href = data.redirect || '<?= $baseUrl ?>/checkout/success/<?= $order['id'] ?>';
                    }, 2000);
                }
            } else {
                // Error checking status
                console.error('Error checking payment status:', data.message);
            }
        })
        .catch(error => {
            console.error('Error checking payment status:', error);
        });
    }
    
    paymentButton.addEventListener('click', initiatePayment);
});
</script>
<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
