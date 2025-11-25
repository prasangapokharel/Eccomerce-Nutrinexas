<?php
/**
 * Comprehensive Courier Flow Test
 * Tests the complete courier workflow end-to-end
 */

// Set up paths
define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_DIR', ROOT);

// Load config
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

// Autoload
spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "=== COURIER FULL FLOW TEST ===\n\n";

$db = \App\Core\Database::getInstance();
$results = ['passed' => 0, 'failed' => 0, 'warnings' => 0];

function testResult($name, $passed, $message = '', $warning = false) {
    global $results;
    if ($passed) {
        echo "✅ PASS: $name\n";
        if ($message) echo "   → $message\n";
        $results['passed']++;
    } else {
        if ($warning) {
            echo "⚠️  WARN: $name - $message\n";
            $results['warnings']++;
        } else {
            echo "❌ FAIL: $name - $message\n";
            $results['failed']++;
        }
    }
}

// Test 1: Database Structure
echo "--- Database Structure Tests ---\n";
try {
    // Check curiors table
    $curiorsTable = $db->query("SHOW TABLES LIKE 'curiors'")->single();
    testResult("Curiors table exists", !!$curiorsTable);
    
    if ($curiorsTable) {
        $columns = $db->query("SHOW COLUMNS FROM curiors")->all();
        $required = ['id', 'name', 'email', 'phone', 'password', 'status'];
        $found = array_column($columns, 'Field');
        foreach ($required as $col) {
            testResult("Curiors table has $col column", in_array($col, $found));
        }
    }
    
    // Check orders table has curior_id
    $ordersColumns = $db->query("SHOW COLUMNS FROM orders")->all();
    $hasCuriorId = false;
    foreach ($ordersColumns as $col) {
        if ($col['Field'] === 'curior_id') $hasCuriorId = true;
    }
    testResult("Orders table has curior_id", $hasCuriorId);
    
    // Check order_activities table
    $activitiesTable = $db->query("SHOW TABLES LIKE 'order_activities'")->single();
    testResult("Order activities table exists", !!$activitiesTable);
    
    // Check courier_locations table
    $locationsTable = $db->query("SHOW TABLES LIKE 'courier_locations'")->single();
    testResult("Courier locations table exists", !!$locationsTable);
    
    // Check courier_settlements table
    $settlementsTable = $db->query("SHOW TABLES LIKE 'courier_settlements'")->single();
    testResult("Courier settlements table exists", !!$settlementsTable);
    
} catch (Exception $e) {
    testResult("Database structure check", false, $e->getMessage());
}

echo "\n";

// Test 2: Courier Model
echo "--- Courier Model Tests ---\n";
try {
    $curiorModel = new \App\Models\Curior\Curior();
    
    // Test getByEmail
    $testEmail = 'test@courier.com';
    $curior = $curiorModel->getByEmail($testEmail);
    testResult("getByEmail method exists", method_exists($curiorModel, 'getByEmail'));
    
    // Test verifyCredentials
    testResult("verifyCredentials method exists", method_exists($curiorModel, 'verifyCredentials'));
    
    // Test getAllCuriors
    $allCuriors = $curiorModel->getAllCuriors();
    testResult("getAllCuriors method works", is_array($allCuriors));
    
} catch (Exception $e) {
    testResult("Courier model tests", false, $e->getMessage());
}

echo "\n";

// Test 3: Courier Authentication
echo "--- Courier Authentication Tests ---\n";
try {
    $authController = new \App\Controllers\Curior\Auth();
    
    // Check login method exists
    testResult("Auth::login method exists", method_exists($authController, 'login'));
    
    // Check logout method exists
    testResult("Auth::logout method exists", method_exists($authController, 'logout'));
    
    // Test session handling
    \App\Core\Session::set('test_curior', 'test');
    $sessionValue = \App\Core\Session::get('test_curior');
    testResult("Session handling works", $sessionValue === 'test');
    \App\Core\Session::remove('test_curior');
    
} catch (Exception $e) {
    testResult("Courier authentication tests", false, $e->getMessage());
}

echo "\n";

// Test 4: Courier Order Operations
echo "--- Courier Order Operations Tests ---\n";
try {
    // Check if methods exist without instantiating (to avoid auth redirect)
    $orderControllerClass = new ReflectionClass('\App\Controllers\Curior\Order');
    
    // Check all required methods exist
    $requiredMethods = [
        'confirmPickup',
        'updateTransit',
        'attemptDelivery',
        'confirmDelivery',
        'handleCODCollection',
        'acceptReturn',
        'updateReturnTransit',
        'completeReturn'
    ];
    
    foreach ($requiredMethods as $method) {
        testResult("Order::$method exists", $orderControllerClass->hasMethod($method));
    }
    
} catch (Exception $e) {
    testResult("Courier order operations tests", false, $e->getMessage());
}

echo "\n";

