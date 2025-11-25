<?php
namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    
    /**
     * Get notifications by user ID
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getByUserId($userId, $limit = 10)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        return $this->db->query($sql)->bind([$userId, $limit])->all();
    }
    
    /**
     * Get unread notifications count by user ID
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND is_read = 0";
        $result = $this->db->query($sql)->bind([$userId])->single();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Mark notification as read
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function markAsRead($id, $userId)
    {
        $sql = "UPDATE {$this->table} SET is_read = 1 WHERE id = ? AND user_id = ?";
        return $this->db->query($sql)->bind([$id, $userId])->execute();
    }
    
    /**
     * Mark all notifications as read for a user
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead($userId)
    {
        $sql = "UPDATE {$this->table} SET is_read = 1 WHERE user_id = ?";
        return $this->db->query($sql)->bind([$userId])->execute();
    }
    
    /**
     * Create a new notification
     *
     * @param array $data
     * @return int|bool
     */
    public function createNotification($data)
    {
        return $this->create($data);
    }
}
