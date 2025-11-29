<?php
/**
 * Comprehensive SEO Meta Tags for Product View Page
 * Ultra high-level SEO implementation with dynamic OG images
 */

// Get product primary image dynamically
$productImageModel = new \App\Models\ProductImage();
$primaryImage = $productImageModel->getPrimaryImage($product['id'] ?? 0);

// Build absolute image URL for OG tags (must be absolute)
$ogImageUrl = '';
if ($primaryImage && !empty($primaryImage['image_url'])) {
    $imgUrl = $primaryImage['image_url'];
    if (filter_var($imgUrl, FILTER_VALIDATE_URL)) {
        $ogImageUrl = $imgUrl;
    } else {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $ogImageUrl = $baseUrl . (strpos($imgUrl, '/') === 0 ? '' : '/') . ltrim($imgUrl, '/');
    }
} elseif (!empty($product['image'])) {
    $imgUrl = $product['image'];
    if (filter_var($imgUrl, FILTER_VALIDATE_URL)) {
        $ogImageUrl = $imgUrl;
    } else {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $ogImageUrl = $baseUrl . (strpos($imgUrl, '/') === 0 ? '' : '/') . ltrim($imgUrl, '/');
    }
} else {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $defaultImagePath = \App\Core\View::asset('images/products/default.jpg');
    if (filter_var($defaultImagePath, FILTER_VALIDATE_URL)) {
        $ogImageUrl = $defaultImagePath;
    } else {
        $ogImageUrl = $baseUrl . (strpos($defaultImagePath, '/') === 0 ? '' : '/') . ltrim($defaultImagePath, '/');
    }
}

// Ensure HTTPS for OG image
$ogImageUrl = str_replace('http://', 'https://', $ogImageUrl);

// Product data
$productName = htmlspecialchars($product['product_name'] ?? 'Product', ENT_QUOTES, 'UTF-8');
$productDescription = strip_tags($product['description'] ?? $product['short_description'] ?? '');
$productCategory = htmlspecialchars($product['category'] ?? 'Supplements', ENT_QUOTES, 'UTF-8');
$productPrice = isset($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : ($product['price'] ?? 0);
$productCurrency = 'NPR';
$productSku = htmlspecialchars($product['sku'] ?? $product['id'] ?? '', ENT_QUOTES, 'UTF-8');
$productBrand = htmlspecialchars($product['brand'] ?? 'NutriNexus', ENT_QUOTES, 'UTF-8');
$productSlug = htmlspecialchars($product['slug'] ?? $product['id'] ?? '', ENT_QUOTES, 'UTF-8');

// Build absolute product URL
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$productUrl = \App\Core\View::url('products/view/' . $productSlug);
if (strpos($productUrl, 'http') !== 0) {
    $productUrl = $baseUrl . (strpos($productUrl, '/') === 0 ? '' : '/') . ltrim($productUrl, '/');
}
$productUrl = str_replace('http://', 'https://', $productUrl);

// SEO optimized title and description
$seoTitle = $productName . ' - ' . $productCategory . ' | NutriNexus Nepal';
$seoDescription = !empty($productDescription) 
    ? htmlspecialchars(substr($productDescription, 0, 155), ENT_QUOTES, 'UTF-8') 
    : 'Premium ' . $productCategory . ' from NutriNexus. High-quality supplements and health products with fast delivery across Nepal.';

// Availability
$productAvailability = (isset($product['stock_quantity']) && $product['stock_quantity'] > 0) ? 'InStock' : 'OutOfStock';

// Get review data for schema
$reviewModel = new \App\Models\Review();
$avgRating = $reviewModel->getAverageRating($product['id'] ?? 0) ?: 4.5;
$reviewCount = $reviewModel->getReviewCount($product['id'] ?? 0) ?: 0;

// Get additional images for schema
$additionalImages = [];
$allImages = $productImageModel->getByProductId($product['id'] ?? 0);
foreach ($allImages as $img) {
    if (!empty($img['image_url'])) {
        $imgUrl = $img['image_url'];
        if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
            $imgUrl = $baseUrl . (strpos($imgUrl, '/') === 0 ? '' : '/') . ltrim($imgUrl, '/');
        }
        $additionalImages[] = str_replace('http://', 'https://', $imgUrl);
    }
}
if (empty($additionalImages)) {
    $additionalImages[] = $ogImageUrl;
}

