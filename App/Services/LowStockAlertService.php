<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Product;
use App\Models\Setting;

/**
 * Low Stock Alert Service
 * Monitors and alerts when products are running low
 */
class LowStockAlertService
{
    private $db;
    private $productModel;
    private $settingModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->productModel = new Product();
        // Setting model is optional - use default threshold if not available
        try {
            $this->settingModel = new Setting();
        } catch (Exception $e) {
            $this->settingModel = null;
        }
    }

    /**
     * Get products with low stock
     */
    public function getLowStockProducts(int $threshold = null): array
    {
        if ($threshold === null) {
            if ($this->settingModel && method_exists($this->settingModel, 'get')) {
                $threshold = (int)$this->settingModel->get('low_stock_threshold', 10);
            } else {
                $threshold = 10; // Default threshold
            }
        }

        $sql = "SELECT id, product_name, stock_quantity, category, price
                FROM products
                WHERE stock_quantity <= ? AND stock_quantity > 0 AND status = 'active'
                ORDER BY stock_quantity ASC";
        
        return $this->db->query($sql)->bind([$threshold])->all();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts(): array
    {
        $sql = "SELECT id, product_name, stock_quantity, category, price
                FROM products
                WHERE stock_quantity = 0 AND status = 'active'
                ORDER BY product_name ASC";
        
        return $this->db->query($sql)->all();
    }

    /**
     * Send low stock alerts to admin
     */
    public function sendLowStockAlerts(): array
    {
        $results = [
            'low_stock_count' => 0,
            'out_of_stock_count' => 0,
            'alerts_sent' => 0
        ];

        try {
            $lowStockProducts = $this->getLowStockProducts();
            $outOfStockProducts = $this->getOutOfStockProducts();

            $results['low_stock_count'] = count($lowStockProducts);
            $results['out_of_stock_count'] = count($outOfStockProducts);

            // TODO: Send email/SMS alerts to admin
            if (count($lowStockProducts) > 0 || count($outOfStockProducts) > 0) {
                error_log("Low stock alert: {$results['low_stock_count']} low stock, {$results['out_of_stock_count']} out of stock");
                $results['alerts_sent'] = 1;
            }

            return $results;
        } catch (Exception $e) {
            error_log("Low stock alert error: " . $e->getMessage());
            return $results;
        }
    }
}

