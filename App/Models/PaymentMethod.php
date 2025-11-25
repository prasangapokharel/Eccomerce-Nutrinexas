<?php

namespace App\Models;

use App\Core\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';
    protected $primaryKey = 'id';

    /**
     * Get all active payment methods
     */
    public function getActive()
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY id";
        return $this->db->query($sql)->all();
    }

    /**
     * Get payment method by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql)->bind([$id])->single();
    }

    /**
     * Update payment method status
     */
    public function updateStatus($id, $isActive)
    {
        $sql = "UPDATE {$this->table} SET is_active = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql)->bind([$isActive, $id])->execute();
    }
}
