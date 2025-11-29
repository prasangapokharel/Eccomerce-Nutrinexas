<?php
// Ensure autoloader is loaded
if (!class_exists('League\CommonMark\CommonMarkConverter')) {
    $composerAutoload = dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        require_once $composerAutoload;
    }
}

use App\Helpers\CurrencyHelper;
use App\Core\Session;
use League\CommonMark\CommonMarkConverter;

include dirname(__DIR__) . '/components/pricing-helper.php';

// User authentication check
$userId = \App\Core\Session::get('user_id');
$isLoggedIn = !empty($userId);

// Check user review status
$productId = $product['id'] ?? null;
$userReview = null;
if ($isLoggedIn && $productId) {
    $reviewModel = new \App\Models\Review();
    if (method_exists($reviewModel, 'getUserReview')) {
        $userReview = $reviewModel->getUserReview($userId, $productId);
    } elseif (method_exists($reviewModel, 'hasUserReviewed') && $reviewModel->hasUserReviewed($userId, $productId)) {
        $productReviews = $reviewModel->getByProductId($productId);
        foreach ($productReviews as $rev) {
            if (isset($rev['user_id']) && $rev['user_id'] == $userId) {
                $userReview = $rev;
                break;
            }
        }
    }
}

// Product data
$productName = htmlspecialchars($product['product_name'] ?? 'Product');
$productDescription = strip_tags($product['description'] ?? '');
$productPrice = isset($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'] ?? 0;
$productCurrency = 'NPR';
$productCategory = htmlspecialchars($product['category'] ?? '');
$normalizedCategory = strtolower(trim($product['category'] ?? ''));
$productBrand = 'NutriNexus';

// Check if product is scheduled
$isScheduled = false;
$isAvailable = isset($product['stock_quantity']) && $product['stock_quantity'] > 0;

// Calculate remaining days for scheduled products
$remainingDays = 0;
$launchTimestamp = null;
$mysteryPrice = null;
$mysteryPriceRange = null;
if (isset($product['is_scheduled']) && $product['is_scheduled'] == 1 && !empty($product['scheduled_date'])) {
    $scheduledTimestamp = strtotime($product['scheduled_date']);
    $currentTimestamp = time();
    
    // Product is scheduled only if launch date is in the future
    // If launch date equals or is before current date, allow ordering
    if ($scheduledTimestamp > $currentTimestamp) {
        $isScheduled = true;
        $isAvailable = false;
        $launchTimestamp = $scheduledTimestamp; // Only set if date hasn't passed
        $remainingDays = ceil(($scheduledTimestamp - $currentTimestamp) / (60 * 60 * 24));
    } else {
        // Launch date has arrived or passed - allow ordering
        $isScheduled = false;
        $launchTimestamp = null; // Don't set launchTimestamp if date has passed
        $isAvailable = isset($product['stock_quantity']) && $product['stock_quantity'] > 0;
    }

    // Calculate mystery price range without leaking sale price
    $regularPrice = floatval($product['price'] ?? 0);
    
    if ($regularPrice > 0) {
        // Create a range that doesn't reveal the actual sale price
        // Use regular price as ceiling and 30% discount as floor (ensures sale price is within range but not revealed)
        $ceilingPrice = $regularPrice;
        $floorPrice = $regularPrice * 0.70; // 30% discount floor (sale_price will be somewhere in this range but not revealed)
        
        // Ensure minimum floor price
        if ($floorPrice <= 0) {
            $floorPrice = max($ceilingPrice * 0.7, 1);
        }
        
        // Ensure floor is less than ceiling
        if ($floorPrice >= $ceilingPrice) {
            $floorPrice = $ceilingPrice * 0.7;
        }
        
        $minCents = (int) round($floorPrice * 100);
        $maxCents = (int) round($ceilingPrice * 100);
        if ($maxCents < $minCents) {
            $maxCents = $minCents;
        }
        $randomCents = mt_rand($minCents, $maxCents);
        $mysteryPrice = $randomCents / 100;
        $mysteryPriceRange = [$floorPrice, $ceilingPrice];
    }
}
$productSku = $product['id'] ?? '';
$productSlug = $product['slug'] ?? $product['id'] ?? '';
$productUrl = \App\Core\View::url('products/view/' . $productSlug);
// Ensure absolute URL for SEO
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
if (strpos($productUrl, 'http') !== 0) {
    $productUrl = $baseUrl . (strpos($productUrl, '/') === 0 ? '' : '/') . ltrim($productUrl, '/');
}
$productAvailability = (isset($product['stock_quantity']) && $product['stock_quantity'] > 0) ? 'InStock' : 'OutOfStock';

// Get main image URL - ensure it's absolute for social sharing
$mainImageUrl = $product['image_url'] ?? ASSETS_URL . '/images/products/default.jpg';

// Ensure absolute URL for social sharing (OG images must be absolute)
if (!empty($mainImageUrl)) {
    // If it's already a full URL (starts with http:// or https://), use it as is
    if (filter_var($mainImageUrl, FILTER_VALIDATE_URL)) {
        // Already absolute URL
    } else {
        // Convert relative URL to absolute
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        if (strpos($mainImageUrl, '/') === 0) {
            $mainImageUrl = $baseUrl . $mainImageUrl;
        } else {
            $mainImageUrl = $baseUrl . '/' . ltrim($mainImageUrl, '/');
        }
    }
}

// Get additional images for schema (multiple images improve SEO)
$additionalImages = [];
if (isset($product['id'])) {
    $productImageModel = new \App\Models\ProductImage();
    $allImages = $productImageModel->getByProductId($product['id']);
    foreach ($allImages as $img) {
        $imgUrl = $img['image_url'] ?? '';
        if (!empty($imgUrl)) {
            if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                if (strpos($imgUrl, '/') === 0) {
                    $imgUrl = $baseUrl . $imgUrl;
                } else {
                    $imgUrl = $baseUrl . '/' . ltrim($imgUrl, '/');
                }
            }
            $additionalImages[] = $imgUrl;
        }
    }
}
// Add main image to additional images if not already there
if (!in_array($mainImageUrl, $additionalImages)) {
    array_unshift($additionalImages, $mainImageUrl);
}

// SEO data - enhanced
$seoTitle = htmlspecialchars($productName . ' - ' . $productCategory . ' | NutriNexus Nepal', ENT_QUOTES, 'UTF-8');
$seoDescription = !empty($productDescription) 
    ? htmlspecialchars(substr(strip_tags($productDescription), 0, 155), ENT_QUOTES, 'UTF-8') 
    : htmlspecialchars('Premium ' . $productCategory . ' from NutriNexus. High-quality supplements and health products with fast delivery across Nepal.', ENT_QUOTES, 'UTF-8');

// Get site URL for canonical and OG tags (productUrl is already absolute now)
$absoluteProductUrl = $productUrl;

// Calculate sale price if applicable
$finalPrice = $productPrice;
$originalPrice = $product['price'] ?? $productPrice;
if (isset($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $originalPrice) {
    $finalPrice = $product['sale_price'];
}

// Get review data for schema
$reviewSchema = [];
if (!empty($reviews) && is_array($reviews)) {
    foreach (array_slice($reviews, 0, 5) as $review) {
        $reviewSchema[] = [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name' => htmlspecialchars($review['user_name'] ?? 'Anonymous', ENT_QUOTES, 'UTF-8')
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => (int)($review['rating'] ?? 5),
                'bestRating' => 5
            ],
            'reviewBody' => htmlspecialchars($review['review'] ?? $review['review_text'] ?? '', ENT_QUOTES, 'UTF-8')
        ];
    }
}

ob_start();
// Include comprehensive SEO meta tags with all structured data
include __DIR__ . '/../seo/product-seo.php';
?>

