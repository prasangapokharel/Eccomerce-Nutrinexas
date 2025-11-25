
<?php
/**
 * Quick Action Buttons Component
 * Native-style icon buttons without labels (tooltips only)
 */
?>

<div class="bg-white mx-2 rounded-3xl shadow-sm mb-2 overflow-hidden border border-gray-100/50">
    <div class="p-3 sm:p-4">
        <div class="grid grid-cols-4 gap-4 sm:gap-6">
            <!-- Sale -->
            <a href="<?= \App\Core\View::url('products?sort=price-low&sale=1') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Sale">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Sale</span>
            </a>

            <!-- Coupon -->
            <a href="<?= \App\Core\View::url('coupons') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Coupons">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                    </svg>
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Coupon</span>
            </a>

            <!-- Affiliate -->
            <a href="<?= \App\Core\View::url('affiliate/products') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Affiliate Program">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Affiliate</span>
            </a>

            <!-- Top Sale -->
            <a href="<?= \App\Core\View::url('products?sort=popular') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Top Selling Products">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Top Sale</span>
            </a>

            <!-- New Arrivals -->
            <a href="<?= \App\Core\View::url('products?sort=newest') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="New Arrivals">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">New</span>
            </a>

            <!-- Best Sellers -->
            <a href="<?= \App\Core\View::url('products?sort=popular&featured=1') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Best Sellers">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Best</span>
            </a>

            <!-- Flash Sale -->
            <a href="<?= \App\Core\View::url('products?flash=1') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Flash Sale">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Flash</span>
            </a>

            <!-- Categories -->
            <a href="<?= \App\Core\View::url('products') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="All Categories">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Categories</span>
            </a>
        </div>
    </div>
</div>