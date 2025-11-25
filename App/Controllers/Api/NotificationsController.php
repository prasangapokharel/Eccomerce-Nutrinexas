<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Notification;

class NotificationsController extends Controller
{
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->notificationModel = new Notification();
    }

    /**
     * Get notification count for current user
     */
    public function count()
    {
        header('Content-Type: application/json');
        
        try {
            if (!Session::has('user_id')) {
                echo json_encode(['success' => true, 'count' => 0]);
                return;
            }

            $userId = Session::get('user_id');
            $count = $this->notificationModel->getUnreadCount($userId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
        } catch (Exception $e) {
            error_log('Notification count error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load notification count',
                'count' => 0
            ]);
        }
    }
}


















