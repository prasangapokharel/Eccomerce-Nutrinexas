<header class="bg-white shadow-sm border-b border-gray-200 flex-shrink-0">
    <div class="flex items-center justify-between p-4 lg:p-6">
        <div class="flex items-center space-x-4">
            <button id="mobileMenuButton" class="lg:hidden text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            <!-- Breadcrumb -->
            <nav class="hidden md:flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li><a href="<?= \App\Core\View::url('curior/dashboard') ?>" class="hover:text-primary transition-colors">Courier</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li class="text-gray-900 font-medium"><?= $title ?? 'Dashboard' ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="flex items-center space-x-4">
            <!-- View Store -->
            <a href="<?= \App\Core\View::url('') ?>" class="p-2 text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100" title="View Store">
                <i class="fas fa-store text-lg"></i>
            </a>
            
            <!-- User Dropdown -->
            <div class="relative" data-profile-dropdown style="z-index: 1000;">
                <button type="button" class="flex items-center space-x-2 text-gray-700 hover:text-primary p-2 rounded-lg hover:bg-gray-100" aria-haspopup="true" aria-expanded="false">
                    <?php
                    $curiorName = \App\Core\Session::get('curior_name') ?? 'Courier';
                    ?>
                    <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium">
                        <?= strtoupper(substr($curiorName, 0, 1)) ?>
                    </div>
                    <span class="hidden md:block font-medium"><?= htmlspecialchars($curiorName) ?></span>
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.25 8.29a.75.75 0 01-.02-1.08z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div class="absolute top-full right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50" data-profile-menu>
                    <a href="<?= \App\Core\View::url('curior/performance') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-chart-line w-4 mr-2"></i>Performance
                    </a>
                    <a href="<?= \App\Core\View::url('curior/settlements') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-money-bill-wave w-4 mr-2"></i>Settlement
                    </a>
                    <hr class="my-1">
                    <a href="<?= \App\Core\View::url('curior/logout') ?>" class="block px-4 py-2 text-sm text-error hover:bg-error-50">
                        <i class="fas fa-sign-out-alt w-4 mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
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

            profileDropdown.addEventListener('mouseenter', showProfileMenu);
            profileDropdown.addEventListener('mouseleave', hideProfileMenu);
            profileMenu.addEventListener('mouseenter', showProfileMenu);
            profileMenu.addEventListener('mouseleave', hideProfileMenu);

            profileBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (profileMenu.classList.contains('hidden')) {
                    showProfileMenu();
                } else {
                    hideProfileMenu();
                }
            });

            profileBtn.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') hideProfileMenu();
            });

            document.addEventListener('click', (e) => {
                if (!profileDropdown.contains(e.target)) {
                    hideProfileMenu();
                }
            });
        }
    }
});
</script>
