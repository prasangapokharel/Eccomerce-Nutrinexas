<?php 
ob_start(); 
use App\Helpers\CurrencyHelper;
use App\Helpers\PaymentGatewayHelper;
use App\Models\Setting;

// Get website URL from settings table
$settingModel = new Setting();
$baseUrl = $settingModel->get('website_url', URLROOT);

// Get dynamic eSewa logo
$esewaLogo = PaymentGatewayHelper::getGatewayLogo('eSewa');
?>
<div class="container mx-auto px-4 py-12">
    <div class="max-w-3xl mx-auto bg-white cardclip shadow-md p-8">
        <div class="flex flex-col items-center mb-8">
            <div class="rounded-full bg-primary/10 p-3 mb-4">
                <img src="<?= htmlspecialchars($esewaLogo) ?>" alt="eSewa" class="w-16 h-16" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <svg class="w-16 h-16 text-primary hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">Complete Your Payment</h1>
            <p class="text-gray-600 text-lg">Secure payment with eSewa. Click the button below to proceed.</p>
        </div>

        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
            <div class="bg-gray-50 p-6 rounded-lg">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Order Number:</span>
                    <span class="font-medium"><?= htmlspecialchars($order['invoice']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Amount:</span>
                    <span class="font-medium"><?= CurrencyHelper::format($order['total_amount']) ?></span>
                </div>
            </div>
        </div>

        <!-- Loading overlay -->
        <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white p-8 rounded-lg flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary mb-3"></div>
                <p class="text-gray-800 font-medium">Processing payment...</p>
            </div>
        </div>

        <!-- Payment success animation -->
        <div id="paymentSuccessAnimation" class="fixed inset-0 bg-black bg-opacity-50 flex flex-col items-center justify-center z-50 hidden">
            <div class="bg-white p-8 rounded-lg flex flex-col items-center">
                <div class="bg-green-100 rounded-full p-3 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h2>
                <p class="text-gray-600 mb-4">Your order has been confirmed.</p>
            </div>
        </div>

        <!-- Payment error display -->
        <div id="paymentError" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-6 hidden">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
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
                <span class="mr-2">Pay with eSewa</span>
                <img src="<?= htmlspecialchars($esewaLogo) ?>" alt="eSewa" class="h-6 w-auto" onerror="this.style.display='none';">
            </button>
        </div>

        <div class="mt-6 text-center">
            <a href="<?= $baseUrl ?>/orders" class="text-gray-600 hover:text-gray-900">
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
    
    function showError(message) {
        paymentError.classList.remove('hidden');
        errorMessage.textContent = message || 'Payment failed. Please try again.';
        paymentButton.disabled = false;
        paymentButton.innerHTML = '<span class="mr-2">Pay with eSewa</span><img src="<?= htmlspecialchars($esewaLogo) ?>" alt="eSewa" class="h-6 w-auto">';
    }
    
    function initiatePayment() {
        // Show loading overlay
        loadingOverlay.classList.remove('hidden');
        paymentError.classList.add('hidden');
        paymentButton.disabled = true;
        
        console.log('Initiating eSewa payment for order:', <?= $order['id'] ?>);
        console.log('Request URL:', '<?= $baseUrl ?>/checkout/initiateEsewa/<?= $order['id'] ?>');
        
        fetch('<?= $baseUrl ?>/checkout/initiateEsewa/<?= $order['id'] ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Hide loading overlay
            loadingOverlay.classList.add('hidden');
            
            if (data.success && data.payment_data && data.payment_url) {
                console.log('eSewa Payment Data:', data.payment_data);
                console.log('eSewa Payment URL:', data.payment_url);
                console.log('eSewa Mode:', data.mode);
                
                // Create form and submit to eSewa
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = data.payment_url;
                form.target = '_blank';
                
                // Add all payment data as hidden inputs
                Object.keys(data.payment_data).forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = data.payment_data[key];
                    form.appendChild(input);
                });
                
                // Log the form data for debugging
                console.log('Submitting form to:', data.payment_url);
                console.log('Form data:', data.payment_data);
                
                // Append form to body and submit
                document.body.appendChild(form);
                
                // Add a small delay to ensure form is properly appended
                setTimeout(() => {
                    console.log('Submitting form now...');
                    form.submit();
                    
                    // Remove form after a delay to ensure submission completes
                    setTimeout(() => {
                        if (document.body.contains(form)) {
                            document.body.removeChild(form);
                        }
                    }, 1000);
                }, 100);
                
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
    
    paymentButton.addEventListener('click', initiatePayment);
});
</script>
<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>