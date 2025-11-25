<?php
// Get the base URL for the application
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host;

// Default meta values
$siteName = 'Nutrinexus';
$defaultTitle = 'Nutrinexus - Premium Nutrition & Wellness Products';
$defaultDescription = 'Discover premium nutrition and wellness products at Nutrinexus. Quality supplements, health foods, and wellness solutions for a healthier lifestyle.';
$defaultKeywords = 'nutrition, wellness, supplements, health, fitness, organic, natural products';

// Get current page info for dynamic meta tags
$currentPage = $_SERVER['REQUEST_URI'];
$pageTitle = isset($pageTitle) ? $pageTitle : $defaultTitle;
$pageDescription = isset($pageDescription) ? $pageDescription : $defaultDescription;
$pageKeywords = isset($pageKeywords) ? $pageKeywords : $defaultKeywords;
$pageImage = isset($pageImage) ? $pageImage : $baseUrl . '/images/logo/logo.svg';
?>

<!-- Basic Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php echo htmlspecialchars($pageTitle); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
<meta name="author" content="Nutrinexus">
<meta name="robots" content="index, follow">

<!-- Favicon Links -->
<link rel="icon" type="image/x-icon" href="<?php echo $baseUrl; ?>/favicon.ico">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $baseUrl; ?>/images/favicon/favicon-16x16.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $baseUrl; ?>/images/favicon/favicon-32x32.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $baseUrl; ?>/images/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="<?php echo $baseUrl; ?>/images/favicon/android-chrome-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="<?php echo $baseUrl; ?>/images/favicon/android-chrome-512x512.png">
<link rel="manifest" href="<?php echo $baseUrl; ?>/images/favicon/site.webmanifest">

<!-- Open Graph Meta Tags -->
<meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($pageImage); ?>">
<meta property="og:url" content="<?php echo $baseUrl . $currentPage; ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo $siteName; ?>">

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($pageImage); ?>">

<!-- Additional SEO Meta Tags -->
<meta name="theme-color" content="#0A3167">
<meta name="msapplication-TileColor" content="#0A3167">
<meta name="msapplication-config" content="<?php echo $baseUrl; ?>/images/favicon/browserconfig.xml">

<!-- Canonical URL -->
<link rel="canonical" href="<?php echo $baseUrl . $currentPage; ?>">

<!-- Preconnect for Performance -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>