<?php

namespace App\Controllers\Seller;

use Exception;

class Support extends BaseSellerController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \App\Core\Database::getInstance();
    }

    public function index()
    {
        $statusFilter = $_GET['status'] ?? '';
        $params = [$this->sellerId];
        $where = "seller_id = ?";
        
        if ($statusFilter) {
            $where .= " AND status = ?";
            $params[] = $statusFilter;
        }
        
        $tickets = $this->db->query(
            "SELECT t.*, 
                    (SELECT COUNT(*) FROM seller_ticket_replies WHERE ticket_id = t.id) as reply_count,
                    (SELECT MAX(created_at) FROM seller_ticket_replies WHERE ticket_id = t.id) as last_reply_at
             FROM seller_support_tickets t
             WHERE {$where}
             ORDER BY updated_at DESC",
            $params
        )->all();
        
        $this->view('seller/support/index', [
            'title' => 'Support Tickets',
            'tickets' => $tickets,
            'statusFilter' => $statusFilter
        ]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $this->view('seller/support/create', [
            'title' => 'Create Support Ticket'
        ]);
    }

    public function detail($id)
    {
        $ticket = $this->db->query(
            "SELECT * FROM seller_support_tickets WHERE id = ? AND seller_id = ?",
            [$id, $this->sellerId]
        )->single();
        
        if (!$ticket) {
            $this->setFlash('error', 'Ticket not found');
            $this->redirect('seller/support');
            return;
        }
        
        $replies = $this->db->query(
            "SELECT * FROM seller_ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC",
            [$id]
        )->all();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
            $this->handleReply($id);
            return;
        }
        
        $this->view('seller/support/detail', [
            'title' => 'Ticket #' . $ticket['ticket_number'],
            'ticket' => $ticket,
            'replies' => $replies
        ]);
    }

    private function handleCreate()
    {
        try {
            $subject = trim($_POST['subject'] ?? '');
            $category = $_POST['category'] ?? 'general';
            $priority = $_POST['priority'] ?? 'medium';
            $message = trim($_POST['message'] ?? '');
            
            if (empty($subject) || empty($message)) {
                $this->setFlash('error', 'Subject and message are required');
                $this->redirect('seller/support/create');
                return;
            }
            
            $ticketNumber = 'TKT-' . strtoupper(uniqid());
            
            $result = $this->db->query(
                "INSERT INTO seller_support_tickets 
                 (seller_id, ticket_number, subject, category, priority, status) 
                 VALUES (?, ?, ?, ?, ?, 'open')",
                [$this->sellerId, $ticketNumber, $subject, $category, $priority]
            )->execute();
            
            if ($result) {
                $ticketId = $this->db->lastInsertId();
                
                $this->db->query(
                    "INSERT INTO seller_ticket_replies (ticket_id, user_id, user_type, message) 
                     VALUES (?, ?, 'seller', ?)",
                    [$ticketId, $this->sellerId, $message]
                )->execute();
                
                $this->setFlash('success', 'Support ticket created successfully');
                $this->redirect('seller/support/detail/' . $ticketId);
            } else {
                $this->setFlash('error', 'Failed to create ticket');
                $this->redirect('seller/support/create');
            }
        } catch (Exception $e) {
            error_log('Create ticket error: ' . $e->getMessage());
            $this->setFlash('error', 'Error creating ticket');
            $this->redirect('seller/support/create');
        }
    }

    private function handleReply($ticketId)
    {
        try {
            $message = trim($_POST['reply'] ?? '');
            
            if (empty($message)) {
                $this->setFlash('error', 'Message is required');
                $this->redirect('seller/support/detail/' . $ticketId);
                return;
            }
            
            $this->db->query(
                "INSERT INTO seller_ticket_replies (ticket_id, user_id, user_type, message) 
                 VALUES (?, ?, 'seller', ?)",
                [$ticketId, $this->sellerId, $message]
            )->execute();
            
            $this->db->query(
                "UPDATE seller_support_tickets SET status = 'waiting_reply', updated_at = NOW() WHERE id = ?",
                [$ticketId]
            )->execute();
            
            $this->setFlash('success', 'Reply sent successfully');
            $this->redirect('seller/support/detail/' . $ticketId);
        } catch (Exception $e) {
            error_log('Reply error: ' . $e->getMessage());
            $this->setFlash('error', 'Error sending reply');
            $this->redirect('seller/support/detail/' . $ticketId);
        }
    }
}

