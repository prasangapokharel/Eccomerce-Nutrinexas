<?php
/**
 * Create 10 Approved Sellers with Random Logos
 */

require_once __DIR__ . '/../App/Config/config.php';

if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Models\Seller;

echo "=== Creating 10 Approved Sellers ===\n\n";

$db = Database::getInstance();
$sellerModel = new Seller();

// Image URLs to randomly assign
$logoUrls = [
    'https://upload.wikimedia.org/wikipedia/en/e/e4/Unilever.svg',
    'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR-cu8R1cpq5vQPKEQO4Zqe5TP7HayD7xHD0Q&s',
    'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRlAHSjq7Gk3-FboSL_OMkC42bdCkxD12e4mw&s'
];

// Seller names and company names
$sellerData = [
    ['name' => 'Rajesh Kumar', 'company' => 'Kumar Trading Co.'],
    ['name' => 'Priya Sharma', 'company' => 'Sharma Enterprises'],
    ['name' => 'Amit Patel', 'company' => 'Patel Distributors'],
    ['name' => 'Sneha Gupta', 'company' => 'Gupta Wholesale'],
    ['name' => 'Vikram Singh', 'company' => 'Singh Retail Solutions'],
    ['name' => 'Anjali Mehta', 'company' => 'Mehta Trading House'],
    ['name' => 'Rahul Verma', 'company' => 'Verma Commerce'],
    ['name' => 'Kavita Reddy', 'company' => 'Reddy Supply Chain'],
    ['name' => 'Deepak Joshi', 'company' => 'Joshi Merchants'],
    ['name' => 'Sunita Agarwal', 'company' => 'Agarwal Business Group']
];

$created = 0;
$failed = 0;
$sellerIds = [];

foreach ($sellerData as $index => $data) {
    try {
        // Generate unique email
        $email = 'seller' . ($index + 1) . '@nutrinexus.com';
        
        // Check if email already exists
        $existing = $sellerModel->findByEmail($email);
        if ($existing) {
            echo "⚠ Seller with email {$email} already exists, skipping...\n";
            continue;
        }
        
        // Random logo selection
        $randomLogo = $logoUrls[array_rand($logoUrls)];
        
        // Create seller data
        $sellerInfo = [
            'name' => $data['name'],
            'email' => $email,
            'password' => password_hash('password123', PASSWORD_DEFAULT), // Default password
            'phone' => '984' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'company_name' => $data['company'],
            'address' => 'Kathmandu, Nepal',
            'logo_url' => $randomLogo,
            'status' => 'active',
            'is_approved' => 1, // Approved
            'commission_rate' => 10.00
        ];
        
        // Create seller
        $sellerId = $sellerModel->create($sellerInfo);
        
        if ($sellerId) {
            $sellerIds[] = $sellerId;
            echo "✓ Seller #{$sellerId} created: {$data['name']} ({$data['company']})\n";
            echo "  Email: {$email}\n";
            echo "  Logo: {$randomLogo}\n";
            echo "  Status: Active & Approved\n";
            $created++;
        } else {
            echo "✗ Failed to create seller: {$data['name']}\n";
            $failed++;
        }
        
    } catch (Exception $e) {
        echo "✗ Error creating seller {$data['name']}: {$e->getMessage()}\n";
        $failed++;
    }
}

// Summary
echo "\n=== Summary ===\n";
echo "Created: {$created}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($created + $failed) . "\n";

if ($created > 0) {
    echo "\n✓ Successfully created {$created} approved sellers!\n";
    echo "\nSeller IDs: " . implode(', ', $sellerIds) . "\n";
    echo "\nAll sellers are:\n";
    echo "  - Status: Active\n";
    echo "  - Approved: Yes\n";
    echo "  - Default Password: password123\n";
    echo "  - Logos: Randomly assigned from provided URLs\n";
}

exit($failed > 0 ? 1 : 0);










