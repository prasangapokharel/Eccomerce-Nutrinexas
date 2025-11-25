<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Config/config.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Services/EmailProcessorService.php';

use App\Services\EmailProcessorService;

class EmailQueueProcessor
{
    private $emailProcessor;
    private $maxExecutionTime = 300; // 5 minutes
    private $batchSize = 10;
    
    public function __construct()
    {
        $this->emailProcessor = new EmailProcessorService();
        set_time_limit($this->maxExecutionTime);
    }
    
    public function run()
    {
        echo "Starting email queue processor...\n";
        echo "Max execution time: {$this->maxExecutionTime} seconds\n";
        echo "Batch size: {$this->batchSize}\n\n";
        
        $startTime = time();
        $totalProcessed = 0;
        $totalFailed = 0;
        
        while ((time() - $startTime) < $this->maxExecutionTime) {
            $result = $this->emailProcessor->processEmails($this->batchSize);
            
            $totalProcessed += $result['processed'];
            $totalFailed += $result['failed'];
            
            if ($result['total'] == 0) {
                echo "No more emails to process. Sleeping for 30 seconds...\n";
                sleep(30);
                continue;
            }
            
            echo "Processed: {$result['processed']}, Failed: {$result['failed']}, Total: {$result['total']}\n";
            
            // Small delay between batches
            usleep(100000); // 0.1 seconds
        }
        
        echo "\nEmail queue processing completed!\n";
        echo "Total processed: {$totalProcessed}\n";
        echo "Total failed: {$totalFailed}\n";
        
        // Show queue statistics
        $stats = $this->emailProcessor->getProcessingStats();
        echo "\nQueue Statistics:\n";
        foreach ($stats as $status => $count) {
            echo ucfirst($status) . ": {$count}\n";
        }
    }
    
    public function runOnce()
    {
        echo "Processing email queue once...\n";
        
        $result = $this->emailProcessor->processEmails($this->batchSize);
        
        echo "Processed: {$result['processed']}, Failed: {$result['failed']}, Total: {$result['total']}\n";
        
        return $result;
    }
}

// Check if running from command line
if (php_sapi_name() === 'cli') {
    $processor = new EmailQueueProcessor();
    
    // Check for command line arguments
    $args = $argv ?? [];
    
    if (in_array('--once', $args)) {
        $processor->runOnce();
    } else {
        $processor->run();
    }
} else {
    // Web access - process once
    $processor = new EmailQueueProcessor();
    $result = $processor->runOnce();
    
    header('Content-Type: application/json');
    echo json_encode($result);
}
















