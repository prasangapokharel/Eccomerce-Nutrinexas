<?php

namespace App\Controllers\Curior;

use App\Core\Database;

class Support extends BaseCuriorController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Help & Support page
     */
    public function index()
    {
        $this->view('curior/support/index', [
            'page' => 'support',
            'title' => 'Help & Support'
        ]);
    }

    /**
     * Submit support request
     */
    public function submit()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type = trim($_POST['type'] ?? 'general');

        if (empty($subject) || empty($message)) {
            $this->jsonResponse(['success' => false, 'message' => 'Subject and message are required']);
            return;
        }

        try {
            $sql = "INSERT INTO courier_support_requests (curior_id, subject, message, type, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())";
            $this->db->query($sql, [$this->curiorId, $subject, $message, $type])->execute();
            
            $this->jsonResponse(['success' => true, 'message' => 'Support request submitted successfully']);
        } catch (\Exception $e) {
            error_log('Curior Support: Submit error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error submitting request']);
        }
    }
}

