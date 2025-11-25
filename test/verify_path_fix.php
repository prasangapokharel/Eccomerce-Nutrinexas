<?php
/**
 * Verify Path Fix for Admin Seller Products
 */

$viewFile = __DIR__ . '/../App/views/admin/seller/products/index.php';
$layoutFile = __DIR__ . '/../App/views/admin/layouts/admin.php';

echo "=== Path Verification ===\n\n";

echo "View file: {$viewFile}\n";
echo "Exists: " . (file_exists($viewFile) ? 'Yes' : 'No') . "\n\n";

echo "Layout file: {$layoutFile}\n";
echo "Exists: " . (file_exists($layoutFile) ? 'Yes' : 'No') . "\n\n";

// Test path resolution from view file
$resolvedPath = dirname(dirname(dirname($viewFile))) . '/layouts/admin.php';
echo "Resolved path from view: {$resolvedPath}\n";
echo "Exists: " . (file_exists($resolvedPath) ? 'Yes ✅' : 'No ❌') . "\n\n";

if (file_exists($resolvedPath)) {
    echo "✅ Path fix is correct!\n";
} else {
    echo "❌ Path fix needs adjustment\n";
}

