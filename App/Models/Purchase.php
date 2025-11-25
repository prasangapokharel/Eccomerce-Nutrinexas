<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class Purchase extends Model
{
    protected $table = 'purchases';
    protected $primaryKey = 'purchase_id';
    private $cache;

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }

    /**
     * Get all purchases with product and supplier information
     */
    public function getAllPurchases()
    {
        $cacheKey = 'purchases_all';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT p.*, wp.product_name, wp.sku, s.supplier_name,
                (SELECT SUM(amount) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id) as paid_amount,
                (p.total_amount - COALESCE((SELECT SUM(amount) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id), 0)) as remaining_amount
                FROM {$this->table} p
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                ORDER BY p.purchase_date DESC";
        
        $result = $this->query($sql);
        $data = is_array($result) ? $result : [];
        
        $this->cache->set($cacheKey, $data, 300); // Cache for 5 minutes
        return $data;
    }

    /**
     * Get purchase by ID with full details
     */
    public function getPurchaseById($id)
    {
        $cacheKey = "purchase_{$id}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT p.*, wp.product_name, wp.sku, s.supplier_name,
                (SELECT SUM(amount) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id) as paid_amount,
                (p.total_amount - COALESCE((SELECT SUM(amount) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id), 0)) as remaining_amount
                FROM {$this->table} p
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE p.purchase_id = ?";
        
        $result = $this->query($sql, [$id]);
        
        $data = null;
        if (is_array($result) && !empty($result)) {
            $data = isset($result[0]) ? $result[0] : $result;
        }
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * Get purchases by supplier ID
     */
    public function getPurchasesBySupplier($supplierId)
    {
        $cacheKey = "purchases_supplier_{$supplierId}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT p.*, wp.product_name, wp.sku,
                (SELECT SUM(amount) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id) as paid_amount,
                (p.total_amount - COALESCE((SELECT SUM(amount) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id), 0)) as remaining_amount
                FROM {$this->table} p
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                WHERE p.supplier_id = ?
                ORDER BY p.purchase_date DESC";
        
        $result = $this->query($sql, [$supplierId]);
        $data = is_array($result) ? $result : [];
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * Create new purchase
     */
    public function createPurchase($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (product_id, supplier_id, quantity, unit_cost, total_amount, 
                 payment_method, status, purchase_date, expected_delivery, notes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $result = $this->query($sql, [
            $data['product_id'],
            $data['supplier_id'],
            $data['quantity'],
            $data['unit_cost'],
            $data['total_amount'],
            $data['payment_method'] ?? 'cod',
            $data['status'] ?? 'pending',
            $data['purchase_date'] ?? date('Y-m-d H:i:s'),
            $data['expected_delivery'] ?? null,
            $data['notes'] ?? null
        ]);
        
        if ($result !== false) {
            $this->clearPurchaseCache();
        }
        
        return $result !== false;
    }

    /**
     * Update purchase
     */
    public function updatePurchase($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                product_id = ?, supplier_id = ?, quantity = ?, unit_cost = ?, 
                total_amount = ?, payment_method = ?, status = ?, 
                purchase_date = ?, expected_delivery = ?, notes = ?, updated_at = NOW() 
                WHERE purchase_id = ?";
        
        $result = $this->query($sql, [
            $data['product_id'],
            $data['supplier_id'],
            $data['quantity'],
            $data['unit_cost'],
            $data['total_amount'],
            $data['payment_method'] ?? 'cod',
            $data['status'] ?? 'pending',
            $data['purchase_date'] ?? date('Y-m-d H:i:s'),
            $data['expected_delivery'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
        
        if ($result !== false) {
            $this->clearPurchaseCache();
        }
        
        return $result !== false;
    }

    /**
     * Delete purchase
     */
    public function deletePurchase($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE purchase_id = ?";
        $result = $this->query($sql, [$id]);
        
        if ($result !== false) {
            $this->clearPurchaseCache();
        }
        
        return $result !== false;
    }

    /**
     * Update purchase status
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE purchase_id = ?";
        $result = $this->query($sql, [$status, $id]);
        
        if ($result !== false) {
            $this->clearPurchaseCache();
        }
        
        return $result !== false;
    }

    /**
     * Get purchase statistics
     */
    public function getPurchaseStats()
    {
        $cacheKey = 'purchase_stats';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT 
                COUNT(*) as total_purchases,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_purchases,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_purchases,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_purchases,
                SUM(CASE WHEN status = 'remaining' THEN 1 ELSE 0 END) as remaining_purchases,
                SUM(total_amount) as total_purchase_amount,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status IN ('partial', 'remaining') THEN total_amount ELSE 0 END) as outstanding_amount
                FROM {$this->table}";
        
        $result = $this->query($sql);
        
        $data = [
            'total_purchases' => 0,
            'pending_purchases' => 0,
            'paid_purchases' => 0,
            'partial_purchases' => 0,
            'remaining_purchases' => 0,
            'total_purchase_amount' => 0,
            'paid_amount' => 0,
            'outstanding_amount' => 0
        ];
        
        if (is_array($result) && !empty($result)) {
            $data = isset($result[0]) ? $result[0] : $result;
        }
        
        $this->cache->set($cacheKey, $data, 600); // Cache for 10 minutes
        return $data;
    }

    /**
     * Get recent purchases
     */
    public function getRecentPurchases($limit = 10)
    {
        $cacheKey = "recent_purchases_{$limit}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT p.*, wp.product_name, s.supplier_name
                FROM {$this->table} p
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                ORDER BY p.purchase_date DESC
                LIMIT ?";
        
        $result = $this->query($sql, [$limit]);
        $data = is_array($result) ? $result : [];
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * Search purchases
     */
    public function searchPurchases($searchTerm)
    {
        $searchPattern = "%{$searchTerm}%";
        
        $sql = "SELECT p.*, wp.product_name, wp.sku, s.supplier_name
                FROM {$this->table} p
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE wp.product_name LIKE ? OR wp.sku LIKE ? OR s.supplier_name LIKE ? OR p.notes LIKE ?
                ORDER BY p.purchase_date DESC";
        
        $result = $this->query($sql, [$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get purchases by date range
     */
    public function getPurchasesByDateRange($startDate, $endDate)
    {
        $sql = "SELECT p.*, wp.product_name, s.supplier_name
                FROM {$this->table} p
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE p.purchase_date BETWEEN ? AND ?
                ORDER BY p.purchase_date DESC";
        
        $result = $this->query($sql, [$startDate, $endDate]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get purchases by status
     */
    public function getPurchasesByStatus($status)
    {
        $cacheKey = "purchases_status_{$status}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT p.*, wp.product_name, s.supplier_name
                FROM {$this->table} p
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE p.status = ?
                ORDER BY p.purchase_date DESC";
        
        $result = $this->query($sql, [$status]);
        $data = is_array($result) ? $result : [];
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * Clear purchase-related cache
     */
    private function clearPurchaseCache()
    {
        $this->cache->delete('purchases_all');
        $this->cache->delete('purchase_stats');
        $this->cache->deletePattern('purchase_*');
        $this->cache->deletePattern('purchases_*');
        $this->cache->deletePattern('recent_purchases_*');
    }
}