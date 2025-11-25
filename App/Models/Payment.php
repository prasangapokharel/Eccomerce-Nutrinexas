<?php
namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    protected $table = 'payment';
    protected $primaryKey = 'id';
    
    /**
     * Get payment by order ID
     *
     * @param int $orderId
     * @return array|null
     */
    public function getByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE booking_id = ? ORDER BY created_at DESC LIMIT 1";
        return $this->db->query($sql)->bind([$orderId])->single();
    }
    
    /**
     * Update payment status by order ID
     *
     * @param int $orderId
     * @param string $status
     * @return bool
     */
    public function updateStatusByOrderId($orderId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ? WHERE booking_id = ?";
        return $this->db->query($sql)->bind([$status, $orderId])->execute();
    }
    
    /**
     * Create a new payment record
     *
     * @param array $data
     * @return int|false
     */
    public function createPayment($data)
    {
        return $this->create($data);
    }
    
    /**
     * Get all payments with user and order details
     *
     * @return array
     */
    public function getAllWithDetails()
    {
        $sql = "SELECT p.*, u.username, u.email, o.invoice, o.total_amount, o.status as order_status
                FROM {$this->table} p
                JOIN users u ON p.user_id = u.id
                JOIN orders o ON p.booking_id = o.id
                ORDER BY p.created_at DESC";
        return $this->db->query($sql)->all();
    }
}
