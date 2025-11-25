<!-- Bottom Navigation for Mobile -->
<div class="fixed bottom-0 left-0 right-0  border-t rounded-t-2xl border-primary/20 z-40 lg:hidden bg-primary">
    <div class="flex items-center justify-around py-2">
        <!-- Home -->
        <a href="<?= \App\Core\View::url('') ?>" class="flex flex-col items-center py-2 px-3 text-white">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
        </a>

        <!-- Categories -->
        <a href="<?= \App\Core\View::url('categories') ?>" class="flex flex-col items-center py-2 px-3 text-white">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
            </svg>
        </a>

        <!-- Cart (Middle - Elevated) -->
        <a href="<?= \App\Core\View::url('cart') ?>" class="relative -mt-9 flex flex-col items-center">
            <div class="w-14 h-14 rounded-full bg-accent shadow-lg flex items-center justify-center relative">
                <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
            </div>
        </a>

        <!-- Orders (only for logged in users) / Products (for non-logged in) -->
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
            <!-- Orders (only show when logged in) -->
            <a href="<?= \App\Core\View::url('orders') ?>" class="flex flex-col items-center py-2 px-3 text-white">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 14.25 6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008V13.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </a>
        <?php else: ?>
            <!-- Products (show when not logged in) -->
            <a href="<?= \App\Core\View::url('products') ?>" class="flex flex-col items-center py-2 px-3 text-white">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
            </a>
        <?php endif; ?>

        <!-- Profile (only for logged in users) / Login (for non-logged in) -->
        <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
            <!-- Profile (only show when logged in) -->
            <a href="<?= \App\Core\View::url('user/account') ?>" class="flex flex-col items-center py-2 px-3 text-white">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <!-- <span class="text-xs mt-1 font-medium text-center">Profile</span> -->
            </a>
        <?php else: ?>
            <!-- Login (show when not logged in) -->
            <a href="<?= \App\Core\View::url('auth/login') ?>" class="flex flex-col items-center py-2 px-3 text-white">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </a>
        <?php endif; ?>
    </div>
</div>

<script>

// Mobile search functionality
document.addEventListener('DOMContentLoaded', function() {
    
    const mobileSearchToggle = document.getElementById('mobileSearchToggle');
    const mobileSearchOverlay = document.getElementById('mobileSearchOverlay');
    const closeMobileSearch = document.getElementById('closeMobileSearch');
    
    if (mobileSearchToggle && mobileSearchOverlay && closeMobileSearch) {
        mobileSearchToggle.addEventListener('click', function() {
            mobileSearchOverlay.classList.remove('hidden');
        });
        
        closeMobileSearch.addEventListener('click', function() {
            mobileSearchOverlay.classList.add('hidden');
        });
        
        // Close on overlay click
        mobileSearchOverlay.addEventListener('click', function(e) {
            if (e.target === mobileSearchOverlay) {
                mobileSearchOverlay.classList.add('hidden');
            }
        });
    }
});
</script>

<style>

/* Mobile scrolling fixes */
@media (max-width: 1023px) {
    body {
        padding-bottom: 80px; /* Space for bottom nav */
        overflow-x: hidden; /* Prevent horizontal scroll */
    }
    
    .min-h-screen {
        min-height: calc(100vh - 80px); /* Account for bottom nav */
    }
    
    /* Fix for pages with content that might overflow */
    .container {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    /* Ensure proper scrolling on mobile */
    html, body {
        -webkit-overflow-scrolling: touch;
        overflow-x: hidden;
    }
    
    /* Fix for specific pages */
    .max-w-md {
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* Remove bottom padding when bottom nav is hidden */
    body.no-bottom-nav {
        padding-bottom: 0;
    }
}
</style>