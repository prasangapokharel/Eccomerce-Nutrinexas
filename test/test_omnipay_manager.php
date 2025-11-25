<?php
/**
 * Smoke test for the Omnipay integration layer.
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';
require_once ROOT . DS . 'vendor' . DS . 'autoload.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Services\Payments\OmnipayGatewayManager;

echo "=== Omnipay Manager Smoke Test ===\n\n";

$results = ['passed' => 0, 'failed' => 0];

$assert = function (string $label, bool $statement, string $message = '') use (&$results) {
    if ($statement) {
        echo "✅ PASS: {$label}\n";
        $results['passed']++;
    } else {
        echo "❌ FAIL: {$label} {$message}\n";
        $results['failed']++;
    }
};

try {
    $manager = new OmnipayGatewayManager();
    $drivers = $manager->getAvailableDrivers();
    $assert('Driver whitelist loaded', !empty($drivers), '(no drivers found)');

    $dummyRecord = [
        'id' => 0,
        'slug' => 'dummy-omnipay',
        'type' => 'omnipay',
        'is_test_mode' => 1,
        'parameters' => json_encode([
            'driver' => '\\App\\Services\\Payments\\Drivers\\LocalDummyGateway',
            'test_mode' => true
        ])
    ];

    $gateway = $manager->createGatewayFromRecord($dummyRecord);
    $assert('Gateway instantiated', method_exists($gateway, 'purchase'));
    $assert(
        'Driver resolution works',
        $manager->resolveDriver($dummyRecord) === '\\App\\Services\\Payments\\Drivers\\LocalDummyGateway'
    );

} catch (\Throwable $e) {
    $assert('Manager bootstraps without exceptions', false, $e->getMessage());
}

echo "\nSummary: {$results['passed']} passed / {$results['failed']} failed\n";
if ($results['failed'] === 0) {
    echo "🎉 Omnipay integration ready.\n";
} else {
    echo "⚠️  Please review the failures above.\n";
}

