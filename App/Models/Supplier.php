<?php
namespace App\Models;

use App\Core\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
    protected $primaryKey = 'supplier_id';

    /**
     * Get all active suppliers
     */
    public function getActiveSuppliers()
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY supplier_name ASC";
        $result = $this->query($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Get all suppliers (including inactive)
     */
    public function getAllSuppliers()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY supplier_name ASC";
        $result = $this->query($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Get supplier by ID
     */
    public function getSupplierById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE supplier_id = ?";
        $result = $this->query($sql, [$id]);
        
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return null;
    }

    /**
     * Create new supplier
     */
    public function createSupplier($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (supplier_name, phone, email, address, contact_person, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $result = $this->query($sql, [
            $data['supplier_name'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['contact_person'] ?? null,
            $data['status'] ?? 'active'
        ]);
        
        return $result !== false;
    }

    /**
     * Update supplier
     */
    public function updateSupplier($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                supplier_name = ?, phone = ?, email = ?, address = ?, 
                contact_person = ?, status = ?, updated_at = NOW() 
                WHERE supplier_id = ?";
        
        $result = $this->query($sql, [
            $data['supplier_name'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['contact_person'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
        
        return $result !== false;
    }

    /**
     * Delete supplier
     */
    public function deleteSupplier($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE supplier_id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Toggle supplier status
     */
    public function toggleStatus($id)
    {
        $sql = "UPDATE {$this->table} SET status = CASE 
                WHEN status = 'active' THEN 'inactive' 
                ELSE 'active' 
                END, updated_at = NOW() 
                WHERE supplier_id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Check if supplier exists by name
     */
    public function existsByName($name, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE supplier_name = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND supplier_id != ?";
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
     * Get supplier statistics
     */
    public function getSupplierStats()
    {
        $sql = "SELECT 
                COUNT(*) as total_suppliers,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_suppliers,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_suppliers
                FROM {$this->table}";
        
        $result = $this->query($sql);
        
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return [
            'total_suppliers' => 0,
            'active_suppliers' => 0,
            'inactive_suppliers' => 0
        ];
    }

    /**
     * Search suppliers
     */
    public function searchSuppliers($searchTerm)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE supplier_name LIKE ? OR phone LIKE ? OR email LIKE ? OR contact_person LIKE ?
                ORDER BY supplier_name ASC";
        
        $searchPattern = "%{$searchTerm}%";
        $result = $this->query($sql, [$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        return is_array($result) ? $result : [];
    }
}