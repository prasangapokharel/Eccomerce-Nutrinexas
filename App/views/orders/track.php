<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">Track Your Order</h1>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" action="<?= \App\Core\View::url('orders/track') ?>">
                <div class="mb-6">
                    <label for="invoice" class="block text-sm font-medium text-gray-700 mb-2">
                        Order Number
                    </label>
                    <input 
                        type="text" 
                        id="invoice" 
                        name="invoice" 
                        required
                        placeholder="Enter your order number (e.g., NTX202507023317)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?= htmlspecialchars($_POST['invoice'] ?? '') ?>"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        You can find your order number in your order confirmation email.
                    </p>
                </div>
                
                <button 
                    type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200"
                >
                    <i class="fas fa-search mr-2"></i>
                    Track Order
                </button>
            </form>
        </div>
        
        <div class="mt-8 text-center">
            <p class="text-gray-600 text-sm">
                Don't have an account? 
                <a href="<?= \App\Core\View::url('auth/register') ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                    Create one here
                </a> to track all your orders easily.
            </p>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
