<?php
/**
 * Mid Banner Component
 * Displays mid-section banner ad (Tier 2)
 * Used for Search Mid, Category Mid, Home Mid placements
 */

use App\Services\BannerAdDisplayService;
use App\Helpers\AdTrackingHelper;

$slotKey = $slotKey ?? null;
if (!$slotKey) {
    return;
}

$banner = null;
if (isset($bannerData) && is_array($bannerData)) {
    $banner = $bannerData;
} else {
    $bannerService = new BannerAdDisplayService();
    $banner = $bannerService->getBannerBySlot($slotKey);
}
?>

<?php if (!empty($banner)): ?>
    <div class="mx-2 mb-4">
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden relative">
            <!-- Ads Label - Top Right -->
            <div class="absolute top-2 right-2 z-20 bg-black/80 text-white px-2.5 py-1 rounded-md text-xs font-bold backdrop-blur-sm shadow-lg" style="letter-spacing: 0.5px;">
                Ads
            </div>
            
            <a href="<?= htmlspecialchars($banner['banner_link'] ?? '#') ?>" 
               target="_blank" 
               rel="noopener noreferrer"
               onclick="<?= AdTrackingHelper::getClickTrackingJS($banner['id']) ?>"
               class="block relative">
                <img src="<?= htmlspecialchars($banner['banner_image']) ?>" 
                     alt="Advertisement" 
                     class="w-full h-auto object-cover rounded-2xl"
                     style="max-width: 100%; height: auto; display: block;"
                     loading="lazy"
                     onload="<?= AdTrackingHelper::getReachTrackingJS($banner['id']) ?>"
                     onerror="this.style.display='none'">
            </a>
        </div>
    </div>
<?php endif; ?>

<?php unset($bannerData); ?>

