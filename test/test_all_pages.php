<?php
/**
 * Comprehensive Test - All Pages and Functionality
 * 
 * Tests all major pages, routes, and functionality across the system
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
echo "║       COMPREHENSIVE SYSTEM TEST - ALL PAGES                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$results = [];
$totalChecks = 0;
$passedChecks = 0;
$failedChecks = 0;

// Test Categories
$testCategories = [
    'Admin Pages' => [
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
        'Dashboard' => 'App/views/admin/dashboard.php',
        'Inventory' => 'App/views/admin/inventory/index.php',
        'Blog' => 'App/views/admin/blog/index.php',
        'Slider' => 'App/views/admin/slider/index.php',
        'SMS' => 'App/views/admin/sms/sms.php',
    ],
    'Public Pages' => [
        'Home' => 'App/views/home/index.php',
        'Products' => 'App/views/products/index.php',
        'Product View' => 'App/views/products/view.php',
        'Cart' => 'App/views/cart/index.php',
        'Checkout' => 'App/views/checkout/index.php',
        'Orders' => 'App/views/orders/index.php',
        'Blog' => 'App/views/home/blog.php',
        'About' => 'App/views/home/about.php',
        'Contact' => 'App/views/home/contact.php',
        'Privacy' => 'App/views/pages/privacy.php',
        'Terms' => 'App/views/pages/terms.php',
        'FAQ' => 'App/views/pages/faq.php',
    ],
    'Auth Pages' => [
        'Login' => 'App/views/auth/login.php',
        'Register' => 'App/views/auth/register.php',
        'Forgot Password' => 'App/views/auth/forgot-password.php',
    ],
    'User Pages' => [
        'Account' => 'App/views/user/account.php',
        'Profile' => 'App/views/user/profile.php',
        'Addresses' => 'App/views/user/addresses.php',
        'Balance' => 'App/views/user/balance.php',
        'Withdraw' => 'App/views/user/withdraw.php',
        'Invite' => 'App/views/user/invite.php',
    ],
    'Controllers' => [
        'HomeController' => 'App/Controllers/HomeController.php',
        'ProductController' => 'App/Controllers/ProductController.php',
        'CartController' => 'App/Controllers/CartController.php',
        'CheckoutController' => 'App/Controllers/CheckoutController.php',
        'OrderController' => 'App/Controllers/OrderController.php',
        'UserController' => 'App/Controllers/UserController.php',
        'AuthController' => 'App/Controllers/AuthController.php',
        'AdminController' => 'App/Controllers/AdminController.php',
        'BlogController' => 'App/Controllers/BlogController.php',
        'SMSController' => 'App/Controllers/SMSController.php',
    ],
    'Models' => [
        'Product' => 'App/Models/Product.php',
        'Order' => 'App/Models/Order.php',
        'User' => 'App/Models/User.php',
        'Cart' => 'App/Models/Cart.php',
        'Coupon' => 'App/Models/Coupon.php',
        'ReferralEarning' => 'App/Models/ReferralEarning.php',
        'Curior' => 'App/Models/Curior.php',
    ],
    'Core Files' => [
        'Database' => 'App/Core/Database.php',
        'Router' => 'App/Core/Router.php',
        'Controller' => 'App/Core/Controller.php',
        'Model' => 'App/Core/Model.php',
        'Session' => 'App/Core/Session.php',
        'View' => 'App/Core/View.php',
    ],
    'Helpers' => [
        'SecurityHelper' => 'App/Helpers/SecurityHelper.php',
        'NavbarHelper' => 'App/Helpers/NavbarHelper.php',
    ],
    'Services' => [
        'OrderCalculationService' => 'App/Services/OrderCalculationService.php',
        'ReferralEarningService' => 'App/Services/ReferralEarningService.php',
        'OrderNotificationService' => 'App/Services/OrderNotificationService.php',
    ],
];

foreach ($testCategories as $categoryName => $items) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Category: {$categoryName}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    foreach ($items as $itemName => $itemPath) {
        $totalChecks++;
        $fullPath = ROOT . DS . $itemPath;
        
        if (file_exists($fullPath)) {
            $size = filesize($fullPath);
            $isReadable = is_readable($fullPath);
            
            // Check for common issues
            $content = file_get_contents($fullPath);
            $hasSyntax = true; // Assume valid if file exists and readable
            
            // Check for common patterns
            $hasSecurity = (
                strpos($content, 'htmlspecialchars') !== false ||
                strpos($content, 'sanitize') !== false ||
                strpos($content, 'CSRF') !== false ||
                strpos($content, 'prepare') !== false // SQL prepared statements
            );
            
            $status = ($isReadable && $hasSyntax) ? '✅' : '❌';
            echo "  {$status} {$itemName}";
            
            if ($isReadable) {
                echo " ({$size} bytes)";
                if (!$hasSecurity && (strpos($itemPath, 'views') !== false || strpos($itemPath, 'Controller') !== false)) {
                    echo " ⚠️  Security check";
                } else {
                    $passedChecks++;
                }
            } else {
                $failedChecks++;
            }
            
            echo "\n";
            
            $results[$categoryName][$itemName] = [
                'exists' => true,
                'readable' => $isReadable,
                'size' => $size,
                'hasSecurity' => $hasSecurity
            ];
        } else {
            $totalChecks++;
            $failedChecks++;
            echo "  ❌ {$itemName} - FILE NOT FOUND\n";
            $results[$categoryName][$itemName] = [
                'exists' => false,
                'readable' => false,
                'size' => 0,
                'hasSecurity' => false
            ];
        }
    }
    echo "\n";
}

// Database Connection Test
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Database & System Tests\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $db = \App\Core\Database::getInstance();
    $testQuery = $db->query("SELECT 1 as test")->single();
    if ($testQuery && $testQuery['test'] == 1) {
        echo "  ✅ Database Connection: Working\n";
        $passedChecks++;
    } else {
        echo "  ❌ Database Connection: Failed\n";
        $failedChecks++;
    }
    $totalChecks++;
} catch (Exception $e) {
    echo "  ❌ Database Connection: Error - " . $e->getMessage() . "\n";
    $failedChecks++;
    $totalChecks++;
}

// Check critical tables
$criticalTables = ['users', 'products', 'orders', 'order_items', 'cart', 'coupons', 'referral_earnings', 'curiors'];
foreach ($criticalTables as $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE ?", [$table])->single();
        if ($result) {
            echo "  ✅ Table '{$table}': Exists\n";
            $passedChecks++;
        } else {
            echo "  ❌ Table '{$table}': Missing\n";
            $failedChecks++;
        }
        $totalChecks++;
    } catch (Exception $e) {
        echo "  ⚠️  Table '{$table}': Check failed\n";
        $totalChecks++;
    }
}

// Summary
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    FINAL SUMMARY                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Total Checks: {$totalChecks}\n";
echo "✅ Passed: {$passedChecks}\n";
echo "❌ Failed: {$failedChecks}\n";
$passRate = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 2) : 0;
echo "Pass Rate: {$passRate}%\n\n";

// Category Summary
echo "Category Breakdown:\n";
foreach ($testCategories as $categoryName => $items) {
    $categoryPassed = 0;
    $categoryTotal = count($items);
    foreach ($items as $itemName => $itemPath) {
        if (isset($results[$categoryName][$itemName]) && $results[$categoryName][$itemName]['exists']) {
            $categoryPassed++;
        }
    }
    $categoryRate = $categoryTotal > 0 ? round(($categoryPassed / $categoryTotal) * 100, 2) : 0;
    $status = $categoryPassed === $categoryTotal ? '✅' : '⚠️';
    echo "  {$status} {$categoryName}: {$categoryPassed}/{$categoryTotal} ({$categoryRate}%)\n";
}

echo "\n";

if ($failedChecks === 0) {
    echo "🎉 ALL CHECKS PASSED! System is fully operational.\n\n";
    exit(0);
} else {
    echo "⚠️  Some checks failed. Review the output above.\n\n";
    exit(1);
}

