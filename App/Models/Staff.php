<?php
namespace App\Models;

use App\Core\Model;

class Staff extends Model
{
    protected $table = 'staff';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'phone', 'email', 'address', 'password', 'assignment_type', 'assigned_cities', 'assigned_products', 'status', 'created_at', 'updated_at'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all staff members
     */
    public function getAllStaff()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->query($sql)->all();
    }

    /**
     * Get staff by email
     */
    public function getByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->query($sql, [$email])->single();
    }

    /**
     * Find staff by email (alias for getByEmail)
     */
    public function findByEmail($email)
    {
        return $this->getByEmail($email);
    }

    /**
     * Get staff by phone
     */
    public function getByPhone($phone)
    {
        $sql = "SELECT * FROM {$this->table} WHERE phone = ?";
        return $this->db->query($sql, [$phone])->single();
    }

    /**
     * Get staff by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id])->single();
    }

    /**
     * Create new staff member
     */
    public function create($data)
    {
        // Hash password if it's not already hashed
        if (!empty($data['password']) && !password_get_info($data['password'])['algo']) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Use the basic staff table structure
        $sql = "INSERT INTO {$this->table} (name, phone, email, address, password, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->db->query($sql, [
            $data['name'],
            $data['phone'],
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['password'],
            $data['status'] ?? 'active',
            $data['created_at'],
            $data['updated_at']
        ]);
    }

    /**
     * Update staff member
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE {$this->table} SET name = ?, phone = ?, email = ?, address = ?, password = ?, status = ?, updated_at = ? WHERE id = ?";
            return $this->db->query($sql, [
                $data['name'],
                $data['phone'],
                $data['email'] ?? null,
                $data['address'] ?? null,
                $data['password'],
                $data['status'],
                $data['updated_at'],
                $id
            ]);
        } else {
            $sql = "UPDATE {$this->table} SET name = ?, phone = ?, email = ?, address = ?, status = ?, updated_at = ? WHERE id = ?";
            return $this->db->query($sql, [
                $data['name'],
                $data['phone'],
                $data['email'] ?? null,
                $data['address'] ?? null,
                $data['status'],
                $data['updated_at'],
                $id
            ]);
        }
    }

    /**
     * Delete staff member
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    /**
     * Verify staff credentials
     */
    public function verifyCredentials($email, $password)
    {
        $staff = $this->getByEmail($email);
        if ($staff && password_verify($password, $staff['password'])) {
            return $staff;
        }
        return false;
    }

    /**
     * Get staff statistics
     */
    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
                FROM {$this->table}";
        return $this->db->query($sql)->single();
    }



}
