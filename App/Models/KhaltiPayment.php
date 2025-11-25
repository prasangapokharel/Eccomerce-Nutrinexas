<?php

namespace App\Models;

use App\Core\Model;

class KhaltiPayment extends Model
{
    protected $table = 'khalti_payments';
    protected $primaryKey = 'id';

    /**
     * Create Khalti payment record
     */
    public function createPayment($data)
    {
        $sql = "INSERT INTO {$this->table} (user_id, order_id, amount, pidx, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $result = $this->db->query($sql)->bind([
            $data['user_id'],
            $data['order_id'],
            $data['amount'],
            $data['pidx'] ?? null,
            $data['status'] ?? 'pending'
        ])->execute();
        
        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update payment status by order ID
     */
    public function updateStatusByOrderId($orderId, $status, $transactionId = null)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($transactionId) {
            $sql .= ", transaction_id = ?";
            $params[] = $transactionId;
        }
        
        $sql .= " WHERE order_id = ?";
        $params[] = $orderId;
        
        return $this->db->query($sql)->bind($params)->execute();
    }

    /**
     * Get payment by order ID
     */
    public function getByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ?";
        return $this->db->query($sql)->bind([$orderId])->single();
    }
}
