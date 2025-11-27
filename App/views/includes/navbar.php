<?php include APPROOT . '/views/seo/meta.php'; ?>



<div class="hidden lg:block sticky top-0 z-50 w-full bg-primary border-b border-white/10 py-3 px-4 sm:px-6 min-h-[75px]" style="overflow: visible;">
    <div class="max-w-7xl mx-auto" style="overflow: visible;">
        <div class="flex flex-wrap items-center justify-between gap-4 min-w-0 w-full" style="overflow: visible;">
            <!-- Logo, Navigation Quick Links & Search -->
            <div class="flex items-center gap-4 flex-1 min-w-0">
                <a href="<?= URLROOT ?>" class="flex items-center gap-3 flex-shrink-0 navbar-brand-link">
                    <div class="w-12 h-12 rounded-full overflow-hidden flex items-center justify-center navbar-logo">
                        <img src="https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/logo.svg"
                            alt="Nutri Nexas"
                            class="w-full h-full rounded-full"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white font-bold text-lg leading-tight">NutriNexus</span>
                    </div>
                </a>
                <div class="hidden xl:flex items-center gap-2 text-white/80 text-sm font-medium">
                    <a href="<?= URLROOT ?>/blogs" class="px-4 py-2 rounded-full border border-white/15 bg-white/5 hover:bg-white/15 transition">
                        Blogs
                    </a>
                    <a href="<?= URLROOT ?>/coupons" class="px-4 py-2 rounded-full border border-white/15 bg-white/5 hover:bg-white/15 transition">
                        Coupons
                    </a>
                    <a href="<?= URLROOT ?>/orders/track" class="px-4 py-2 rounded-full border border-white/15 bg-white/5 hover:bg-white/15 transition">
                        Tracking
                    </a>
                </div>

                <div class="relative flex-1 min-w-[220px] max-w-lg">
                    <form action="<?= \App\Core\View::url('products/search') ?>" method="get" id="desktopSearchForm" class="relative">
                        <input
                            type="search"
                            name="q"
                            id="desktopSearchInput"
                            class="bg-white/10 pl-4 pr-28 py-2.5 rounded-full text-white placeholder:text-white/70 w-full text-sm border border-white/20 focus:bg-white/20 focus:border-white/40 focus:outline-none transition-all duration-200"
                            placeholder="Search products or categories..."
                            autocomplete="off" />
                        <button type="submit"
                                class="absolute top-1 right-1 bottom-1 px-5 rounded-full bg-accent text-white font-semibold text-sm hover:bg-accent/90 transition">
                            Search
                        </button>
                    </form>
                    <div id="desktopSearchSuggestions" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-gray-200 z-[9999] hidden">
                        <div id="desktopSuggestionsList" class="max-h-60 overflow-y-auto"></div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <button id="megaMenuToggle"
                    type="button"
                    class="flex items-center justify-center w-11 h-11 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
                    aria-label="Toggle categories">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h12M4 18h8" />
                    </svg>
                </button>

                <a href="<?= URLROOT ?>/wishlist" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 text-white text-sm font-medium hover:bg-white/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    Wishlist
                </a>

                <a href="<?= URLROOT ?>/cart" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 text-white text-sm font-medium hover:bg-white/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    Cart
                </a>

                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <div class="relative" data-profile-dropdown>
                        <button type="button" class="flex items-center justify-center w-11 h-11 rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors" aria-haspopup="true" aria-expanded="false">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </button>
                        <div class="absolute top-full right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-200 hidden z-[9999]" data-profile-menu>
                            <ul class="py-2 text-sm">
                                <li><a href="<?= URLROOT ?>/user/account" class="flex items-center gap-2 px-4 py-2 text-gray-800 hover:bg-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        My Account
                                    </a></li>
                                <li><a href="<?= URLROOT ?>/orders" class="flex items-center gap-2 px-4 py-2 text-gray-800 hover:bg-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                        </svg>
                                        My Orders
                                    </a></li>
                                <li><a href="<?= URLROOT ?>/wishlist" class="flex items-center gap-2 px-4 py-2 text-gray-800 hover:bg-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        Wishlist
                                    </a></li>
                                <li class="border-t border-gray-200 mt-2 pt-2">
                                    <a href="<?= URLROOT ?>/auth/logout" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= URLROOT ?>/auth/login" class="px-5 py-2 rounded-full bg-accent text-white text-sm font-semibold shadow hover:bg-accent-dark transition-colors">
                        Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
if (!isset($categoriesWithSubs) || !is_array($categoriesWithSubs)) {
    $categoriesWithSubs = \App\Helpers\NavbarHelper::getCategoriesWithSubcategories();
    $categoriesWithSubs = array_filter($categoriesWithSubs, function ($category) {
        $excluded = ['Test', 'test'];
        return !in_array($category['name'], $excluded, true);
    });
}
?>

