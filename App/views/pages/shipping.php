<?php ob_start(); ?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">Shipping Information</h1>
        
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Delivery Timeline</h2>
            <p class="text-gray-700 mb-6">
                We deliver across Nepal with our trusted courier partners. Standard delivery time is 3-4 working days for major cities.
            </p>
            
            <div class="space-y-4">
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Order Processing</h3>
                    <p class="text-gray-600">Orders are processed within 24 hours of confirmation.</p>
                </div>
                
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Shipping</h3>
                    <p class="text-gray-600">Items are shipped within 1-2 business days after processing.</p>
                </div>
                
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Delivery</h3>
                    <p class="text-gray-600">Delivery takes 3-4 working days for major cities, 4-5 days for other areas.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Shipping Charges</h2>
            
            <div class="space-y-4">
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Free Shipping</h3>
                    <p class="text-gray-700">On orders above रु 999</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Standard Shipping</h3>
                    <p class="text-gray-700">रु 99 on orders below रु 999</p>
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <p class="text-gray-800">
                        <strong>COD Charges:</strong> रु 49 additional for Cash on Delivery orders below रु 1499
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Delivery Areas</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Major Cities</h3>
                    <p class="text-gray-600">Kathmandu, Lalitpur, Bhaktapur, Pokhara, Biratnagar - 3 working days</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Other Cities</h3>
                    <p class="text-gray-600">Towns and areas across Nepal - 3-4 working days</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Remote Areas</h3>
                    <p class="text-gray-600">Rural and remote locations - 4-5 working days</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Order Tracking</h2>
            
            <p class="text-gray-700 mb-4">
                Track your order status through multiple channels:
            </p>
            
            <ul class="space-y-2 text-gray-600 mb-6">
                <li>SMS updates on order status</li>
                <li>Email notifications with tracking ID</li>
                <li>Real-time tracking on courier website</li>
                <li>Account dashboard tracking</li>
            </ul>
            
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Need Help?</h3>
                <p class="text-gray-700 mb-4">
                    Contact our shipping support team for any delivery-related queries.
                </p>
                <div class="space-y-2">
                    <p class="text-gray-700">
                        Phone: 9811388848
                    </p>
                    <p class="text-gray-700">
                        Email: shipping@nutrinexus.com
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
