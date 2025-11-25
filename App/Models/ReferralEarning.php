<?php
namespace App\Models;

use App\Core\Model;

class ReferralEarning extends Model
{
    protected $table = 'referral_earnings';
    protected $primaryKey = 'id';
    
    /**
     * Find referral earning by order ID
     *
     * @param int $orderId
     * @return array|false
     */
    public function findByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ?";
        return $this->db->query($sql)->bind([$orderId])->single();
    }
    
    /**
     * Get referral earnings by user ID
     *
     * @param int $userId
     * @return array
     */
    public function getByUserId($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return $this->db->query($sql)->bind([$userId])->all();
    }
    
    /**
     * Get referral earnings by user ID with order details
     *
     * @param int $userId
     * @return array
     */
    public function getByUserIdWithFullDetails($userId)
    {
        $sql = "SELECT re.*, o.invoice, o.total_amount, u.first_name, u.last_name, u.email as referred_user
                FROM {$this->table} re
                JOIN orders o ON re.order_id = o.id
                JOIN users u ON o.user_id = u.id
                WHERE re.user_id = ?
                ORDER BY re.created_at DESC";
        return $this->db->query($sql)->bind([$userId])->all();
    }
    
    /**
     * Get total earnings for a user
     *
     * @param int $userId
     * @return float
     */
    public function getTotalEarnings($userId)
    {
        $sql = "SELECT SUM(amount) as total FROM {$this->table} WHERE user_id = ? AND status != 'cancelled'";
        $result = $this->db->query($sql)->bind([$userId])->single();
        return $result && $result['total'] ? (float)$result['total'] : 0;
    }
    
    /**
     * Get count of referral earnings by user ID
     *
     * @param int $userId
     * @return int
     */
    public function getCountByUserId($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND status != 'cancelled'";
        $result = $this->db->query($sql)->bind([$userId])->single();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get all referral earnings with details
     *
     * @return array
     */
    public function getAllWithDetails()
    {
        $sql = "SELECT re.*, o.invoice, o.total_amount, u.first_name, u.last_name, u.email as referrer_email,
                ru.first_name as referred_first_name, ru.last_name as referred_last_name, ru.email as referred_email
                FROM {$this->table} re
                JOIN orders o ON re.order_id = o.id
                JOIN users u ON re.user_id = u.id
                JOIN users ru ON o.user_id = ru.id
                ORDER BY re.created_at DESC";
        return $this->db->query($sql)->all();
    }
    
    /**
     * Get pending referral earnings
     *
     * @param int $limit
     * @return array
     */
    public function getPendingEarnings($limit = 10)
    {
        $sql = "SELECT re.*, o.invoice, o.total_amount, u.first_name, u.last_name, u.email as referrer_email
                FROM {$this->table} re
                JOIN orders o ON re.order_id = o.id
                JOIN users u ON re.user_id = u.id
                WHERE re.status = 'pending'
                ORDER BY re.created_at ASC
                LIMIT ?";
        return $this->db->query($sql)->bind([$limit])->all();
    }
    
    /**
     * Get referral earnings by status
     *
     * @param string $status
     * @param int $limit
     * @return array
     */
    public function getEarningsByStatus($status, $limit = 50)
    {
        $sql = "SELECT re.*, o.invoice, o.total_amount, u.first_name, u.last_name, u.email as referrer_email
                FROM {$this->table} re
                JOIN orders o ON re.order_id = o.id
                JOIN users u ON re.user_id = u.id
                WHERE re.status = ?
                ORDER BY re.created_at DESC
                LIMIT ?";
        return $this->db->query($sql)->bind([$status, $limit])->all();
    }
    
    /**
     * Update multiple earnings status
     *
     * @param array $ids
     * @param string $status
     * @return bool
     */
    public function updateMultipleStatus($ids, $status)
    {
        if (empty($ids)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$status], $ids);
        
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)";
        return $this->db->query($sql)->bind($params)->execute();
    }
}