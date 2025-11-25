<?php ob_start(); ?>
<?php
// Enable all errors and warnings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Optionally set headers to show that errors may appear
header('Content-Type: text/html; charset=utf-8');
?>
<div class="container mx-auto px-4 py-8">
    <!-- Page Title -->
    <div class="bg-primary-DEFAULT text-white py-8">
        <div class="container mx-auto px-4">
            <h1 class="text-2xl md:text-3xl font-bold text-black">Product Authenticator</h1>
            <div class="flex items-center text-sm mt-2">
                <a href="/" class="text-accent-DEFAULT">Home</a>
                <span class="mx-2">/</span>
                <span>Authenticator</span>
            </div>
        </div>
    </div>

    <!-- Authenticator Content -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
        <div class="flex items-center justify-center mb-6">
            <img src="https://wellversed.in/cdn/shop/files/logo_8c9a5eb8-66bc-4f74-94cb-5555379d90e1_200x80.svg?v=1744814193" alt="Wellcore Logo" class="h-12">
        </div>
        <h2 class="text-xl font-semibold text-primary-DEFAULT mb-4 text-center">Verify Your Product</h2>
        <p class="text-gray-600 mb-4 text-center">To ensure the authenticity of your product, please use our secure authenticator tool. Click the button below to proceed.</p>
        <p class="text-red-600 font-medium text-center mb-6">Notice: Please authenticate the product before consuming. Otherwise, return as soon as possible.</p>
        <div class="flex justify-center bg-primary">
            <a 
                href="https://wellversed.in/pages/authenticator?srsltid=AfmBOoowZZ3retpCCgE7cspdkdtOHwpI_n_aSym81HATVnZDhftsMYr4" 
                target="_blank" 
                class="inline-block px-8 py-3 bg-primary-DEFAULT text-white rounded-lg hover:bg-primary-dark transition duration-200"
            >
                Open Authenticator
            </a>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>