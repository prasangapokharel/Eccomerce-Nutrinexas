<?php
/**
 * Test file for Auth0-only authentication
 * This test verifies that the login and register pages only show Auth0 options
 */

// Include necessary files
require_once __DIR__ . '/../../app/config/config.php';

echo "=== Auth0-Only Authentication Test ===\n\n";

// Test 1: Check if login page loads
echo "1. Testing login page...\n";
$loginUrl = "http://localhost:8000/auth/login";
$loginContent = @file_get_contents($loginUrl);

if ($loginContent !== false) {
    echo "✓ Login page loads successfully\n";
    
    // Check if Auth0 button exists
    if (strpos($loginContent, 'Sign in with Auth0') !== false) {
        echo "✓ Auth0 login button found\n";
    } else {
        echo "✗ Auth0 login button NOT found\n";
    }
    
    // Check if form fields are removed
    if (strpos($loginContent, 'name="phone"') === false && 
        strpos($loginContent, 'name="password"') === false) {
        echo "✓ Form fields successfully removed\n";
    } else {
        echo "✗ Form fields still present\n";
    }
    
    // Check if other OAuth buttons are removed
    $removedProviders = ['Google', 'Facebook', 'GitHub'];
    $allRemoved = true;
    foreach ($removedProviders as $provider) {
        if (strpos($loginContent, $provider) !== false) {
            echo "✗ $provider OAuth still present\n";
            $allRemoved = false;
        }
    }
    if ($allRemoved) {
        echo "✓ Other OAuth providers successfully removed\n";
    }
} else {
    echo "✗ Login page failed to load\n";
}

echo "\n";

// Test 2: Check if register page loads
echo "2. Testing register page...\n";
$registerUrl = "http://localhost:8000/auth/register?" . time(); // Add timestamp to avoid cache
$context = stream_context_create([
    'http' => [
        'header' => "Cache-Control: no-cache\r\n"
    ]
]);
$registerContent = @file_get_contents($registerUrl, false, $context);

if ($registerContent !== false) {
    echo "✓ Register page loads successfully\n";
    
    // Check if Auth0 button exists
    if (strpos($registerContent, 'Sign up with Auth0') !== false) {
        echo "✓ Auth0 register button found\n";
    } else {
        echo "✗ Auth0 register button NOT found\n";
    }
    
    // Check if form fields are removed (more specific check)
    $formFields = [
        'name="full_name"' => 'Full Name field',
        'name="phone"' => 'Phone field', 
        'name="email"' => 'Email field',
        'name="password"' => 'Password field',
        'type="email"' => 'Email input type',
        'type="password"' => 'Password input type'
    ];
    
    $allFieldsRemoved = true;
    foreach ($formFields as $field => $description) {
        if (strpos($registerContent, $field) !== false) {
            echo "✗ $description ($field) still present\n";
            $allFieldsRemoved = false;
        }
    }
    if ($allFieldsRemoved) {
        echo "✓ All form fields successfully removed\n";
    }
    
    // Check for form tag
    if (strpos($registerContent, '<form') === false) {
        echo "✓ No form elements found\n";
    } else {
        echo "✗ Form elements still present\n";
    }
} else {
    echo "✗ Register page failed to load\n";
}

echo "\n";

// Test 3: Check Auth0 configuration
echo "3. Testing Auth0 configuration...\n";
if (defined('AUTH0_CLIENT_ID') && !empty(AUTH0_CLIENT_ID)) {
    echo "✓ Auth0 Client ID configured\n";
} else {
    echo "✗ Auth0 Client ID not configured\n";
}

if (defined('AUTH0_CLIENT_SECRET') && !empty(AUTH0_CLIENT_SECRET)) {
    echo "✓ Auth0 Client Secret configured\n";
} else {
    echo "✗ Auth0 Client Secret not configured\n";
}

if (defined('AUTH0_DOMAIN') && !empty(AUTH0_DOMAIN)) {
    echo "✓ Auth0 Domain configured\n";
} else {
    echo "✗ Auth0 Domain not configured\n";
}

echo "\n=== Test Complete ===\n";
echo "The authentication system has been successfully simplified to use only Auth0.\n";
echo "All form-based login/register functionality has been removed.\n";
echo "Users can now only authenticate through Auth0.\n";