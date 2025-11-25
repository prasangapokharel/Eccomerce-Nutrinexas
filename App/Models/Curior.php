<?php
namespace App\Models;

use App\Core\Model;

class Curior extends Model
{
    protected $table = 'curiors';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'phone', 'email', 'address', 'password', 'status', 'created_at', 'updated_at'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all curiors
     */
    public function getAllCuriors()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->query($sql)->all();
    }

    /**
     * Get all curiors (alias for getAllCuriors)
     */
    public function getAll()
    {
        return $this->getAllCuriors();
    }

    /**
     * Get curior by email
     */
    public function getByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->query($sql, [$email])->single();
    }

    /**
     * Find curior by email (alias for getByEmail)
     */
    public function findByEmail($email)
    {
        return $this->getByEmail($email);
    }

    /**
     * Get curior by phone
     */
    public function getByPhone($phone)
    {
        $sql = "SELECT * FROM {$this->table} WHERE phone = ?";
        return $this->db->query($sql, [$phone])->single();
    }

    /**
     * Get curior by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id])->single();
    }

    /**
     * Create new curior
     */
    public function create($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Use base Model::create to ensure proper bind + execute and return lastInsertId
        $payload = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'password' => $data['password'],
            'status' => $data['status'] ?? 'active',
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at']
        ];

        return parent::create($payload);
    }

    /**
     * Update curior
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
     * Delete curior
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    /**
     * Verify curior credentials
     */
    public function verifyCredentials($email, $password)
    {
        $curior = $this->getByEmail($email);
        if ($curior && password_verify($password, $curior['password'])) {
            return $curior;
        }
        return false;
    }

    /**
     * Find curior by ID (alias for getById)
     */
    public function find($id)
    {
        return $this->getById($id);
    }

    /**
     * Get curior statistics
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
