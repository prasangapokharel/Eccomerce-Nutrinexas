<?php

namespace App\Models;

use App\Core\Model;

class StockMovementLog extends Model
{
    protected $table = 'stock_movement_log';
    protected $primaryKey = 'id';

    /**
     * Log stock movement
     */
    public function log($productId, $movementType, $quantity, $previousStock, $newStock, $options = [])
    {
        $data = [
            'product_id' => $productId,
            'seller_id' => $options['seller_id'] ?? null,
            'movement_type' => $movementType,
            'quantity' => abs($quantity),
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'notes' => $options['notes'] ?? null,
            'created_by' => $options['created_by'] ?? null
        ];

        return $this->db->query(
            "INSERT INTO {$this->table} 
             (product_id, seller_id, movement_type, quantity, previous_stock, new_stock, reference_type, reference_id, notes, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['product_id'], $data['seller_id'], $data['movement_type'],
                $data['quantity'], $data['previous_stock'], $data['new_stock'],
                $data['reference_type'], $data['reference_id'], $data['notes'], $data['created_by']
            ]
        )->execute();
    }

    /**
     * Get stock movements for a seller
     */
    public function getBySellerId($sellerId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT sml.*, p.product_name, p.seller_id
                FROM {$this->table} sml
                INNER JOIN products p ON sml.product_id = p.id
                WHERE p.seller_id = ?
                ORDER BY sml.created_at DESC
                LIMIT ? OFFSET ?";
        
        return $this->db->query($sql, [$sellerId, $limit, $offset])->all();
    }

    /**
     * Get stock movements for a product
     */
    public function getByProductId($productId, $limit = 50)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE product_id = ?
                ORDER BY created_at DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$productId, $limit])->all();
    }
}

