<?php
namespace App\Models\Curior;

use App\Core\Model;

class Curior extends Model
{
    protected $table = 'curiors';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'password',
        'status',
        'reset_token',
        'reset_token_expires_at',
        'created_at',
        'updated_at'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getAllCuriors()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->query($sql)->all();
    }

    public function getAll()
    {
        return $this->getAllCuriors();
    }

    public function getByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->query($sql, [$email])->single();
    }

    public function findByEmail($email)
    {
        return $this->getByEmail($email);
    }

    public function getByPhone($phone)
    {
        $sql = "SELECT * FROM {$this->table} WHERE phone = ?";
        return $this->db->query($sql, [$phone])->single();
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id])->single();
    }

    public function find($id)
    {
        return $this->getById($id);
    }

    public function create($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

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

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    public function saveResetToken(int $curiorId, string $token, string $expiresAt): void
    {
        $sql = "UPDATE {$this->table} SET reset_token = ?, reset_token_expires_at = ?, updated_at = ? WHERE id = ?";
        $this->db->query($sql, [$token, $expiresAt, date('Y-m-d H:i:s'), $curiorId]);
    }

    public function findByResetToken(string $token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE reset_token = ? LIMIT 1";
        return $this->db->query($sql, [$token])->single();
    }

    public function clearResetToken(int $curiorId): void
    {
        $sql = "UPDATE {$this->table} SET reset_token = NULL, reset_token_expires_at = NULL, updated_at = ? WHERE id = ?";
        $this->db->query($sql, [date('Y-m-d H:i:s'), $curiorId]);
    }

    public function updatePassword(int $curiorId, string $password): bool
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE {$this->table} SET password = ?, updated_at = ? WHERE id = ?";
        return (bool) $this->db->query($sql, [$hashed, date('Y-m-d H:i:s'), $curiorId]);
    }

    public function verifyCredentials($email, $password)
    {
        $curior = $this->getByEmail($email);
        if ($curior && password_verify($password, $curior['password'])) {
            return $curior;
        }
        return false;
    }

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

