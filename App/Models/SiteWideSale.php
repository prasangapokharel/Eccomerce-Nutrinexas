<?php

namespace App\Models;

use App\Core\Model;

class SiteWideSale extends Model
{
    protected $table = 'site_wide_sales';
    protected $primaryKey = 'id';

    /**
     * Get active sale (only one can be active at a time)
     */
    public function getActiveSale()
    {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND start_date IS NOT NULL
                AND end_date IS NOT NULL
                AND start_date <= ? 
                AND end_date >= ?
                ORDER BY created_at DESC 
                LIMIT 1";
        
        try {
            $result = $this->db->query($sql, [$now, $now])->single();
            // Database single() returns false when no rows found, or array when found
            if ($result === false || !is_array($result) || empty($result) || !isset($result['id']) || empty($result['id'])) {
                return false;
            }
            return $result;
        } catch (\Exception $e) {
            error_log('getActiveSale error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if active sale exists
     */
    public function hasActiveSale()
    {
        try {
            $activeSale = $this->getActiveSale();
            // getActiveSale returns false if no active sale exists
            if ($activeSale === false) {
                return false;
            }
            // If we got a result, verify it's valid
            if (is_array($activeSale) && isset($activeSale['id']) && !empty($activeSale['id'])) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            error_log('hasActiveSale error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new sale (only if no active sale exists)
     */
    public function createSale($data)
    {
        // Check if active sale already exists
        if ($this->hasActiveSale()) {
            return false;
        }

        // Check if discount_percent column exists (for backward compatibility)
        try {
            $hasDiscountPercent = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'discount_percent'")->single();
            
            if ($hasDiscountPercent && !empty($hasDiscountPercent)) {
                $sql = "INSERT INTO {$this->table} 
                        (sale_name, sale_percent, discount_percent, start_date, end_date, is_active, note) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $result = $this->db->query($sql, [
                    $data['sale_name'] ?? 'Site-Wide Sale',
                    $data['sale_percent'],
                    null, // discount_percent - deprecated, use sale_percent
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'] ?? 1,
                    $data['note'] ?? null
                ])->execute();
            } else {
                $sql = "INSERT INTO {$this->table} 
                        (sale_name, sale_percent, start_date, end_date, is_active, note) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                
                $result = $this->db->query($sql, [
                    $data['sale_name'] ?? 'Site-Wide Sale',
                    $data['sale_percent'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'] ?? 1,
                    $data['note'] ?? null
                ])->execute();
            }
        } catch (\Exception $e) {
            error_log('createSale error: ' . $e->getMessage());
            throw $e;
        }
        
        return $result ? $this->db->lastInsertId() : false;
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
        // If activating this sale, check if another active sale exists
        if (isset($data['is_active']) && $data['is_active'] == 1) {
            $activeSale = $this->getActiveSale();
            if ($activeSale && $activeSale['id'] != $id) {
                return false; // Another active sale exists
            }
        }

        $sql = "UPDATE {$this->table} 
                SET sale_name = ?,
                    sale_percent = ?,
                    start_date = ?,
                    end_date = ?,
                    is_active = ?,
                    note = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        return $this->db->query($sql, [
            $data['sale_name'] ?? 'Site-Wide Sale',
            $data['sale_percent'],
            $data['start_date'],
            $data['end_date'],
            $data['is_active'] ?? 0,
            $data['note'] ?? null,
            $id
        ])->execute();
    }

    /**
     * Delete sale
     */
    public function deleteSale($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id])->execute();
    }

    /**
     * Calculate sale price for a product
     */
    public function calculateSalePrice($originalPrice, $salePercent)
    {
        if ($salePercent <= 0 || $salePercent >= 100) {
            return $originalPrice;
        }
        
        $discountAmount = ($originalPrice * $salePercent) / 100;
        return max(0, $originalPrice - $discountAmount);
    }
}
