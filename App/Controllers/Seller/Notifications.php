<?php

namespace App\Controllers\Seller;

use Exception;

class Notifications extends BaseSellerController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \App\Core\Database::getInstance();
    }

    public function index()
    {
        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';
        
        $params = [$this->sellerId];
        $where = "seller_id = ?";
        
        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }
        
        $notifications = $this->db->query(
            "SELECT * FROM seller_notifications 
             WHERE {$where}
             ORDER BY created_at DESC 
             LIMIT 100",
            $params
        )->all();
        
        $unreadCount = $this->db->query(
            "SELECT COUNT(*) as count FROM seller_notifications WHERE seller_id = ? AND is_read = 0",
            [$this->sellerId]
        )->single()['count'] ?? 0;
        
        $this->view('seller/notifications/index', [
            'title' => 'Notifications',
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'unreadOnly' => $unreadOnly
        ]);
    }

    public function markRead($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->db->query(
                "UPDATE seller_notifications SET is_read = 1 WHERE id = ? AND seller_id = ?",
                [$id, $this->sellerId]
            )->execute();
            
            $this->setFlash('success', 'Notification marked as read');
        }
        
        $this->redirect('seller/notifications');
    }

    public function markAllRead()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->db->query(
                "UPDATE seller_notifications SET is_read = 1 WHERE seller_id = ? AND is_read = 0",
                [$this->sellerId]
            )->execute();
            
            $this->setFlash('success', 'All notifications marked as read');
        }
        
        $this->redirect('seller/notifications');
    }
}