<div class="bg-gray-50 min-h-screen pb-20">
    <!-- Breadcrumb - Desktop Only -->
    <div class="hidden lg:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <nav class="text-xs text-gray-500">
            <div class="flex items-center">
                <a href="<?= \App\Core\View::url('') ?>" class="hover:text-primary">Home</a>
                <span class="mx-2">›</span>
                <a href="<?= \App\Core\View::url('products') ?>" class="hover:text-primary">Products</a>
                <span class="mx-2">›</span>
                <a href="<?= \App\Core\View::url('products/category/' . urlencode($product['category'] ?? '')) ?>" class="hover:text-primary"><?= htmlspecialchars($product['category'] ?? 'Category') ?></a>
                <span class="mx-2">›</span>
                <span class="text-primary font-medium"><?= $productName ?></span>
                                </div>
        </nav>
    </div>

    <!-- Product Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                
                <!-- Product Image/Video -->
                <div class="p-4 lg:p-6">
                    <!-- Main Product Media Slider -->
                    <div id="product-media-slider" class="aspect-square overflow-hidden rounded-2xl bg-gray-50 shadow-sm mb-4 relative touch-pan-y">
                        <div id="product-media-track" class="flex h-full transition-transform duration-300 ease-out" style="transform: translateX(0%);">
                            <?php 
                            // Include all media in slider
                            $allMediaForSlider = [];
                            if (!empty($additionalMedia)) {
                                $allMediaForSlider = $additionalMedia;
                            } else {
                                $allMediaForSlider[] = [
                                    'image_url' => $mainImageUrl,
                                    'is_primary' => 1
                                ];
                            }
                            
                            foreach ($allMediaForSlider as $index => $media): 
                                $mediaUrl = $media['image_url'] ?? '';
                                $isMediaVideo = \App\Helpers\MediaHelper::isVideo($mediaUrl);
                            ?>
                                <div class="product-media-slide flex-shrink-0 w-full h-full">
                                    <?php if ($isMediaVideo): ?>
                                        <video src="<?= htmlspecialchars($mediaUrl) ?>" 
                                               class="w-full h-full object-contain"
                                               controls
                                               preload="metadata"
                                               poster="<?= \App\Core\View::asset('images/products/default.jpg') ?>">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($mediaUrl) ?>" 
                                             alt="<?= $productName ?> - Image <?= $index + 1 ?>" 
                                             id="<?= $index === 0 ? 'main-product-media' : '' ?>"
                                             class="w-full h-full object-contain transition-all duration-300"
                                             onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Slider Dots -->
                        <?php if (count($allMediaForSlider) > 1): ?>
                            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2 z-10">
                                <?php foreach ($allMediaForSlider as $index => $media): ?>
                                    <button type="button" 
                                            class="product-slider-dot w-2 h-2 rounded-full transition-all duration-300 <?= $index === 0 ? 'bg-white w-6' : 'bg-white/50' ?>"
                                            data-slide-index="<?= $index ?>"
                                            aria-label="Go to slide <?= $index + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Thumbnail Gallery -->
                    <?php 
                    // Get all images/videos from product_images table including main image
                    $additionalMedia = [];
                    if (isset($product['id'])) {
                        $productImageModel = new \App\Models\ProductImage();
                        $allMedia = $productImageModel->getByProductId($product['id']);
                        
                        // Process all media URLs to ensure they're properly formatted
                        foreach ($allMedia as $media) {
                            $mediaUrl = $media['image_url'] ?? '';
                            if (!empty($mediaUrl)) {
                                // If already absolute URL (http/https), use as-is
                                if (filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                                    $media['image_url'] = $mediaUrl;
                                } else {
                                    // Convert relative to absolute URL
                                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                                    if (strpos($mediaUrl, '/') === 0) {
                                        $media['image_url'] = $baseUrl . $mediaUrl;
                                    } else {
                                        $media['image_url'] = ASSETS_URL . '/uploads/images/' . $mediaUrl;
                                    }
                                }
                                $additionalMedia[] = $media;
                            }
                        }
                        
                        // If no media found, add main image as thumbnail
                        if (empty($additionalMedia) && !empty($mainImageUrl)) {
                            $additionalMedia[] = [
                                'image_url' => $mainImageUrl,
                                'is_primary' => 1
                            ];
                        }
                    }
                    ?>
                    <?php if (!empty($additionalMedia)): ?>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        <?php foreach (array_slice($additionalMedia, 0, 4) as $index => $media): ?>
                            <?php 
                            $thumbnailUrl = $media['image_url'] ?? '';
                            $isThumbnailVideo = \App\Helpers\MediaHelper::isVideo($thumbnailUrl);
                            $isPrimary = !empty($media['is_primary']);
                            ?>
                            <div class="aspect-square rounded-2xl overflow-hidden border-2 transition-all duration-200 <?= $isPrimary ? 'border-primary shadow-md' : 'border-gray-200 hover:border-gray-300' ?> cursor-pointer product-thumbnail relative" 
                                 data-image-url="<?= htmlspecialchars($thumbnailUrl) ?>"
                                 data-media-type="<?= $isThumbnailVideo ? 'video' : 'image' ?>">
                                <?php if ($isThumbnailVideo): ?>
                                    <video src="<?= htmlspecialchars($thumbnailUrl) ?>" 
                                           class="w-full h-full object-cover transition-all duration-200 hover:scale-110" 
                                           preload="metadata" muted></video>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/30 text-white text-xl">
                                        <i class="fas fa-play"></i>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($thumbnailUrl) ?>" 
                                         alt="<?= $productName ?> - Image <?= $index + 1 ?>" 
                                         class="w-full h-full object-cover transition-all duration-200 hover:scale-110"
                                         onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="p-4 lg:p-6 flex flex-col">
        <!-- Product Title -->
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h1 class="text-2xl lg:text-3xl font-bold text-primary flex-1">
                            <?= $productName ?>
                        </h1>
                        <button type="button"
                                id="share-product-link"
                                class="w-11 h-11 rounded-full border border-neutral-200 text-primary flex items-center justify-center bg-white"
                                aria-label="Copy product link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                            </svg>
                        </button>
                    </div>
                    <input type="hidden" id="product-url-copy" value="<?= $absoluteProductUrl ?>">
                    <input type="hidden" id="product-name-share" value="<?= htmlspecialchars($productName) ?>">
                    <input type="hidden" id="product-image-share" value="<?= htmlspecialchars($mainImageUrl) ?>">
                    <p id="product-share-feedback" class="text-xs text-success hidden mb-3">Link copied!</p>
                    
                    <!-- Category Badge and Stats -->
                    <div class="mb-3 flex items-center gap-3 flex-wrap">
                        <span class="inline-flex items-center px-2 py-1 bg-primary/10 text-primary text-xs font-medium rounded">
                            <?= htmlspecialchars($product['category'] ?? 'Product') ?>
                        </span>
                        
                        <!-- View Count -->
                        <div class="flex items-center gap-1 text-xs text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span id="view-count"><?= number_format($viewCount ?? 0) ?></span>
                            <span class="text-gray-500">watched</span>
                        </div>
                        
                        <!-- Like Count -->
                        <div class="flex items-center gap-1 text-xs text-gray-600">
                            <button type="button" 
                                    id="like-btn" 
                                    class="flex items-center gap-1 hover:text-primary transition-colors"
                                    data-product-id="<?= $product['id'] ?>"
                                    data-liked="<?= ($isLiked ?? false) ? '1' : '0' ?>">
                                <i class="<?= ($isLiked ?? false) ? 'fas' : 'far' ?> fa-heart text-sm <?= ($isLiked ?? false) ? 'text-red-500 fill-red-500' : 'text-gray-600' ?>"></i>
                                <span id="like-count"><?= number_format($likeCount ?? 0) ?></span>
                            </button>
                        </div>
                    </div>
                        
                    <!-- Price -->
                    <div class="mb-4">
                        <?php 
                        // Use pricingHelper for consistent sale calculation
                        $pricing = $pricingHelper($product);
                        $hasSale = $pricing['hasSale'];
                        $currentPrice = $pricing['current'];
                        $originalPrice = $pricing['original'];
                        $discountPercent = $pricing['discountPercent'];
                        ?>
                        
                        <?php if (!$isScheduled && $hasSale && $currentPrice < $originalPrice): ?>
                            <div class="flex items-baseline gap-2 flex-wrap">
                                <span class="text-xl font-bold text-primary"><?= CurrencyHelper::format($currentPrice) ?></span>
                                <span class="text-sm text-gray-500 line-through"><?= CurrencyHelper::format($originalPrice) ?></span>
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded">
                                    <?= $discountPercent ?>% OFF
                                </span>
                            </div>
                        <?php elseif (!$isScheduled): ?>
                            <span class="text-xl font-bold text-primary"><?= CurrencyHelper::format($originalPrice) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Scheduled Date Display -->
                    <?php if ($isScheduled && !empty($product['scheduled_date']) && $launchTimestamp): ?>
                        <div class="mb-4 p-4 bg-primary/10 border border-primary/20 rounded-2xl">
                            <div class="flex items-center justify-between gap-4 flex-wrap">
                                <div>
                                    <p class="text-xs font-semibold text-primary uppercase tracking-[0.2em]">Launches in</p>
                                    <p class="text-lg font-bold text-primary" data-launch-countdown="<?= $launchTimestamp ?>" data-countdown-format="full">--:--:--</p>
                                </div>
                                <?php if (!empty($mysteryPrice)): ?>
                                <div class="text-right">
                                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Mystery launch price</p>
                                    <p class="text-xl font-semibold text-primary mystery-price-animated" 
                                       data-min="<?= $mysteryPriceRange[0] ?? $mysteryPrice ?>" 
                                       data-max="<?= $mysteryPriceRange[1] ?? $mysteryPrice ?>">
                                        <?= CurrencyHelper::format($mysteryPrice) ?>
                                    </p>
                                    <?php if (!empty($mysteryPriceRange)): ?>
                                        <p class="text-[10px] text-neutral-500">Between <?= CurrencyHelper::format($mysteryPriceRange[0]) ?> – <?= CurrencyHelper::format($mysteryPriceRange[1]) ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3 flex items-center gap-2 text-xs text-primary">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Launches on <?= date('F j, Y g:i A', $launchTimestamp) ?></span>
                            </div>
                            <?php if (!empty($product['scheduled_message'])): ?>
                                <p class="text-xs text-neutral-600 mt-2"><?= htmlspecialchars($product['scheduled_message']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Stock Status -->
                    <?php if (!$isScheduled): ?>
                    <div class="mb-4">
                        <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0): ?>
                            <div class="flex items-center text-green-600 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                In Stock (<?= $product['stock_quantity'] ?> available)
                                </div>
                        <?php else: ?>
                            <div class="flex items-center text-red-600 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                Out of Stock
                            </div>
                        <?php endif; ?>
                                </div>
                    <?php endif; ?>

                    <!-- Product Type & Digital Badge -->
                    <?php 
                    $isDigital = !empty($product['is_digital']);
                    $productType = $product['product_type_main'] ?? $product['product_type'] ?? null;
                    $showProductTypeBadge = $productType && strtolower(trim($productType)) !== $normalizedCategory;
                    $colors = !empty($product['colors']) ? (is_string($product['colors']) ? json_decode($product['colors'], true) : $product['colors']) : [];
                    ?>
                    <?php if ($isDigital || $showProductTypeBadge): ?>
                        <div class="mb-4 flex items-center gap-2 flex-wrap">
                            <?php if ($isDigital): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-download mr-1"></i>Digital Product - No Shipping Required
                                </span>
                            <?php endif; ?>
                            <?php if ($showProductTypeBadge): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    <?php
                                    switch(strtolower($productType)) {
                                        case 'digital': echo 'bg-purple-100 text-purple-800'; break;
                                        case 'accessories': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'supplement': echo 'bg-green-100 text-green-800'; break;
                                        case 'vitamins': echo 'bg-yellow-100 text-yellow-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800'; break;
                                    }
                                    ?>
                                ">
                                    <i class="fas fa-<?= $isDigital ? 'download' : 'box' ?> mr-1"></i>
                                    <?= htmlspecialchars(ucfirst($productType)) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Color Options - Selectable -->
                    <?php 
                    $colors = !empty($product['colors']) ? (is_string($product['colors']) ? json_decode($product['colors'], true) : $product['colors']) : [];
                    if (!empty($colors) && is_array($colors) && count($colors) > 0): 
                    ?>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Color: <span class="text-red-500" id="selected-color-display"></span>
                            </label>
                            <div class="flex items-center gap-2 flex-wrap">
                                <?php foreach ($colors as $index => $color): ?>
                                    <?php
                                    $colorName = trim($color);
                                    $isHex = preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $colorName);
                                    $colorId = 'color-' . $index;
                                    ?>
                                    <button type="button" 
                                            class="color-option flex items-center gap-1 px-3 py-2 rounded-2xl border-2 border-gray-300 bg-white hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer <?= $index === 0 ? 'border-primary bg-primary/10' : '' ?>"
                                            data-color="<?= htmlspecialchars($colorName) ?>"
                                            data-color-id="<?= $colorId ?>"
                                            onclick="selectColor('<?= htmlspecialchars($colorName) ?>', '<?= $colorId ?>')">
                                        <?php if ($isHex): ?>
                                            <span class="w-5 h-5 rounded-full border-2 border-gray-300 shadow-sm" style="background-color: <?= htmlspecialchars($colorName) ?>"></span>
                                        <?php else: ?>
                                            <span class="w-5 h-5 rounded-full border-2 border-gray-300 bg-gradient-to-br from-gray-200 to-gray-300"></span>
                                        <?php endif; ?>
                                        <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($colorName) ?></span>
                                    </button>
                                <?php endforeach; ?>
                                    </div>
                            <input type="hidden" id="selected-color" name="selected_color" value="<?= htmlspecialchars(trim($colors[0])) ?>">
                        </div>
                    <?php endif; ?>

                    <!-- Size Options - Selectable -->
                    <?php 
                    $sizes = !empty($product['size_available']) ? (is_string($product['size_available']) ? json_decode($product['size_available'], true) : $product['size_available']) : [];
                    // Also check weight field for sizes (sometimes sizes are stored in weight field like "XXL, LG, XXXL")
                    if (empty($sizes) && !empty($product['weight'])) {
                        $weightValue = trim($product['weight']);
                        // Check if weight contains comma-separated sizes
                        if (strpos($weightValue, ',') !== false && preg_match('/\b(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL|Small|Medium|Large|Extra Large)\b/i', $weightValue)) {
                            $sizes = array_map('trim', explode(',', $weightValue));
                        }
                    }
                    if (!empty($sizes) && is_array($sizes) && count($sizes) > 0): 
                    ?>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Size: <span class="text-red-500" id="selected-size-display"></span>
                            </label>
                            <div class="flex items-center gap-2 flex-wrap">
                                <?php foreach ($sizes as $index => $size): ?>
                                    <?php
                                    $sizeName = trim($size);
                                    $sizeId = 'size-' . $index;
                                    ?>
                                    <button type="button" 
                                            class="size-option px-4 py-2 rounded-2xl border-2 border-gray-300 bg-white hover:border-primary hover:bg-primary/5 transition-all duration-200 cursor-pointer font-medium text-sm <?= $index === 0 ? 'border-primary bg-primary/10 text-primary' : 'text-gray-700' ?>"
                                            data-size="<?= htmlspecialchars($sizeName) ?>"
                                            data-size-id="<?= $sizeId ?>"
                                            onclick="selectSize('<?= htmlspecialchars($sizeName) ?>', '<?= $sizeId ?>')">
                                        <?= htmlspecialchars($sizeName) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="selected-size" name="selected_size" value="<?= htmlspecialchars(trim($sizes[0])) ?>">
                        </div>
                    <?php endif; ?>

                    <!-- Product Attributes -->
                    <div class="mb-4 space-y-2">
                        <?php 
                        // Only show weight if it's not being used as sizes
                        $weightValue = isset($product['weight']) ? trim($product['weight']) : '';
                        $isWeightUsedAsSize = false;
                        if (!empty($sizes) && !empty($weightValue) && strpos($weightValue, ',') !== false) {
                            $isWeightUsedAsSize = preg_match('/\b(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL|Small|Medium|Large|Extra Large)\b/i', $weightValue);
                        }
                        if (!empty($weightValue) && !$isWeightUsedAsSize): 
                        ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Weight</span>
                                <span class="font-medium"><?= htmlspecialchars($weightValue) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['serving']) && !empty($product['serving'])): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Serving Size</span>
                                <span class="font-medium"><?= htmlspecialchars($product['serving']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['flavor']) && !empty($product['flavor'])): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Flavor</span>
                                <span class="font-medium"><?= htmlspecialchars($product['flavor']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['material']) && !empty($product['material'])): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Material</span>
                                <span class="font-medium"><?= htmlspecialchars($product['material']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['optimal_weight']) && !empty($product['optimal_weight'])): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Optimal Weight</span>
                                <span class="font-medium"><?= htmlspecialchars($product['optimal_weight']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['serving_size']) && !empty($product['serving_size'])): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Serving Size</span>
                                <span class="font-medium"><?= htmlspecialchars($product['serving_size']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['capsule']) && $product['capsule']): ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Format</span>
                                <span class="font-medium">Capsule/Tablet</span>
                            </div>
                        <?php endif; ?>
                            </div>
                            
                    <!-- Additional Product Information -->
                    <?php if (isset($product['ingredients']) && !empty($product['ingredients'])): ?>
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Ingredients</h3>
                            <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($product['ingredients'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                            <!-- Quantity Selector -->
                    <div class="mb-4">
                            <div class="flex items-center space-x-3">
                            <label class="text-sm font-medium text-gray-700">Quantity:</label>
                                <div class="flex items-center border border-gray-300 rounded-2xl bg-white shadow-sm">
                                    <button type="button" id="decrease-qty" class="px-3 py-2 text-gray-600 hover:text-primary hover:bg-gray-50 transition-colors duration-200 rounded-l-lg">−</button>
                                <input type="number" id="quantity" value="1" min="1" max="<?= min(3, $product['stock_quantity'] ?? 1) ?>" class="w-16 px-2 py-2 text-center border-0 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" style="-moz-appearance: textfield;">
                                <style>
                                    #quantity::-webkit-outer-spin-button,
                                    #quantity::-webkit-inner-spin-button {
                                        -webkit-appearance: none;
                                        margin: 0;
                                    }
                                </style>
                                    <button type="button" id="increase-qty" class="px-3 py-2 text-gray-600 hover:text-primary hover:bg-gray-50 transition-colors duration-200 rounded-r-lg">+</button>
                                </div>
                                <span class="text-xs text-gray-500">Max: <?= min(3, $product['stock_quantity'] ?? 1) ?> per order</span>
                            </div>
                            </div>
                            
                            <!-- Desktop Sticky Action Buttons & QR Code -->
                            <div class="hidden lg:block mt-6 space-y-4">
                                <!-- Action Buttons -->
                                <div class="sticky top-4 z-10 space-y-3">
                                    <?php if ($isScheduled): ?>
                                        <button type="button" class="w-full bg-primary text-white px-6 py-3 rounded-2xl font-semibold text-sm cursor-not-allowed shadow-lg">
                                            <div class="flex items-center justify-center gap-2">
                                                <i class="fas fa-clock"></i>
                                                <span>
                                                    <?php if ($remainingDays > 0): ?>
                                                        Launching in <?= $remainingDays ?> <?= $remainingDays == 1 ? 'day' : 'days' ?>
                                                    <?php else: ?>
                                                        Coming Soon
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </button>
                                    <?php elseif ($isAvailable): ?>
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="flex-1 bg-primary text-white px-6 py-3 rounded-2xl font-semibold text-sm add-to-cart hover:bg-primary-dark transition-colors duration-200 shadow-lg hover:shadow-xl" 
                                                    data-product-id="<?= $product['id'] ?? '' ?>" 
                                                    data-product-name="<?= htmlspecialchars($product['product_name'] ?? 'Product') ?>" 
                                                    data-product-price="<?= isset($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'] ?? '0' ?>">
                                                <span class="btn-text">Add to Cart</span>
                                                <span class="btn-loading hidden">Adding...</span>
                                            </button>
                                            <button type="button" class="flex-1 bg-accent text-white px-6 py-3 rounded-2xl font-semibold text-sm direct-checkout hover:bg-accent-dark transition-colors duration-200 border border-accent shadow-lg hover:shadow-xl" 
                                                    data-product-id="<?= $product['id'] ?? '' ?>" 
                                                    data-product-name="<?= htmlspecialchars($product['product_name'] ?? 'Product') ?>" 
                                                    data-product-price="<?= isset($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'] ?? '0' ?>">
                                                <span class="btn-text">Order Now</span>
                                                <span class="btn-loading hidden">Processing...</span>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" disabled class="w-full bg-gray-400 text-white px-6 py-3 rounded-2xl font-medium text-sm cursor-not-allowed shadow-lg">
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Suggested Products Section -->
                                    <?php if (!empty($lowPriceProducts) && count($lowPriceProducts) > 0): ?>
                                    <div class="rounded-2xl p-4 bg-white border border-gray-200">
                                        <h3 class="text-sm font-semibold text-gray-900 mb-3">You May Also Like</h3>
                                        <div class="grid grid-cols-2 gap-3">
                                            <?php foreach (array_slice($lowPriceProducts, 0, 2) as $suggested): ?>
                                                <?php
                                                $suggestedPrice = isset($suggested['sale_price']) && $suggested['sale_price'] > 0 ? $suggested['sale_price'] : $suggested['price'] ?? 0;
                                                $suggestedImageUrl = $suggested['image_url'] ?? ASSETS_URL . '/images/products/default.jpg';
                                                $suggestedName = htmlspecialchars($suggested['product_name'] ?? 'Product');
                                                $suggestedSlug = $suggested['slug'] ?? $suggested['id'] ?? '';
                                                ?>
                                                <div class="bg-white rounded-2xl border border-gray-200 p-3 hover:shadow-md transition-shadow relative group">
                                                    <div class="flex items-start gap-2">
                                                        <div class="w-16 h-16 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                                                            <img src="<?= htmlspecialchars($suggestedImageUrl) ?>" 
                                                                 alt="<?= $suggestedName ?>" 
                                                                 class="w-full h-full object-cover"
                                                                 onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'; this.onerror=null;">
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <h4 class="text-xs font-medium text-gray-900 truncate mb-1"><?= $suggestedName ?></h4>
                                                            <p class="text-sm font-semibold text-primary">रु<?= number_format($suggestedPrice, 2) ?></p>
                                                        </div>
                                                        <button type="button" 
                                                                onclick="addSuggestedToCart(<?= $suggested['id'] ?>)"
                                                                class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center hover:bg-primary-dark transition-colors"
                                                                title="Add to cart">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
</svg>

                                            </div>
                                            <p class="text-sm text-foreground leading-5">
                                                <span class="font-semibold">Safe and Secure Payments.</span>
                                               <br> Easy returns. 100% Authentic products.
                                            </p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                                </div>
                    </div>
                </div>
                

        <!-- Seller Info Section -->
        <?php if (!empty($seller)): ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden mt-4">
                <div class="p-4">
                    <h2 class="text-base font-semibold text-gray-900 mb-3">Store Information</h2>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 p-3 bg-neutral-50 rounded-2xl">
                        <div class="flex items-center gap-3 flex-1 w-full md:w-auto">
                            <div class="w-12 h-12 rounded-lg overflow-hidden border-2 border-neutral-200 flex-shrink-0">
                                <?php 
                                $storeImage = !empty($seller['logo_url']) ? $seller['logo_url'] : 
                                             \App\Core\View::asset('images/graphics/store.png');
                                ?>
                                <img src="<?= htmlspecialchars($storeImage) ?>" 
                                     alt="<?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.src='<?= \App\Core\View::asset('images/graphics/store.png') ?>'; this.onerror=null;">
                            </div>
                            <div class="flex flex-col flex-1 min-w-0">
                                <span class="text-sm font-semibold text-gray-900 truncate">
                                    <?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>
                                </span>
                                <?php if (!empty($sellerStats)): ?>
                                    <div class="flex items-center gap-4 mt-1 text-xs text-neutral-600">
                                        <?php if ($sellerStats['positive_seller_percent'] > 0): ?>
                                            <span class="flex items-center gap-1">
                                                <span class="text-success font-medium"><?= $sellerStats['positive_seller_percent'] ?>%</span>
                                                <span>Positive</span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($sellerStats['ship_on_time_percent'] > 0): ?>
                                            <span class="flex items-center gap-1">
                                                <span class="text-success font-medium"><?= $sellerStats['ship_on_time_percent'] ?>%</span>
                                                <span>Ship on Time</span>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($sellerStats['total_reviews'] > 0): ?>
                                            <span class="flex items-center gap-1">
                                                <span class="text-primary font-medium">
                                                    <?= $sellerStats['positive_reviews'] + $sellerStats['good_reviews'] ?>
                                                </span>
                                                <span>Good</span>
                                                <?php if ($sellerStats['average_reviews'] > 0): ?>
                                                    <span class="mx-1">•</span>
                                                    <span class="text-warning font-medium"><?= $sellerStats['average_reviews'] ?></span>
                                                    <span>Avg</span>
                                                <?php endif; ?>
                                                <?php if ($sellerStats['negative_reviews'] > 0): ?>
                                                    <span class="mx-1">•</span>
                                                    <span class="text-error font-medium"><?= $sellerStats['negative_reviews'] ?></span>
                                                    <span>Poor</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="<?= \App\Core\View::url('seller/' . urlencode($seller['company_name'] ?? $seller['name'])) ?>" 
                           class="btn btn-outline flex-shrink-0 w-full md:w-auto">
                            Visit Store
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Product Description -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mt-4">
            <div class="p-4">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Description</h2>
                <div class="prose prose-sm max-w-none text-gray-700 markdown-content">
                <?php 
                $fullDescription = $product['description'] ?? '';
                $shortDesc = $product['short_description'] ?? '';
                
                if (!empty($fullDescription)) {
                    // Enhanced markdown detection - check for common markdown patterns
                    $markdownPatterns = [
                        '/^#{1,6}\s/m',           // Headers
                        '/^\*\s/m',               // Unordered lists
                        '/^\d+\.\s/m',            // Ordered lists
                        '/^>\s/m',                // Blockquotes
                        '/```/',                  // Code blocks
                        '/`[^`]+`/',              // Inline code
                        '/^\-\s/m',               // Dashed lists
                        '/^\+\s/m',               // Plus lists
                        '/\[.*?\]\(.*?\)/',       // Links
                        '/^\|.*\|/m',             // Tables
                        '/\*\*.*?\*\*/',          // Bold
                        '/\*.*?\*/',              // Italic
                        '/__.*?__/',              // Bold underscore
                        '/_.*?_/',                // Italic underscore
                        '/~~.*?~~/',              // Strikethrough
                    ];
                    
                    $isMarkdown = false;
                    foreach ($markdownPatterns as $pattern) {
                        if (preg_match($pattern, $fullDescription)) {
                            $isMarkdown = true;
                            break;
                        }
                    }
                    
                    if ($isMarkdown) {
                        try {
                            if (class_exists('League\CommonMark\CommonMarkConverter')) {
                                // Allow inline HTML within Markdown for richer customization
                                $converter = new CommonMarkConverter([
                                    'html_input' => 'allow',
                                    'allow_unsafe_links' => false,
                                ]);
                                $html = $converter->convert($fullDescription);
                                
                                // Enhance images with responsive styling and CDN support
                                $html = preg_replace_callback(
                                    '/<img([^>]*)src=["\']([^"\']*)["\']([^>]*)>/i',
                                    function($matches) {
                                        $attributes = $matches[1] . $matches[3];
                                        $src = $matches[2];
                                        
                                        // Add responsive classes and loading attributes
                                        $newAttributes = ' class="max-w-full h-auto rounded-2xl shadow-sm" loading="lazy"';
                                        
                                        // If it's a CDN URL, add additional styling
                                        if (preg_match('/^https?:\/\/(cdn\.|images\.|img\.)/i', $src)) {
                                            $newAttributes .= ' style="border: 1px solid #e5e7eb;"';
                                        }
                                        
                                        return '<img' . $attributes . ' src="' . htmlspecialchars($src) . '"' . $newAttributes . '>';
                                    },
                                    $html
                                );
                                
                                echo $html;
                            } else {
                                // Fallback: render HTML with an expanded whitelist of tags
                                $allowedTags = '<p><br><br/><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><img><a><table><thead><tbody><tr><th><td><hr><span><div><figure><figcaption><sup><sub><u>';
                                echo strip_tags($fullDescription, $allowedTags);
                            }
                        } catch (Exception $e) {
                            $allowedTags = '<p><br><br/><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><img><a><table><thead><tbody><tr><th><td><hr><span><div><figure><figcaption><sup><sub><u>';
                            echo strip_tags($fullDescription, $allowedTags);
                        }
                    } else {
                        $allowedTags = '<p><br><br/><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><img><a><table><thead><tbody><tr><th><td><hr><span><div><figure><figcaption><sup><sub><u>';
                        echo strip_tags($fullDescription, $allowedTags);
                    }
                } elseif (!empty($shortDesc)) {
                    echo htmlspecialchars($shortDesc);
                } else {
                        echo '<p class="text-gray-500 text-sm">No description provided.</p>';
                }
                ?>
            </div>
        </div>

     

        <!-- Reviews -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mt-4">
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Reviews</h2>
                    <?php if ($isLoggedIn && !$userReview): ?>
                        <button id="open-add-review-drawer" class="inline-flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-2xl text-sm font-medium hover:bg-accent-dark transition-colors shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Review
                        </button>
                    <?php endif; ?>
                </div>
                <?php 
                // Ensure reviews is set and is an array
                if (!isset($reviews) || !is_array($reviews)) {
                    $reviews = [];
                }
                $hasReviews = count($reviews) > 0;
                $totalReviews = isset($reviewCount) ? (int)$reviewCount : ($hasReviews ? count($reviews) : 0);
                if ($hasReviews): ?>
                    <div class="flex items-center gap-2 mb-4">
                        <?php if (isset($averageRating)): ?>
                            <div class="flex items-center text-yellow-500">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="w-5 h-5 <?= $i <= round($averageRating) ? 'fill-current' : 'fill-none' ?> stroke-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm font-semibold text-gray-700"><?= number_format((float)$averageRating, 1) ?> / 5</span>
                        <?php endif; ?>
                        <span class="text-sm text-gray-500">(<?= (int)$totalReviews ?> reviews)</span>
                    </div>
                    <div class="space-y-4 reviews-list">
                        <?php foreach ($reviews as $rev): ?>
                            <?php 
                            // Render each review using partial, if available
                            $partialPath = __DIR__ . '/partials/_review_item.php';
                            if (file_exists($partialPath)) {
                                include $partialPath;
                            } else {
                                // Fallback simple render
                                $rRating = (int)($rev['rating'] ?? 0);
                                $rText = htmlspecialchars($rev['review'] ?? $rev['review_text'] ?? '');
                                $rUser = htmlspecialchars($rev['user_name'] ?? $rev['user'] ?? 'Anonymous');
                                $rDate = !empty($rev['created_at']) ? date('M j, Y', strtotime($rev['created_at'])) : '';
                                ?>
                                <div class="bg-gray-50 rounded-2xl p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-yellow-500">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?= $i <= $rRating ? 'fas fa-star' : 'far fa-star' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php if ($rDate): ?><span class="text-xs text-gray-500"><?= $rDate ?></span><?php endif; ?>
                                    </div>
                                    <p class="mt-2 text-gray-800"><?= $rText ?></p>
                                    <p class="text-xs text-gray-500 mt-1">By <?= $rUser ?></p>
                                </div>
                            <?php } ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-600">No reviews yet. Be the first to review this product.</p>
                <?php endif; ?>
            </div>
        </div>
                    </div>
                                    </div>
                                    </div>
<!-- Fixed Bottom Action Buttons -->
<div class="fixed bottom-0 inset-x-0 bg-white rounded-t-3xl border-t border-gray-200 shadow-xl p-4 z-50 lg:hidden safe-bottom">
    <div class="flex gap-3">
        
        <?php if ($isScheduled && $launchTimestamp): ?>
            <button type="button" class="flex-1 bg-primary text-white px-4 py-3 rounded-2xl font-semibold text-sm cursor-not-allowed">
                <div class="flex items-center justify-between w-full">
                    <div>
                        <span class="text-xs text-white/70 block mb-1">Launches in</span>
                        <span class="text-base font-semibold" data-launch-countdown="<?= $launchTimestamp ?>" data-countdown-format="compact">--:--</span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-white/70 block mb-1">Mystery Price</span>
                        <span class="text-sm font-semibold mystery-price-animated" 
                              data-min="<?= $mysteryPriceRange[0] ?? ($mysteryPrice ?? $productPrice ?? ($product['price'] ?? 0)) ?>" 
                              data-max="<?= $mysteryPriceRange[1] ?? ($mysteryPrice ?? $productPrice ?? ($product['price'] ?? 0)) ?>">
                            <?= CurrencyHelper::format($mysteryPrice ?? $productPrice ?? ($product['price'] ?? 0)) ?>
                        </span>
                    </div>
                </div>
            </button>
        <?php elseif ($isAvailable): ?>
            <button type="button" class="flex-1 bg-primary  text-white px-4 py-3 rounded-2xl font-semibold text-sm add-to-cart hover:bg-primary-dark transition-colors duration-200 shadow-lg hover:shadow-xl" 
                    data-product-id="<?= $product['id'] ?? '' ?>" 
                    data-product-name="<?= htmlspecialchars($product['product_name'] ?? 'Product') ?>" 
                    data-product-price="<?= isset($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'] ?? '0' ?>">
                <span class="btn-text">Add to Cart</span>
                <span class="btn-loading hidden">Adding...</span>
            </button>
            <button type="button" class="flex-1 bg-accent text-white px-4 py-3 rounded-2xl font-semibold text-sm direct-checkout hover:bg-accent-dark transition-colors duration-200 border border-accent" 
                    data-product-id="<?= $product['id'] ?? '' ?>" 
                    data-product-name="<?= htmlspecialchars($product['product_name'] ?? 'Product') ?>" 
                    data-product-price="<?= isset($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'] ?? '0' ?>">
                <span class="btn-text">Order Now</span>
                <span class="btn-loading hidden">Processing...</span>
            </button>
            <?php else: ?>
            <button type="button" disabled class="flex-1 bg-gray-400 text-white px-4 py-3 rounded-2xl font-medium text-sm cursor-not-allowed">
                Out of Stock
            </button>
            <?php endif; ?>
        </div>
    </div>

<!-- Mobile Suggested Products Section -->
<?php if (!empty($lowPriceProducts) && count($lowPriceProducts) > 0): ?>
<div class="lg:hidden mt-4 mb-24 px-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">You May Also Like</h3>
        <div class="grid grid-cols-2 gap-3">
            <?php foreach (array_slice($lowPriceProducts, 0, 2) as $suggested): ?>
                <?php
                $suggestedPrice = isset($suggested['sale_price']) && $suggested['sale_price'] > 0 ? $suggested['sale_price'] : $suggested['price'] ?? 0;
                $suggestedImageUrl = $suggested['image_url'] ?? ASSETS_URL . '/images/products/default.jpg';
                $suggestedName = htmlspecialchars($suggested['product_name'] ?? 'Product');
                $suggestedSlug = $suggested['slug'] ?? $suggested['id'] ?? '';
                ?>
                <div class="bg-white rounded-2xl border border-gray-200 p-3 hover:shadow-md transition-shadow relative group">
                    <div class="flex items-start gap-2">
                        <div class="w-16 h-16 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                            <img src="<?= htmlspecialchars($suggestedImageUrl) ?>" 
                                 alt="<?= $suggestedName ?>" 
                                 class="w-full h-full object-cover"
                                 onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'; this.onerror=null;">
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs font-medium text-gray-900 truncate mb-1"><?= $suggestedName ?></h4>
                            <p class="text-sm font-semibold text-primary">रु<?= number_format($suggestedPrice, 2) ?></p>
                        </div>
                        <button type="button" 
                                onclick="addSuggestedToCart(<?= $suggested['id'] ?>)"
                                class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center hover:bg-primary-dark transition-colors"
                                title="Add to cart">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Review Drawer Form -->
<?php if ($isLoggedIn && !$userReview && $productId): ?>
<div id="review-drawer" class="fixed inset-0 z-50 hidden">
    <div id="review-overlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div id="review-drawer-content" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl transform translate-y-full transition-transform duration-300 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">Write Your Review</h3>
                <button id="close-review-drawer" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="add-review-form" class="space-y-5">
                <input type="hidden" name="product_id" value="<?= $productId ?>">
                
                <!-- Rating Selection -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Your Rating</label>
                    <div class="flex items-center gap-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="rating" value="<?= $i ?>" class="sr-only rating-input" <?= $i === 5 ? 'checked' : '' ?>>
                                <svg class="w-8 h-8 transition-colors duration-200 text-gray-300 hover:text-yellow-400 rating-star" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Review Text -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Your Review</label>
                    <textarea name="review_text" rows="5" 
                              class="w-full resize-none rounded-2xl border border-gray-200 px-4 py-3 text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary placeholder:text-gray-400" 
                              placeholder="Share your experience with this product..."
                              required></textarea>
                    <p class="text-xs text-gray-500 mt-1">Minimum 10 characters</p>
                </div>
                
                <!-- Submit Buttons -->
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-white py-3 rounded-2xl font-semibold hover:bg-primary-dark transition">Submit Review</button>
                    <button type="button" id="cancel-review-drawer" class="flex-1 bg-gray-100 text-gray-800 hover:bg-gray-200 rounded-2xl font-semibold">Cancel</button>
                </div>
                
                <p id="add-review-message" class="text-sm mt-2 hidden"></p>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Power Suggestions -->
<?php if (!empty($relatedProducts) || !empty($internalProductAd)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs uppercase tracking-widest text-primary font-semibold">Because you viewed</p>
                <h3 class="text-2xl font-bold text-gray-900">You might also love</h3>
            </div>
            <a href="<?= \App\Core\View::url('products?sort=popular') ?>"
               class="inline-flex items-center text-primary text-sm font-semibold hover:underline">
                Explore more
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        <div class="relative">
            <div class="overflow-x-auto pb-3 -mx-1" id="power-suggestions">
                <div class="flex gap-3 px-1 snap-x snap-mandatory">
                    <?php $originalProductContext = $product; ?>
                    <?php 
                    // Mix 3 related products + 1 ad
                    $suggestionProducts = [];
                    $relatedCount = 0;
                    foreach ($relatedProducts as $related) {
                        if ($relatedCount < 3) {
                            $suggestionProducts[] = $related;
                            $relatedCount++;
                        }
                    }
                    
                    // Insert ad after 2nd product (or at end if less than 2)
                    if (!empty($internalProductAd)) {
                        $insertPosition = min(2, count($suggestionProducts));
                        array_splice($suggestionProducts, $insertPosition, 0, [$internalProductAd]);
                    }
                    ?>
                    <?php foreach ($suggestionProducts as $suggestion): ?>
                        <div class="min-w-[220px] max-w-[240px] snap-start">
                            <?php
                            $cardOptions = [
                                'theme' => 'light',
                                'showCta' => false,
                                'cardClass' => 'w-full h-full border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-200'
                            ];
                            
                            // Add AD badge for sponsored products
                            if (!empty($suggestion['is_sponsored']) || !empty($suggestion['ad_id'])) {
                                $cardOptions['topRightBadge'] = ['label' => 'AD'];
                            }
                            
                            $product = $suggestion;
                            include dirname(__DIR__) . '/home/sections/shared/product-card.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                    <?php $product = $originalProductContext; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
  // Launch countdown for scheduled products
  (function(){
    const targets = Array.from(document.querySelectorAll('[data-launch-countdown]')).map(el => {
      const targetTs = parseInt(el.dataset.launchCountdown, 10);
      if (!targetTs) return null;
      return {
        el,
        target: targetTs * 1000,
        format: el.dataset.countdownFormat || 'full'
      };
    }).filter(Boolean);
    
    if (!targets.length) return;
    
    const pad = (num) => num.toString().padStart(2, '0');
    
    function formatCountdown(diff, format) {
      if (diff <= 0) {
        return format === 'compact' ? 'Live' : 'Launching now';
      }
      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((diff % (1000 * 60)) / 1000);
      
      if (format === 'compact') {
        return days > 0 ? `${days}d ${pad(hours)}h` : `${pad(hours)}:${pad(minutes)}`;
      }
      
      const dayText = days > 0 ? `${days}d ` : '';
      return `${dayText}${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
    }
    
    function renderCountdown() {
      const now = Date.now();
      targets.forEach(({ el, target, format }) => {
        el.textContent = formatCountdown(target - now, format);
      });
    }
    
    renderCountdown();
    setInterval(renderCountdown, 1000);
  })();

  // Review Drawer Functionality
  const openDrawerBtn = document.getElementById('open-add-review-drawer');
  const reviewDrawer = document.getElementById('review-drawer');
  const drawerContent = document.getElementById('review-drawer-content');
  const closeDrawerBtn = document.getElementById('close-review-drawer');
  const cancelDrawerBtn = document.getElementById('cancel-review-drawer');
  const overlay = document.getElementById('review-overlay');
  const reviewForm = document.getElementById('add-review-form');
  const reviewMessage = document.getElementById('add-review-message');
  
  // Rating stars functionality
  const ratingInputs = document.querySelectorAll('.rating-input');
  const ratingStars = document.querySelectorAll('.rating-star');
  
  ratingInputs.forEach((input, index) => {
    input.addEventListener('change', function() {
      updateStars(parseInt(this.value));
    });
  });
  
  function updateStars(rating) {
    ratingStars.forEach((star, index) => {
      if (index < rating) {
        star.classList.add('text-yellow-400');
        star.classList.remove('text-gray-300');
        star.setAttribute('fill', 'currentColor');
      } else {
        star.classList.remove('text-yellow-400');
        star.classList.add('text-gray-300');
        star.setAttribute('fill', 'none');
      }
    });
  }
  
  // Initialize stars
  if (ratingInputs.length > 0) {
    const checkedInput = document.querySelector('.rating-input:checked');
    if (checkedInput) {
      updateStars(parseInt(checkedInput.value));
    }
  }
  
  function openReviewDrawer() {
    if (reviewDrawer && drawerContent) {
      reviewDrawer.classList.remove('hidden');
      setTimeout(() => {
        drawerContent.classList.remove('translate-y-full');
      }, 10);
      document.body.style.overflow = 'hidden';
    }
  }
  
  function closeReviewDrawer() {
    if (drawerContent && reviewDrawer) {
      drawerContent.classList.add('translate-y-full');
      setTimeout(() => {
        reviewDrawer.classList.add('hidden');
        document.body.style.overflow = 'auto';
      }, 300);
    }
  }
  
  if (openDrawerBtn) {
    openDrawerBtn.addEventListener('click', openReviewDrawer);
  }
  
  if (closeDrawerBtn) {
    closeDrawerBtn.addEventListener('click', closeReviewDrawer);
  }
  
  if (cancelDrawerBtn) {
    cancelDrawerBtn.addEventListener('click', closeReviewDrawer);
  }
  
  if (overlay) {
    overlay.addEventListener('click', closeReviewDrawer);
  }
  
  if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      if (reviewMessage) {
        reviewMessage.textContent = 'Submitting your review...';
        reviewMessage.classList.remove('hidden', 'text-red-600', 'text-green-600');
        reviewMessage.classList.add('text-gray-600');
      }
      
      const formData = new FormData(reviewForm);
      
      fetch('<?= \App\Core\View::url('reviews/submitAjax') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(res => res.json())
      .then(data => {
        if (reviewMessage) {
          if (data.success) {
            reviewMessage.textContent = data.message || 'Review submitted successfully! Reloading...';
            reviewMessage.classList.remove('text-gray-600');
            reviewMessage.classList.add('text-green-600');
            
            // Reload page after 2 seconds
            setTimeout(() => {
              window.location.reload();
            }, 2000);
          } else {
            reviewMessage.textContent = data.message || 'Unable to submit review. Please try again.';
            reviewMessage.classList.remove('text-gray-600');
            reviewMessage.classList.add('text-red-600');
          }
        }
      })
      .catch(error => {
        console.error('Review submission error:', error);
        if (reviewMessage) {
          reviewMessage.textContent = 'Network error. Please try again.';
          reviewMessage.classList.remove('text-gray-600');
          reviewMessage.classList.add('text-red-600');
        }
      });
    });
  }
  
  // Delete Review Functionality (handles all delete buttons)
  const deleteReviewButtons = document.querySelectorAll('.delete-review-btn');
  deleteReviewButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const reviewId = this.getAttribute('data-review-id');
      
      if (!reviewId) {
        console.error('Review ID not found');
        return;
      }
      
      // Confirm deletion
      if (!confirm('Are you sure you want to delete your review? This action cannot be undone.')) {
        return;
      }
      
      // Disable button during deletion
      this.disabled = true;
      this.style.opacity = '0.5';
      
      console.log('Deleting review:', reviewId);
      
      // Use POST with review_id in body (matches ReviewController)
      const formData = new FormData();
      formData.append('review_id', reviewId);
      
      fetch('<?= \App\Core\View::url('reviews/delete') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(res => {
        console.log('Response status:', res.status);
        return res.json();
      })
      .then(data => {
        console.log('Delete response:', data);
        if (data.success) {
          // Show success notification
          if (typeof notyf !== 'undefined') {
            notyf.success('Review deleted successfully!');
          } else {
            alert('Review deleted successfully!');
          }
          // Reload page after 1 second
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          // Show error notification
          if (typeof notyf !== 'undefined') {
            notyf.error(data.message || 'Failed to delete review. Please try again.');
          } else {
            alert(data.message || 'Failed to delete review. Please try again.');
          }
          // Re-enable button
          this.disabled = false;
          this.style.opacity = '1';
        }
      })
      .catch(error => {
        console.error('Delete review error:', error);
        if (typeof notyf !== 'undefined') {
          notyf.error('Network error. Please try again.');
        } else {
          alert('Network error. Please try again.');
        }
        // Re-enable button
        this.disabled = false;
        this.style.opacity = '1';
      });
    });
  });
});
</script>

<script>
// Color and Size Selection Functions
function selectColor(colorName, colorId) {
    // Remove active state from all color options
    document.querySelectorAll('.color-option').forEach(btn => {
        btn.classList.remove('border-primary', 'bg-primary/10');
        btn.classList.add('border-gray-300', 'bg-white');
    });
    
    // Add active state to selected color
    const selectedBtn = document.querySelector(`[data-color-id="${colorId}"]`);
    if (selectedBtn) {
        selectedBtn.classList.remove('border-gray-300', 'bg-white');
        selectedBtn.classList.add('border-primary', 'bg-primary/10');
    }
    
    // Update hidden input and display
    const colorInput = document.getElementById('selected-color');
    if (colorInput) {
        colorInput.value = colorName;
    }
    const displayEl = document.getElementById('selected-color-display');
    if (displayEl) {
        displayEl.textContent = colorName;
    }
}

function selectSize(sizeName, sizeId) {
    // Remove active state from all size options
    document.querySelectorAll('.size-option').forEach(btn => {
        btn.classList.remove('border-primary', 'bg-primary/10', 'text-primary');
        btn.classList.add('border-gray-300', 'bg-white', 'text-gray-700');
    });
    
    // Add active state to selected size
    const selectedBtn = document.querySelector(`[data-size-id="${sizeId}"]`);
    if (selectedBtn) {
        selectedBtn.classList.remove('border-gray-300', 'bg-white', 'text-gray-700');
        selectedBtn.classList.add('border-primary', 'bg-primary/10', 'text-primary');
    }
    
    // Update hidden input and display
    const sizeInput = document.getElementById('selected-size');
    if (sizeInput) {
        sizeInput.value = sizeName;
    }
    const displayEl = document.getElementById('selected-size-display');
    if (displayEl) {
        displayEl.textContent = sizeName;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize selected color and size displays
    const selectedColor = document.getElementById('selected-color');
    const selectedSize = document.getElementById('selected-size');
    
    if (selectedColor && selectedColor.value) {
        const displayEl = document.getElementById('selected-color-display');
        if (displayEl) {
            displayEl.textContent = selectedColor.value;
        }
    }
    
    if (selectedSize && selectedSize.value) {
        const displayEl = document.getElementById('selected-size-display');
        if (displayEl) {
            displayEl.textContent = selectedSize.value;
        }
    }
    
    // Quantity controls
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decrease-qty');
    const increaseBtn = document.getElementById('increase-qty');
    
    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
    }
        
    if (increaseBtn) {
        increaseBtn.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            const maxValue = Math.min(3, parseInt(quantityInput.max) || 3); // Max 3 per order
            if (currentValue < maxValue) {
                quantityInput.value = currentValue + 1;
            } else {
                alert('Maximum 3 quantity allowed per product per order');
            }
        });
    }
    
    // Validate quantity on input change
    if (quantityInput) {
        quantityInput.addEventListener('change', function() {
            const currentValue = parseInt(this.value);
            const maxValue = Math.min(3, parseInt(this.max) || 3);
            if (currentValue > maxValue) {
                this.value = maxValue;
                alert('Maximum 3 quantity allowed per product per order');
            }
            if (currentValue < 1) {
                this.value = 1;
            }
        });
    }
    
    // Product Media Slider Swipe Functionality
    const productSlider = document.getElementById('product-media-slider');
    const productTrack = document.getElementById('product-media-track');
    const productSlides = document.querySelectorAll('.product-media-slide');
    let productCurrentIndex = 0;
    let productStartX = 0;
    let productCurrentX = 0;
    let productIsDragging = false;
    
    if (productSlider && productTrack && productSlides.length > 1) {
        function updateProductSlider() {
            const translateX = -productCurrentIndex * 100;
            productTrack.style.transform = `translateX(${translateX}%)`;
            
            // Update dots
            const dots = document.querySelectorAll('.product-slider-dot');
            dots.forEach((dot, index) => {
                if (index === productCurrentIndex) {
                    dot.classList.remove('bg-white/50', 'w-2');
                    dot.classList.add('bg-white', 'w-6');
                } else {
                    dot.classList.remove('bg-white', 'w-6');
                    dot.classList.add('bg-white/50', 'w-2');
                }
            });
        }
        
        // Dot click handlers
        document.querySelectorAll('.product-slider-dot').forEach((dot, index) => {
            dot.addEventListener('click', () => {
                productCurrentIndex = index;
                updateProductSlider();
            });
        });
        
        productSlider.addEventListener('touchstart', (e) => {
            productStartX = e.touches[0].clientX;
            productIsDragging = true;
        });
        
        productSlider.addEventListener('touchmove', (e) => {
            if (!productIsDragging) return;
            productCurrentX = e.touches[0].clientX;
            const diff = productStartX - productCurrentX;
            const translateX = -productCurrentIndex * 100 - (diff / productSlider.offsetWidth) * 100;
            productTrack.style.transform = `translateX(${translateX}%)`;
        });
        
        productSlider.addEventListener('touchend', () => {
            if (!productIsDragging) return;
            productIsDragging = false;
            const diff = productStartX - productCurrentX;
            const threshold = productSlider.offsetWidth * 0.3;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0 && productCurrentIndex < productSlides.length - 1) {
                    productCurrentIndex++;
                } else if (diff < 0 && productCurrentIndex > 0) {
                    productCurrentIndex--;
                }
            }
            updateProductSlider();
        });
        
        // Mouse drag support
        productSlider.addEventListener('mousedown', (e) => {
            productStartX = e.clientX;
            productIsDragging = true;
            productSlider.style.cursor = 'grabbing';
        });
        
        productSlider.addEventListener('mousemove', (e) => {
            if (!productIsDragging) return;
            e.preventDefault();
            productCurrentX = e.clientX;
            const diff = productStartX - productCurrentX;
            const translateX = -productCurrentIndex * 100 - (diff / productSlider.offsetWidth) * 100;
            productTrack.style.transform = `translateX(${translateX}%)`;
        });
        
        productSlider.addEventListener('mouseup', () => {
            if (!productIsDragging) return;
            productIsDragging = false;
            productSlider.style.cursor = 'grab';
            const diff = productStartX - productCurrentX;
            const threshold = productSlider.offsetWidth * 0.3;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0 && productCurrentIndex < productSlides.length - 1) {
                    productCurrentIndex++;
                } else if (diff < 0 && productCurrentIndex > 0) {
                    productCurrentIndex--;
                }
            }
            updateProductSlider();
        });
        
        productSlider.addEventListener('mouseleave', () => {
            if (productIsDragging) {
                productIsDragging = false;
                productSlider.style.cursor = 'grab';
                updateProductSlider();
            }
        });
    }
    
    // Image/Video thumbnail functionality - update slider instead of replacing media
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    
    if (thumbnails.length > 0 && productSlides.length > 0) {
        thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', function() {
                if (productCurrentIndex !== index) {
                    productCurrentIndex = index;
                    updateProductSlider();
                }
                
                // Update active thumbnail
                thumbnails.forEach(t => {
                    t.classList.remove('border-primary', 'shadow-md');
                    t.classList.add('border-gray-200');
                });
                this.classList.remove('border-gray-200');
                this.classList.add('border-primary', 'shadow-md');
            });
        });
    }
    
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
                const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const productPrice = this.getAttribute('data-product-price');
            let quantity = quantityInput ? parseInt(quantityInput.value) : 1;
            
            if (!productId) {
                console.error('Product ID not found');
                return;
            }
            
            // Validate max quantity (3 per order)
            const maxQuantity = Math.min(3, parseInt(quantityInput?.max || 3));
            if (quantity > maxQuantity) {
                quantity = maxQuantity;
                if (quantityInput) {
                    quantityInput.value = maxQuantity;
                }
                alert('Maximum ' + maxQuantity + ' quantity allowed per product per order');
                return;
            }
            
            if (quantity < 1) {
                quantity = 1;
                if (quantityInput) {
                    quantityInput.value = 1;
                }
            }
            
            // Disable button and show loading state
            this.disabled = true;
            const btnText = this.querySelector('.btn-text');
            const btnLoading = this.querySelector('.btn-loading');
            
            if (btnText) btnText.classList.add('hidden');
            if (btnLoading) btnLoading.classList.remove('hidden');
            
            // Add to cart via AJAX
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            
            // Add selected color if available
            const selectedColor = document.getElementById('selected-color');
            if (selectedColor && selectedColor.value) {
                formData.append('color', selectedColor.value);
            }
            
            // Add selected size if available
            const selectedSize = document.getElementById('selected-size');
            if (selectedSize && selectedSize.value) {
                formData.append('size', selectedSize.value);
            }
            
            fetch('<?= ASSETS_URL ?>/cart/add', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success state
                    if (btnText) {
                        btnText.textContent = 'Added!';
                        btnText.classList.remove('hidden');
                    }
                    if (btnLoading) btnLoading.classList.add('hidden');
                    
                    this.classList.remove('bg-primary', 'bg-primary-dark');
                    this.classList.add('bg-success');
                    
                    // Show success notification
                    if (typeof notyf !== 'undefined') {
                        notyf.success(data.message || 'Product added to cart!');
                    }
                    
                    // Update cart count via CartNotifier
                    if (typeof CartNotifier !== 'undefined') {
                        CartNotifier.setCount(data.cart_count || 0);
                    } else {
                        const cartCountElements = document.querySelectorAll('.cart-count');
                        cartCountElements.forEach(element => {
                            element.textContent = data.cart_count || 0;
                        });
                    }
                    
                    // Trigger cart:added event
                    document.dispatchEvent(new CustomEvent('cart:added', { detail: { count: data.cart_count || 0 } }));
                    
                    // Show green tick icon instead of redirecting
                    setTimeout(() => {
                        if (btnText) {
                            btnText.innerHTML = '<svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Added!';
                        }
                        this.classList.remove('bg-success');
                        this.classList.add('bg-success');
                        
                        // Reset after 3 seconds
                        setTimeout(() => {
                            this.disabled = false;
                            if (btnText) {
                                btnText.textContent = 'Add to Cart';
                            }
                            this.classList.remove('bg-success');
                            this.classList.add('bg-primary');
                        }, 3000);
                    }, 500);
                } else {
                    // Show error state
                    if (btnText) {
                        btnText.textContent = 'Error';
                        btnText.classList.remove('hidden');
                    }
                    if (btnLoading) btnLoading.classList.add('hidden');
                    
                    // Show error notification
                    if (typeof notyf !== 'undefined') {
                        notyf.error(data.message || 'Error adding product to cart');
                    }
                    
                    // Reset after 2 seconds
                    setTimeout(() => {
                        this.disabled = false;
                        if (btnText) {
                            btnText.textContent = 'Add to Cart';
                        }
                        this.classList.remove('bg-success', 'bg-destructive');
                        this.classList.add('bg-primary');
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error state
                if (btnText) {
                    btnText.textContent = 'Error';
                    btnText.classList.remove('hidden');
                }
                if (btnLoading) btnLoading.classList.add('hidden');
                
                // Show error notification
                if (typeof notyf !== 'undefined') {
                    notyf.error('Network error. Please try again.');
                }
                
                // Reset after 2 seconds
                setTimeout(() => {
                    this.disabled = false;
                    if (btnText) {
                        btnText.textContent = 'Add to Cart';
                    }
                    this.classList.remove('bg-success', 'bg-destructive');
                    this.classList.add('bg-primary');
                }, 2000);
            });
        });
    });
        
        // Direct checkout functionality
    document.querySelectorAll('.direct-checkout').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
                const productId = this.getAttribute('data-product-id');
            let quantity = quantityInput ? parseInt(quantityInput.value) : 1;
            
            if (!productId) {
                console.error('Product ID not found');
                return;
            }
            
            // Validate max quantity (3 per order)
            const maxQuantity = Math.min(3, parseInt(quantityInput?.max || 3));
            if (quantity > maxQuantity) {
                quantity = maxQuantity;
                if (quantityInput) {
                    quantityInput.value = maxQuantity;
                }
                alert('Maximum ' + maxQuantity + ' quantity allowed per product per order');
                return;
            }
            
            if (quantity < 1) {
                quantity = 1;
                if (quantityInput) {
                    quantityInput.value = 1;
                }
            }
            
                    // Disable button and show loading state
                    this.disabled = true;
                    const btnText = this.querySelector('.btn-text');
                    const btnLoading = this.querySelector('.btn-loading');
                    
                    if (btnText) btnText.classList.add('hidden');
                    if (btnLoading) btnLoading.classList.remove('hidden');
                    
                    // Add to cart first, then redirect to checkout
                    const formData = new FormData();
                    formData.append('product_id', productId);
                    formData.append('quantity', quantity);
                    
                    // Add selected color if available
                    const selectedColor = document.getElementById('selected-color');
                    if (selectedColor && selectedColor.value) {
                        formData.append('color', selectedColor.value);
                    }
                    
                    // Add selected size if available
                    const selectedSize = document.getElementById('selected-size');
                    if (selectedSize && selectedSize.value) {
                        formData.append('size', selectedSize.value);
                    }
                    
            fetch('<?= ASSETS_URL ?>/cart/add', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success notification
                            if (typeof notyf !== 'undefined') {
                                notyf.success('Product added! Redirecting to checkout...');
                            }
                            
                            // Redirect to checkout
                            setTimeout(() => {
                                window.location.href = '<?= \App\Core\View::url('checkout') ?>';
                            }, 1000);
                        } else {
                            // Show error state
                            if (btnText) {
                                btnText.textContent = 'Error';
                                btnText.classList.remove('hidden');
                            }
                            if (btnLoading) btnLoading.classList.add('hidden');
                            
                            // Show error notification
                            if (typeof notyf !== 'undefined') {
                                notyf.error(data.message || 'Error adding product to cart');
                            }
                            
                            // Reset after 2 seconds
                            setTimeout(() => {
                                this.disabled = false;
                                if (btnText) {
                                    btnText.textContent = 'Order Now';
                                }
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Show error state
                        if (btnText) {
                            btnText.textContent = 'Error';
                            btnText.classList.remove('hidden');
                        }
                        if (btnLoading) btnLoading.classList.add('hidden');
                        
                        // Show error notification
                        if (typeof notyf !== 'undefined') {
                            notyf.error('Network error. Please try again.');
                        }
                        
                        // Reset after 2 seconds
                        setTimeout(() => {
                            this.disabled = false;
                            if (btnText) {
                                btnText.textContent = 'Order Now';
                            }
                        }, 2000);
                    });
        });
    });
    
    // Share Product Drawer Functionality
    const shareLinkBtn = document.getElementById('share-product-link');
    const productUrlCopy = document.getElementById('product-url-copy');
    const productNameShare = document.getElementById('product-name-share');
    const productImageShare = document.getElementById('product-image-share');
    const shareFeedback = document.getElementById('product-share-feedback');
    let shareFeedbackTimeout;
    
    function showShareFeedback() {
        if (!shareFeedback) return;
        shareFeedback.classList.remove('hidden');
        clearTimeout(shareFeedbackTimeout);
        shareFeedbackTimeout = setTimeout(() => {
            shareFeedback.classList.add('hidden');
        }, 3000);
    }
    
    function openShareDrawer() {
        const drawer = document.getElementById('share-drawer');
        const overlay = document.getElementById('share-drawer-overlay');
        if (drawer && overlay) {
            drawer.classList.remove('translate-y-full');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // Reset share drawer slider to first image
            if (shareCurrentIndex !== 0) {
                shareCurrentIndex = 0;
                updateShareSlider();
            }
        }
    }
    
    function changeMainMedia(url, type) {
        const mainMedia = document.getElementById('main-product-media');
        const mediaContainer = mainMedia?.parentElement;
        if (!mediaContainer) return;
        
        if (type === 'video') {
            mediaContainer.innerHTML = '<video id="main-product-media" src="' + url + '" class="w-full h-full object-contain" controls preload="metadata" poster="<?= \App\Core\View::asset('images/products/default.jpg') ?>">Your browser does not support the video tag.</video>';
        } else {
            mediaContainer.innerHTML = '<img id="main-product-media" src="' + url + '" alt="<?= htmlspecialchars($productName) ?>" class="w-full h-full object-contain transition-all duration-300 hover:scale-105" onerror="this.src=\'<?= ASSETS_URL ?>/images/products/default.jpg\'">';
        }
        
        // Update share drawer media URL
        if (productImageShare) {
            productImageShare.value = url;
        }
    }
    
    function closeShareDrawer() {
        const drawer = document.getElementById('share-drawer');
        const overlay = document.getElementById('share-drawer-overlay');
        if (drawer && overlay) {
            drawer.classList.add('translate-y-full');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
    
    function shareToFacebook() {
        const url = encodeURIComponent(productUrlCopy.value);
        const name = encodeURIComponent(productNameShare.value);
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${name}`, '_blank', 'width=600,height=400');
    }
    
    function shareToInstagram() {
        const url = productUrlCopy.value;
        window.open(`https://www.instagram.com/`, '_blank');
    }
    
    function shareToWhatsApp() {
        const url = encodeURIComponent(productUrlCopy.value);
        const name = encodeURIComponent(productNameShare.value);
        window.open(`https://wa.me/?text=${name}%20${url}`, '_blank');
    }
    
    function shareToTelegram() {
        const url = encodeURIComponent(productUrlCopy.value);
        const name = encodeURIComponent(productNameShare.value);
        window.open(`https://t.me/share/url?url=${url}&text=${name}`, '_blank');
    }
    
    function copyProductLink() {
        const url = productUrlCopy.value;
        const copyIcon = document.getElementById('copy-link-icon');
        const copyCheck = document.getElementById('copy-link-check');
        const copyText = document.getElementById('copy-link-text');
        const copyBtn = document.getElementById('copy-link-btn');
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url)
                .then(() => {
                    if (copyIcon) copyIcon.classList.add('hidden');
                    if (copyCheck) copyCheck.classList.remove('hidden');
                    if (copyText) copyText.textContent = 'Copied!';
                    if (copyBtn) copyBtn.classList.add('bg-green-50', 'border', 'border-green-500');
                    
                    setTimeout(() => {
                        if (copyIcon) copyIcon.classList.remove('hidden');
                        if (copyCheck) copyCheck.classList.add('hidden');
                        if (copyText) copyText.textContent = 'Copy Link';
                        if (copyBtn) copyBtn.classList.remove('bg-green-50', 'border', 'border-green-500');
                    }, 2000);
                })
                .catch(() => {
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    
                    if (copyIcon) copyIcon.classList.add('hidden');
                    if (copyCheck) copyCheck.classList.remove('hidden');
                    if (copyText) copyText.textContent = 'Copied!';
                    if (copyBtn) copyBtn.classList.add('bg-green-50', 'border', 'border-green-500');
                    
                    setTimeout(() => {
                        if (copyIcon) copyIcon.classList.remove('hidden');
                        if (copyCheck) copyCheck.classList.add('hidden');
                        if (copyText) copyText.textContent = 'Copy Link';
                        if (copyBtn) copyBtn.classList.remove('bg-green-50', 'border', 'border-green-500');
                    }, 2000);
                });
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = url;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            if (copyIcon) copyIcon.classList.add('hidden');
            if (copyCheck) copyCheck.classList.remove('hidden');
            if (copyText) copyText.textContent = 'Copied!';
            if (copyBtn) copyBtn.classList.add('bg-green-50', 'border', 'border-green-500');
            
            setTimeout(() => {
                if (copyIcon) copyIcon.classList.remove('hidden');
                if (copyCheck) copyCheck.classList.add('hidden');
                if (copyText) copyText.textContent = 'Copy Link';
                if (copyBtn) copyBtn.classList.remove('bg-green-50', 'border', 'border-green-500');
            }, 2000);
        }
    }
    
    function saveProductImage() {
        const imageUrl = productImageShare.value;
        const saveIcon = document.getElementById('save-image-icon');
        const saveCheck = document.getElementById('save-image-check');
        const saveText = document.getElementById('save-image-text');
        const saveBtn = document.getElementById('save-image-btn');
        
        fetch(imageUrl)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = productNameShare.value.replace(/[^a-z0-9]/gi, '_') + '.jpg';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                
                if (saveIcon) saveIcon.classList.add('hidden');
                if (saveCheck) saveCheck.classList.remove('hidden');
                if (saveText) saveText.textContent = 'Saved!';
                if (saveBtn) saveBtn.classList.add('bg-green-50', 'border', 'border-green-500');
                
                setTimeout(() => {
                    if (saveIcon) saveIcon.classList.remove('hidden');
                    if (saveCheck) saveCheck.classList.add('hidden');
                    if (saveText) saveText.textContent = 'Save';
                    if (saveBtn) saveBtn.classList.remove('bg-green-50', 'border', 'border-green-500');
                }, 2000);
            })
            .catch(() => {
                const link = document.createElement('a');
                link.href = imageUrl;
                link.download = productNameShare.value.replace(/[^a-z0-9]/gi, '_') + '.jpg';
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                if (saveIcon) saveIcon.classList.add('hidden');
                if (saveCheck) saveCheck.classList.remove('hidden');
                if (saveText) saveText.textContent = 'Saved!';
                if (saveBtn) saveBtn.classList.add('bg-green-50', 'border', 'border-green-500');
                
                setTimeout(() => {
                    if (saveIcon) saveIcon.classList.remove('hidden');
                    if (saveCheck) saveCheck.classList.add('hidden');
                    if (saveText) saveText.textContent = 'Save';
                    if (saveBtn) saveBtn.classList.remove('bg-green-50', 'border', 'border-green-500');
                }, 2000);
            });
    }
    
    if (shareLinkBtn) {
        shareLinkBtn.addEventListener('click', openShareDrawer);
    }
    
    const shareOverlay = document.getElementById('share-drawer-overlay');
    if (shareOverlay) {
        shareOverlay.addEventListener('click', closeShareDrawer);
    }
    
    const shareDrawerClose = document.getElementById('share-drawer-close');
    if (shareDrawerClose) {
        shareDrawerClose.addEventListener('click', closeShareDrawer);
    }
    
    // Share Drawer Slider Swipe Functionality
    const shareSlider = document.getElementById('share-drawer-slider');
    const shareTrack = document.getElementById('share-drawer-track');
    const shareSlides = document.querySelectorAll('.share-media-slide');
    let shareCurrentIndex = 0;
    let shareStartX = 0;
    let shareCurrentX = 0;
    let shareIsDragging = false;
    
    function updateShareSlider() {
        if (shareTrack && shareSlides.length > 0) {
            const translateX = -shareCurrentIndex * 100;
            shareTrack.style.transform = `translateX(${translateX}%)`;
            
            // Update dots
            const dots = document.querySelectorAll('.share-slider-dot');
            dots.forEach((dot, index) => {
                if (index === shareCurrentIndex) {
                    dot.classList.remove('bg-white/50', 'w-2');
                    dot.classList.add('bg-white', 'w-6');
                } else {
                    dot.classList.remove('bg-white', 'w-6');
                    dot.classList.add('bg-white/50', 'w-2');
                }
            });
        }
    }
    
    // Share drawer dot click handlers
    document.querySelectorAll('.share-slider-dot').forEach((dot, index) => {
        dot.addEventListener('click', () => {
            shareCurrentIndex = index;
            updateShareSlider();
        });
    });
    
    if (shareSlider && shareTrack && shareSlides.length > 1) {
        shareSlider.addEventListener('touchstart', (e) => {
            shareStartX = e.touches[0].clientX;
            shareIsDragging = true;
        });
        
        shareSlider.addEventListener('touchmove', (e) => {
            if (!shareIsDragging) return;
            shareCurrentX = e.touches[0].clientX;
            const diff = shareStartX - shareCurrentX;
            const translateX = -shareCurrentIndex * 100 - (diff / shareSlider.offsetWidth) * 100;
            shareTrack.style.transform = `translateX(${translateX}%)`;
        });
        
        shareSlider.addEventListener('touchend', () => {
            if (!shareIsDragging) return;
            shareIsDragging = false;
            const diff = shareStartX - shareCurrentX;
            const threshold = shareSlider.offsetWidth * 0.3;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0 && shareCurrentIndex < shareSlides.length - 1) {
                    shareCurrentIndex++;
                } else if (diff < 0 && shareCurrentIndex > 0) {
                    shareCurrentIndex--;
                }
            }
            updateShareSlider();
        });
        
        // Mouse drag support
        shareSlider.addEventListener('mousedown', (e) => {
            shareStartX = e.clientX;
            shareIsDragging = true;
            shareSlider.style.cursor = 'grabbing';
        });
        
        shareSlider.addEventListener('mousemove', (e) => {
            if (!shareIsDragging) return;
            e.preventDefault();
            shareCurrentX = e.clientX;
            const diff = shareStartX - shareCurrentX;
            const translateX = -shareCurrentIndex * 100 - (diff / shareSlider.offsetWidth) * 100;
            shareTrack.style.transform = `translateX(${translateX}%)`;
        });
        
        shareSlider.addEventListener('mouseup', () => {
            if (!shareIsDragging) return;
            shareIsDragging = false;
            shareSlider.style.cursor = 'grab';
            const diff = shareStartX - shareCurrentX;
            const threshold = shareSlider.offsetWidth * 0.3;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0 && shareCurrentIndex < shareSlides.length - 1) {
                    shareCurrentIndex++;
                } else if (diff < 0 && shareCurrentIndex > 0) {
                    shareCurrentIndex--;
                }
            }
            updateShareSlider();
        });
        
        shareSlider.addEventListener('mouseleave', () => {
            if (shareIsDragging) {
                shareIsDragging = false;
                shareSlider.style.cursor = 'grab';
                updateShareSlider();
            }
        });
    }
    
    const shareFacebookBtn = document.getElementById('share-facebook');
    if (shareFacebookBtn) {
        shareFacebookBtn.addEventListener('click', shareToFacebook);
    }
    
    const shareInstagramBtn = document.getElementById('share-instagram');
    if (shareInstagramBtn) {
        shareInstagramBtn.addEventListener('click', shareToInstagram);
    }
    
    const shareWhatsAppBtn = document.getElementById('share-whatsapp');
    if (shareWhatsAppBtn) {
        shareWhatsAppBtn.addEventListener('click', shareToWhatsApp);
    }
    
    const shareTelegramBtn = document.getElementById('share-telegram');
    if (shareTelegramBtn) {
        shareTelegramBtn.addEventListener('click', shareToTelegram);
    }
    
    const copyLinkBtn = document.getElementById('copy-link-btn');
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', copyProductLink);
    }
    
    const saveImageBtn = document.getElementById('save-image-btn');
    if (saveImageBtn) {
        saveImageBtn.addEventListener('click', saveProductImage);
    }
    });
    
    // Product card redirect function for suggestions
    if (typeof redirectToProduct === 'undefined') {
        function redirectToProduct(url, adId) {
            // Track ad click if product is sponsored
            if (adId) {
                if (typeof trackAdClick !== 'undefined') {
                    trackAdClick(adId);
                }
            }
            window.location.href = url;
        }
    }
    
    // Add suggested product to cart
    function addSuggestedToCart(productId) {
        const button = event.target.closest('button');
        if (button) {
            button.disabled = true;
            const originalHTML = button.innerHTML;
            button.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        }
        
        fetch('<?= ASSETS_URL ?>/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'product_id=' + encodeURIComponent(productId) + '&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count
                const cartCountElements = document.querySelectorAll('.cart-count');
                cartCountElements.forEach(element => {
                    element.textContent = data.cart_count || 0;
                });
                
                // Update button to show green checkmark icon directly (no alert)
                if (button) {
                    button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    button.classList.remove('bg-primary', 'bg-primary-dark');
                    button.classList.add('bg-success');
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.classList.remove('bg-success');
                        button.classList.add('bg-primary');
                        button.disabled = false;
                    }, 3000);
                }
            } else {
                if (button) {
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                }
                if (data.error && data.error.includes('login')) {
                    window.location.href = '<?= \App\Core\View::url('auth/login') ?>';
                }
            }
        })
        .catch(error => {
            if (button) {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }
            // Silent error handling - button will reset
        });
    }
    </script>

    <!-- Product View Tracking and Like Functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const productId = <?= $product['id'] ?? 0 ?>;
        
        // Record product view
        if (productId) {
            fetch('<?= \App\Core\View::url('products/view/record') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.view_count !== undefined) {
                    const viewCountEl = document.getElementById('view-count');
                    if (viewCountEl) {
                        viewCountEl.textContent = data.view_count.toLocaleString();
                    }
                }
            })
            .catch(error => {
                console.error('View tracking error:', error);
            });
        }
        
        // Like/Unlike functionality
        const likeBtn = document.getElementById('like-btn');
        if (likeBtn) {
            likeBtn.addEventListener('click', function() {
                const userId = <?= $isLoggedIn ? 'true' : 'false' ?>;
                if (!userId) {
                    window.location.href = '<?= \App\Core\View::url('auth/login') ?>';
                    return;
                }
                
                fetch('<?= \App\Core\View::url('products/like/toggle') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const likeIcon = this.querySelector('i');
                        const likeCountEl = document.getElementById('like-count');

                        const isLiked = !!(Object.prototype.hasOwnProperty.call(data, 'is_liked') ? data.is_liked : data.liked);
                        if (isLiked) {
                            this.dataset.liked = '1';
                            if (likeIcon) {
                                likeIcon.classList.remove('far', 'text-gray-600');
                                likeIcon.classList.add('fas', 'text-red-500', 'fill-red-500');
                            }
                        } else {
                            this.dataset.liked = '0';
                            if (likeIcon) {
                                likeIcon.classList.remove('fas', 'text-red-500', 'fill-red-500');
                                likeIcon.classList.add('far', 'text-gray-600');
                            }
                        }
                        
                        if (likeCountEl) {
                            likeCountEl.textContent = data.like_count.toLocaleString();
                        }
                    } else {
                        if (data.message && (data.message.includes('login') || data.message.includes('Please login'))) {
                            window.location.href = '<?= \App\Core\View::url('auth/login') ?>';
                        } else {
                            alert(data.message || 'Failed to update like status');
                        }
                    }
                })
                .catch(error => {
                    console.error('Like error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        }
    });

    // Mystery Price Animation (Casino Effect - Infinite)
    document.addEventListener('DOMContentLoaded', function() {
        const mysteryPriceElements = document.querySelectorAll('.mystery-price-animated');
        
        mysteryPriceElements.forEach(function(element) {
            const min = parseFloat(element.getAttribute('data-min')) || 0;
            const max = parseFloat(element.getAttribute('data-max')) || 0;
            
            if (min <= 0 || max <= 0 || min >= max) return;
            
            let animationId;
            const updateInterval = 120; // Slower update - every 120ms for more excitement
            
            function formatCurrency(value) {
                return 'रु' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            
            function animate() {
                // Random value between min and max
                const randomValue = min + (max - min) * Math.random();
                element.textContent = formatCurrency(randomValue);
                
                // Infinite loop - keep animating
                animationId = setTimeout(animate, updateInterval);
            }
            
            // Start infinite animation
            animate();
        });
    });
    </script>

<!-- Share Drawer Component -->
<div id="share-drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity duration-300"></div>
<div id="share-drawer" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl z-50 transform translate-y-full transition-transform duration-300 ease-out max-h-[90vh] overflow-y-auto">
    <div class="p-6">
        <!-- Drawer Header -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Share Product</h3>
            <button id="share-drawer-close" type="button" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Product Media Slider -->
        <div class="mb-6">
            <div id="share-drawer-slider" class="aspect-square overflow-hidden rounded-2xl bg-gray-50 relative touch-pan-y">
                <div id="share-drawer-track" class="flex h-full transition-transform duration-300 ease-out" style="transform: translateX(0%);">
                    <?php 
                    // Use all media for share drawer slider
                    $shareMediaList = !empty($additionalMedia) ? $additionalMedia : [['image_url' => $mainImageUrl, 'is_primary' => 1]];
                    foreach ($shareMediaList as $index => $media): 
                        $shareMediaUrl = $media['image_url'] ?? '';
                        $isShareVideo = \App\Helpers\MediaHelper::isVideo($shareMediaUrl);
                    ?>
                        <div class="share-media-slide flex-shrink-0 w-full h-full">
                            <?php if ($isShareVideo): ?>
                                <video src="<?= htmlspecialchars($shareMediaUrl) ?>" 
                                       class="w-full h-full object-contain" 
                                       autoplay
                                       muted
                                       loop
                                       playsinline
                                       preload="metadata"></video>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($shareMediaUrl) ?>" 
                                     alt="<?= htmlspecialchars($productName) ?> - Image <?= $index + 1 ?>" 
                                     id="<?= $index === 0 ? 'share-drawer-media' : '' ?>"
                                     class="w-full h-full object-contain">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- Share Drawer Slider Dots -->
                <?php if (count($shareMediaList) > 1): ?>
                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2 z-10">
                        <?php foreach ($shareMediaList as $index => $media): ?>
                            <button type="button" 
                                    class="share-slider-dot w-2 h-2 rounded-full transition-all duration-300 <?= $index === 0 ? 'bg-white w-6' : 'bg-white/50' ?>"
                                    data-slide-index="<?= $index ?>"
                                    aria-label="Go to slide <?= $index + 1 ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Share Buttons Row -->
        <div class="grid grid-cols-4 gap-3 mb-4">
            <button id="share-facebook" type="button" class="flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </button>
            
            <button id="share-instagram" type="button" class="flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 hover:opacity-90 text-white transition-opacity">
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </button>
            
            <button id="share-whatsapp" type="button" class="flex items-center justify-center w-14 h-14 rounded-2xl bg-green-500 hover:bg-green-600 text-white transition-colors">
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
            </button>
            
            <button id="share-telegram" type="button" class="flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-400 hover:bg-blue-500 text-white transition-colors">
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
            </button>
        </div>
        
        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3">
            <button id="copy-link-btn" type="button" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl font-medium transition-colors relative">
                <svg id="copy-link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                </svg>
                <svg id="copy-link-check" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-green-600 hidden">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span id="copy-link-text">Copy Link</span>
            </button>
            
            <button id="save-image-btn" type="button" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl font-medium transition-colors relative">
                <svg id="save-image-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                <svg id="save-image-check" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-green-600 hidden">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span id="save-image-text">Save</span>
            </button>
        </div>
    </div>
</div>

<style>
/* Ensure review actions sit above floating sticky elements */
.review-action { position: relative; z-index: 1101; }
.review-sticky-safe { padding-bottom: 90px; }

/* Product Slider Styles */
#product-media-slider, #share-drawer-slider {
    cursor: grab;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

#product-media-slider:active, #share-drawer-slider:active {
    cursor: grabbing;
}

#product-media-track, #share-drawer-track {
    will-change: transform;
}

.product-media-slide, .share-media-slide {
    touch-action: pan-y;
}

/* Slider Dots */
.product-slider-dot, .share-slider-dot {
    cursor: pointer;
    border: none;
    outline: none;
    transition: all 0.3s ease;
}

.product-slider-dot:hover, .share-slider-dot:hover {
    opacity: 0.8;
}

@media (min-width: 1024px) {
  .review-sticky-safe { padding-bottom: 0; }
}
</style>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/main.php';
?>


