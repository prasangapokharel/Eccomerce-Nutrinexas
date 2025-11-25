<?php ob_start(); ?>
<?php
$title = 'Payment Methods - NutriNexus';
$description = 'Manage your payment methods and billing information.';
?>

<div class="min-h-screen bg-gray-100 py-6 px-4">
    <div class="max-w-md mx-auto bg-white rounded-3xl border border-primary/10 shadow-sm px-5 py-6 space-y-6">
        <header class="flex items-center justify-between text-primary">
            <button type="button" class="w-10 h-10 rounded-2xl border border-primary/20 flex items-center justify-center" onclick="history.back()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 4L6 12l8 8"/>
                </svg>
            </button>
            <div class="text-sm font-semibold tracking-wide">Payment Methods</div>
            <div class="w-10"></div>
        </header>

        <div class="space-y-4">
            <div class="bg-primary/5 rounded-2xl p-4 border border-primary/10">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M3 10h18"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-primary">Card Payment</h3>
                        <p class="text-xs text-primary/70">Credit & Debit Cards</p>
                    </div>
                </div>
                <p class="text-xs text-primary/70">Pay securely using Khalti, eSewa, or other payment gateways at checkout.</p>
            </div>

            <div class="bg-primary/5 rounded-2xl p-4 border border-primary/10">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-primary">Cash on Delivery</h3>
                        <p class="text-xs text-primary/70">Pay when you receive</p>
                    </div>
                </div>
                <p class="text-xs text-primary/70">Available for most locations. Pay cash when your order arrives.</p>
            </div>

            <div class="bg-primary/5 rounded-2xl p-4 border border-primary/10">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-primary">Bank Transfer</h3>
                        <p class="text-xs text-primary/70">Direct bank payment</p>
                    </div>
                </div>
                <p class="text-xs text-primary/70">Transfer directly to our bank account. Contact support for account details.</p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-primary/5 to-primary/10 rounded-2xl p-4 border border-primary/10">
            <h3 class="text-sm font-semibold text-primary mb-2">Payment Security</h3>
            <p class="text-xs text-primary/70 leading-relaxed">All payment methods are secure and encrypted. We never store your full payment details. For any payment-related queries, contact our support team.</p>
        </div>

        <a href="<?= \App\Core\View::url('pages/contact') ?>" class="w-full bg-primary text-white font-semibold py-3 rounded-2xl text-center block hover:bg-primary/90 transition-colors">
            Contact Support
        </a>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

