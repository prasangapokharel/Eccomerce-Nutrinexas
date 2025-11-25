<?php ob_start(); ?>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/tailwind.css">
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4 py-6">
    <div class="grid lg:grid-cols-2 items-center gap-10 max-w-6xl w-full">
        <div class="border border-gray-200 rounded-2xl p-8 max-w-lg w-full bg-white shadow-[0_20px_45px_-15px_rgba(79,70,229,0.25)] mx-auto">
            <form action="<?= \App\Core\View::url('seller/login') ?>" method="POST" class="space-y-6">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">

                <div class="text-center mb-10">
                    <h1 class="text-3xl font-semibold text-primary">Seller Login</h1>
                    <p class="text-gray-600 text-sm mt-4">Sign in to manage products, orders, ads, and more.</p>
                </div>

                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="p-3 rounded-xl text-sm <?= strpos($_SESSION['flash_message'], 'error') !== false ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' ?>">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <div>
                    <label class="text-sm font-medium text-gray-900 mb-2 block" for="email">Email Address</label>
                    <div class="relative">
                        <input id="email" name="email" type="email" required
                               class="w-full text-sm border border-gray-300 rounded-xl px-4 py-3 pr-12 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition"
                               placeholder="seller@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#94a3b8" class="w-5 h-5 absolute right-4 top-1/2 -translate-y-1/2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15A2.25 2.25 0 012.25 17.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.125 1.958l-7.5 4.286a2.25 2.25 0 01-2.25 0L4.875 8.951A2.25 2.25 0 013.75 6.993V6.75" />
                        </svg>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-900 mb-2 block" for="password">Password</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required
                               class="w-full text-sm border border-gray-300 rounded-xl px-4 py-3 pr-12 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition"
                               placeholder="Enter your password">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#94a3b8" class="w-5 h-5 absolute right-4 top-1/2 -translate-y-1/2" viewBox="0 0 128 128">
                            <path d="M64 104C22.127 104 1.367 67.496.504 65.943a4 4 0 0 1 0-3.887C1.367 60.504 22.127 24 64 24s62.633 36.504 63.496 38.057a4 4 0 0 1 0 3.887C126.633 67.496 105.873 104 64 104zM8.707 63.994C13.465 71.205 32.146 96 64 96c31.955 0 50.553-24.775 55.293-31.994C114.535 56.795 95.854 32 64 32 32.045 32 13.447 56.775 8.707 63.994zM64 88c-13.234 0-24-10.766-24-24s10.766-24 24-24 24 10.766 24 24-10.766 24-24 24zm0-40c-8.822 0-16 7.178-16 16s7.178 16 16 16 16-7.178 16-16-7.178-16-16-16z" />
                        </svg>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-gray-600">
                        <input type="checkbox" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        Remember me
                    </label>
                    <a href="<?= \App\Core\View::url('seller/forgot-password') ?>" class="text-primary hover:underline">Forgot password?</a>
                </div>

                <div class="!mt-10">
                    <button type="submit"
                            class="w-full py-3 rounded-xl bg-primary text-white font-semibold tracking-wide shadow-lg shadow-primary/30 hover:bg-primary-dark transition">
                        Sign In
                    </button>
                    <p class="text-sm text-center text-gray-600 mt-6">
                        Don't have an account?
                        <a href="<?= \App\Core\View::url('seller/register') ?>" class="text-primary font-semibold hover:underline">Register as Seller</a>
                    </p>
                    <p class="text-sm text-center text-gray-500 mt-2">
                        <a href="<?= \App\Core\View::url('') ?>" class="text-primary hover:underline">← Back to Store</a>
                    </p>
                </div>
            </form>
        </div>

        <div class="max-lg:hidden">
            <div class="rounded-3xl overflow-hidden shadow-[0_20px_45px_-15px_rgba(15,23,42,0.35)] border border-primary/10">
                <img src="<?= ASSETS_URL ?>/images/screen/seller/login.png" alt="Seller workspace" class="w-full h-full object-cover">
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Login - NutriNexus</title>
    <link rel="stylesheet" href="<?= \App\Core\View::asset('css/seller.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?= $content ?>

    
</body>
</html>
