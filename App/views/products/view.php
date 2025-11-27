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
$productBrand = 'NutriNexus';

// Check if product is scheduled
$isScheduled = isset($product['is_scheduled']) && $product['is_scheduled'] == 1;
$isAvailable = isset($product['stock_quantity']) && $product['stock_quantity'] > 0 && !$isScheduled;

// Calculate remaining days for scheduled products
$remainingDays = 0;
if ($isScheduled && !empty($product['scheduled_date'])) {
    $scheduledTimestamp = strtotime($product['scheduled_date']);
    $currentTimestamp = time();
    if ($scheduledTimestamp > $currentTimestamp) {
        $remainingDays = ceil(($scheduledTimestamp - $currentTimestamp) / (60 * 60 * 24));
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
?>

<!-- Essential SEO Meta Tags -->
<meta name="title" content="<?= $seoTitle ?>">
<meta name="description" content="<?= $seoDescription ?>">
<meta name="keywords" content="<?= htmlspecialchars($productName . ', ' . $productCategory . ', supplements Nepal, buy online, NutriNexus', ENT_QUOTES, 'UTF-8') ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= $absoluteProductUrl ?>">

<!-- Open Graph Meta Tags (Facebook, LinkedIn, etc.) -->
<meta property="og:type" content="product">
<meta property="og:title" content="<?= $seoTitle ?>">
<meta property="og:description" content="<?= $seoDescription ?>">
<meta property="og:url" content="<?= $absoluteProductUrl ?>">
<meta property="og:image" content="<?= htmlspecialchars($mainImageUrl, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image:secure_url" content="<?= htmlspecialchars(str_replace('http://', 'https://', $mainImageUrl), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="NutriNexus Nepal">
<meta property="og:locale" content="en_US">

<!-- Product-specific Open Graph Tags -->
<meta property="product:price:amount" content="<?= number_format($finalPrice, 2, '.', '') ?>">
<meta property="product:price:currency" content="<?= $productCurrency ?>">
<meta property="product:availability" content="<?= $productAvailability ?>">
<?php if (!empty($product['brand'])): ?>
<meta property="product:brand" content="<?= htmlspecialchars($product['brand'], ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<?php if (!empty($productSku)): ?>
<meta property="product:retailer_item_id" content="<?= htmlspecialchars($productSku, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $seoTitle ?>">
<meta name="twitter:description" content="<?= $seoDescription ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($mainImageUrl, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:image:alt" content="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:site" content="@nutrinexus">
<meta name="twitter:creator" content="@nutrinexus">

<!-- Enhanced Product Schema (JSON-LD) -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Product",
    "name": <?= json_encode($productName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "description": <?= json_encode($seoDescription, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "image": <?= json_encode(count($additionalImages) > 1 ? $additionalImages : [$mainImageUrl], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "sku": <?= json_encode($productSku, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "brand": {
        "@type": "Brand",
        "name": <?= json_encode($productBrand, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    },
    "offers": {
        "@type": "Offer",
        "price": "<?= number_format($finalPrice, 2, '.', '') ?>",
        "priceCurrency": "<?= $productCurrency ?>",
        "availability": "https://schema.org/<?= $productAvailability ?>",
        "url": <?= json_encode($absoluteProductUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        "priceValidUntil": "<?= date('Y-m-d', strtotime('+1 year')) ?>",
        "itemCondition": "https://schema.org/NewCondition"
        <?php if ($finalPrice < $originalPrice): ?>,
        "priceSpecification": {
            "@type": "UnitPriceSpecification",
            "price": "<?= number_format($finalPrice, 2, '.', '') ?>",
            "priceCurrency": "<?= $productCurrency ?>",
            "referenceQuantity": {
                "@type": "QuantitativeValue",
                "value": 1
            }
        }
        <?php endif; ?>
    },
    "category": <?= json_encode($productCategory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    <?php if (!empty($averageRating) && $averageRating > 0): ?>
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "<?= number_format($averageRating, 1) ?>",
        "reviewCount": "<?= (int)($reviewCount ?? 0) ?>",
        "bestRating": "5",
        "worstRating": "1"
    },
    <?php endif; ?>
    <?php if (!empty($reviewSchema)): ?>
    "review": <?= json_encode($reviewSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    <?php endif; ?>
    "url": <?= json_encode($absoluteProductUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
}
</script>

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
                
                <!-- Product Image -->
                <div class="p-4 lg:p-6">
                    <!-- Main Product Image -->
                    <div class="aspect-square overflow-hidden rounded-2xl bg-gray-50 shadow-sm mb-4">
                        <img id="main-product-image" 
                             src="<?= htmlspecialchars($mainImageUrl) ?>" 
                             alt="<?= $productName ?>" 
                             class="w-full h-full object-contain transition-all duration-300 hover:scale-105"
                             onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'">
                    </div>

                    <!-- Thumbnail Gallery -->
                    <?php 
                    // Get additional images from product_images table
                    $additionalImages = [];
                    if (isset($product['id'])) {
                        $productImageModel = new \App\Models\ProductImage();
                        $additionalImages = $productImageModel->getByProductId($product['id']);
                    }
                    ?>
                    <?php if (!empty($additionalImages) && count($additionalImages) > 1): ?>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        <?php foreach (array_slice($additionalImages, 0, 4) as $index => $image): ?>
                            <?php $thumbnailUrl = filter_var($image['image_url'], FILTER_VALIDATE_URL) ? $image['image_url'] : ASSETS_URL . '/uploads/images/' . $image['image_url']; ?>
                            <div class="aspect-square rounded-2xl overflow-hidden border-2 transition-all duration-200 <?= $image['is_primary'] ? 'border-primary shadow-md' : 'border-gray-200 hover:border-gray-300' ?> cursor-pointer product-thumbnail" 
                                 data-image-url="<?= htmlspecialchars($thumbnailUrl) ?>">
                                <img src="<?= htmlspecialchars($thumbnailUrl) ?>" 
                                     alt="<?= $productName ?> - Image <?= $index + 1 ?>" 
                                     class="w-full h-full object-cover transition-all duration-200 hover:scale-110"
                                     onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="p-4 lg:p-6 flex flex-col">
        <!-- Product Title -->
                    <h1 class="text-2xl lg:text-3xl font-bold text-primary mb-3"><?= $productName ?></h1>
                    
                    <!-- Seller Info -->
                    <?php if (!empty($seller)): ?>
                        <div class="mb-3">
                            <a href="<?= \App\Core\View::url('seller/' . urlencode($seller['company_name'] ?? $seller['name'])) ?>" 
                               class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-colors group">
                                <div class="w-8 h-8 rounded-full overflow-hidden border-2 border-gray-200 group-hover:border-primary transition-colors flex-shrink-0">
                                    <?php if (!empty($seller['logo_url'])): ?>
                                        <img src="<?= htmlspecialchars($seller['logo_url']) ?>" 
                                             alt="<?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>"
                                             class="w-full h-full object-cover"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <?php endif; ?>
                                    <div class="w-full h-full bg-primary text-white flex items-center justify-center text-xs font-bold <?= !empty($seller['logo_url']) ? 'hidden' : 'flex' ?>">
                                        <?= strtoupper(substr($seller['company_name'] ?? $seller['name'] ?? 'S', 0, 1)) ?>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-500">Sold by</span>
                                    <span class="text-sm font-semibold text-gray-900 group-hover:text-primary transition-colors">
                                        <?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>
                                    </span>
                                </div>
                                <i class="fas fa-chevron-right text-xs text-gray-400 group-hover:text-primary ml-auto transition-colors"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Category Badge -->
                    <div class="mb-3">
                        <span class="inline-flex items-center px-2 py-1 bg-primary/10 text-primary text-xs font-medium rounded">
                            <?= htmlspecialchars($product['category'] ?? 'Product') ?>
                        </span>
                        </div>
                        
                    <!-- Price -->
                    <div class="mb-4">
                        <?php 
                        // Check for sale price (from site-wide sale or manual sale_price)
                        $hasSale = false;
                        $salePrice = null;
                        $originalPrice = floatval($product['price'] ?? 0);
                        
                        // Check if product is on site-wide sale
                        if (!empty($product['is_on_sale']) && 
                            !empty($product['sale_start_date']) && 
                            !empty($product['sale_end_date']) &&
                            !empty($product['sale_discount_percent']) &&
                            $product['sale_discount_percent'] > 0) {
                            $now = date('Y-m-d H:i:s');
                            if ($product['sale_start_date'] <= $now && $product['sale_end_date'] >= $now) {
                                $discountPercent = floatval($product['sale_discount_percent']);
                                $salePrice = $originalPrice - (($originalPrice * $discountPercent) / 100);
                                $hasSale = true;
                            }
                        }
                        
                        // Check manual sale_price
                        if (!empty($product['sale_price']) && $product['sale_price'] < $originalPrice) {
                            if (!$hasSale || $product['sale_price'] < $salePrice) {
                                $salePrice = floatval($product['sale_price']);
                                $hasSale = true;
                            }
                        }
                        ?>
                        
                        <?php if ($hasSale && $salePrice < $originalPrice): ?>
                            <div class="flex items-baseline gap-2 flex-wrap">
                                <span class="text-xl font-bold text-primary"><?= CurrencyHelper::format($salePrice) ?></span>
                                <span class="text-sm text-gray-500 line-through"><?= CurrencyHelper::format($originalPrice) ?></span>
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded">
                                    <?= round((($originalPrice - $salePrice) / $originalPrice) * 100) ?>% OFF
                                </span>
                            </div>
                            <?php if (!empty($product['sale_end_date'])): ?>
                                <div class="text-xs text-gray-600 mt-1">
                                    Sale ends: <?= date('M j, Y', strtotime($product['sale_end_date'])) ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-xl font-bold text-primary"><?= CurrencyHelper::format($originalPrice) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Scheduled Date Display -->
                    <?php if (!empty($product['scheduled_date']) && strtotime($product['scheduled_date']) > time()): ?>
                        <div class="mb-4 p-3 bg-primary/10 border border-primary/20 rounded-2xl">
                            <div class="flex items-center text-primary text-sm font-semibold">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Launch Date: <?= date('F j, Y', strtotime($product['scheduled_date'])) ?></span>
                            </div>
                            <?php if (!empty($product['scheduled_message'])): ?>
                                <p class="text-xs text-primary mt-1"><?= htmlspecialchars($product['scheduled_message']) ?></p>
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
                    $colors = !empty($product['colors']) ? (is_string($product['colors']) ? json_decode($product['colors'], true) : $product['colors']) : [];
                    ?>
                    <?php if ($isDigital || $productType): ?>
                        <div class="mb-4 flex items-center gap-2 flex-wrap">
                            <?php if ($isDigital): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-download mr-1"></i>Digital Product - No Shipping Required
                                </span>
                            <?php endif; ?>
                            <?php if ($productType): ?>
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
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                                </div>
                    </div>
                </div>
                

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

        <!-- Suggested Products Section -->
        <?php if (!empty($lowPriceProducts) && count($lowPriceProducts) > 0): ?>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mt-4">
            <div class="p-4">
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
        
        <?php if ($isScheduled): ?>
            <button type="button" class="flex-1 bg-primary text-white px-4 py-3 rounded-2xl font-semibold text-sm cursor-not-allowed">
                <div class="flex items-center justify-between w-full">
                    <span>
                        <i class="fas fa-clock mr-2"></i>
                        <?php if ($remainingDays > 0): ?>
                            Launching in <?= $remainingDays ?> <?= $remainingDays == 1 ? 'day' : 'days' ?>
                        <?php else: ?>
                            Coming Soon
                        <?php endif; ?>
                    </span>
                    <span class="text-xs text-white/80 launch-countdown">Launches soon</span>
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
    var targets = document.querySelectorAll('.launch-countdown');
    if (!targets || targets.length === 0) return;
    var targetStr = '<?= isset($scheduledDate) && $scheduledDate ? $scheduledDate->format('Y-m-d H:i:s') : '' ?>';
    if (!targetStr) return;
    var targetTime = new Date(targetStr.replace(' ', 'T')).getTime();
    function fmt(n){ return n.toString().padStart(2,'0'); }
    function render(){
      var now = Date.now();
      var diff = targetTime - now;
      var text = '';
      if (diff <= 0) {
        text = 'Launching now';
      } else {
        var d = Math.floor(diff / (1000*60*60*24));
        var h = Math.floor((diff % (1000*60*60*24)) / (1000*60*60));
        var m = Math.floor((diff % (1000*60*60)) / (1000*60));
        var s = Math.floor((diff % (1000*60)) / 1000);
        text = 'Launches in ' + d + 'd ' + fmt(h) + ':' + fmt(m) + ':' + fmt(s);
      }
      targets.forEach(function(el){ el.textContent = text; });
    }
    render();
    setInterval(render, 1000);
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
    
    // Image thumbnail functionality
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    const mainImage = document.getElementById('main-product-image');
    
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            const imageUrl = this.getAttribute('data-image-url');
            if (mainImage && imageUrl) {
                mainImage.src = imageUrl;
                
                // Update active thumbnail
                thumbnails.forEach(t => {
                    t.classList.remove('border-primary', 'shadow-md');
                    t.classList.add('border-gray-200');
                });
                this.classList.remove('border-gray-200');
                this.classList.add('border-primary', 'shadow-md');
            }
        });
    });
    
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
                    
                    // Update cart count
                    const cartCountElements = document.querySelectorAll('.cart-count');
                    cartCountElements.forEach(element => {
                        element.textContent = data.cart_count || 0;
                    });
                    
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
    
    // Share Product Link Functionality (Native, no animation)
    const shareLinkBtn = document.getElementById('share-product-link');
    const productUrlCopy = document.getElementById('product-url-copy');
    
    if (shareLinkBtn && productUrlCopy) {
        shareLinkBtn.addEventListener('click', function() {
            const url = productUrlCopy.value;
            
            // Use Clipboard API if available
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url);
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
        });
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

    

<style>
/* Ensure review actions sit above floating sticky elements */
.review-action { position: relative; z-index: 1101; }
.review-sticky-safe { padding-bottom: 90px; }

@media (min-width: 1024px) {
  .review-sticky-safe { padding-bottom: 0; }
}
</style>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/main.php';
?>


