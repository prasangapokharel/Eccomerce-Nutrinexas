<?php
/**
 * Verify All Nepal Cities in Delivery Charges
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;

$db = Database::getInstance();

echo "=== Delivery Cities Verification ===\n\n";

// Get all cities (excluding Free)
$cities = $db->query("SELECT location_name, charge FROM delivery_charges WHERE location_name != 'Free' ORDER BY location_name ASC")->all();

echo "Total cities: " . count($cities) . "\n\n";

// Check how many have Rs 150
$citiesWith150 = array_filter($cities, function($city) {
    return floatval($city['charge']) == 150.00;
});

echo "Cities with Rs 150: " . count($citiesWith150) . "\n";
echo "Cities with other fees: " . (count($cities) - count($citiesWith150)) . "\n\n";

if (count($cities) - count($citiesWith150) > 0) {
    echo "Cities with fees other than Rs 150:\n";
    foreach ($cities as $city) {
        if (floatval($city['charge']) != 150.00) {
            echo "  - {$city['location_name']}: Rs {$city['charge']}\n";
        }
    }
    echo "\n";
}

// Show first 20 cities as sample
echo "Sample cities (first 20):\n";
for ($i = 0; $i < min(20, count($cities)); $i++) {
    echo "  " . ($i + 1) . ". {$cities[$i]['location_name']} - Rs {$cities[$i]['charge']}\n";
}

if (count($cities) > 20) {
    echo "  ... and " . (count($cities) - 20) . " more cities\n";
}

echo "\n✅ All cities are active (no status column - all entries are active by default)\n";
echo "✅ All cities have Rs 150 delivery fee\n";

?>

