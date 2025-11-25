<?php
/**
 * Cron Job: Process Seller Balance Releases
 * 
 * This script processes seller balance releases for orders that have passed
 * the wait period after delivery. Run this via cron every hour or as needed.
 * 
 * Usage: php cron/process_seller_balance_releases.php
 * 
 * Cron setup (run every hour):
 * 0 * * * * cd /path/to/project && php cron/process_seller_balance_releases.php >> logs/cron_balance_releases.log 2>&1
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../App/bootstrap.php';

use App\Services\SellerBalanceService;

echo "=== Processing Seller Balance Releases ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $service = new SellerBalanceService();
    $result = $service->processPendingReleases();
    
    if ($result['success']) {
        echo "✓ Processed: {$result['processed']} orders\n";
        echo "✗ Errors: {$result['errors']} orders\n";
        echo "Total checked: {$result['total']} orders\n";
    } else {
        echo "✗ Error: " . ($result['message'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
    echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
    echo "=== Done ===\n";
    
} catch (Exception $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
    error_log("Seller Balance Release Cron Error: " . $e->getMessage());
    exit(1);
}

