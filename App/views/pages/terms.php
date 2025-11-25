<?php ob_start(); ?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">Terms and Conditions</h1>
        
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <p class="text-gray-700 mb-4">
                Welcome to NutriNexus. By using our services, you agree to these terms and conditions.
            </p>
            <p class="text-sm text-gray-500">
                <strong>Last Updated:</strong> <?= date('F j, Y') ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Acceptance of Terms</h2>
            <p class="text-gray-600 mb-4">
                By accessing and using NutriNexus website and services, you accept and agree to be bound by the terms and provision of this agreement.
            </p>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-gray-700">
                    If you do not agree to these terms, please do not use our services.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Product Information & Orders</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Product Accuracy</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>All product information is provided in good faith</li>
                        <li>Prices and availability subject to change</li>
                        <li>Product images are for illustration purposes</li>
                        <li>We reserve the right to limit quantities</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Order Processing</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>Orders processed within 24 hours</li>
                        <li>Payment confirmation required</li>
                        <li>We may cancel orders for any reason</li>
                        <li>Order modifications subject to availability</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Payment & Pricing</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">Accepted Payment Methods</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>Credit Cards</li>
                        <li>Debit Cards</li>
                        <li>Net Banking</li>
                        <li>UPI</li>
                        <li>Cash on Delivery (COD)</li>
                    </ul>
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <p class="text-gray-800">
                        All prices are in Nepali Rupees (रु) and include applicable taxes.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">User Responsibilities</h2>
            
            <div class="space-y-3">
                <p class="text-gray-600">Provide accurate and complete information during registration and ordering</p>
                <p class="text-gray-600">Use products as directed and consult healthcare professionals when needed</p>
                <p class="text-gray-600">Not misuse our website or engage in fraudulent activities</p>
                <p class="text-gray-600">Respect intellectual property rights and not reproduce our content</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Limitation of Liability</h2>
            
            <p class="text-gray-600 mb-4">
                NutriNexus shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service.
            </p>
            
            <p class="text-gray-600">
                Our total liability for any claim arising from your use of our services shall not exceed the amount you paid for the product in question.
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Intellectual Property</h2>
            
            <p class="text-gray-600 mb-4">
                All content on this website, including text, graphics, logos, images, and software, is the property of NutriNexus and is protected by copyright and other intellectual property laws.
            </p>
            
            <p class="text-gray-600">
                You may not reproduce, distribute, modify, or create derivative works from any content on this website without our express written permission.
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Governing Law</h2>
            
            <p class="text-gray-600">
                These terms and conditions shall be governed by and construed in accordance with the laws of Nepal. Any disputes arising from these terms shall be subject to the exclusive jurisdiction of the courts of Nepal.
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Contact Information</h2>
            
            <div class="bg-gray-50 p-6 rounded-lg">
                <p class="text-gray-600 mb-4">
                    For questions about these terms, please contact us:
                </p>
                <div class="space-y-2">
                    <p class="text-gray-700">Email: legal@nutrinexus.com</p>
                    <p class="text-gray-700">Phone: 9811388848</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
