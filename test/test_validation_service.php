<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ValidationService;
use Respect\Validation\Validator as v;

echo "=== ValidationService Test ===\n\n";

// Test 1: Basic validation with Respect\Validation pattern
echo "Test 1: Basic validation (email, price, quantity)\n";
echo str_repeat("-", 50) . "\n";

$data1 = [
    'email' => 'test@example.com',
    'price' => 99.99,
    'quantity' => 5
];

$validator = v::key('email', v::email())
    ->key('price', v::numericVal()->positive())
    ->key('quantity', v::intVal()->min(1));

if ($validator->validate($data1)) {
    echo "✓ Valid data passed\n";
} else {
    echo "✗ Valid data failed\n";
}

// Test 2: Using ValidationService with direct Respect\Validation
echo "\nTest 2: Using ValidationService with Respect\\Validation\n";
echo str_repeat("-", 50) . "\n";

$service = new ValidationService($data1);
$rules = [
    'email' => v::email(),
    'price' => v::numericVal()->positive(),
    'quantity' => v::intVal()->min(1)
];

if ($service->validate($rules)) {
    echo "✓ Validation passed\n";
} else {
    echo "✗ Validation failed\n";
    print_r($service->getErrors());
}

// Test 3: Invalid data
echo "\nTest 3: Invalid data (should fail)\n";
echo str_repeat("-", 50) . "\n";

$invalidData = [
    'email' => 'invalid-email',
    'price' => -10,
    'quantity' => 0
];

$invalidRules = [
    'email' => v::email(),
    'price' => v::numericVal()->positive(),
    'quantity' => v::intVal()->min(1)
];

// Test with direct Respect\Validation first
$directValidator = v::key('email', v::email())
    ->key('price', v::numericVal()->positive())
    ->key('quantity', v::intVal()->min(1));

if ($directValidator->validate($invalidData)) {
    echo "✗ Direct validator passed (should fail)\n";
} else {
    echo "✓ Direct validator correctly failed\n";
    try {
        $directValidator->assert($invalidData);
    } catch (\Respect\Validation\Exceptions\ValidationException $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}

$service2 = new ValidationService($invalidData);
if ($service2->validate($invalidRules)) {
    echo "✗ ValidationService passed (should have failed)\n";
    echo "Debug - Errors: ";
    print_r($service2->getErrors());
} else {
    echo "✓ ValidationService correctly failed\n";
    echo "Errors:\n";
    foreach ($service2->getErrors() as $field => $errors) {
        echo "  - $field: " . implode(', ', (array)$errors) . "\n";
    }
}

// Test 4: Using string-based rules
echo "\nTest 4: Using string-based rules\n";
echo str_repeat("-", 50) . "\n";

$data3 = [
    'email' => 'user@example.com',
    'age' => 25,
    'name' => 'John Doe'
];

$service3 = ValidationService::make($data3);
$stringRules = [
    'email' => 'required|email',
    'age' => 'required|int|min:18|max:100',
    'name' => 'required|string|min:3|maxLength:50'
];

if ($service3->validate($stringRules)) {
    echo "✓ String-based validation passed\n";
} else {
    echo "✗ String-based validation failed\n";
    print_r($service3->getErrors());
}

// Test 5: Real-world product validation example
echo "\nTest 5: Product validation example\n";
echo str_repeat("-", 50) . "\n";

$productData = [
    'email' => 'seller@example.com',
    'price' => 149.99,
    'quantity' => 10
];

$productValidator = v::key('email', v::email())
    ->key('price', v::numericVal()->positive())
    ->key('quantity', v::intVal()->min(1));

if ($productValidator->validate($productData)) {
    echo "✓ Product data is valid\n";
} else {
    echo "✗ Product data is invalid\n";
}

$service4 = ValidationService::make($productData);
$productRules = [
    'email' => v::email(),
    'price' => v::numericVal()->positive(),
    'quantity' => v::intVal()->min(1)
];

if ($service4->validate($productRules)) {
    echo "✓ ValidationService validated product data successfully\n";
} else {
    echo "✗ ValidationService failed\n";
    print_r($service4->getErrors());
}

echo "\n=== All Tests Completed ===\n";

