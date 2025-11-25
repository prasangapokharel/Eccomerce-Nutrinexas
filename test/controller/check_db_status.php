<?php
require_once __DIR__ . '/../../app/config/config.php';

use App\Core\Database;

echo "=== Database Status Check ===\n";

try {
    $db = Database::getInstance();
    
    // Check withdrawal status
    $withdrawal = $db->query('SELECT id, status, amount, user_id FROM withdrawals WHERE id = 2')->single();
    if ($withdrawal) {
        echo "Withdrawal ID 2 Status: " . $withdrawal['status'] . "\n";
        echo "Amount: " . $withdrawal['amount'] . "\n";
        echo "User ID: " . $withdrawal['user_id'] . "\n";
    } else {
        echo "Withdrawal ID 2 not found\n";
    }
    
    // Check recent email queue entries
    echo "\nRecent Email Queue Entries:\n";
    $emails = $db->query('SELECT to_email, subject, status, created_at FROM email_queue ORDER BY created_at DESC LIMIT 5')->all();
    foreach ($emails as $email) {
        echo "- To: " . $email['to_email'] . " | Subject: " . substr($email['subject'], 0, 50) . "... | Status: " . $email['status'] . " | Created: " . $email['created_at'] . "\n";
    }
    
    // Check total email queue count
    $count = $db->query('SELECT COUNT(*) as total FROM email_queue')->single();
    echo "\nTotal emails in queue: " . $count['total'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>