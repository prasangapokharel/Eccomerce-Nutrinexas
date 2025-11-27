<?php
/**
 * Product Grid Banner Component
 * Displays small rectangle banner ad between product grid rows (after every 10 products)
 * Tier 3 placement - High click performance
 */

use App\Services\BannerAdDisplayService;
use App\Helpers\AdTrackingHelper;

$bannerService = new BannerAdDisplayService();
$banner = $bannerService->getProductGridBanner();
?>

<?php if (!empty($banner)): ?>
    <div class="col-span-full my-4">
        <div class="bg-white rounded-lg shadow-sm overflow-hidden relative">
            <!-- Ads Label - Top Right -->
            <div class="absolute top-2 right-2 z-20 bg-black/80 text-white px-2 py-1 rounded-md text-xs font-bold backdrop-blur-sm shadow-lg" style="letter-spacing: 0.5px;">
                Ads
            </div>
            
            <a href="<?= htmlspecialchars($banner['banner_link'] ?? '#') ?>" 
               target="_blank" 
               rel="noopener noreferrer"
               onclick="<?= AdTrackingHelper::getClickTrackingJS($banner['id']) ?>"
               class="block relative">
                <img src="<?= htmlspecialchars($banner['banner_image']) ?>" 
                     alt="Advertisement" 
                     class="w-full h-auto object-cover rounded-lg"
                     style="max-width: 100%; height: auto; display: block;"
                     loading="lazy"
                     onload="<?= AdTrackingHelper::getReachTrackingJS($banner['id']) ?>"
                     onerror="this.style.display='none'">
            </a>
        </div>
    </div>
<?php endif; ?>


