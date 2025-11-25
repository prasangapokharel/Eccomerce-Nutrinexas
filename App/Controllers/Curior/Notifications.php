<?php

namespace App\Controllers\Curior;

use App\Core\Database;

class Notifications extends BaseCuriorController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Notifications page
     */
    public function index()
    {
        $sql = "SELECT * FROM order_activities 
                WHERE created_by = ? 
                OR (action IN ('order_assigned', 'urgent_delivery', 'cod_reminder', 'return_pickup_alert') AND order_id IN (SELECT id FROM orders WHERE curior_id = ?))
                ORDER BY created_at DESC 
                LIMIT 100";
        
        $notifications = $this->db->query($sql, ['curior_' . $this->curiorId, $this->curiorId])->all();
        
        $this->view('curior/notifications/index', [
            'notifications' => $notifications,
            'page' => 'notifications',
            'title' => 'Notifications'
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markRead()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->db->query("UPDATE order_activities SET is_read = 1 WHERE id = ?", [$id])->execute();
        }

        $this->jsonResponse(['success' => true]);
    }
}

