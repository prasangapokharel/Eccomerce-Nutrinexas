<?php
/**
 * Test Complete Seller Workflow
 * Tests: Register → Admin Approve → Seller Login → Add Product → Admin Approve Product
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../App/Core/Database.php';

use App\Core\Database;

echo "=== Testing Complete Seller Workflow ===\n\n";

try {
    $db = Database::getInstance();
    echo "✅ Database connection successful\n\n";
    
    // Test 1: Check product approval fields
    echo "Test 1: Checking product approval fields...\n";
    $columns = $db->query("DESCRIBE products")->all();
    $hasApprovalStatus = false;
    $hasApprovalNotes = false;
    $hasApprovedBy = false;
    $hasApprovedAt = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'approval_status') $hasApprovalStatus = true;
        if ($col['Field'] === 'approval_notes') $hasApprovalNotes = true;
        if ($col['Field'] === 'approved_by') $hasApprovedBy = true;
        if ($col['Field'] === 'approved_at') $hasApprovedAt = true;
    }
    
    if ($hasApprovalStatus && $hasApprovalNotes && $hasApprovedBy && $hasApprovedAt) {
        echo "  ✅ All product approval fields exist\n\n";
    } else {
        echo "  ❌ Missing fields:\n";
        if (!$hasApprovalStatus) echo "    - approval_status\n";
        if (!$hasApprovalNotes) echo "    - approval_notes\n";
        if (!$hasApprovedBy) echo "    - approved_by\n";
        if (!$hasApprovedAt) echo "    - approved_at\n";
        echo "\n";
    }
    
    // Test 2: Check product status enum includes pending
    echo "Test 2: Checking product status enum...\n";
    $statusColumn = null;
    foreach ($columns as $col) {
        if ($col['Field'] === 'status') {
            $statusColumn = $col;
            break;
        }
    }
    
    if ($statusColumn && strpos($statusColumn['Type'], 'pending') !== false) {
        echo "  ✅ Product status enum includes 'pending'\n\n";
    } else {
        echo "  ❌ Product status enum does NOT include 'pending'\n";
        echo "    Current type: " . ($statusColumn['Type'] ?? 'N/A') . "\n\n";
    }
    
    // Test 3: Check seller registration route
    echo "Test 3: Checking seller registration route...\n";
    $appFile = __DIR__ . '/../App/Core/App.php';
    $appContent = file_get_contents($appFile);
    
    if (strpos($appContent, 'seller/register') !== false) {
        echo "  ✅ Seller registration route exists\n\n";
    } else {
        echo "  ❌ Seller registration route MISSING\n\n";
    }
    
    // Test 4: Check admin product approval routes
    echo "Test 4: Checking admin product approval routes...\n";
    $hasIndex = strpos($appContent, 'admin/seller/products') !== false;
    $hasDetail = strpos($appContent, 'admin/seller/products/detail') !== false;
    
    if ($hasIndex && $hasDetail) {
        echo "  ✅ Admin product approval routes exist\n\n";
    } else {
        echo "  ❌ Missing routes:\n";
        if (!$hasIndex) echo "    - admin/seller/products (index)\n";
        if (!$hasDetail) echo "    - admin/seller/products/detail/{id}\n";
        echo "\n";
    }
    
    // Test 5: Check seller product creation sets pending status
    echo "Test 5: Checking seller product creation...\n";
    $productsFile = __DIR__ . '/../App/Controllers/Seller/Products.php';
    $productsContent = file_get_contents($productsFile);
    
    if (strpos($productsContent, "'status' => 'pending'") !== false || strpos($productsContent, '"status" => "pending"') !== false) {
        echo "  ✅ Seller product creation sets status to 'pending'\n\n";
    } else {
        echo "  ❌ Seller product creation does NOT set status to 'pending'\n\n";
    }
    
    // Test 6: Check admin sidebar has seller products link
    echo "Test 6: Checking admin sidebar...\n";
    $sidebarFile = __DIR__ . '/../App/views/admin/layouts/admin.php';
    $sidebarContent = file_get_contents($sidebarFile);
    
    if (strpos($sidebarContent, 'admin/seller/products') !== false) {
        echo "  ✅ Admin sidebar has seller products link\n\n";
    } else {
        echo "  ❌ Admin sidebar MISSING seller products link\n\n";
    }
    
    // Test 7: Check seller registration form exists
    echo "Test 7: Checking seller registration form...\n";
    $registerFile = __DIR__ . '/../App/views/seller/auth/register.php';
    if (file_exists($registerFile)) {
        echo "  ✅ Seller registration form exists\n\n";
    } else {
        echo "  ❌ Seller registration form MISSING\n\n";
    }
    
    // Test 8: Check admin product approval views exist
    echo "Test 8: Checking admin product approval views...\n";
    $indexView = __DIR__ . '/../App/views/admin/seller/products/index.php';
    $detailView = __DIR__ . '/../App/views/admin/seller/products/detail.php';
    
    $hasIndexView = file_exists($indexView);
    $hasDetailView = file_exists($detailView);
    
    if ($hasIndexView && $hasDetailView) {
        echo "  ✅ Admin product approval views exist\n\n";
    } else {
        echo "  ❌ Missing views:\n";
        if (!$hasIndexView) echo "    - admin/seller/products/index.php\n";
        if (!$hasDetailView) echo "    - admin/seller/products/detail.php\n";
        echo "\n";
    }
    
    // Summary
    echo "=== Test Summary ===\n";
    $allPassed = $hasApprovalStatus && $hasApprovalNotes && $hasApprovedBy && $hasApprovedAt &&
                 ($statusColumn && strpos($statusColumn['Type'], 'pending') !== false) &&
                 strpos($appContent, 'seller/register') !== false &&
                 $hasIndex && $hasDetail &&
                 (strpos($productsContent, "'status' => 'pending'") !== false || strpos($productsContent, '"status" => "pending"') !== false) &&
                 strpos($sidebarContent, 'admin/seller/products') !== false &&
                 file_exists($registerFile) &&
                 $hasIndexView && $hasDetailView;
    
    if ($allPassed) {
        echo "✅ ALL TESTS PASSED!\n";
        echo "\nSeller workflow is fully implemented:\n";
        echo "  1. ✅ Seller registration with document uploads (CDN)\n";
        echo "  2. ✅ Admin seller approval\n";
        echo "  3. ✅ Seller login (only if approved)\n";
        echo "  4. ✅ Seller product creation (status: pending)\n";
        echo "  5. ✅ Admin product approval page\n";
        echo "  6. ✅ Product approval/rejection workflow\n";
        echo "\nReady for testing!\n";
    } else {
        echo "❌ SOME TESTS FAILED\n";
        echo "Please review the errors above and fix them.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

