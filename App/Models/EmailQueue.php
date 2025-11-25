<?php
namespace App\Models;

use App\Core\Model;
use App\Helpers\EmailHelper;
use Exception;

class EmailQueue extends Model
{
    protected $table = 'email_queue';
    protected $primaryKey = 'id';

    /**
     * Add an email to the queue
     */
    public function queueEmail($toEmail, $subject, $body, $toName = '', $priority = 5, $metadata = [], $scheduledAt = null)
    {
        try {
            $data = [
                'to_email' => $toEmail,
                'to_name' => $toName,
                'subject' => $subject,
                'body' => $body,
                'priority' => $priority,
                'metadata' => json_encode($metadata),
                'scheduled_at' => $scheduledAt,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $emailId = $this->create($data);
            
            if ($emailId) {
                // Process immediately if high priority
                if ($priority <= 2) {
                    $this->processEmail($emailId);
                }
                return $emailId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Failed to queue email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue template email
     */
    public function queueTemplateEmail($toEmail, $toName, $subject, $template, $data = [], $priority = 5)
    {
        try {
            // Load and process template
            $templatePath = $this->findTemplatePath($template);
            
            if (!$templatePath || !file_exists($templatePath)) {
                error_log("Email template not found: " . $template);
                return false;
            }
            
            // Get template content
            $body = file_get_contents($templatePath);
            
            if ($body === false) {
                error_log("Failed to read email template: " . $templatePath);
                return false;
            }
            
            // Replace placeholders with data
            foreach ($data as $key => $value) {
                $body = str_replace('{{' . $key . '}}', htmlspecialchars($value), $body);
            }
            
            return $this->queueEmail($toEmail, $subject, $body, $toName, $priority, $data);
            
        } catch (Exception $e) {
            error_log('Failed to queue template email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process a specific email by ID
     */
    public function processEmail($id)
    {
        try {
            // Get the email from the queue
            $email = $this->find($id);
            
            if (!$email) {
                return false;
            }
            
            // Mark as processing
            $this->markAsProcessing($id);
            
            // Send the email using EmailHelper
            $success = EmailHelper::send(
                $email['to_email'],
                $email['subject'],
                $email['body']
            );
            
            if ($success) {
                // Mark as sent
                $this->markAsSent($id);
                return true;
            } else {
                // Mark as failed
                $this->markAsFailed($id, 'Failed to send email');
                return false;
            }
        } catch (Exception $e) {
            // Mark as failed with error message
            $this->markAsFailed($id, $e->getMessage());
            error_log('Error processing email ID ' . $id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pending emails from the queue
     */
    public function getPendingEmails($limit = 10)
    {
        $now = date('Y-m-d H:i:s');
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'pending' 
                AND (scheduled_at IS NULL OR scheduled_at <= ?)
                AND attempts < max_attempts
                ORDER BY priority ASC, created_at ASC
                LIMIT ?";
        
        return $this->db->query($sql)->bind([$now, $limit])->all();
    }

    /**
     * Mark an email as processing
     */
    public function markAsProcessing($id)
    {
        $sql = "UPDATE {$this->table} 
                SET status = 'processing', 
                    attempts = attempts + 1,
                    last_attempt = NOW(),
                    updated_at = NOW()
                WHERE id = ?";
        
        return $this->db->query($sql)->bind([$id])->execute();
    }

    /**
     * Mark an email as sent
     */
    public function markAsSent($id)
    {
        $sql = "UPDATE {$this->table} 
                SET status = 'sent', 
                    sent_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?";
        
        return $this->db->query($sql)->bind([$id])->execute();
    }

    /**
     * Mark an email as failed
     */
    public function markAsFailed($id, $errorMessage)
    {
        $sql = "UPDATE {$this->table} 
                SET status = 'failed', 
                    error_message = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        return $this->db->query($sql)->bind([$errorMessage, $id])->execute();
    }

    /**
     * Process the email queue
     */
    public function processQueue($limit = 10)
    {
        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Get pending emails
        $pendingEmails = $this->getPendingEmails($limit);
        
        if (empty($pendingEmails)) {
            return $results;
        }
        
        foreach ($pendingEmails as $email) {
            $results['processed']++;
            
            try {
                $success = $this->processEmail($email['id']);
                
                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Email ID {$email['id']}: Failed to send email";
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Email ID {$email['id']}: {$e->getMessage()}";
            }
        }
        
        return $results;
    }

    /**
     * Get email queue statistics
     */
    public function getStatistics()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'failed' AND attempts >= max_attempts THEN 1 ELSE 0 END) as max_attempts_reached
                FROM {$this->table}";
        
        return $this->db->query($sql)->single();
    }

    /**
     * Clean up old sent emails
     */
    public function cleanupOldEmails($olderThanDays = 30)
    {
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$olderThanDays} days"));
        
        $sql = "DELETE FROM {$this->table} 
                WHERE status = 'sent'
                AND sent_at < ?";
        
        $this->db->query($sql)->bind([$cutoffTime])->execute();
        
        return $this->db->rowCount();
    }

    /**
     * Find template path
     */
    private function findTemplatePath($template)
    {
        $possiblePaths = [
            defined('APPROOT') ? APPROOT . '/assets/templates/' . $template . '.html' : null,
            dirname(dirname(__DIR__)) . '/assets/templates/' . $template . '.html',
            dirname(dirname(dirname(__DIR__))) . '/assets/templates/' . $template . '.html',
            $_SERVER['DOCUMENT_ROOT'] . '/assets/templates/' . $template . '.html',
            __DIR__ . '/../../assets/templates/' . $template . '.html'
        ];

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
