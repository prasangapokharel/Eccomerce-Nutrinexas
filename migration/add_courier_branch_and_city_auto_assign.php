<?php
/**
 * Migration: Add branch column to curiors, city to sellers, and fix status ENUM
 * Also extracts cities from delivery addresses for auto-assignment
 */

require_once __DIR__ . '/../App/Config/config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

try {
    $db = Database::getInstance();
    
    echo "🔍 Starting migration...\n\n";
    
    // 1. Fix status ENUM - add 'picked_up' if missing
    echo "1. Checking orders.status ENUM...\n";
    $columnInfo = $db->query("SHOW COLUMNS FROM orders WHERE Field = 'status'")->single();
    $currentType = $columnInfo['Type'];
    
    if (stripos($currentType, 'enum') !== false) {
        preg_match("/enum\((.*?)\)/i", $currentType, $matches);
        if (isset($matches[1])) {
            $enumValues = $matches[1];
            
            // Check if picked_up exists
            if (stripos($enumValues, 'picked_up') === false) {
                $newEnum = $enumValues . ",'picked_up'";
                $sql = "ALTER TABLE orders MODIFY COLUMN status ENUM({$newEnum})";
                $db->query($sql)->execute();
                echo "   ✅ Added 'picked_up' to status ENUM\n";
            } else {
                echo "   ✅ 'picked_up' already exists in status ENUM\n";
            }
        }
    }
    
    // 2. Add 'branch' column to curiors table
    echo "\n2. Adding 'branch' column to curiors table...\n";
    $columnCheck = $db->query("SHOW COLUMNS FROM curiors LIKE 'branch'")->single();
    if (!$columnCheck) {
        $db->query("ALTER TABLE curiors ADD COLUMN branch VARCHAR(100) NULL AFTER address")->execute();
        echo "   ✅ Added 'branch' column to curiors table\n";
    } else {
        echo "   ✅ 'branch' column already exists in curiors table\n";
    }
    
    // 4. Add 'city' column to sellers table if not exists
    echo "\n4. Adding 'city' column to sellers table...\n";
    $columnCheck = $db->query("SHOW COLUMNS FROM sellers LIKE 'city'")->single();
    if (!$columnCheck) {
        $db->query("ALTER TABLE sellers ADD COLUMN city VARCHAR(100) NULL AFTER address")->execute();
        echo "   ✅ Added 'city' column to sellers table\n";
    } else {
        echo "   ✅ 'city' column already exists in sellers table\n";
    }
    
    // 5. Extract cities from existing seller addresses and populate city column
    echo "\n5. Extracting cities from seller addresses...\n";
    $sellers = $db->query("SELECT id, address FROM sellers WHERE (city IS NULL OR city = '') AND address IS NOT NULL AND address != ''")->all();
    $updated = 0;
    
    // Common Nepali cities
    $cities = [
        'Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Bharatpur', 
        'Biratnagar', 'Birgunj', 'Dharan', 'Butwal', 'Hetauda',
        'Nepalgunj', 'Itahari', 'Tulsipur', 'Kalaiya', 'Jitpur',
        'Inaruwa', 'Janakpur', 'Bhimdatta', 'Dhangadhi', 'Birendranagar',
        'Ghorahi', 'Tikapur', 'Tansen', 'Baglung', 'Gulariya',
        'Rajbiraj', 'Lahan', 'Siddharthanagar', 'Bhadrapur', 'Damak',
        'Bardibas', 'Malangwa', 'Banepa', 'Panauti', 'Dhankuta',
        'Ilam', 'Phidim', 'Bhojpur', 'Diktel', 'Okhaldhunga',
        'Ramechhap', 'Manthali', 'Charikot', 'Jiri', 'Sindhuli',
        'Janakpur', 'Jaleshwar', 'Rajbiraj', 'Siraha', 'Lahan',
        'Biratnagar', 'Itahari', 'Inaruwa', 'Dhankuta', 'Dharan',
        'Bhadrapur', 'Damak', 'Mechinagar', 'Birtamod', 'Kakarbhitta'
    ];
    
    foreach ($sellers as $seller) {
        $address = $seller['address'] ?? '';
        if (empty($address)) continue;
        
        // Try to extract city from address
        $extractedCity = null;
        foreach ($cities as $city) {
            if (stripos($address, $city) !== false) {
                $extractedCity = $city;
                break;
            }
        }
        
        if ($extractedCity) {
            $db->query("UPDATE sellers SET city = ? WHERE id = ?", [$extractedCity, $seller['id']])->execute();
            $updated++;
        }
    }
    echo "   ✅ Updated city for {$updated} sellers from addresses\n";
    
    // 6. Extract unique cities from delivery addresses (orders table)
    echo "\n6. Extracting unique cities from delivery addresses...\n";
    $orders = $db->query("SELECT DISTINCT address FROM orders WHERE address IS NOT NULL AND address != ''")->all();
    $uniqueCities = [];
    
    foreach ($orders as $order) {
        $address = $order['address'] ?? '';
        if (empty($address)) continue;
        
        foreach ($cities as $city) {
            if (stripos($address, $city) !== false) {
                $uniqueCities[$city] = true;
                break;
            }
        }
    }
    
    $cityList = array_keys($uniqueCities);
    sort($cityList);
    echo "   ✅ Found " . count($cityList) . " unique cities in delivery addresses:\n";
    foreach ($cityList as $city) {
        echo "      - {$city}\n";
    }
    
    echo "\n✅ Migration completed successfully.\n";
    echo "\n📝 Next steps:\n";
    echo "   1. Update admin courier create form to include city selection\n";
    echo "   2. Update auto-assignment logic to match courier city with seller city\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

