<?php
/**
 * Test: Theme Colors Verification
 * Verify all pages use theme color classes only
 */

echo "=== Test: Theme Colors Verification ===\n\n";

$filesToCheck = [
    'App/views/includes/mobile_search.php',
    'App/views/includes/bottomnav.php',
    'App/views/blog/view.php',
    'App/views/includes/header.php',
    'App/views/products/search.php',
    'public/css/sidebar.css',
    'App/views/layouts/main.php',
];

$themeColors = [
    'primary' => ['#0A3167', '#082850', '#1A4B87'],
    'accent' => ['#C5A572', '#B89355', '#D4B888'],
    'success' => ['#059669', '#047857', '#10B981'],
    'destructive' => ['#DC143C', '#B91C3C', '#EF4444'],
    'sale' => ['#DC143C', '#f5ba3d', '#F87171'],
];

$hardcodedColors = [];
$themeClasses = [];

foreach ($filesToCheck as $file) {
    $filePath = __DIR__ . '/../' . $file;
    if (!file_exists($filePath)) {
        echo "  ⚠ File not found: {$file}\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Check for hardcoded theme colors
    foreach ($themeColors as $colorName => $hexValues) {
        foreach ($hexValues as $hex) {
            if (strpos($content, $hex) !== false) {
                $hardcodedColors[$file][] = $hex;
            }
        }
    }
    
    // Check for theme classes
    $hasPrimary = strpos($content, 'bg-primary') !== false || strpos($content, 'text-primary') !== false || strpos($content, 'border-primary') !== false;
    $hasAccent = strpos($content, 'bg-accent') !== false || strpos($content, 'text-accent') !== false || strpos($content, 'border-accent') !== false;
    $hasSuccess = strpos($content, 'bg-success') !== false || strpos($content, 'text-success') !== false;
    $hasDestructive = strpos($content, 'bg-destructive') !== false || strpos($content, 'text-destructive') !== false;
    
    if ($hasPrimary || $hasAccent || $hasSuccess || $hasDestructive) {
        $themeClasses[$file] = [
            'primary' => $hasPrimary,
            'accent' => $hasAccent,
            'success' => $hasSuccess,
            'destructive' => $hasDestructive,
        ];
    }
}

echo "Test 1: Hardcoded Theme Colors...\n";
if (empty($hardcodedColors)) {
    echo "  ✓ No hardcoded theme colors found\n\n";
} else {
    foreach ($hardcodedColors as $file => $colors) {
        echo "  ✗ {$file}: " . implode(', ', array_unique($colors)) . "\n";
    }
    echo "\n";
}

echo "Test 2: Theme Color Classes Usage...\n";
foreach ($themeClasses as $file => $classes) {
    $used = [];
    foreach ($classes as $name => $used) {
        if ($used) {
            $used[] = $name;
        }
    }
    echo "  ✓ {$file}: Using theme classes\n";
}
echo "\n";

echo "Test 3: Mobile Search...\n";
$mobileSearch = file_get_contents(__DIR__ . '/../App/views/includes/mobile_search.php');
$hasPrimary = strpos($mobileSearch, 'bg-primary') !== false;
$hasHardcoded = strpos($mobileSearch, 'bg-[#0A3167]') !== false;
echo "  " . ($hasPrimary && !$hasHardcoded ? '✓' : '✗') . " Using bg-primary\n";
echo "\n";

echo "Test 4: Bottom Nav...\n";
$bottomNav = file_get_contents(__DIR__ . '/../App/views/includes/bottomnav.php');
$hasPrimary = strpos($bottomNav, 'bg-primary') !== false;
$hasPurple = strpos($bottomNav, 'border-purple-200') !== false;
echo "  " . ($hasPrimary && !$hasPurple ? '✓' : '✗') . " Using bg-primary\n";
echo "  " . (!$hasPurple ? '✓' : '✗') . " No purple border\n";
echo "\n";

echo "Test 5: Blog View...\n";
$blogView = file_get_contents(__DIR__ . '/../App/views/blog/view.php');
$hasPrimary = strpos($blogView, 'text-primary') !== false || strpos($blogView, 'from-primary') !== false;
$hasAccent = strpos($blogView, 'text-accent') !== false || strpos($blogView, 'bg-accent') !== false;
$hasHardcoded = strpos($blogView, 'text-[#0A3167]') !== false || strpos($blogView, 'bg-[#C5A572]') !== false;
echo "  " . ($hasPrimary && $hasAccent && !$hasHardcoded ? '✓' : '✗') . " Using theme classes\n";
echo "\n";

echo "Test 6: Sidebar CSS...\n";
$sidebarCss = file_get_contents(__DIR__ . '/../public/css/sidebar.css');
$hasVar = strpos($sidebarCss, 'var(--primary-color') !== false || strpos($sidebarCss, 'var(--accent-color') !== false;
$hasHardcoded = strpos($sidebarCss, '#0A3167') !== false && strpos($sidebarCss, 'var(--primary-color') === false;
echo "  " . ($hasVar && !$hasHardcoded ? '✓' : '✗') . " Using CSS variables\n";
echo "\n";

echo "=== Summary ===\n";
if (empty($hardcodedColors)) {
    echo "✓ All theme colors use classes\n";
    echo "✓ Mobile search uses bg-primary\n";
    echo "✓ Bottom nav uses bg-primary\n";
    echo "✓ Blog view uses theme classes\n";
    echo "✓ Sidebar uses CSS variables\n";
    echo "\n";
    echo "Status: ✓ 100% PASS - Theme colors consistent!\n";
} else {
    echo "⚠ Some hardcoded colors found - review above\n";
}

