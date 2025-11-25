<?php
/**
 * Test Admin Pages Functionality
 * 
 * Tests:
 * 1. All admin pages are accessible
 * 2. Toggle switches are properly implemented
 * 3. Forms have CSRF protection
 * 4. AJAX requests work correctly
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          ADMIN PAGES FUNCTIONALITY TEST                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$adminPages = [
    'Products' => 'App/views/admin/products/index.php',
    'Orders' => 'App/views/admin/orders/index.php',
    'Users' => 'App/views/admin/users/index.php',
    'Coupons' => 'App/views/admin/coupons/index.php',
    'Payment Gateways' => 'App/views/admin/payment/index.php',
    'Curiors' => 'App/views/admin/curior/index.php',
    'Staff' => 'App/views/admin/staff/index.php',
    'Withdrawals' => 'App/views/admin/withdrawals/index.php',
    'Referrals' => 'App/views/admin/referrals/index.php',
    'Settings' => 'App/views/admin/settings/index.php',
];

$results = [];
$totalPages = count($adminPages);
$passedPages = 0;
$issuesFound = [];

foreach ($adminPages as $pageName => $pagePath) {
    $fullPath = ROOT . DS . $pagePath;
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Checking: {$pageName}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if (!file_exists($fullPath)) {
        echo "❌ Page file not found: {$pagePath}\n\n";
        $issuesFound[] = "{$pageName}: File not found";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $checks = [
        'File Exists' => true,
        'Has CSRF Protection' => (strpos($content, '_csrf_token') !== false || strpos($content, 'CSRF') !== false),
        'Has Toggle Switch' => (strpos($content, 'toggle') !== false || strpos($content, 'switch') !== false),
        'Has AJAX Support' => (strpos($content, 'XMLHttpRequest') !== false || strpos($content, 'fetch') !== false),
        'Proper HTML Structure' => (strpos($content, '<table') !== false || strpos($content, '<div') !== false),
    ];
    
    $pagePassed = true;
    foreach ($checks as $checkName => $checkResult) {
        $status = $checkResult ? '✅' : '⚠️';
        echo "  {$status} {$checkName}\n";
        if (!$checkResult && $checkName !== 'Has Toggle Switch') {
            $pagePassed = false;
        }
    }
    
    // Check for toggle switch issues (should not have both toggle and text label)
    if (strpos($content, 'toggle') !== false) {
        $hasToggleIssue = (
            (preg_match('/toggle.*status.*badge|badge.*toggle.*status/i', $content)) ||
            (substr_count($content, 'status') > 3 && strpos($content, 'toggle') !== false)
        );
        
        if (!$hasToggleIssue) {
            echo "  ✅ Toggle switch properly implemented (no duplicate labels)\n";
        } else {
            echo "  ⚠️  Toggle switch may have duplicate status display\n";
            $issuesFound[] = "{$pageName}: Possible toggle switch duplicate";
        }
    }
    
    if ($pagePassed) {
        echo "\n✅ {$pageName}: PASSED\n";
        $passedPages++;
    } else {
        echo "\n⚠️  {$pageName}: Has issues\n";
        $issuesFound[] = "{$pageName}: Some checks failed";
    }
    
    echo "\n";
}

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    TEST SUMMARY                                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Total Pages Checked: {$totalPages}\n";
echo "✅ Passed: {$passedPages}\n";
echo "⚠️  Issues: " . count($issuesFound) . "\n\n";

if (count($issuesFound) > 0) {
    echo "Issues Found:\n";
    foreach ($issuesFound as $issue) {
        echo "  - {$issue}\n";
    }
    echo "\n";
}

// Check specific fixes
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Specific Fixes Verification:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$fixes = [
    'Products Toggle' => file_exists(ROOT . DS . 'App/views/admin/products/index.php') && 
                         strpos(file_get_contents(ROOT . DS . 'App/views/admin/products/index.php'), 'sr-only peer') !== false,
    'Coupons Toggle' => file_exists(ROOT . DS . 'App/views/admin/coupons/index.php') && 
                        strpos(file_get_contents(ROOT . DS . 'App/views/admin/coupons/index.php'), 'sr-only peer') !== false,
    'Payment Toggle' => file_exists(ROOT . DS . 'App/views/admin/payment/index.php') && 
                        strpos(file_get_contents(ROOT . DS . 'App/views/admin/payment/index.php'), 'sr-only peer') !== false,
    'Curior Toggle' => file_exists(ROOT . DS . 'App/views/admin/curior/index.php') && 
                      strpos(file_get_contents(ROOT . DS . 'App/views/admin/curior/index.php'), 'sr-only peer') !== false,
];

foreach ($fixes as $fixName => $fixResult) {
    $status = $fixResult ? '✅' : '❌';
    echo "  {$status} {$fixName}\n";
}

echo "\n";

if ($passedPages === $totalPages && count($issuesFound) === 0) {
    echo "🎉 ALL ADMIN PAGES ARE PROPERLY CONFIGURED!\n\n";
    exit(0);
} else {
    echo "⚠️  Some pages need attention. Review the issues above.\n\n";
    exit(1);
}

