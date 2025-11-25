<?php

namespace App\Models;

use App\Core\Model;

class WholesaleProduct extends Model
{
    protected $table = 'wholesale_products';
    protected $primaryKey = 'product_id';

    /**
     * Get all products with supplier information
     */
    public function getAllProductsWithSuppliers()
    {
        $sql = "SELECT wp.*, s.supplier_name, s.phone as supplier_phone, s.email as supplier_email 
                FROM {$this->table} wp 
                LEFT JOIN suppliers s ON wp.supplier_id = s.supplier_id 
                ORDER BY wp.product_name ASC";
        
        $result = $this->query($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Get product by ID with supplier information
     */
    public function getProductById($id)
    {
        $sql = "SELECT wp.*, s.supplier_name, s.phone as supplier_phone, s.email as supplier_email, s.address as supplier_address, s.contact_person
                FROM {$this->table} wp 
                LEFT JOIN suppliers s ON wp.supplier_id = s.supplier_id 
                WHERE wp.product_id = ?";
        
        $result = $this->query($sql, [$id]);
        
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return null;
    }

    /**
     * Find product by SKU or barcode
     */
    public function findBySku($sku)
    {
        $sql = "SELECT wp.*, s.supplier_name, s.phone as supplier_phone, s.email as supplier_email
                FROM {$this->table} wp 
                LEFT JOIN suppliers s ON wp.supplier_id = s.supplier_id 
                WHERE wp.sku = ? AND wp.status = 'active'";
        
        $result = $this->query($sql, [$sku]);
        
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return null;
    }

    /**
     * Create new wholesale product
     */
    public function createProduct($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (supplier_id, product_name, type, cost_amount, selling_price, quantity, min_stock_level, description, sku, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $result = $this->query($sql, [
            $data['supplier_id'],
            $data['product_name'],
            $data['type'] ?? null,
            $data['cost_amount'],
            $data['selling_price'] ?? null,
            $data['quantity'] ?? 0,
            $data['min_stock_level'] ?? 10,
            $data['description'] ?? null,
            $data['sku'] ?? null,
            $data['status'] ?? 'active'
        ]);
        
        return $result !== false;
    }

    /**
     * Update wholesale product
     */
    public function updateProduct($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                supplier_id = ?, product_name = ?, type = ?, cost_amount = ?, selling_price = ?, 
                quantity = ?, min_stock_level = ?, description = ?, sku = ?, status = ?, updated_at = NOW() 
                WHERE product_id = ?";
        
        $result = $this->query($sql, [
            $data['supplier_id'],
            $data['product_name'],
            $data['type'] ?? null,
            $data['cost_amount'],
            $data['selling_price'] ?? null,
            $data['quantity'] ?? 0,
            $data['min_stock_level'] ?? 10,
            $data['description'] ?? null,
            $data['sku'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
        
        return $result !== false;
    }

    /**
     * Delete wholesale product
     */
    public function deleteProduct($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE product_id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Check if product exists by SKU
     */
    public function existsBySku($sku, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE sku = ?";
        $params = [$sku];
        
        if ($excludeId) {
            $sql .= " AND product_id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->query($sql, $params);
        
        if (is_array($result) && !empty($result)) {
            $row = isset($result[0]) ? $result[0] : $result;
            return isset($row['count']) && $row['count'] > 0;
        }
        
        return false;
    }

    /**
     * Update product quantity
     */
    public function updateQuantity($id, $newQuantity)
    {
        $sql = "UPDATE {$this->table} SET quantity = ?, updated_at = NOW() WHERE product_id = ?";
        $result = $this->query($sql, [$newQuantity, $id]);
        return $result !== false;
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts($threshold = 5)
    {
        $sql = "SELECT wp.*, s.supplier_name 
                FROM {$this->table} wp 
                LEFT JOIN suppliers s ON wp.supplier_id = s.supplier_id 
                WHERE wp.quantity <= ? AND wp.status = 'active' 
                ORDER BY wp.quantity ASC";
        
        $result = $this->query($sql, [$threshold]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get product statistics
     */
    public function getProductStats()
    {
        $sql = "SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_products,
                SUM(CASE WHEN status = 'discontinued' THEN 1 ELSE 0 END) as discontinued_products,
                SUM(quantity) as total_quantity,
                SUM(CASE WHEN quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock_count,
                AVG(cost_amount) as avg_cost,
                AVG(selling_price) as avg_selling_price
                FROM {$this->table}";
        
        $result = $this->query($sql);
        
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return [
            'total_products' => 0,
            'active_products' => 0,
            'inactive_products' => 0,
            'discontinued_products' => 0,
            'total_quantity' => 0,
            'low_stock_count' => 0,
            'avg_cost' => 0,
            'avg_selling_price' => 0
        ];
    }

    /**
     * Search products
     */
    public function searchProducts($searchTerm)
    {
        $sql = "SELECT wp.*, s.supplier_name 
                FROM {$this->table} wp 
                LEFT JOIN suppliers s ON wp.supplier_id = s.supplier_id 
                WHERE wp.product_name LIKE ? OR wp.sku LIKE ? OR wp.type LIKE ? OR s.supplier_name LIKE ?
                ORDER BY wp.product_name ASC";
        
        $searchPattern = "%{$searchTerm}%";
        $result = $this->query($sql, [$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get products by supplier
     */
    public function getProductsBySupplier($supplierId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE supplier_id = ? ORDER BY product_name ASC";
        $result = $this->query($sql, [$supplierId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get products by type
     */
    public function getProductsByType($type)
    {
        $sql = "SELECT wp.*, s.supplier_name 
                FROM {$this->table} wp 
                LEFT JOIN suppliers s ON wp.supplier_id = s.supplier_id 
                WHERE wp.type = ? AND wp.status = 'active' 
                ORDER BY wp.product_name ASC";
        
        $result = $this->query($sql, [$type]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get all product types
     */
    public function getProductTypes()
    {
        $sql = "SELECT DISTINCT type FROM {$this->table} WHERE type IS NOT NULL AND type != '' ORDER BY type ASC";
        $result = $this->query($sql);
        
        if (is_array($result)) {
            return array_column($result, 'type');
        }
        
        return [];
    }

    /**
     * Toggle product status
     */
    public function toggleStatus($id)
    {
        $sql = "UPDATE {$this->table} SET status = CASE 
                WHEN status = 'active' THEN 'inactive' 
                ELSE 'active' 
                END, updated_at = NOW() 
                WHERE product_id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Get products with profit margin
     */
    public function getProductsWithProfitMargin()
    {
        $sql = "SELECT wp.*, s.supplier_name,
                (wp.selling_price - wp.cost_amount) as profit_amount,
                CASE 
                    WHEN wp.selling_price > 0 THEN ROUND(((wp.selling_price - wp.cost_amount) / wp.selling_price) * 100, 2)
                    ELSE 0 
                END as profit_margin_percent
                FROM {$this->table} wp 
                LEFT JOIN suppliers s ON wp.supplier_id = s.supplier_id 
                WHERE wp.status = 'active' AND wp.selling_price > 0
                ORDER BY profit_margin_percent DESC";
        
        $result = $this->query($sql);
        return is_array($result) ? $result : [];
    }
}
