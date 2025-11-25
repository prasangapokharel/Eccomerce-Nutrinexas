<header class="bg-white shadow-sm border-b border-primary/10 flex-shrink-0">
    <div class="flex items-center justify-between p-4 lg:p-6">
        <div class="flex items-center space-x-4">
            <button id="mobileMenuButton" class="lg:hidden text-primary hover:text-primary-dark p-2 rounded-lg hover:bg-primary/10">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            <!-- Breadcrumb -->
            <nav class="hidden md:flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li><a href="<?= \App\Core\View::url('seller/dashboard') ?>" class="text-primary hover:text-primary-dark transition-colors">Seller</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li class="text-gray-900 font-medium"><?= $title ?? 'Dashboard' ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="flex items-center space-x-4">
            <!-- View Store -->
            <a href="<?= \App\Core\View::url('') ?>" class="p-2 text-primary hover:text-primary-dark rounded-lg hover:bg-primary/10" title="View Store">
                <i class="fas fa-store text-lg"></i>
            </a>
            
            <!-- Notifications -->
            <a href="<?= \App\Core\View::url('seller/notifications') ?>" class="relative p-2 text-primary hover:text-primary-dark rounded-lg hover:bg-primary/10">
                <i class="fas fa-bell text-lg"></i>
                <?php
                $unreadCount = 0;
                try {
                    $db = \App\Core\Database::getInstance();
                    $sellerId = \App\Core\Session::get('seller_id');
                    if ($sellerId) {
                        $result = $db->query("SELECT COUNT(*) as count FROM seller_notifications WHERE seller_id = ? AND is_read = 0", [$sellerId])->single();
                        $unreadCount = $result['count'] ?? 0;
                    }
                } catch (Exception $e) {
                    // Ignore errors
                }
                if ($unreadCount > 0):
                ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            
            <!-- User Dropdown -->
            <div class="relative" data-profile-dropdown style="z-index: 1000;">
                <button type="button" class="flex items-center space-x-2 text-gray-700 hover:text-primary p-2 rounded-lg hover:bg-primary/10" aria-haspopup="true" aria-expanded="false">
                    <?php
                    $sellerName = \App\Core\Session::get('seller_name') ?? 'Seller';
                    $sellerLogo = \App\Core\Session::get('seller_logo_url') ?? null;
                    ?>
                    <?php if ($sellerLogo): ?>
                        <img src="<?= htmlspecialchars($sellerLogo) ?>" 
                             alt="<?= htmlspecialchars($sellerName) ?>"
                             class="w-8 h-8 rounded-full object-cover border border-gray-200"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php endif; ?>
                    <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium <?= $sellerLogo ? 'hidden' : '' ?>">
                        <?= strtoupper(substr($sellerName, 0, 1)) ?>
                    </div>
                    <span class="hidden md:block font-medium"><?= htmlspecialchars($sellerName) ?></span>
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div class="absolute top-full right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50" data-profile-menu>
                    <a href="<?= \App\Core\View::url('seller/profile') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user w-4 mr-2"></i>Profile
                    </a>
                    <a href="<?= \App\Core\View::url('seller/bank-account') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-university w-4 mr-2"></i>Bank Account
                    </a>
                    <a href="<?= \App\Core\View::url('seller/settings') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-cog w-4 mr-2"></i>Settings
                    </a>
                    <hr class="my-1">
                    <a href="<?= \App\Core\View::url('seller/logout') ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt w-4 mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Profile dropdown handler (like home page)
document.addEventListener('DOMContentLoaded', function() {
    const profileDropdown = document.querySelector('[data-profile-dropdown]');
    if (profileDropdown) {
        const profileBtn = profileDropdown.querySelector('button');
        const profileMenu = profileDropdown.querySelector('[data-profile-menu]');
        
        if (profileBtn && profileMenu) {
            let profileHideTimer;
            const showProfileMenu = () => {
                clearTimeout(profileHideTimer);
                profileMenu.classList.remove('hidden');
                profileBtn.setAttribute('aria-expanded', 'true');
            };
            const hideProfileMenu = () => {
                profileHideTimer = setTimeout(() => {
                    profileMenu.classList.add('hidden');
                    profileBtn.setAttribute('aria-expanded', 'false');
                }, 120);
            };

            // Hover interactions
            profileDropdown.addEventListener('mouseenter', showProfileMenu);
            profileDropdown.addEventListener('mouseleave', hideProfileMenu);
            profileMenu.addEventListener('mouseenter', showProfileMenu);
            profileMenu.addEventListener('mouseleave', hideProfileMenu);

            // Click toggle
            profileBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (profileMenu.classList.contains('hidden')) {
                    showProfileMenu();
                } else {
                    hideProfileMenu();
                }
            });

            // Keyboard support
            profileBtn.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') hideProfileMenu();
            });

            // Click outside to close
            document.addEventListener('click', (e) => {
                if (!profileDropdown.contains(e.target)) {
                    hideProfileMenu();
                }
            });
        }
    }
});
</script>
