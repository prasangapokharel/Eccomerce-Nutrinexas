<?php

namespace App\Models;

use App\Core\Model;

class EsewaPayment extends Model
{
    protected $table = 'esewa_payments';
    protected $primaryKey = 'id';

    /**
     * Create a new eSewa payment record
     */
    public function createPayment($data)
    {
        return $this->create($data);
    }

    /**
     * Update eSewa payment record
     */
    public function updatePayment($id, $data)
    {
        return $this->update($id, $data);
    }

    /**
     * Update payment status by order ID
     */
    public function updateStatusByOrderId($orderId, $status, $transactionId = null, $referenceId = null)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW()";
        $params = [$status];

        if ($transactionId) {
            $sql .= ", transaction_id = ?";
            $params[] = $transactionId;
        }

        if ($referenceId) {
            $sql .= ", reference_id = ?";
            $params[] = $referenceId;
        }

        $sql .= " WHERE order_id = ?";
        $params[] = $orderId;

        return $this->db->query($sql)->bind($params)->execute();
    }

    /**
     * Find eSewa payment by order ID
     */
    public function findByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
        return $this->querySingle($sql, [$orderId]);
    }

    /**
     * Find eSewa payment by transaction ID
     */
    public function findByTransactionId($transactionId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE transaction_id = ? LIMIT 1";
        return $this->querySingle($sql, [$transactionId]);
    }

    /**
     * Find eSewa payment by reference ID
     */
    public function findByReferenceId($referenceId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE reference_id = ? LIMIT 1";
        return $this->querySingle($sql, [$referenceId]);
    }

    /**
     * Get all eSewa payments for a user
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
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount
                FROM {$this->table}";
        
        return $this->querySingle($sql);
    }
}