// Calculate final price
$finalPrice = $productPrice;
$originalPrice = $product['price'] ?? $productPrice;
if (isset($product['sale_price']) && $product['sale_price'] > 0 && $product['sale_price'] < $originalPrice) {
    $finalPrice = $product['sale_price'];
}
?>

<!-- Essential SEO Meta Tags -->
<meta name="title" content="<?= $seoTitle ?>">
<meta name="description" content="<?= $seoDescription ?>">
<meta name="keywords" content="<?= htmlspecialchars($productName . ', ' . $productCategory . ', supplements Nepal, buy online, NutriNexus, ' . strtolower($productName), ENT_QUOTES, 'UTF-8') ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="author" content="NutriNexus">
<meta name="googlebot" content="index, follow">
<meta name="bingbot" content="index, follow">
<link rel="canonical" href="<?= $productUrl ?>">

<!-- Open Graph Meta Tags (Facebook, LinkedIn, etc.) -->
<meta property="og:type" content="product">
<meta property="og:title" content="<?= $seoTitle ?>">
<meta property="og:description" content="<?= $seoDescription ?>">
<meta property="og:url" content="<?= $productUrl ?>">
<meta property="og:image" content="<?= htmlspecialchars($ogImageUrl, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image:secure_url" content="<?= htmlspecialchars($ogImageUrl, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= htmlspecialchars($productName, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:site_name" content="NutriNexus Nepal">
<meta property="og:locale" content="en_US">
<meta property="og:locale:alternate" content="ne_NP">

<!-- Product-specific Open Graph Tags -->
<meta property="product:price:amount" content="<?= number_format($finalPrice, 2, '.', '') ?>">
<meta property="product:price:currency" content="<?= $productCurrency ?>">
<meta property="product:availability" content="<?= $productAvailability ?>">
<meta property="product:condition" content="new">
<?php if (!empty($productBrand)): ?>
<meta property="product:brand" content="<?= htmlspecialchars($productBrand, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<?php if (!empty($productSku)): ?>
<meta property="product:retailer_item_id" content="<?= htmlspecialchars($productSku, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $seoTitle ?>">
<meta name="twitter:description" content="<?= $seoDescription ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($ogImageUrl, ENT_QUOTES, 'UTF-8') ?>">
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
    "image": <?= json_encode($additionalImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "sku": <?= json_encode($productSku, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "mpn": <?= json_encode($productSku, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "brand": {
        "@type": "Brand",
        "name": <?= json_encode($productBrand, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    },
    "category": <?= json_encode($productCategory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "offers": {
        "@type": "Offer",
        "url": <?= json_encode($productUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        "priceCurrency": "<?= $productCurrency ?>",
        "price": "<?= number_format($finalPrice, 2, '.', '') ?>",
        "priceValidUntil": "<?= date('Y-m-d', strtotime('+1 year')) ?>",
        "availability": "https://schema.org/<?= $productAvailability ?>",
        "itemCondition": "https://schema.org/NewCondition",
        "seller": {
            "@type": "Organization",
            "name": "NutriNexus"
        }
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "<?= number_format($avgRating, 1) ?>",
        "reviewCount": "<?= $reviewCount ?>",
        "bestRating": "5",
        "worstRating": "1"
    }
}
</script>

<!-- Breadcrumb Schema -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "<?= $baseUrl ?>"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "Products",
            "item": "<?= $baseUrl ?>/products"
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": <?= json_encode($productCategory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            "item": "<?= $baseUrl ?>/products/category/<?= urlencode($productCategory) ?>"
        },
        {
            "@type": "ListItem",
            "position": 4,
            "name": <?= json_encode($productName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            "item": <?= json_encode($productUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        }
    ]
}
</script>

