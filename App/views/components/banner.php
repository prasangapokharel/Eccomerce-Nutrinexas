<?php
/**
 * Banner Component
 * 
 * Displays active banners from the database
 * Recommended size: 1290 × 493 pixels (aspect ratio ~2.6:1)
 */

use App\Models\Banner;

$bannerModel = new Banner();
$activeBanners = $bannerModel->getActiveBanners();

if (empty($activeBanners)) {
    return;
}
?>

<section class="py-0">
    <div class="container mx-auto px-4">
        <?php foreach ($activeBanners as $banner): ?>
            <div class="bg-white mx-2 rounded-3xl shadow-sm overflow-hidden relative">
                <!-- Preloader Skeleton -->
                <div class="banner-preloader absolute inset-0 bg-gray-200 animate-pulse z-10" style="aspect-ratio: 2.6 / 1;">
                    <div class="w-full h-full bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200"></div>
                </div>
                
                <!-- ADs Badge -->
                <div class="absolute top-2 right-2 z-20 bg-black/70 text-white px-2 py-1 rounded-md text-xs font-semibold backdrop-blur-sm">
                    ADs
                </div>
                
                <?php if (!empty($banner['link_url'])): ?>
                    <a href="<?= \App\Core\View::url('banner/click/' . $banner['id']) ?>" 
                       class="block w-full relative z-0"
                       onclick="trackBannerView(<?= $banner['id'] ?>)">
                        <img src="<?= htmlspecialchars($banner['image_url']) ?>" 
                             alt="Promotional Banner" 
                             class="w-full h-auto object-cover banner-image opacity-0 transition-opacity duration-300"
                             style="aspect-ratio: 2.6 / 1; max-height: 493px;"
                             loading="lazy"
                             decoding="async"
                             fetchpriority="low"
                             onload="this.classList.add('opacity-100'); this.parentElement.parentElement.querySelector('.banner-preloader').style.display='none'; trackBannerView(<?= $banner['id'] ?>);"
                             onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'; this.classList.add('opacity-100'); this.parentElement.parentElement.querySelector('.banner-preloader').style.display='none';">
                    </a>
                <?php else: ?>
                    <div class="block w-full relative z-0">
                        <img src="<?= htmlspecialchars($banner['image_url']) ?>" 
                             alt="Promotional Banner" 
                             class="w-full h-auto object-cover banner-image opacity-0 transition-opacity duration-300"
                             style="aspect-ratio: 2.6 / 1; max-height: 493px;"
                             loading="lazy"
                             decoding="async"
                             fetchpriority="low"
                             onload="this.classList.add('opacity-100'); this.parentElement.querySelector('.banner-preloader').style.display='none'; trackBannerView(<?= $banner['id'] ?>);"
                             onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'; this.classList.add('opacity-100'); this.parentElement.querySelector('.banner-preloader').style.display='none';">
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
function trackBannerView(bannerId) {
    // Track view only once per session
    const viewKey = 'banner_view_' + bannerId;
    if (sessionStorage.getItem(viewKey)) {
        return;
    }
    
    sessionStorage.setItem(viewKey, 'true');
    
    // Send view tracking request
        fetch('<?= \App\Core\View::url('banner/view/') ?>' + bannerId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).catch(err => console.error('Banner view tracking error:', err));
}
</script>

