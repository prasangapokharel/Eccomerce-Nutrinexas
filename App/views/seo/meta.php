<?php
// Enhanced SEO Meta Tags Configuration for NutriNexas.shop
// This version includes better product-specific handling

// Default SEO values
$default_title = "NutriNexas Nepal - Top Supplements Store | MuscleBlaze, Wellcore, Protein & Vitamins";
$default_description = "NutriNexas is Nepal's leading online store for premium nutrition supplements. Discover authentic MuscleBlaze, Wellcore, protein powders, vitamins, and health products with fast delivery across Nepal.";
$default_keywords = "NutriNexas, supplements Nepal, protein powder Nepal, MuscleBlaze Nepal, Wellcore Nepal, authentic supplements Nepal, nutrition store Kathmandu";
$default_image = ASSETS_URL . '/images/store-image.jpg';
$site_name = "NutriNexas";
$twitter_handle = "@nutrinexas";

// Page-specific overrides
$page_title = isset($page_title) ? $page_title : $default_title;
$meta_description = isset($meta_description) ? $meta_description : $default_description;
$meta_keywords = isset($meta_keywords) ? $meta_keywords : $default_keywords;
$og_image = isset($og_image) ? $og_image : $default_image;
$canonical_url = isset($canonical_url) ? $canonical_url : "https://nutrinexas.shop" . $_SERVER['REQUEST_URI'];
$page_type = isset($page_type) ? $page_type : "website";

// Product-specific variables with better defaults
$product_name = isset($product_name) ? $product_name : "";
$product_price = isset($product_price) ? $product_price : "";
$product_currency = isset($product_currency) ? $product_currency : "NPR";
$product_availability = isset($product_availability) ? $product_availability : "InStock";
$product_condition = isset($product_condition) ? $product_condition : "NewCondition";
$product_brand = isset($product_brand) ? $product_brand : "NutriNexas";
$product_sku = isset($product_sku) ? $product_sku : "";
$product_rating = isset($product_rating) ? $product_rating : "4.5";
$product_review_count = isset($product_review_count) ? $product_review_count : "0";
?>

<!-- Essential Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- SEO Meta Tags -->
<title><?= htmlspecialchars($page_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
<meta name="keywords" content="<?= htmlspecialchars($meta_keywords) ?>">
<meta name="author" content="NutriNexas Team">
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">

<!-- Open Graph Meta Tags -->
<meta property="og:locale" content="en_US">
<meta property="og:type" content="<?= htmlspecialchars($page_type) ?>">
<meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_description) ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
<meta property="og:site_name" content="<?= htmlspecialchars($site_name) ?>">
<meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="<?= htmlspecialchars($twitter_handle) ?>">
<meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta_description) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">


<!-- Favicon Links -->
<link rel="apple-touch-icon" sizes="180x180" href="<?= ASSETS_URL ?>/images/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= ASSETS_URL ?>/images/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= ASSETS_URL ?>/images/favicon/favicon-16x16.png">
<link rel="manifest" href="<?= ASSETS_URL ?>/images/favicon/site.webmanifest">
<link rel="mask-icon" href="<?= ASSETS_URL ?>/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#da532c">
<meta name="theme-color" content="#ffffff">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="icon" href="<?= ASSETS_URL ?>/images/favicon/icon.png" type="image/x-icon">


<!-- Additional SEO Tags -->
<meta name="geo.region" content="NP">
<meta name="geo.country" content="Nepal">
<meta name="language" content="en-US">
<meta name="distribution" content="global">
<meta name="rating" content="general">
<meta name="revisit-after" content="1 days">

<!-- Product-specific Schema.org JSON-LD -->
<?php if ($page_type === "product" && !empty($product_name)): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "<?= htmlspecialchars($product_name) ?>",
  "image": "<?= htmlspecialchars($og_image) ?>",
  "description": "<?= htmlspecialchars($meta_description) ?>",
  "brand": {
    "@type": "Brand",
    "name": "<?= htmlspecialchars($product_brand) ?>"
  },
  <?php if (!empty($product_sku)): ?>
  "sku": "<?= htmlspecialchars($product_sku) ?>",
  <?php endif; ?>
  "offers": {
    "@type": "Offer",
    "url": "<?= htmlspecialchars($canonical_url) ?>",
    "priceCurrency": "<?= htmlspecialchars($product_currency) ?>",
    "price": "<?= htmlspecialchars($product_price) ?>",
    "availability": "https://schema.org/<?= htmlspecialchars($product_availability) ?>",
    "itemCondition": "https://schema.org/<?= htmlspecialchars($product_condition) ?>",
    "seller": {
      "@type": "Organization",
      "name": "NutriNexas"
    }
  }
  <?php if ($product_review_count > 0): ?>
  ,"aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?= htmlspecialchars($product_rating) ?>",
    "reviewCount": "<?= htmlspecialchars($product_review_count) ?>"
  }
  <?php endif; ?>
}
</script>
<?php endif; ?>

<!-- Organization Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "NutriNexas",
  "url": "https://nutrinexas.shop/",
  "logo": "https://nutrinexas.shop/logo.svg",
  "sameAs": [
    "https://facebook.com/nutrinexas",
    "https://instagram.com/nutrinexasnp"
  ]
}
</script>
