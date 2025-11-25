<?php

namespace App\Models;

use App\Core\Model;

class SiteWideSale extends Model
{
    protected $table = 'site_wide_sales';
    protected $primaryKey = 'id';

    /**
     * Get active sale
     */
    public function getActiveSale()
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND start_date <= ? 
                AND end_date >= ?
                ORDER BY created_at DESC 
                LIMIT 1";
        
        return $this->db->query($sql, [$now, $now])->single();
    }

    /**
     * Create a new sale
     */
    public function createSale($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (sale_name, discount_percent, start_date, end_date, is_active, note) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->query($sql, [
            $data['sale_name'],
            $data['discount_percent'],
            $data['start_date'],
            $data['end_date'],
            $data['is_active'] ?? 1,
            $data['note'] ?? null
        ])->execute();
        
        if ($result) {
            $saleId = $this->db->lastInsertId();
            // Apply sale to all products
            $this->applySaleToAllProducts($saleId, $data['discount_percent'], $data['start_date'], $data['end_date']);
            return $saleId;
        }
        
        return false;
    }

    /**
     * Apply sale to all active products
     */
    private function applySaleToAllProducts($saleId, $discountPercent, $startDate, $endDate)
    {
        $sql = "UPDATE products 
                SET sale_start_date = ?,
                    sale_end_date = ?,
                    sale_discount_percent = ?,
                    is_on_sale = 1,
                    updated_at = NOW()
                WHERE status = 'active' 
                AND (sale_price IS NULL OR sale_price = 0 OR sale_price >= price)";
        
        return $this->db->query($sql, [$startDate, $endDate, $discountPercent])->execute();
    }

    /**
     * Update sale status based on dates
     */
    public function updateSaleStatus()
    {
        $now = date('Y-m-d H:i:s');
        
        // Activate sales that should start
        $this->db->query("
            UPDATE {$this->table} 
            SET is_active = 1 
            WHERE start_date <= ? 
            AND end_date >= ? 
            AND is_active = 0
        ", [$now, $now])->execute();
        
        // Deactivate expired sales
        $this->db->query("
            UPDATE {$this->table} 
            SET is_active = 0 
            WHERE end_date < ? 
            AND is_active = 1
        ", [$now])->execute();
        
        // Update product sale status
        $this->updateProductSaleStatus();
    }

    /**
     * Update product sale status based on dates
     */
    private function updateProductSaleStatus()
    {
        $now = date('Y-m-d H:i:s');
        
        // Activate products on sale
        $this->db->query("
            UPDATE products 
            SET is_on_sale = 1 
            WHERE sale_start_date <= ? 
            AND sale_end_date >= ? 
            AND is_on_sale = 0
            AND sale_discount_percent > 0
        ", [$now, $now])->execute();
        
        // Deactivate expired product sales
        $this->db->query("
            UPDATE products 
            SET is_on_sale = 0 
            WHERE sale_end_date < ? 
            AND is_on_sale = 1
        ", [$now])->execute();
    }

    /**
     * Calculate sale price for a product
     */
    public function calculateSalePrice($originalPrice, $discountPercent)
    {
        if ($discountPercent <= 0 || $discountPercent >= 100) {
            return $originalPrice;
        }
        
        $discountAmount = ($originalPrice * $discountPercent) / 100;
        return max(0, $originalPrice - $discountAmount);
    }

    /**
     * Get all sales
     */
    public function getAllSales()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->query($sql)->all();
    }

    /**
     * Get sale by ID
     */
    public function getSaleById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id])->single();
    }

    /**
     * Update sale
     */
    public function updateSale($id, $data)
    {
        $sql = "UPDATE {$this->table} 
                SET sale_name = ?,
                    discount_percent = ?,
                    start_date = ?,
                    end_date = ?,
                    is_active = ?,
                    note = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        $result = $this->db->query($sql, [
            $data['sale_name'],
            $data['discount_percent'],
            $data['start_date'],
            $data['end_date'],
            $data['is_active'] ?? 1,
            $data['note'] ?? null,
            $id
        ])->execute();
        
        if ($result) {
            // Reapply sale to all products
            $this->applySaleToAllProducts($id, $data['discount_percent'], $data['start_date'], $data['end_date']);
        }
        
        return $result;
    }

    /**
     * Delete sale and remove from products
     */
    public function deleteSale($id)
    {
        // Remove sale from all products
        $this->db->query("
            UPDATE products 
            SET sale_start_date = NULL,
                sale_end_date = NULL,
                sale_discount_percent = 0,
                is_on_sale = 0,
                updated_at = NOW()
            WHERE sale_start_date IS NOT NULL
        ")->execute();
        
        // Delete sale record
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id])->execute();
    }
}

