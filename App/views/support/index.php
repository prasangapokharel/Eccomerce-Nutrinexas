<?php ob_start(); ?>
<?php
$title = 'Help & Support - NutriNexus';
$description = 'Get help with your orders, account, and more. Contact our support team or browse our FAQ.';
?>

<div class="min-h-screen bg-gray-50 py-6 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-3xl border border-[#0A3167]/10 shadow-sm px-5 py-6 mb-6">
            <h1 class="text-2xl font-bold text-[#0A3167] mb-2">Help & Support</h1>
            <p class="text-sm text-[#0A3167]/70">We're here to help you with any questions or concerns</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <a href="#faq" class="bg-white rounded-2xl border border-[#0A3167]/10 shadow-sm p-6 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-[#0A3167]/10 rounded-xl flex items-center justify-center mb-4">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#0A3167]">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M9 9a3 3 0 115 2c-1 1-.8 1.5-1 3"/>
                        <circle cx="12" cy="17" r=".5"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-[#0A3167] mb-2">Frequently Asked Questions</h3>
                <p class="text-sm text-[#0A3167]/70">Find answers to common questions</p>
            </a>

            <a href="<?= \App\Core\View::url('pages/contact') ?>" class="bg-white rounded-2xl border border-[#0A3167]/10 shadow-sm p-6 hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-[#0A3167]/10 rounded-xl flex items-center justify-center mb-4">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#0A3167]">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-[#0A3167] mb-2">Contact Us</h3>
                <p class="text-sm text-[#0A3167]/70">Get in touch with our support team</p>
            </a>
        </div>

        <div class="bg-white rounded-3xl border border-[#0A3167]/10 shadow-sm px-5 py-6 mb-6">
            <h2 class="text-xl font-bold text-[#0A3167] mb-4" id="faq">Frequently Asked Questions</h2>
            
            <div class="space-y-4">
                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-semibold text-[#0A3167] mb-2">How do I place an order?</h3>
                    <p class="text-sm text-[#0A3167]/70">Browse our products, add items to your cart, and proceed to checkout. You can pay using various payment methods including Khalti, eSewa, or cash on delivery.</p>
                </div>

                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-semibold text-[#0A3167] mb-2">What are your delivery options?</h3>
                    <p class="text-sm text-[#0A3167]/70">We offer delivery across Nepal. Delivery charges vary by location. Check our shipping page for detailed information about delivery times and charges.</p>
                </div>

                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-semibold text-[#0A3167] mb-2">How can I track my order?</h3>
                    <p class="text-sm text-[#0A3167]/70">Once your order is placed, you can track it from the "My Orders" section in your account. You'll receive updates via email and SMS.</p>
                </div>

                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-semibold text-[#0A3167] mb-2">What is your return policy?</h3>
                    <p class="text-sm text-[#0A3167]/70">We accept returns within 7 days of delivery for unopened products in original packaging. Please contact support to initiate a return.</p>
                </div>

                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-base font-semibold text-[#0A3167] mb-2">How do I become a VIP member?</h3>
                    <p class="text-sm text-[#0A3167]/70">VIP status is granted to active sponsors. Contact our support team to learn more about our referral program and VIP benefits.</p>
                </div>

                <div class="pb-4">
                    <h3 class="text-base font-semibold text-[#0A3167] mb-2">How do I update my account information?</h3>
                    <p class="text-sm text-[#0A3167]/70">You can update your profile, addresses, and payment methods from your account dashboard. Go to "My Profile" to edit your personal information.</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-[#0A3167] to-[#0A3167]/90 rounded-3xl border border-[#0A3167]/20 shadow-sm px-5 py-6 text-white">
            <h2 class="text-xl font-bold mb-4">Still Need Help?</h2>
            <p class="text-sm text-white/90 mb-4">Our support team is available to assist you with any questions or concerns.</p>
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>+977 9811388848</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <span>nutrinexusnp@gmail.com</span>
                </div>
            </div>
            <a href="<?= \App\Core\View::url('pages/contact') ?>" class="inline-block mt-4 px-6 py-2 bg-white text-[#0A3167] rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                Contact Support
            </a>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

