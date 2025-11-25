<?php
/**
 * Test Seller Features
 * Tests all seller functionality including wallet, withdrawals, support, notifications, reviews
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../App/Core/Database.php';

use App\Core\Database;

echo "=== Testing Seller Features ===\n\n";

try {
    $db = Database::getInstance();
    echo "✅ Database connection successful\n\n";
    
    // Test 1: Check all seller tables exist
    echo "Test 1: Checking seller tables...\n";
    $requiredTables = [
        'seller_wallet',
        'seller_wallet_transactions',
        'seller_withdraw_requests',
        'seller_bank_accounts',
        'seller_support_tickets',
        'seller_ticket_replies',
        'seller_notifications',
        'seller_settings',
        'seller_reviews'
    ];
    
    $allTables = $db->query("SHOW TABLES")->all();
    $tableNames = [];
    foreach ($allTables as $table) {
        $tableNames[] = array_values($table)[0];
    }
    
    $allTablesExist = true;
    foreach ($requiredTables as $table) {
        if (in_array($table, $tableNames)) {
            echo "  ✓ $table exists\n";
        } else {
            echo "  ✗ $table MISSING\n";
            $allTablesExist = false;
        }
    }
    
    if ($allTablesExist) {
        echo "✅ All seller tables exist\n\n";
    } else {
        echo "❌ Some seller tables are missing\n\n";
    }
    
    // Test 2: Check seller_id in shared tables
    echo "Test 2: Checking seller_id in shared tables...\n";
    $sharedTables = ['products', 'orders', 'coupons'];
    $allHaveSellerId = true;
    
    foreach ($sharedTables as $table) {
        $columns = $db->query("SHOW COLUMNS FROM `$table` LIKE 'seller_id'")->single();
        if ($columns) {
            echo "  ✓ $table has seller_id column\n";
        } else {
            echo "  ✗ $table MISSING seller_id column\n";
            $allHaveSellerId = false;
        }
    }
    
    if ($allHaveSellerId) {
        echo "✅ All shared tables have seller_id column\n\n";
    } else {
        echo "❌ Some shared tables are missing seller_id\n\n";
    }
    
    // Test 3: Check controllers exist
    echo "Test 3: Checking seller controllers...\n";
    $controllers = [
        'App/Controllers/Seller/Wallet.php',
        'App/Controllers/Seller/WithdrawRequests.php',
        'App/Controllers/Seller/Support.php',
        'App/Controllers/Seller/Notifications.php',
        'App/Controllers/Seller/Reviews.php'
    ];
    
    $allControllersExist = true;
    foreach ($controllers as $controller) {
        $path = __DIR__ . '/../' . $controller;
        if (file_exists($path)) {
            echo "  ✓ $controller exists\n";
        } else {
            echo "  ✗ $controller MISSING\n";
            $allControllersExist = false;
        }
    }
    
    if ($allControllersExist) {
        echo "✅ All seller controllers exist\n\n";
    } else {
        echo "❌ Some controllers are missing\n\n";
    }
    
    // Test 4: Check views exist
    echo "Test 4: Checking seller views...\n";
    $views = [
        'App/views/seller/wallet/index.php',
        'App/views/seller/wallet/transactions.php',
        'App/views/seller/withdraw-requests/index.php',
        'App/views/seller/withdraw-requests/create.php',
        'App/views/seller/withdraw-requests/detail.php',
        'App/views/seller/support/index.php',
        'App/views/seller/support/create.php',
        'App/views/seller/support/detail.php',
        'App/views/seller/notifications/index.php',
        'App/views/seller/reviews/index.php'
    ];
    
    $allViewsExist = true;
    foreach ($views as $view) {
        $path = __DIR__ . '/../' . $view;
        if (file_exists($path)) {
            echo "  ✓ $view exists\n";
        } else {
            echo "  ✗ $view MISSING\n";
            $allViewsExist = false;
        }
    }
    
    if ($allViewsExist) {
        echo "✅ All seller views exist\n\n";
    } else {
        echo "❌ Some views are missing\n\n";
    }
    
    // Test 5: Check routes in App.php
    echo "Test 5: Checking routes in App.php...\n";
    $appFile = __DIR__ . '/../App/Core/App.php';
    $appContent = file_get_contents($appFile);
    
    $requiredRoutes = [
        'seller/wallet',
        'seller/wallet/transactions',
        'seller/withdraw-requests',
        'seller/withdraw-requests/create',
        'seller/support',
        'seller/support/create',
        'seller/notifications',
        'seller/reviews'
    ];
    
    $allRoutesExist = true;
    foreach ($requiredRoutes as $route) {
        if (strpos($appContent, $route) !== false) {
            echo "  ✓ Route '$route' exists\n";
        } else {
            echo "  ✗ Route '$route' MISSING\n";
            $allRoutesExist = false;
        }
    }
    
    if ($allRoutesExist) {
        echo "✅ All seller routes exist\n\n";
    } else {
        echo "❌ Some routes are missing\n\n";
    }
    
    // Test 6: Check sidebar has new menu items
    echo "Test 6: Checking seller sidebar menu items...\n";
    $sidebarFile = __DIR__ . '/../App/views/seller/layouts/sidebar.php';
    $sidebarContent = file_get_contents($sidebarFile);
    
    $requiredMenuItems = [
        'seller/wallet',
        'seller/reviews',
        'seller/support',
        'seller/notifications'
    ];
    
    $allMenuItemsExist = true;
    foreach ($requiredMenuItems as $item) {
        if (strpos($sidebarContent, $item) !== false) {
            echo "  ✓ Menu item '$item' exists\n";
        } else {
            echo "  ✗ Menu item '$item' MISSING\n";
            $allMenuItemsExist = false;
        }
    }
    
    if ($allMenuItemsExist) {
        echo "✅ All seller menu items exist\n\n";
    } else {
        echo "❌ Some menu items are missing\n\n";
    }
    
    // Summary
    echo "=== Test Summary ===\n";
    $allPassed = $allTablesExist && $allHaveSellerId && $allControllersExist && 
                 $allViewsExist && $allRoutesExist && $allMenuItemsExist;
    
    if ($allPassed) {
        echo "✅ ALL TESTS PASSED!\n";
        echo "\nSeller features are fully implemented and ready to use.\n";
    } else {
        echo "❌ SOME TESTS FAILED\n";
        echo "Please review the errors above and fix them.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

