<?php ob_start(); ?>
<?php
$title = 'Settings - NutriNexus';
$description = 'Manage your account settings, notifications, and security preferences.';
?>

<div class="min-h-screen bg-gray-100 py-6 px-4">
    <div class="max-w-md mx-auto bg-white rounded-3xl border border-primary/10 shadow-sm px-5 py-6 space-y-6">
        <header class="flex items-center justify-between text-primary">
            <button type="button" class="w-10 h-10 rounded-2xl border border-primary/20 flex items-center justify-center" onclick="history.back()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 4L6 12l8 8"/>
                </svg>
            </button>
            <div class="text-sm font-semibold tracking-wide">Settings</div>
            <div class="w-10"></div>
        </header>

        <div class="space-y-4">
            <div class="bg-primary/5 rounded-2xl p-4 border border-primary/10">
                <h3 class="text-base font-semibold text-primary mb-3">Notifications</h3>
                <div class="space-y-3">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-sm font-medium text-primary">Order Updates</span>
                            <p class="text-xs text-primary/70">Get notified about order status changes</p>
                        </div>
                        <input type="checkbox" checked class="w-5 h-5 rounded border-primary/20 text-primary focus:ring-[#0A3167]">
                    </label>
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-sm font-medium text-primary">Promotional Emails</span>
                            <p class="text-xs text-primary/70">Receive offers and product updates</p>
                        </div>
                        <input type="checkbox" class="w-5 h-5 rounded border-primary/20 text-primary focus:ring-[#0A3167]">
                    </label>
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <span class="text-sm font-medium text-primary">SMS Notifications</span>
                            <p class="text-xs text-primary/70">Get order updates via SMS</p>
                        </div>
                        <input type="checkbox" checked class="w-5 h-5 rounded border-primary/20 text-primary focus:ring-[#0A3167]">
                    </label>
                </div>
            </div>

            <div class="bg-primary/5 rounded-2xl p-4 border border-primary/10">
                <h3 class="text-base font-semibold text-primary mb-3">Security</h3>
                <div class="space-y-3">
                    <a href="<?= \App\Core\View::url('user/profile') ?>" class="flex items-center justify-between p-3 bg-white rounded-xl border border-primary/10 hover:border-primary/20 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                    <path d="M12 14c-4 0-7 2-7 4v1h14v-1c0-2-3-4-7-4zm0-2a4 4 0 100-8 4 4 0 000 8z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-primary">Update Profile</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary/60">
                            <path d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="<?= \App\Core\View::url('auth/forgotPassword') ?>" class="flex items-center justify-between p-3 bg-white rounded-xl border border-primary/10 hover:border-primary/20 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-primary">Change Password</span>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary/60">
                            <path d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-primary/5 rounded-2xl p-4 border border-primary/10">
                <h3 class="text-base font-semibold text-primary mb-3">Privacy</h3>
                <div class="space-y-2 text-xs text-primary/70">
                    <a href="<?= \App\Core\View::url('pages/privacy') ?>" class="block py-2 hover:text-primary transition-colors">Privacy Policy</a>
                    <a href="<?= \App\Core\View::url('pages/terms') ?>" class="block py-2 hover:text-primary transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-primary/5 to-primary/10 rounded-2xl p-4 border border-primary/10">
            <h3 class="text-sm font-semibold text-primary mb-2">Account Information</h3>
            <div class="space-y-2 text-xs text-primary/70">
                <p>Email: <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
                <p>Member since: <?= !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A' ?></p>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

