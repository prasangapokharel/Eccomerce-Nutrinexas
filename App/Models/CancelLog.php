<?php

namespace App\Models;

use App\Core\Model;

class CancelLog extends Model
{
    protected $table = 'order_cancel_log';
    protected $primaryKey = 'id';

    /**
     * Create a new cancel log entry
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (order_id, seller_id, reason, status) VALUES (?, ?, ?, ?)";
        $params = [
            $data['order_id'],
            $data['seller_id'] ?? null,
            $data['reason'],
            $data['status'] ?? 'processing'
        ];
        
        if ($this->db->query($sql)->bind($params)->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get cancel log by order ID
     */
    public function getByOrderId($orderId)
    {
        return $this->db->query("SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY created_at DESC LIMIT 1", [$orderId])->single();
    }

    /**
     * Get all cancel logs with order details and seller name
     */
    public function getAllWithOrders()
    {
        try {
            $sql = "SELECT c.*, o.invoice, o.customer_name, u.email as customer_email, o.total_amount, o.status as order_status,
                           s.name as seller_name, s.company_name
                    FROM {$this->table} c
                    LEFT JOIN orders o ON c.order_id = o.id
                    LEFT JOIN users u ON o.user_id = u.id
                    LEFT JOIN sellers s ON c.seller_id = s.id
                    ORDER BY c.created_at DESC";
            return $this->db->query($sql)->all();
        } catch (\Exception $e) {
            error_log('CancelLog getAllWithOrders error: ' . $e->getMessage());
            // Return empty array if query fails
            return [];
        }
    }

    /**
     * Get cancel logs by status
     */
    public function getByStatus($status)
    {
        $sql = "SELECT c.*, o.invoice, o.customer_name, o.customer_email, o.total_amount, o.status as order_status,
                       s.name as seller_name, s.company_name
                FROM {$this->table} c
                LEFT JOIN orders o ON c.order_id = o.id
                LEFT JOIN sellers s ON c.seller_id = s.id
                WHERE c.status = ?
                ORDER BY c.created_at DESC";
        return $this->db->query($sql, [$status])->all();
    }

    /**
     * Get cancel logs by seller ID
     */
    public function getBySellerId($sellerId)
    {
        $sql = "SELECT c.*, o.invoice, o.customer_name, u.email as customer_email, o.total_amount, o.status as order_status
                FROM {$this->table} c
                LEFT JOIN orders o ON c.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE c.seller_id = ?
                ORDER BY c.created_at DESC";
        return $this->db->query($sql, [$sellerId])->all();
    }

    /**
     * Update cancel log status
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        return $this->db->query($sql, [$status, $id])->execute();
    }

    /**
     * Get cancel log by ID
     */
    public function find($id)
    {
        $sql = "SELECT c.*, o.invoice, o.customer_name, u.email as customer_email, o.total_amount, o.status as order_status,
                       s.name as seller_name, s.company_name, s.email as seller_email
                FROM {$this->table} c
                LEFT JOIN orders o ON c.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN sellers s ON c.seller_id = s.id
                WHERE c.id = ?";
        return $this->db->query($sql, [$id])->single();
    }
}

