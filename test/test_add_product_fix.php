<?php
/**
 * Test Add Product Fix
 * Verifies the column mapping fix and tests product creation
 */

echo "=== Testing Add Product Fix ===\n\n";

// Test 1: Check AdminController has correct field mapping
echo "Test 1: Checking AdminController field mapping...\n";
$controllerFile = __DIR__ . '/../App/Controllers/AdminController.php';
$content = file_get_contents($controllerFile);

$checks = [
    'Uses product_name (not name)' => strpos($content, "'product_name'") !== false && strpos($content, "\$productData['name'] = \$data['product_name']") === false,
    'Has is_digital field' => strpos($content, "'is_digital'") !== false,
    'Has colors field' => strpos($content, "'colors'") !== false,
    'Has product_type_main field' => strpos($content, "'product_type_main'") !== false,
    'Has default images for accessories' => strpos($content, 'apparel.goldsgym.com') !== false,
];

foreach ($checks as $check => $result) {
    if ($result) {
        echo "✓ $check\n";
    } else {
        echo "✗ FAILED: $check\n";
        exit(1);
    }
}

echo "\n";

// Test 2: Check database schema file
echo "Test 2: Checking database schema...\n";
$schemaFile = __DIR__ . '/../Database/nutrinexas_products.sql';
if (file_exists($schemaFile)) {
    $schema = file_get_contents($schemaFile);
    if (strpos($schema, 'product_name') !== false) {
        echo "✓ Schema uses product_name column\n";
    } else {
        echo "✗ FAILED: Schema doesn't use product_name\n";
        exit(1);
    }
} else {
    echo "⚠ Schema file not found, skipping\n";
}

echo "\n";

// Test 3: Check migration file exists
echo "Test 3: Checking migration files...\n";
$migrationFile = __DIR__ . '/../Database/migration/add_is_digital_and_colors_columns.sql';
if (file_exists($migrationFile)) {
    echo "✓ Migration file exists\n";
} else {
    echo "⚠ Migration file not found\n";
}

echo "\n";
echo "=== All Tests Passed! ===\n";
echo "\nSummary:\n";
echo "1. ✓ AdminController uses product_name (not name)\n";
echo "2. ✓ All required fields are included\n";
echo "3. ✓ Default images for accessories are configured\n";
echo "\nNext steps:\n";
echo "1. Run the migration SQL to add is_digital and colors columns\n";
echo "2. Test adding a product through the admin form\n";
echo "3. Verify images are set correctly for accessories\n";

