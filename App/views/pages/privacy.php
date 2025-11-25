<?php ob_start(); ?>

<div class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">Privacy Policy</h1>
        
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <p class="text-gray-700 mb-4">
                At NutriNexus, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, and safeguard your data.
            </p>
            <p class="text-sm text-gray-500">
                <strong>Last Updated:</strong> <?= date('F j, Y') ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Information We Collect</h2>
            
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Personal Information</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>Name and contact details</li>
                        <li>Email address and phone number</li>
                        <li>Shipping and billing addresses</li>
                        <li>Date of birth (for age verification)</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Transaction Data</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>Purchase history and preferences</li>
                        <li>Payment information (encrypted)</li>
                        <li>Order details and delivery status</li>
                        <li>Customer service interactions</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Technical Information</h3>
                    <p class="text-gray-600 mb-2">
                        We automatically collect certain technical information when you visit our website:
                    </p>
                    <ul class="space-y-2 text-gray-600">
                        <li>IP address and browser type</li>
                        <li>Device information and operating system</li>
                        <li>Website usage patterns and preferences</li>
                        <li>Cookies and similar tracking technologies</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">How We Use Your Information</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Order Processing</h3>
                    <p class="text-gray-600">To process your orders, arrange delivery, and provide customer support for your purchases.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Communication</h3>
                    <p class="text-gray-600">To send order updates, promotional offers, newsletters, and respond to your inquiries.</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Service Improvement</h3>
                    <p class="text-gray-600">To analyze website usage, improve our services, and develop new products based on customer needs.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Information Sharing & Disclosure</h2>
            
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <h3 class="font-semibold text-red-800 mb-2">We Never Sell Your Data</h3>
                <p class="text-red-700">
                    NutriNexus does not sell, rent, or trade your personal information to third parties for marketing purposes.
                </p>
            </div>
            
            <p class="font-semibold text-gray-900 mb-3">We may share your information only in these limited circumstances:</p>
            
            <div class="space-y-3">
                <div>
                    <h4 class="font-semibold text-gray-900 mb-1">Service Providers</h4>
                    <p class="text-gray-600">With trusted partners who help us operate our business (payment processors, shipping companies, etc.)</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-900 mb-1">Legal Requirements</h4>
                    <p class="text-gray-600">When required by law, court order, or to protect our rights and safety</p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-gray-900 mb-1">Business Transfers</h4>
                    <p class="text-gray-600">In the event of a merger, acquisition, or sale of our business assets</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Data Security</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Security Measures</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>SSL encryption for all data transmission</li>
                        <li>Secure servers with regular security updates</li>
                        <li>Encrypted storage of sensitive information</li>
                        <li>Limited access to personal data</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Payment Security</h3>
                    <p class="text-gray-600">
                        We use industry-standard payment processing systems that comply with PCI DSS standards.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Your Rights & Choices</h2>
            
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3">You have the right to:</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>Access and review your personal information</li>
                        <li>Update or correct your information</li>
                        <li>Request deletion of your data</li>
                        <li>Opt-out of marketing communications</li>
                    </ul>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="font-semibold text-gray-900 mb-3">How to Exercise Your Rights</h3>
                    <p class="text-gray-600 mb-4">
                        To exercise any of these rights, please contact us at:
                    </p>
                    <div class="space-y-2">
                        <p class="text-gray-700">Email: privacy@nutrinexus.com</p>
                        <p class="text-gray-700">Phone: 9811388848</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Cookies & Tracking</h2>
            
            <p class="text-gray-600 mb-4">
                We use cookies and similar tracking technologies to enhance your browsing experience, analyze website traffic, and personalize content.
            </p>
            
            <div class="space-y-3">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Essential Cookies</h3>
                    <p class="text-gray-600">Required for website functionality and security</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Analytics Cookies</h3>
                    <p class="text-gray-600">Help us understand how visitors use our website</p>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Marketing Cookies</h3>
                    <p class="text-gray-600">Used to deliver relevant advertisements</p>
                </div>
            </div>
            
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4">
                <p class="text-gray-800">
                    You can control cookie preferences through your browser settings or our cookie consent banner.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Data Retention</h2>
            
            <p class="text-gray-600 mb-4">
                We retain your personal information only as long as necessary to fulfill the purposes outlined in this privacy policy, unless a longer retention period is required by law.
            </p>
            
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="font-semibold text-gray-900 mb-3">Retention Periods</h3>
                <ul class="space-y-2 text-gray-600">
                    <li><strong>Account Information:</strong> Until account deletion or 3 years of inactivity</li>
                    <li><strong>Order History:</strong> 7 years for tax and legal compliance</li>
                    <li><strong>Marketing Data:</strong> Until you unsubscribe or opt-out</li>
                    <li><strong>Website Analytics:</strong> 26 months maximum</li>
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Children's Privacy</h2>
            
            <div class="bg-red-50 border border-red-200 p-6 rounded-lg">
                <h3 class="font-semibold text-red-800 mb-2">Age Restriction</h3>
                <p class="text-red-700 mb-3">
                    Our services are not intended for children under 18 years of age. We do not knowingly collect personal information from children under 18.
                </p>
                <p class="text-red-700">
                    If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Contact Us</h2>
            
            <div class="space-y-4">
                <p class="text-gray-700">
                    If you have any questions about this Privacy Policy or our data practices, please contact us:
                </p>
                
                <div class="bg-gray-50 p-6 rounded-lg">
                    <p class="text-gray-700 mb-2">Email: privacy@nutrinexus.com</p>
                    <p class="text-gray-700 mb-2">Phone: 9811388848</p>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="font-semibold text-gray-900 mb-3">Policy Updates</h3>
                    <p class="text-gray-600 mb-4">
                        We may update this Privacy Policy from time to time. We will notify you of any material changes by email notification to registered users and prominent notice on our website.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
