<?php

// Include the application bootstrap
require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/core/Database.php';
require_once dirname(__DIR__) . '/app/models/EmailQueue.php';
require_once dirname(__DIR__) . '/app/helpers/EmailHelper.php';

use App\Models\EmailQueue;

try {
    echo "Starting email queue processing...\n";
    
    $emailQueue = new EmailQueue();
    
    // Process up to 50 emails at a time
    $results = $emailQueue->processQueue(50);
    
    echo "Email processing completed:\n";
    echo "- Processed: {$results['processed']}\n";
    echo "- Success: {$results['success']}\n";
    echo "- Failed: {$results['failed']}\n";
    
    if (!empty($results['errors'])) {
        echo "Errors:\n";
        foreach ($results['errors'] as $error) {
            echo "- $error\n";
        }
    }
    
    // Clean up old emails (older than 30 days)
    $cleaned = $emailQueue->cleanupOldEmails(30);
    echo "Cleaned up $cleaned old emails\n";
    
    // Show queue statistics
    $stats = $emailQueue->getStatistics();
    echo "\nQueue Statistics:\n";
    echo "- Total: {$stats['total']}\n";
    echo "- Pending: {$stats['pending']}\n";
    echo "- Processing: {$stats['processing']}\n";
    echo "- Sent: {$stats['sent']}\n";
    echo "- Failed: {$stats['failed']}\n";
    
} catch (Exception $e) {
    echo "Error processing email queue: " . $e->getMessage() . "\n";
    error_log("Email queue processing error: " . $e->getMessage());
}
