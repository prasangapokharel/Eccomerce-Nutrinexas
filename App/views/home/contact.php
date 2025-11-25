<?php ob_start(); ?>
<div class="container mx-auto px-4 py-12">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6 text-center">Contact Nutri Nexus</h1>
        
        <!-- Contact Information -->
        <div class="bg-white rounded-lg shadow-md p-8 mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Get in Touch</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-phone text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Phone</h3>
                    <p class="text-gray-600">977-9811388848</p>
                    <p class="text-sm text-gray-500">24/7 Business Support</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-envelope text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Email</h3>
                    <p class="text-gray-600">nutrinexusnp@gmail.com</p>
                    <p class="text-sm text-gray-500">Quick Response</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-truck text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Delivery Partner</h3>
                    <p class="text-gray-600">Everest Courier</p>
                    <p class="text-sm text-gray-500">Fast & Reliable</p>
                </div>
            </div>
        </div>
        

    </div>
</div>
<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
