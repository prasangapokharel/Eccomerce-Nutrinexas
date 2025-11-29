
<?php
/**
 * Quick Action Buttons Component
 * Native-style icon buttons without labels (tooltips only)
 */
?>

<div class="bg-white mx-2 rounded-3xl shadow-sm mb-2 overflow-hidden border border-gray-100/50">
    <div class="p-3 sm:p-4">
        <div class="grid grid-cols-4 gap-4 sm:gap-6">
            <!-- Stores -->
            <a href="<?= \App\Core\View::url('sellers') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Stores">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200 overflow-hidden">
                    <img src="<?= \App\Core\View::publicAsset('images/screen/store.png') ?>" alt="Stores" class="w-full h-full object-contain p-2" loading="lazy" decoding="async">
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Stores</span>
            </a>

            <!-- Coupon -->
            <a href="<?= \App\Core\View::url('coupons') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Coupons">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200 overflow-hidden">
                    <img src="<?= \App\Core\View::publicAsset('images/screen/coupon.svg') ?>" alt="Coupon" class="w-full h-full object-contain p-2" loading="lazy" decoding="async">
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Coupon</span>
            </a>

            <!-- Affiliate -->
            <a href="<?= \App\Core\View::url('affiliate/products') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Affiliate Program">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200 overflow-hidden">
                    <img src="<?= \App\Core\View::publicAsset('images/screen/affilate.svg') ?>" alt="Affiliate" class="w-full h-full object-contain p-2" loading="lazy" decoding="async">
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Affiliate</span>
            </a>

            <!-- Top Sale -->
            <a href="<?= \App\Core\View::url('products?sort=popular') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Top Selling Products">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200 overflow-hidden">
                    <img src="<?= \App\Core\View::publicAsset('images/screen/topsale.svg') ?>" alt="Top Sale" class="w-full h-full object-contain p-2" loading="lazy" decoding="async">
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Top Sale</span>
            </a>

            <!-- Launching Soon -->
            <a href="<?= \App\Core\View::url('products/launching') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="Launching Soon">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200 overflow-hidden">
                    <img src="<?= \App\Core\View::publicAsset('images/screen/schedule.svg') ?>" alt="New Arrivals" class="w-full h-full object-contain p-2" loading="lazy" decoding="async">
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Launching</span>
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

            <!-- Categories -->
            <a href="<?= \App\Core\View::url('products') ?>" 
               class="flex flex-col items-center justify-center w-full relative group"
               title="All Categories">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-gray-50 rounded-2xl flex items-center justify-center border border-gray-200 overflow-hidden">
                    <img src="<?= \App\Core\View::publicAsset('images/screen/cateogory.svg') ?>" alt="Categories" class="w-full h-full object-contain p-2" loading="lazy" decoding="async">
                </div>
                <span class="text-[10px] sm:text-xs text-gray-600 mt-1.5 font-medium">Categories</span>
            </a>
        </div>
    </div>
</div>