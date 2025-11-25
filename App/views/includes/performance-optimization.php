<?php
/**
 * Performance Optimization Script
 * Turbo-fast loading optimizations
 */

// Initialize performance cache
if (!class_exists('App\Helpers\PerformanceCache')) {
    require_once ROOT_DIR . '/App/Helpers/PerformanceCache.php';
}
\App\Helpers\PerformanceCache::init();

// Get cache statistics for debugging
$cacheStats = \App\Helpers\PerformanceCache::getCacheStats();
?>

<!-- Performance Optimization Meta Tags -->
<meta name="format-detection" content="telephone=no">
<meta name="theme-color" content="#0A3167">
<meta name="msapplication-TileColor" content="#0A3167">
<meta name="msapplication-config" content="<?= ASSETS_URL ?>/images/favicon/browserconfig.xml">

<!-- Preload Critical Resources -->
<link rel="preload" href="<?= ASSETS_URL ?>/css/optimized.css" as="style">
<link rel="preload" href="<?= ASSETS_URL ?>/js/optimized.js" as="script">
<link rel="preload" href="<?= ASSETS_URL ?>/images/logo/logo.min.svg" as="image">

<!-- DNS Prefetch for External Resources -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//cdn.jsdelivr.net">
<link rel="dns-prefetch" href="//qkjsnpejxzujoaktpgpq.supabase.co">



<!-- Performance Monitoring Script -->
<script>
    // Performance monitoring
    window.performanceData = {
        startTime: performance.now(),
        cacheStats: <?= json_encode($cacheStats) ?>,
        debugMode: <?= defined('DEBUG') && DEBUG ? 'true' : 'false' ?>
    };
    
    // Lazy loading for images
    function initLazyLoading() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => {
            img.classList.add('lazy-image');
            imageObserver.observe(img);
        });
    }
    
    // Preload critical images
    function preloadCriticalImages() {
        const criticalImages = [
            '<?= ASSETS_URL ?>/images/products/default.jpg',
            '<?= ASSETS_URL ?>/images/logo/logo.min.svg'
        ];
        
        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initLazyLoading();
            preloadCriticalImages();
        });
    } else {
        initLazyLoading();
        preloadCriticalImages();
    }
    
    // Performance logging
    window.addEventListener('load', function() {
        const loadTime = performance.now() - window.performanceData.startTime;
        console.log('Page load time:', loadTime + 'ms');
        
        if (window.performanceData.debugMode) {
            console.log('Cache stats:', window.performanceData.cacheStats);
        }
    });
</script>

<!-- Service Worker for caching (if supported) -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    console.log('SW registered: ', registration);
                })
                .catch(function(registrationError) {
                    console.log('SW registration failed: ', registrationError);
                });
        });
    }
</script>
