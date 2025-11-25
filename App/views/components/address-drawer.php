<?php
/**
 * Address Drawer Component
 * Shows a bottom drawer prompting user to add address after login/register
 * Only displays if user has no saved addresses
 */
use App\Core\Session;
use App\Models\Address;

if (!Session::has('user_id')) {
    return; // Don't show if not logged in
}

$userId = Session::get('user_id');
$addressModel = new Address();
$defaultAddress = $addressModel->getDefaultAddress($userId);

// Only show if user has no default address
if ($defaultAddress) {
    return;
}
?>

<!-- Address Drawer -->
<div id="address-drawer" class="fixed inset-x-0 bottom-0 z-[9999] transform translate-y-full transition-transform duration-300 ease-in-out">
    <div class="bg-white rounded-t-3xl shadow-2xl border-t border-gray-200 max-w-md mx-auto">
        <!-- Drawer Handle -->
        <div class="flex justify-center pt-3 pb-2">
            <div class="w-12 h-1 bg-gray-300 rounded-full"></div>
        </div>
        
        <!-- Drawer Content -->
        <div class="px-6 pb-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Add Your Address</h3>
                    <p class="text-sm text-gray-600">Enter your delivery address to get started</p>
                </div>
                <button onclick="closeAddressDrawer()" class="ml-4 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="flex-1">
                    <p class="text-sm text-gray-700 mb-2">We need your address to deliver your orders</p>
                    <a href="<?= \App\Core\View::url('user/address') ?>" 
                       class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Address
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backdrop -->
<div id="address-drawer-backdrop" class="fixed inset-0 bg-black/50 z-[9998] opacity-0 transition-opacity duration-300 pointer-events-none"></div>

<script>
(function() {
    'use strict';
    
    function initAddressDrawer() {
        // Check if drawer should be shown
        const drawer = document.getElementById('address-drawer');
        const backdrop = document.getElementById('address-drawer-backdrop');
        
        if (!drawer || !backdrop) {
            return; // Drawer elements not found
        }
        
        // Show drawer on any page if user has no default address
        // Check if we should show (not on address page itself to avoid loop)
        const pathname = window.location.pathname;
        const isAddressPage = pathname.includes('/user/address') || pathname.includes('/address');
        
        if (!isAddressPage) {
            setTimeout(function() {
                const drawerEl = document.getElementById('address-drawer');
                const backdropEl = document.getElementById('address-drawer-backdrop');
                
                if (drawerEl && backdropEl) {
                    drawerEl.classList.remove('translate-y-full');
                    backdropEl.classList.remove('opacity-0', 'pointer-events-none');
                    backdropEl.classList.add('opacity-100', 'pointer-events-auto');
                }
            }, 1500); // Show after 1.5 seconds
        }
    }
    
    // Close drawer function
    window.closeAddressDrawer = function() {
        const drawer = document.getElementById('address-drawer');
        const backdrop = document.getElementById('address-drawer-backdrop');
        
        if (drawer && backdrop) {
            drawer.classList.add('translate-y-full');
            backdrop.classList.remove('opacity-100', 'pointer-events-auto');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAddressDrawer);
    } else {
        initAddressDrawer();
    }
    
    // Close on backdrop click
    document.addEventListener('DOMContentLoaded', function() {
        const backdrop = document.getElementById('address-drawer-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', closeAddressDrawer);
        }
    });
})();
</script>

