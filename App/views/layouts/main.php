<!DOCTYPE html>
<?php
// Error reporting - only show in development
if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $title ?? 'NutriNexus - Premium Supplements & Health Products' ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= $description ?? 'Discover premium supplements, health products, and wellness solutions at NutriNexus. Quality products for a healthier lifestyle with fast delivery across Nepal.' ?>">
    <meta name="keywords" content="<?= $keywords ?? 'supplements, health products, wellness, vitamins, protein, fitness, nutrition, Nepal, online shopping, premium quality' ?>">
    <meta name="author" content="NutriNexus">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="bingbot" content="index, follow">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= $title ?? 'NutriNexus - Premium Supplements & Health Products' ?>">
    <meta property="og:description" content="<?= $description ?? 'Discover premium supplements, health products, and wellness solutions at NutriNexus. Quality products for a healthier lifestyle.' ?>">
    <meta property="og:url" content="<?= URLROOT . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:site_name" content="NutriNexus">
    <meta property="og:image" content="<?= $og_image ?? ASSETS_URL . '/images/og-image.jpg' ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="NutriNexus - Premium Supplements & Health Products">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $title ?? 'NutriNexus - Premium Supplements & Health Products' ?>">
    <meta name="twitter:description" content="<?= $description ?? 'Discover premium supplements, health products, and wellness solutions at NutriNexus.' ?>">
    <meta name="twitter:image" content="<?= $og_image ?? ASSETS_URL . '/images/og-image.jpg' ?>">
    <meta name="twitter:image:alt" content="NutriNexus - Premium Supplements & Health Products">
    
    <!-- Additional SEO Meta Tags -->
    <meta name="theme-color" content="#6366F1">
    <meta name="msapplication-TileColor" content="#6366F1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="<?= ASSETS_URL ?>/css/tailwind.css" as="style">
    <link rel="preload" href="<?= ASSETS_URL ?>/css/app.css" as="style">
    <link rel="preload" href="<?= \App\Core\View::asset('images/logo/logo.png') ?>" as="image" fetchpriority="high">
    <meta name="apple-mobile-web-app-title" content="NutriNexus">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= URLROOT . $_SERVER['REQUEST_URI'] ?>">
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "NutriNexus",
        "url": "<?= URLROOT ?>",
        "logo": "<?= ASSETS_URL ?>/images/logo.png",
        "description": "Premium supplements, health products, and wellness solutions",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "NP"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "customer service",
            "availableLanguage": ["English", "Nepali"]
        },
        "sameAs": [
            "https://facebook.com/nutrinexus",
            "https://instagram.com/nutrinexus"
        ]
    }
    </script>
    <link rel="stylesheet" href="<?= \App\Core\View::asset('css/tailwind.css') ?>">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/image-performance.css">
    <link rel="icon" href="https://nutrinexas.shop/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="https://nutrinexas.shop/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= ASSETS_URL ?>/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= ASSETS_URL ?>/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= ASSETS_URL ?>/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="<?= ASSETS_URL ?>/images/favicon/site.webmanifest">
    <link rel="mask-icon" href="<?= ASSETS_URL ?>/images/favicon/safari-pinned-tab.svg" color="#6366F1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/favicon/icon.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
  
    <?php if (isset($extraStyles)): ?>
        <?= $extraStyles ?>
    <?php endif; ?>
 
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <div id="global-page-loader" class="text-primary fixed inset-0 z-50 flex items-center justify-center pointer-events-none">
        <img src="<?= \App\Core\View::publicAsset('images/loader/loader2.gif') ?>" alt="Loading..." class="w-19 h-19">
    </div>
    <?php include ROOT_DIR . '/App/views/includes/navbar.php'; ?>
 
    <main class="flex-grow">
        <?php if (\App\Core\Session::hasFlash()): ?>
            <?php $flash = \App\Core\Session::getFlash(); ?>
            <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 py-3 sm:py-4">
                <div class="<?= $flash['type'] === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700' ?> px-3 sm:px-4 py-2 sm:py-3 rounded relative border-l-4 flex items-center" role="alert">
                    <span class="<?= $flash['type'] === 'success' ? 'text-green-500' : 'text-red-500' ?> flex-shrink-0 mr-2">
                        <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    </span>
                    <span class="block text-sm sm:text-base"><?= $flash['message'] ?></span>
                    <button type="button" class="absolute top-0 right-0 mt-3 mr-3 text-gray-400 hover:text-gray-500" onclick="this.parentElement.remove()">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="w-full max-w-6xl mx-auto  sm:px-6  sm:py-6">
            <?= $content ?>
        </div>
    </main>


    <footer class="mt-auto bg-primary-dark text-white hidden md:block">
        <?php include ROOT_DIR . '/App/views/includes/footer.php'; ?>
    </footer>
    
    <!-- Bottom Navigation for Mobile -->
    <?php 
    // Hide bottom navigation on specific pages
    $currentPage = $_SERVER['REQUEST_URI'] ?? '';
    $hideBottomNav = false;
    
    // Check if current page is product view
    if (strpos($currentPage, '/products/view/') !== false) {
        $hideBottomNav = true;
    }
    
    // Check if current page is cart
    if (strpos($currentPage, '/cart') !== false) {
        $hideBottomNav = true;
    }
    
    // Check if current page is checkout
    if (strpos($currentPage, '/checkout') !== false) {
        $hideBottomNav = true;
    }

    // Check if current page is login
    if (strpos($currentPage, '/auth/login') !== false) {
        $hideBottomNav = true;
    }
    
    // Check if current page is register
    if (strpos($currentPage, '/auth/register') !== false) {
        $hideBottomNav = true;
    }
    
    if (!$hideBottomNav) {
        include ROOT_DIR . '/App/views/includes/bottomnav.php';
    } else {
        // Add class to body to remove bottom padding when bottom nav is hidden
        echo '<script>document.body.classList.add("no-bottom-nav");</script>';
    }
    ?>
    
    <!-- Mobile navigation drawer overlay (hidden by default) -->
    <div id="mobileNavOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="closeMobileNav()"></div>
    
    <script>
        // Mobile navigation toggle functions
        function openMobileNav() {
            document.getElementById('mobileNav').classList.remove('translate-x-full');
            document.getElementById('mobileNavOverlay').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
        
        function closeMobileNav() {
            document.getElementById('mobileNav').classList.add('translate-x-full');
            document.getElementById('mobileNavOverlay').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        
        // Check for iOS safe area
        if (navigator.userAgent.match(/iPhone|iPad|iPod/)) {
            document.documentElement.classList.add('has-safe-area');
        }

        // Add resize listener for dynamic adjustments
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeMobileNav();
            }
        });

        // Footer visibility handled by CSS media queries
    </script>
    
        <script src="<?= ASSETS_URL ?>/js/loader.js"></script>
        <script src="<?= ASSETS_URL ?>/js/image-performance.js"></script>
        <script src="<?= ASSETS_URL ?>/js/cartNotifier.js"></script>
    
    <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
    
    <!-- Session Recovery Script -->
    <?php if (\App\Core\Session::get('user_id')): ?>
        <?= \App\Helpers\SessionRecoveryHelper::getLocalStorageScript(\App\Core\Session::get('persistent_token', '')) ?>
    <?php else: ?>
        <?= \App\Helpers\SessionRecoveryHelper::getTokenRecoveryScript() ?>
    <?php endif; ?>
</body>
</html>
