<?php

namespace App\Services;

use App\Core\Database;

class EmailQueueService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Add email to queue
     */
    public function addToQueue($toEmail, $toName, $subject, $template, $templateData = [], $scheduledAt = null)
    {
        try {
            $sql = "INSERT INTO email_queue (to_email, to_name, subject, body, metadata, scheduled_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $scheduledAt = $scheduledAt ?: date('Y-m-d H:i:s');
            
            // Store template name and data in metadata
            $metadata = json_encode([
                'template' => $template,
                'template_data' => $templateData
            ]);
            
            $this->db->query($sql)->bind([
                $toEmail,
                $toName,
                $subject,
                '', // body will be generated during processing
                $metadata,
                $scheduledAt
            ])->execute();
            
            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("EmailQueueService::addToQueue Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pending emails for processing
     */
    public function getPendingEmails($limit = 10)
    {
        try {
            $sql = "SELECT * FROM email_queue 
                    WHERE status = 'pending' 
                    AND scheduled_at <= NOW() 
                    ORDER BY created_at ASC 
                    LIMIT ?";
            
            return $this->db->query($sql)->bind([$limit])->all();
        } catch (\Exception $e) {
            error_log("EmailQueueService::getPendingEmails Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update email status
     */
    public function updateStatus($id, $status, $errorMessage = null)
    {
        try {
            $sql = "UPDATE email_queue 
                    SET status = ?, 
                        error_message = ?, 
                        attempts = attempts + 1,
                        sent_at = CASE WHEN ? = 'sent' THEN NOW() ELSE sent_at END,
                        last_attempt = NOW()
                    WHERE id = ?";
            
            $this->db->query($sql)->bind([
                $status,
                $errorMessage,
                $status,
                $id
            ])->execute();
            
            return true;
        } catch (\Exception $e) {
            error_log("EmailQueueService::updateStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark email as failed after max attempts
     */
    public function markAsFailed($id, $errorMessage)
    {
        try {
            $sql = "UPDATE email_queue 
                    SET status = 'failed', 
                        error_message = ?,
                        last_attempt = NOW()
                    WHERE id = ?";
            
            $this->db->query($sql)->bind([$errorMessage, $id])->execute();
            return true;
        } catch (\Exception $e) {
            error_log("EmailQueueService::markAsFailed Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats()
    {
        try {
            $sql = "SELECT 
                        status,
                        COUNT(*) as count
                    FROM email_queue 
                    GROUP BY status";
            
            $results = $this->db->query($sql)->all();
            
            $stats = [
                'pending' => 0,
                'processing' => 0,
                'sent' => 0,
                'failed' => 0
            ];
            
            foreach ($results as $result) {
                $stats[$result['status']] = (int)$result['count'];
            }
            
            return $stats;
        } catch (\Exception $e) {
            error_log("EmailQueueService::getQueueStats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old processed emails (older than 30 days)
     */
    public function cleanOldEmails()
    {
        try {
            $sql = "DELETE FROM email_queue 
                    WHERE status IN ('sent', 'failed') 
                    AND processed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $this->db->query($sql)->execute();
            return true;
        } catch (\Exception $e) {
            error_log("EmailQueueService::cleanOldEmails Error: " . $e->getMessage());
            return false;
        }
    }
}
