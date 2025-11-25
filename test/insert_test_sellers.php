<?php
/**
 * Script to insert 10 test sellers with professional logos
 * Run: php test/insert_test_sellers.php
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../App/Core/Database.php';

use App\Core\Database;

$db = Database::getInstance();

// Test sellers data with provided logo URLs
$sellers = [
    [
        'name' => 'Wellcore Nutrition',
        'email' => 'wellcore@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234567',
        'company_name' => 'Wellcore Nutrition',
        'address' => 'Kathmandu, Nepal',
        'logo_url' => 'https://wearewellcore.com/wp-content/uploads/2023/09/Logo_Wellcore_grande-1.png',
        'description' => 'Premium health and wellness products. We specialize in high-quality supplements and nutritional solutions for a healthier lifestyle.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 10.00
    ],
    [
        'name' => 'Vital Health Solutions',
        'email' => 'vital@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234568',
        'company_name' => 'Vital Health Solutions',
        'address' => 'Lalitpur, Nepal',
        'logo_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRlAHSjq7Gk3-FboSL_OMkC42bdCkxD12e4mw&s',
        'description' => 'Your trusted partner for essential vitamins and minerals. We provide scientifically-backed nutritional supplements.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 12.00
    ],
    [
        'name' => 'HK Vitals',
        'email' => 'hkvitals@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234569',
        'company_name' => 'HK Vitals',
        'address' => 'Pokhara, Nepal',
        'logo_url' => 'https://www.blife.in/assets/images/hkvitals.png',
        'description' => 'Leading provider of premium vitamins and health supplements. Quality products for optimal wellness.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 11.00
    ],
    [
        'name' => 'NutriMax Pro',
        'email' => 'nutrimax@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234570',
        'company_name' => 'NutriMax Pro',
        'address' => 'Bhaktapur, Nepal',
        'logo_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRBbLape9i4PluxVH9btowdJUNEPfmDdLzK_g&s',
        'description' => 'Professional-grade nutrition supplements for athletes and fitness enthusiasts. Maximum performance, maximum results.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 10.50
    ],
    [
        'name' => 'Unilever Health',
        'email' => 'unilever@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234571',
        'company_name' => 'Unilever Health',
        'address' => 'Kathmandu, Nepal',
        'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/e/e4/Unilever.svg',
        'description' => 'Global leader in health and wellness products. Trusted by millions worldwide for quality and innovation.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 9.50
    ],
    [
        'name' => 'Pure Wellness Co',
        'email' => 'purewellness@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234572',
        'company_name' => 'Pure Wellness Co',
        'address' => 'Chitwan, Nepal',
        'logo_url' => 'https://wearewellcore.com/wp-content/uploads/2023/09/Logo_Wellcore_grande-1.png',
        'description' => '100% natural and organic health products. Pure ingredients for pure wellness.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 11.50
    ],
    [
        'name' => 'FitLife Nutrition',
        'email' => 'fitlife@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234573',
        'company_name' => 'FitLife Nutrition',
        'address' => 'Biratnagar, Nepal',
        'logo_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRlAHSjq7Gk3-FboSL_OMkC42bdCkxD12e4mw&s',
        'description' => 'Fitness-focused nutritional supplements. Fuel your active lifestyle with premium products.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 10.00
    ],
    [
        'name' => 'GreenVita Supplements',
        'email' => 'greenvita@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234574',
        'company_name' => 'GreenVita Supplements',
        'address' => 'Dharan, Nepal',
        'logo_url' => 'https://www.blife.in/assets/images/hkvitals.png',
        'description' => 'Plant-based supplements for sustainable health. Eco-friendly products for conscious consumers.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 12.00
    ],
    [
        'name' => 'ProActive Health',
        'email' => 'proactive@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234575',
        'company_name' => 'ProActive Health',
        'address' => 'Butwal, Nepal',
        'logo_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRBbLape9i4PluxVH9btowdJUNEPfmDdLzK_g&s',
        'description' => 'Advanced nutritional solutions for proactive health management. Science-backed formulas.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 11.00
    ],
    [
        'name' => 'Elite Nutrition Hub',
        'email' => 'elite@nutrinexus.com',
        'password' => password_hash('Test@123', PASSWORD_DEFAULT),
        'phone' => '+977-9801234576',
        'company_name' => 'Elite Nutrition Hub',
        'address' => 'Hetauda, Nepal',
        'logo_url' => 'https://upload.wikimedia.org/wikipedia/en/e/e4/Unilever.svg',
        'description' => 'Premium nutrition for elite performance. Professional-grade supplements for serious athletes.',
        'status' => 'active',
        'is_approved' => 1,
        'commission_rate' => 10.50
    ]
];

try {
    $db->beginTransaction();
    
    $inserted = 0;
    $skipped = 0;
    
    foreach ($sellers as $sellerData) {
        // Check if seller already exists
        $existing = $db->query(
            "SELECT id FROM sellers WHERE email = ?",
            [$sellerData['email']]
        )->single();
        
        if ($existing) {
            echo "⏭️  Skipped: {$sellerData['company_name']} (already exists)\n";
            $skipped++;
            continue;
        }
        
        // Insert seller
        $sql = "INSERT INTO sellers (name, email, password, phone, company_name, address, logo_url, description, status, is_approved, commission_rate, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $db->query($sql, [
            $sellerData['name'],
            $sellerData['email'],
            $sellerData['password'],
            $sellerData['phone'],
            $sellerData['company_name'],
            $sellerData['address'],
            $sellerData['logo_url'],
            $sellerData['description'],
            $sellerData['status'],
            $sellerData['is_approved'],
            $sellerData['commission_rate']
        ]);
        
        $inserted++;
        echo "✅ Inserted: {$sellerData['company_name']}\n";
    }
    
    $db->commit();
    
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✨ Successfully inserted {$inserted} sellers\n";
    echo "⏭️  Skipped {$skipped} existing sellers\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

