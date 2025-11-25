<?php ob_start(); ?>

<!-- Register Page with Normal Form and Optional Auth0 -->
<div class="min-h-screen relative flex items-center justify-center px-4 py-2 login-page fixed login-bg">
    
    <!-- Overlay for better text readability -->
    <div class="absolute inset-0 bg-white bg-opacity-20"></div>
    
    <!-- Register Container -->
    <div class="relative z-5 w-full max-w-md mx-auto mb-40 fixed overflow-hidden">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Join NutriNexus</h1>
            <p class="text-[#626262] text-base">Create your account to get started</p>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
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
            <div class="mb-6 <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?> border border-green-200 rounded-lg p-4">
                <p class="text-sm"><?= htmlspecialchars($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Referral Code Display - Premium Design -->
        <?php if (isset($inviter) && $inviter): ?>
            <?php 
            $isVip = isset($inviter['sponsor_status']) && $inviter['sponsor_status'] === 'active';
            $inviterName = htmlspecialchars(trim($inviter['first_name'] . ' ' . $inviter['last_name']));
            $inviterImageUrl = !empty($inviter['profile_image']) 
                ? (filter_var($inviter['profile_image'], FILTER_VALIDATE_URL) 
                    ? $inviter['profile_image'] 
                    : ASSETS_URL . '/profileimage/' . basename($inviter['profile_image']))
                : ASSETS_URL . '/images/default-avatar.png';
            ?>
            <!-- Premium Inviter Profile Card -->
            <div class="mb-6 relative overflow-hidden rounded-2xl <?= $isVip ? 'bg-gradient-to-br from-yellow-50 via-amber-50 to-yellow-100 border-2 border-yellow-300 shadow-xl' : 'bg-gradient-to-br from-primary/10 via-primary/5 to-white border-2 border-primary/30 shadow-lg' ?>">
                <!-- Decorative Background -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-accent rounded-full blur-2xl"></div>
                </div>
                
                <div class="relative p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <!-- Inviter Avatar with VIP Badge -->
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 <?= $isVip ? 'ring-4 ring-yellow-400 shadow-xl' : 'ring-2 ring-primary shadow-lg' ?> rounded-full overflow-hidden bg-white p-1">
                                <img src="<?= htmlspecialchars($inviterImageUrl) ?>" 
                                     alt="<?= $inviterName ?>"
                                     class="w-full h-full object-cover rounded-full"
                                     loading="eager"
                                     onerror="this.src='<?= ASSETS_URL ?>/images/default-avatar.png'">
                            </div>
                            <?php if ($isVip): ?>
                                <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-white rounded-full p-1 shadow-xl border-2 border-yellow-400">
                                    <img src="<?= ASSETS_URL ?>/images/icons/vip.png" 
                                         alt="VIP" 
                                         class="w-full h-full object-contain"
                                         onerror="this.style.display='none';">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Inviter Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <p class="text-base sm:text-lg font-bold <?= $isVip ? 'text-yellow-900' : 'text-primary' ?>">
                                    <?= $inviterName ?>
                                </p>
                                <?php if ($isVip): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-gradient-to-r from-yellow-400 to-yellow-600 text-white shadow-md">
                                        <i class="fas fa-star mr-1"></i> VIP
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm <?= $isVip ? 'text-yellow-800' : 'text-gray-700' ?> font-semibold mb-3">
                                <?= $isVip ? '✨ Premium VIP Member' : '👤 Referred you' ?>
                            </p>
                            
                            <!-- VIP Benefits Display -->
                            <?php if ($isVip): ?>
                                <div class="mt-3 space-y-2">
                                    <p class="text-xs font-bold text-yellow-900 mb-2">VIP Benefits You'll Get:</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div class="flex items-center gap-2 text-xs text-yellow-800">
                                            <i class="fas fa-check-circle text-yellow-600"></i>
                                            <span>Earn Referral Commissions</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-yellow-800">
                                            <i class="fas fa-check-circle text-yellow-600"></i>
                                            <span>Exclusive VIP Badge</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-yellow-800">
                                            <i class="fas fa-check-circle text-yellow-600"></i>
                                            <span>Priority Support</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-yellow-800">
                                            <i class="fas fa-check-circle text-yellow-600"></i>
                                            <span>Special Discounts</span>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mt-2 p-3 bg-white/60 rounded-lg border border-primary/20">
                                    <p class="text-xs text-gray-700 font-medium">
                                        <i class="fas fa-gift text-primary mr-1"></i>
                                        Join through this referral and start earning rewards!
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (isset($referralCode) && !empty($referralCode)): ?>
            <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-xl p-4 shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-ticket-alt text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-green-900">Referral Code Applied</p>
                        <p class="text-xs text-green-700">Code: <strong><?= htmlspecialchars($referralCode) ?></strong></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Register Form Card -->
        <div class="login-form bg-white rounded-2xl shadow-xl p-8">
            <!-- Normal Registration Form -->
            <form method="POST" action="<?= \App\Core\View::url('auth/processRegister') ?>" class="space-y-6">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                <!-- Full Name Field -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                           placeholder="Enter your full name"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email <span class="text-gray-400">(Optional)</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                           placeholder="Enter your email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <!-- Phone Field -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                           placeholder="Enter your phone number"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           minlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                           placeholder="Enter your password (min 6 characters)">
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required
                           minlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                           placeholder="Confirm your password">
                </div>

                <!-- Referral Code Field (Hidden if already set) -->
                <input type="hidden" 
                       name="referral_code" 
                       value="<?= htmlspecialchars($referralCode ?? '') ?>">

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full px-6 py-4 bg-primary text-white font-bold rounded-xl shadow-lg hover:bg-primary-dark transition-colors">
                    Create Account
                </button>
            </form>

            <!-- Divider -->
            <?php if (defined('AUTH0_ENABLED') && AUTH0_ENABLED): ?>
            <div class="my-6 flex items-center">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="px-4 text-sm text-gray-500">OR</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <!-- Auth0 Register Button (Optional) -->
            <a href="<?= \App\Core\View::url('auth0/login') ?>" 
               class="w-full flex items-center justify-center px-6 py-4 bg-gray-100 text-gray-700 font-bold rounded-xl shadow-lg hover:bg-gray-200 transition-colors group">
                <svg class="w-6 h-6 mr-3" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M21.98 7.448L19.62 0H4.347L2.02 7.448c-1.352 4.312.03 9.206 3.815 12.015L12.007 24l6.157-4.537c3.785-2.809 5.167-7.703 3.815-12.015z"/>
                    <path fill="#FFF" d="M12.007 17.25c-2.9 0-5.25-2.35-5.25-5.25s2.35-5.25 5.25-5.25 5.25 2.35 5.25 5.25-2.35 5.25-5.25 5.25zm0-8.5c-1.795 0-3.25 1.455-3.25 3.25s1.455 3.25 3.25 3.25 3.25-1.455 3.25-3.25-1.455-3.25-3.25-3.25z"/>
                </svg>
                <span>Sign up with Auth0</span>
            </a>
            <?php endif; ?>

            <!-- Sign In Link -->
            <div class="text-center pt-4">
                <p class="text-[#626262] text-sm">
                    Already have an account? 
                    <a href="<?= \App\Core\View::url('auth/login') ?>" class="text-primary font-semibold hover:underline">
                        Sign In
                    </a>
                </p>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle referral code from URL and store in cookie
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');
    
    if (refCode) {
        // Store referral code in cookie for 30 days
        document.cookie = `referral_code=${refCode}; max-age=${30 * 24 * 60 * 60}; path=/`;
        
        // Set the hidden input if it exists
        const referralInput = document.querySelector('input[name="referral_code"]');
        if (referralInput) {
            referralInput.value = refCode;
        }
    } else {
        // Check if referral code exists in cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'referral_code' && value) {
                const referralInput = document.querySelector('input[name="referral_code"]');
                if (referralInput) {
                    referralInput.value = value;
                }
                break;
            }
        }
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
