<?php ob_start(); ?>

<div class="min-h-[calc(100vh-80px)] bg-neutral-50 py-16 px-4">
    <div class="max-w-xl mx-auto text-center mb-10 space-y-3">
        <p class="text-primary text-xs font-semibold tracking-[0.4em] uppercase">Support</p>
        <h1 class="text-3xl md:text-4xl font-bold text-foreground">Track Your Order</h1>
        <p class="text-sm text-neutral-500">Enter the invoice number from your confirmation email to view the latest status.</p>
    </div>

    <?php if (\App\Core\Session::hasFlash()): ?>
        <?php 
            $flash = \App\Core\Session::getFlash(); 
            $alertClasses = $flash['type'] === 'success'
                ? 'bg-success/10 border-success text-success'
                : 'bg-error/10 border-error text-error';
        ?>
        <div class="max-w-lg mx-auto mb-6 rounded-2xl border px-4 py-3 text-sm <?= $alertClasses ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-3xl border border-neutral-200 shadow-sm p-6 md:p-8">
            <form method="POST" action="<?= \App\Core\View::url('orders/track') ?>" class="space-y-5">
                <div class="text-left">
                    <label for="invoice" class="block text-sm font-semibold text-neutral-700 mb-2">
                        Order Number
                    </label>
                    <input
                        type="text"
                        id="invoice"
                        name="invoice"
                        required
                        placeholder="e.g., NTX202511219254"
                        value="<?= htmlspecialchars($_POST['invoice'] ?? $_GET['invoice'] ?? '') ?>"
                        class="input native-input w-full"
                    >
                    <p class="mt-2 text-xs text-neutral-500">
                        Your invoice number is listed in your confirmation email and SMS.
                    </p>
                </div>

                <button type="submit" class="btn w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M16 10.5a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0z" />
                    </svg>
                    Track Order
                </button>
            </form>
        </div>

        <div class="mt-8 text-center text-sm text-neutral-500">
            <p>
                Don’t have an account?
                <a href="<?= \App\Core\View::url('auth/register') ?>" class="text-primary font-semibold hover:text-primary-dark">Create one here</a>
                to view all your orders in one place.
            </p>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