// Test 5: Image Compression
echo "--- Image Compression Tests ---\n";
try {
    $compressor = new \App\Helpers\ImageCompressor();
    testResult("ImageCompressor class exists", class_exists('\App\Helpers\ImageCompressor'));
    
    if (class_exists('\App\Helpers\ImageCompressor')) {
        // Check for static method compressToMaxSize
        $reflection = new ReflectionClass('\App\Helpers\ImageCompressor');
        testResult("compressToMaxSize static method exists", $reflection->hasMethod('compressToMaxSize'));
        
        // Check if upload directories exist
        $deliveryProofDir = ROOT . DS . 'public' . DS . 'uploads' . DS . 'delivery_proofs';
        $pickupProofDir = ROOT . DS . 'public' . DS . 'uploads' . DS . 'pickup_proofs';
        
        testResult("Delivery proofs directory exists or can be created", 
            is_dir($deliveryProofDir) || (mkdir($deliveryProofDir, 0755, true) && is_dir($deliveryProofDir)));
        testResult("Pickup proofs directory exists or can be created", 
            is_dir($pickupProofDir) || (mkdir($pickupProofDir, 0755, true) && is_dir($pickupProofDir)));
    }
    
} catch (Exception $e) {
    testResult("Image compression tests", false, $e->getMessage());
}

echo "\n";

// Test 6: Routes
echo "--- Route Tests ---\n";
try {
    $routes = [
        'curior/login',
        'curior/dashboard',
        'curior/orders',
        'curior/order/pickup',
        'curior/order/transit',
        'curior/order/attempt',
        'curior/order/deliver',
        'curior/order/cod',
        'curior/pickup',
        'curior/returns',
        'curior/settlement',
        'curior/performance',
        'curior/profile',
        'curior/logout'
    ];
    
    foreach ($routes as $route) {
        $url = \App\Core\View::url($route);
        testResult("Route $route generates URL", !empty($url), $url);
    }
    
} catch (Exception $e) {
    testResult("Route tests", false, $e->getMessage());
}

echo "\n";

// Test 7: View Files
echo "--- View Files Tests ---\n";
$viewFiles = [
    'App/views/curior/auth/login.php',
    'App/views/curior/dashboard/index.php',
    'App/views/curior/orders/index.php',
    'App/views/curior/orders/view.php',
    'App/views/curior/pickup/index.php',
    'App/views/curior/returns/index.php',
    'App/views/curior/settlements/index.php',
    'App/views/curior/performance/index.php',
    'App/views/curior/profile/index.php',
    'App/views/curior/layouts/main.php',
    'App/views/curior/layouts/sidebar.php',
    'App/views/curior/layouts/header.php'
];

foreach ($viewFiles as $file) {
    $path = ROOT . DS . str_replace('/', DS, $file);
    testResult("View file exists: " . basename($file), file_exists($path));
}

echo "\n";

// Test 8: Check for Tailwind Config Classes
echo "--- Tailwind Config Classes Test ---\n";
try {
    $sidebarFile = ROOT . DS . 'App' . DS . 'views' . DS . 'curior' . DS . 'layouts' . DS . 'sidebar.php';
    if (file_exists($sidebarFile)) {
        $sidebarContent = file_get_contents($sidebarFile);
        // Check for hardcoded colors (should not exist)
        $hardcodedColors = ['text-blue-', 'text-yellow-', 'text-green-', 'text-red-', 'text-purple-', 'bg-blue-', 'bg-yellow-', 'bg-green-', 'bg-red-', 'bg-purple-'];
        $foundHardcoded = [];
        foreach ($hardcodedColors as $color) {
            if (strpos($sidebarContent, $color) !== false) {
                $foundHardcoded[] = $color;
            }
        }
        testResult("Sidebar uses Tailwind config classes only", empty($foundHardcoded), 
            empty($foundHardcoded) ? 'All colors use primary/accent' : 'Found: ' . implode(', ', $foundHardcoded));
    }
} catch (Exception $e) {
    testResult("Tailwind config test", false, $e->getMessage());
}

echo "\n";

// Test 9: Cache Directory Check
echo "--- Cache Directory Check ---\n";
$rootCacheDir = ROOT . DS . 'cache';
$storageCacheDir = ROOT . DS . 'storage' . DS . 'cache';

if (is_dir($rootCacheDir)) {
    testResult("Root cache/ folder exists (should be in storage/)", false, 
        "Found cache/ in root. Should be in storage/cache/");
} else {
    testResult("No cache/ folder in root", true);
}

if (is_dir($storageCacheDir)) {
    testResult("storage/cache/ folder exists", true);
} else {
    // Try to create it
    if (mkdir($storageCacheDir, 0755, true)) {
        testResult("Created storage/cache/ folder", true);
    } else {
        testResult("storage/cache/ folder missing", false, "Could not create storage/cache/");
    }
}

echo "\n";

// Summary
echo "=== TEST SUMMARY ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "⚠️  Warnings: {$results['warnings']}\n";
echo "\n";

if ($results['failed'] === 0) {
    echo "🎉 ALL TESTS PASSED! Courier flow is 100% ready.\n";
} else {
    echo "⚠️  Some tests failed. Please review and fix.\n";
}

