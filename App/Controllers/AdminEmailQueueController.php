<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\EmailQueueService;
use App\Services\EmailProcessorService;

class AdminEmailQueueController extends Controller
{
    private $emailQueueService;
    private $emailProcessorService;
    
    public function __construct()
    {
        parent::__construct();
        $this->emailQueueService = new EmailQueueService();
        $this->emailProcessorService = new EmailProcessorService();
    }
    
    /**
     * Display email queue management page
     */
    public function index()
    {
        // Check admin access
        if (!$this->isAdmin()) {
            $this->redirect('auth/login');
            return;
        }
        
        // Get queue statistics
        $stats = $this->emailQueueService->getQueueStats();
        
        // Get recent emails
        $emails = $this->getRecentEmails();
        
        $this->view('admin/email-queue', [
            'title' => 'Email Queue Management',
            'stats' => $stats,
            'emails' => $emails
        ]);
    }
    
    /**
     * Process email queue
     */
    public function process()
    {
        // Check admin access
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        try {
            $result = $this->emailProcessorService->processEmails(20); // Process up to 20 emails
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } catch (\Exception $e) {
            error_log('AdminEmailQueueController::process Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process queue']);
        }
    }
    
    /**
     * Clean old emails
     */
    public function clean()
    {
        // Check admin access
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        try {
            $result = $this->emailQueueService->cleanOldEmails();
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
        } catch (\Exception $e) {
            error_log('AdminEmailQueueController::clean Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to clean old emails']);
        }
    }
    
    /**
     * Get recent emails for display
     */
    private function getRecentEmails($limit = 20)
    {
        try {
            $sql = "SELECT * FROM email_queue 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            
            $db = \App\Core\Database::getInstance();
            return $db->query($sql)->bind([$limit])->all();
        } catch (\Exception $e) {
            error_log('AdminEmailQueueController::getRecentEmails Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin()
    {
        return \App\Core\Session::has('user_id') && 
               \App\Core\Session::get('user_role') === 'admin';
    }
}
















