<?php ob_start(); ?>

<!-- Login Page with Normal Form and Optional Auth0 -->
<div class="min-h-screen relative flex items-center justify-center px-4 py-2 login-page fixed login-bg">
    
    <!-- Overlay for better text readability -->
    <div class="absolute inset-0 bg-white bg-opacity-20"></div>
    
    <!-- Login Container -->
    <div class="relative z-5 w-full max-w-md mx-auto mb-40 fixed overflow-hidden">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Welcome Back</h1>
            <p class="text-[#626262] text-base">Sign in to continue to NutriNexus</p>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-4">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            <?php foreach ($errors as $error): ?>
                                <?= htmlspecialchars($error) ?><br>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (\App\Core\Session::hasFlash()): ?>
            <?php $flash = \App\Core\Session::getFlash(); ?>
            <div class="mb-6 <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?> border border-green-200 rounded-2xl p-4">
                <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Login Form Card -->
        <div class="login-form bg-white rounded-2xl shadow-xl p-8">
            <!-- Normal Login Form -->
            <form method="POST" action="<?= \App\Core\View::url('auth/processLogin') ?>" class="space-y-6">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                <!-- Phone Field -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none"
                           placeholder="Enter your phone number"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                           placeholder="Enter your password">
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="remember_me" 
                               class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="<?= \App\Core\View::url('auth/forgotPassword') ?>" 
                       class="text-sm text-primary hover:underline font-medium">
                        Forgot Password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full px-6 py-3 bg-primary text-white rounded-2xl font-semibold text-sm hover:bg-primary-dark transition-colors shadow-lg">
                    Sign In
                </button>
            </form>

            <!-- Auth0 Login Button (Always shown below Sign In) -->
            <div class="mt-4">
                <a href="<?= \App\Core\View::url('auth/google') ?>" 
                   class="w-full flex items-center justify-center px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-2xl font-semibold text-sm shadow-sm hover:bg-gray-50 hover:border-gray-400 transition-all">
                    <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span>Sign in with Google</span>
                </a>
            </div>

            <?php if (defined('AUTH0_ENABLED') && AUTH0_ENABLED): ?>
            <div class="mt-3">
            <a href="<?= \App\Core\View::url('auth0/login') ?>" 
                   class="w-full flex items-center justify-center px-6 py-4 bg-[#EB5424] text-white font-semibold rounded-xl shadow-sm hover:bg-[#D4461F] transition-all">
                    <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M21.98 7.448L19.62 0H4.347L2.02 7.448c-1.352 4.312.03 9.206 3.815 12.015L12.007 24l6.157-4.537c3.785-2.809 5.167-7.703 3.815-12.015z"/>
                    <path fill="#FFF" d="M12.007 17.25c-2.9 0-5.25-2.35-5.25-5.25s2.35-5.25 5.25-5.25 5.25 2.35 5.25 5.25-2.35 5.25-5.25 5.25zm0-8.5c-1.795 0-3.25 1.455-3.25 3.25s1.455 3.25 3.25 3.25 3.25-1.455 3.25-3.25-1.455-3.25-3.25-3.25z"/>
                </svg>
                <span>Sign in with Auth0</span>
            </a>
            </div>
            <?php endif; ?>

            <!-- Create Account Link -->
            <div class="text-center pt-4">
                <p class="text-[#626262] text-sm">
                    Don't have an account? 
                    <a href="<?= \App\Core\View::url('auth/register') ?>" class="text-primary font-semibold hover:underline">
                        Create new account
                    </a>
                </p>
            </div>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
