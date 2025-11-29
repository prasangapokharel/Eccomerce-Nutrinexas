<?php

namespace App\Models;

use App\Core\Model;

/**
 * COD Payment Model
 * Handles Cash on Delivery payment records
 */
class CODPayment extends Model
{
    protected $table = 'cod_payments';
    protected $primaryKey = 'id';

    /**
     * Create a new COD payment record
     */
    public function createPayment($data)
    {
        return $this->create($data);
    }

    /**
     * Update COD payment record
     */
    public function updatePayment($id, $data)
    {
        return $this->update($id, $data);
    }

    /**
     * Update payment status by order ID
     */
    public function updateStatusByOrderId($orderId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE order_id = ?";
        return $this->db->query($sql)->bind([$status, $orderId])->execute();
    }

    /**
     * Find COD payment by order ID
     */
    public function findByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
        return $this->querySingle($sql, [$orderId]);
    }

    /**
     * Get all COD payments for a user
     */
    public function findByUserId($userId, $limit = 50)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        return $this->query($sql, [$userId, $limit]);
    }

    /**
     * Get payment statistics
     */
    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_payments,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount
                FROM {$this->table}";
        
        return $this->querySingle($sql);
    }
}





