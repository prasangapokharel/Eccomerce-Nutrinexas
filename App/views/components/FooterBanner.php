<?php
/**
 * Footer Banner Component
 * Displays banner ad at the very bottom of the site footer
 * Tier 3 placement - Low price, good for small companies
 * Always viewed by long scrolling users
 */

use App\Services\BannerAdDisplayService;
use App\Helpers\AdTrackingHelper;

$bannerService = new BannerAdDisplayService();
$banner = $bannerService->getGlobalFooterBanner();
?>

<?php if (!empty($banner)): ?>
    <div class="w-full mt-8 mb-4">
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

