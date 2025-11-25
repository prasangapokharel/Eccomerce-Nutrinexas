<?php
/**
 * Test Order Calculation
 * Verifies that order totals are calculated correctly with discounts
 */

define('ROOT', dirname(__DIR__));
require_once ROOT . '/App/Config/config.php';
require_once ROOT . '/App/Services/OrderCalculationService.php';

use App\Services\OrderCalculationService;

echo "=== Order Calculation Test ===\n\n";

$testCases = [
    [
        'name' => 'Test Case 1: With discount (from image)',
        'subtotal' => 1500.00,
        'discount' => 300.00,
        'tax_rate' => 0,
        'delivery' => 150.00,
        'expected_total' => 1350.00
    ],
    [
        'name' => 'Test Case 2: No discount',
        'subtotal' => 1000.00,
        'discount' => 0.00,
        'tax_rate' => 12,
        'delivery' => 100.00,
        'expected_total' => 1220.00 // 1000 + (1000 * 0.12) + 100
    ],
    [
        'name' => 'Test Case 3: Discount with tax',
        'subtotal' => 2000.00,
        'discount' => 500.00,
        'tax_rate' => 13,
        'delivery' => 200.00,
        'expected_total' => 1895.00 // (2000-500) + (1500*0.13) + 200 = 1500 + 195 + 200
    ],
    [
        'name' => 'Test Case 4: Free delivery',
        'subtotal' => 500.00,
        'discount' => 50.00,
        'tax_rate' => 0,
        'delivery' => 0.00,
        'expected_total' => 450.00
    ]
];

$passed = 0;
$failed = 0;

foreach ($testCases as $test) {
    echo "Testing: {$test['name']}\n";
    echo "  Subtotal: रु{$test['subtotal']}\n";
    echo "  Discount: -रु{$test['discount']}\n";
    echo "  Tax Rate: {$test['tax_rate']}%\n";
    echo "  Delivery: रु{$test['delivery']}\n";
    
    $result = OrderCalculationService::calculateTotals(
        $test['subtotal'],
        $test['discount'],
        $test['delivery'],
        $test['tax_rate']
    );
    
    $calculatedTotal = $result['total'];
    $expectedTotal = $test['expected_total'];
    
    echo "  Expected Total: रु{$expectedTotal}\n";
    echo "  Calculated Total: रु{$calculatedTotal}\n";
    
    // Allow small floating point differences
    $difference = abs($calculatedTotal - $expectedTotal);
    if ($difference < 0.01) {
        echo "  ✅ PASS\n\n";
        $passed++;
    } else {
        echo "  ❌ FAIL - Difference: रु{$difference}\n\n";
        $failed++;
    }
}

echo "=== Test Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n\n";

if ($failed === 0) {
    echo "✅ All tests passed! Calculation is correct.\n";
    exit(0);
} else {
    echo "❌ Some tests failed. Please review the calculation logic.\n";
    exit(1);
}

