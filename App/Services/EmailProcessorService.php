<?php

namespace App\Services;

use App\Core\Database;
use App\Helpers\EmailHelper;

class EmailProcessorService
{
    private $db;
    private $emailHelper;
    private $emailQueueService;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->emailHelper = new EmailHelper();
        $this->emailQueueService = new EmailQueueService();
    }
    
    /**
     * Process pending emails
     */
    public function processEmails($limit = 10)
    {
        $emails = $this->emailQueueService->getPendingEmails($limit);
        $processed = 0;
        $failed = 0;
        
        foreach ($emails as $email) {
            try {
                // Mark as processing
                $this->emailQueueService->updateStatus($email['id'], 'processing');
                
                // Process the email
                $success = $this->sendEmail($email);
                
                if ($success) {
                    $this->emailQueueService->updateStatus($email['id'], 'sent');
                    $processed++;
                } else {
                    $this->handleFailedEmail($email);
                    $failed++;
                }
                
            } catch (\Exception $e) {
                error_log("EmailProcessorService::processEmails Error for email ID {$email['id']}: " . $e->getMessage());
                $this->handleFailedEmail($email, $e->getMessage());
                $failed++;
            }
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => count($emails)
        ];
    }
    
    /**
     * Send individual email
     */
    private function sendEmail($email)
    {
        try {
            // Get template info from metadata
            $metadata = json_decode($email['metadata'], true);
            $template = $metadata['template'] ?? '';
            $templateData = $metadata['template_data'] ?? [];
            
            // Send email using EmailHelper
            $result = \App\Helpers\EmailHelper::sendTemplate(
                $email['to_email'],
                $email['subject'],
                $template,
                $templateData,
                $email['to_name']
            );
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("EmailProcessorService::sendEmail Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle failed email
     */
    private function handleFailedEmail($email, $errorMessage = null)
    {
        $attempts = $email['attempts'] + 1;
        $maxAttempts = $email['max_attempts'];
        
        if ($attempts >= $maxAttempts) {
            $this->emailQueueService->markAsFailed($email['id'], $errorMessage ?: 'Max attempts reached');
        } else {
            // Reschedule for retry (exponential backoff)
            $retryDelay = pow(2, $attempts) * 60; // 2, 4, 8 minutes
            $scheduledAt = date('Y-m-d H:i:s', time() + $retryDelay);
            
            $sql = "UPDATE email_queue 
                    SET status = 'pending', 
                        scheduled_at = ?,
                        error_message = ?
                    WHERE id = ?";
            
            $this->db->query($sql)->bind([
                $scheduledAt,
                $errorMessage,
                $email['id']
            ])->execute();
        }
    }
    
    /**
     * Process specific email by ID
     */
    public function processEmailById($id)
    {
        try {
            $sql = "SELECT * FROM email_queue WHERE id = ? AND status = 'pending'";
            $email = $this->db->query($sql)->bind([$id])->single();
            
            if (!$email) {
                return false;
            }
            
            // Mark as processing
            $this->emailQueueService->updateStatus($email['id'], 'processing');
            
            // Process the email
            $success = $this->sendEmail($email);
            
            if ($success) {
                $this->emailQueueService->updateStatus($email['id'], 'sent');
                return true;
            } else {
                $this->handleFailedEmail($email);
                return false;
            }
            
        } catch (\Exception $e) {
            error_log("EmailProcessorService::processEmailById Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get processing statistics
     */
    public function getProcessingStats()
    {
        return $this->emailQueueService->getQueueStats();
    }
}
