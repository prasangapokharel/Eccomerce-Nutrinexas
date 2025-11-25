<?php ob_start(); ?>
<?php
use App\Services\BannerAdDisplayService;

$title = 'NutriNexus - Premium Supplements & Nutrition';
$description = 'Discover premium quality supplements and nutrition products at NutriNexus. Transform your fitness journey with our wide range of proteins, vitamins, and wellness products.';
$sectionsPath = __DIR__ . '/sections';
include __DIR__ . '/../components/pricing-helper.php';

$bannerDisplayService = new BannerAdDisplayService();
$homeSliderBanner = $bannerDisplayService->getBannerBySlot('slot_home_slider_banner');
$homeCategoriesBanner = $bannerDisplayService->getBannerBySlot('slot_home_categories_banner');
?>

<div class="bg-gray-50">
    <section class="overflow-hidden" style="background: transparent !important;">
        <div class="container mx-auto px-4">
            <?php include __DIR__ . '/../components/slider.php'; ?>
        </div>
    </section>

    <!-- Tier 1 · Top Hero Banner -->
    <section class="py-4">
        <div class="container mx-auto px-4">
            <?php include $sectionsPath . '/banner-ads.php'; ?>
        </div>
    </section>

   

    <section class="py-3">
        <?php include $sectionsPath . '/quick-actions.php'; ?>
    </section>

    <!-- Banner below slider -->
    <?php if (!empty($homeSliderBanner)): ?>
        <section class="py-4">
            <div class="container mx-auto px-4">
                <?php 
                $slotKey = 'slot_home_slider_banner';
                $bannerData = $homeSliderBanner;
                include __DIR__ . '/../components/MidBanner.php'; 
                ?>
            </div>
        </section>
    <?php endif; ?>
    <section class="py-6">
        <?php include $sectionsPath . '/categories.php'; ?>
    </section>
    

    <!-- Banner below categories section -->
    <?php if (!empty($homeCategoriesBanner)): ?>
        <section class="py-4">
            <div class="container mx-auto px-4">
                <?php 
                $slotKey = 'slot_home_categories_banner';
                $bannerData = $homeCategoriesBanner;
                include __DIR__ . '/../components/MidBanner.php'; 
                ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="py-6">
        <?php include $sectionsPath . '/flash-sale.php'; ?>
    </section>

    <section class="py-6">
        <?php include $sectionsPath . '/top-sale.php'; ?>
    </section>

    <section class="py-6">
        <?php include $sectionsPath . '/latest-products.php'; ?>
    </section>

    <section class="py-6">
        <?php include $sectionsPath . '/featured-products.php'; ?>
    </section>

    <!-- Infinite Scroll Products Section -->
    <section class="py-2">
        <?php include $sectionsPath . '/Newproductsinfinnite.php'; ?>
    </section>

    <?php include $sectionsPath . '/home-assets.php'; ?>
</div>

<?php 
$content = ob_get_clean(); 
include dirname(dirname(__FILE__)) . '/layouts/main.php'; 
?>