<!-- Desktop Mega Menu -->
<div id="categoryMegaMenu" class="hidden bg-primary text-white shadow-2xl border border-white/15 rounded-3xl mx-4 mt-4">
    <div class="max-w-7xl mx-auto px-6 py-8 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($categoriesWithSubs as $category): ?>
                <?php
                // Get category image URL
                $categoryImageUrl = $category['image_url'] ?? null;
                if ($categoryImageUrl && !filter_var($categoryImageUrl, FILTER_VALIDATE_URL)) {
                    $categoryImageUrl = \App\Core\View::asset('uploads/images/' . $categoryImageUrl);
                }
                if (!$categoryImageUrl) {
                    $categoryImageUrl = \App\Core\View::asset('images/products/default.jpg');
                }
                ?>
                <div class="category-card border border-white/20 rounded-2xl p-4 bg-white text-gray-900 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-start justify-between text-primary mb-3">
                        <h3 class="text-base font-medium"><?= htmlspecialchars($category['name']) ?></h3>
                        <!-- Small Category Image Thumbnail -->
                        <div class="flex-shrink-0 ml-3">
                            <img src="<?= htmlspecialchars($categoryImageUrl) ?>" 
                                 alt="<?= htmlspecialchars($category['name']) ?>" 
                                 class="w-12 h-12 rounded-2xl object-cover border-2 border-primary/20 shadow-sm"
                                 onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                        </div>
                    </div>
                    <?php if (!empty($category['subcategories'])): ?>
                        <div class="mt-3 space-y-2 text-sm text-gray-600">
                            <?php foreach (array_slice($category['subcategories'], 0, 4) as $subcategory): ?>
                                <a href="<?= URLROOT ?>/products/category/<?= $category['slug'] ?>/<?= $subcategory['slug'] ?>"
                                    class="flex items-center justify-between px-3 py-2 rounded-2xl hover:bg-primary/5 transition-colors">
                                    <span class="text-gray-800 font-medium"><?= htmlspecialchars($subcategory['name']) ?></span>
                                    <?php if ($subcategory['product_count'] > 0): ?>
                                        <span class="text-xs text-gray-400 font-normal"><?= $subcategory['product_count'] ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <a href="<?= URLROOT ?>/products/category/<?= $category['slug'] ?>"
                        class="mt-4 inline-flex items-center text-sm font-medium text-accent hover:text-accent-dark">
                        View all <?= htmlspecialchars($category['name']) ?>
                        <svg class="w-4 h-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0L17 8.586a1 1 0 010 1.414l-3.293 3.293a1 1 0 01-1.414-1.414L13.586 10H4a1 1 0 110-2h9.586l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Mobile Search Component -->
<?php include ROOT_DIR . '/App/views/includes/mobile_search.php'; ?>

<!-- Toast Container -->
<div id="toastContainer" class="fixed top-20 right-4 z-50 w-80 md:w-96 space-y-3"></div>

<!-- JavaScript -->


<script>
    // Navigation dropdown interactions (click-based)
    document.addEventListener('DOMContentLoaded', function() {
        // Mega menu toggle
        const megaToggle = document.getElementById('megaMenuToggle');
        const megaMenu = document.getElementById('categoryMegaMenu');

        const hideMegaMenu = () => {
            if (!megaMenu) return;
            megaMenu.classList.add('hidden');
            megaMenu.classList.remove('block');
            megaToggle?.setAttribute('aria-expanded', 'false');
        };

        const toggleMegaMenu = () => {
            if (!megaMenu) return;
            megaMenu.classList.toggle('hidden');
            megaMenu.classList.toggle('block');
            megaToggle?.setAttribute('aria-expanded', megaMenu.classList.contains('hidden') ? 'false' : 'true');
        };

        megaToggle?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleMegaMenu();
        });

        document.addEventListener('click', (e) => {
            if (!megaMenu || megaMenu.classList.contains('hidden')) return;
            if (!megaMenu.contains(e.target) && e.target !== megaToggle && !megaToggle.contains(e.target)) {
                hideMegaMenu();
            }
        });

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                hideMegaMenu();
            }
        });

        window.addEventListener('scroll', () => {
            hideMegaMenu();
        });

        // Handle category dropdowns
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

        dropdownToggles.forEach(toggle => {
            const menuId = toggle.getAttribute('data-dropdown-toggle');
            const menu = document.querySelector(`[data-dropdown-menu="${menuId}"]`);
            if (!menu) return;

            function toggleDropdown() {
                menu.classList.toggle('hidden');
                menu.classList.toggle('block');
                toggle.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
            }

            function hideDropdown() {
                menu.classList.add('hidden');
                menu.classList.remove('block');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                toggleDropdown();
                return false;
            }, true);

            // Hide dropdown when menu item is clicked
            menu.querySelectorAll('.dropdown-item').forEach((item) => {
                item.addEventListener('click', () => {
                    hideDropdown();
                });
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target) && event.target !== toggle) {
                    hideDropdown();
                }
            });

            // Keyboard support
            toggle.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    hideDropdown();
                }
            });
        });

        // Profile dropdown click handler
        const profileDropdown = document.querySelector('[data-profile-dropdown]');
        if (profileDropdown) {
            const profileBtn = profileDropdown.querySelector('button');
            const profileMenu = profileDropdown.querySelector('[data-profile-menu]');

            if (profileBtn && profileMenu) {
                function toggleProfileMenu() {
                    profileMenu.classList.toggle('hidden');
                    profileMenu.classList.toggle('block');
                    profileBtn.setAttribute('aria-expanded', profileMenu.classList.contains('hidden') ? 'false' : 'true');
                }

                function hideProfileMenu() {
                    profileMenu.classList.add('hidden');
                    profileMenu.classList.remove('block');
                    profileBtn.setAttribute('aria-expanded', 'false');
                }

                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleProfileMenu();
                });

                // Hide when clicking outside
                document.addEventListener('click', (e) => {
                    if (!profileDropdown.contains(e.target)) {
                        hideProfileMenu();
                    }
                });

                // Keyboard support
                profileBtn.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        hideProfileMenu();
                    }
                });
            }
        }
    });
</script>

<style>
    .category-card {
        min-height: 180px;
    }
    
    .category-card img {
        transition: transform 0.2s ease, border-color 0.2s ease;
    }
    
    .category-card:hover img {
        transform: scale(1.05);
        border-color: rgba(var(--primary-rgb, 124, 58, 237), 0.4);
    }
</style>