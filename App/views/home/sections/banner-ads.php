<?php
/**
 * Banner Ads Section
 * Displays external banner advertisements using fixed tier slots.
 * Tier 1 - Premium Position (Homepage/Category)
 */
use App\Services\BannerAdDisplayService;
use App\Helpers\AdTrackingHelper;

$bannerService = new BannerAdDisplayService();
$banner = $bannerService->getHomepageBanner(); // Get single banner using probability formula
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

<script>
// Ad tracking functions (if not already defined)
if (typeof trackAdReach === 'undefined') {
    function trackAdReach(adId) {
        if (!adId) return;
        fetch('<?= \App\Core\View::url("ads/reach") ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ads_id: adId, ip_address: '<?= $_SERVER["REMOTE_ADDR"] ?? "" ?>'}),
            keepalive: true
        }).catch(() => {});
    }
}

if (typeof trackAdClick === 'undefined') {
    function trackAdClick(adId) {
        if (!adId) return;
        fetch('<?= \App\Core\View::url("ads/click") ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ads_id: adId, ip_address: '<?= $_SERVER["REMOTE_ADDR"] ?? "" ?>'}),
            keepalive: true
        }).catch(() => {});
    }
}
</script>



