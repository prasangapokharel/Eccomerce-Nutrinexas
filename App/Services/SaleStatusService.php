<?php

namespace App\Services;

use App\Core\Database;
use App\Models\SiteWideSale;

/**
 * Service to update sale statuses automatically
 * Should be called via cron job or scheduled task
 */
class SaleStatusService
{
    private $db;
    private $saleModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->saleModel = new SiteWideSale();
    }

    /**
     * Update all sale statuses
     * Call this method periodically (e.g., via cron job every hour)
     */
    public function updateAllSaleStatuses()
    {
        try {
            // Update site-wide sale statuses
            $this->saleModel->updateSaleStatus();
            
            // Update product sale prices based on active sales
            $this->updateProductSalePrices();
            
            return true;
        } catch (\Exception $e) {
            error_log('SaleStatusService Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update product sale prices based on active site-wide sales
     */
    private function updateProductSalePrices()
    {
        $activeSale = $this->saleModel->getActiveSale();
        
        if (!$activeSale) {
            // No active sale - deactivate all product sales
            $this->db->query("
                UPDATE products 
                SET is_on_sale = 0,
                    updated_at = NOW()
                WHERE is_on_sale = 1
            ")->execute();
            return;
        }
        
        $now = date('Y-m-d H:i:s');
        
        // Update products that should be on sale
        $this->db->query("
            UPDATE products 
            SET sale_start_date = ?,
                sale_end_date = ?,
                sale_discount_percent = ?,
                is_on_sale = 1,
                updated_at = NOW()
            WHERE status = 'active'
            AND (sale_start_date IS NULL OR sale_start_date != ? OR sale_end_date != ? OR sale_discount_percent != ?)
        ", [
            $activeSale['start_date'],
            $activeSale['end_date'],
            $activeSale['discount_percent'],
            $activeSale['start_date'],
            $activeSale['end_date'],
            $activeSale['discount_percent']
        ])->execute();
        
        // Deactivate expired product sales
        $this->db->query("
            UPDATE products 
            SET is_on_sale = 0,
                updated_at = NOW()
            WHERE sale_end_date < ?
            AND is_on_sale = 1
        ", [$now])->execute();
    }
}

