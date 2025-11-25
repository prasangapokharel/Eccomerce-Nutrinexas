<?php
namespace App\Models;

use App\Core\Model;

class ApiKey extends Model
{
    protected $table = 'api_keys';
    protected $primaryKey = 'id';
    
    /**
     * Generate a new API key
     */
    public function generateKey($userId = null, $name = 'Default API Key', $permissions = ['read', 'write'])
    {
        $key = 'nutrinexas_' . bin2hex(random_bytes(32));
        $hashedKey = hash('sha256', $key);
        
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => $hashedKey,
            'permissions' => json_encode($permissions),
            'is_active' => 1,
            'last_used' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $id = $this->create($data);
        
        if ($id) {
            return [
                'id' => $id,
                'key' => $key,
                'name' => $name,
                'permissions' => $permissions,
                'created_at' => $data['created_at']
            ];
        }
        
        return false;
    }
    
    /**
     * Validate API key
     */
    public function validateKey($key)
    {
        $hashedKey = hash('sha256', $key);
        
        $query = "SELECT * FROM {$this->table} WHERE key_hash = ? AND is_active = 1";
        $result = $this->query($query, [$hashedKey]);
        
        if ($result && count($result) > 0) {
            $apiKey = $result[0];
            
            // Update last used timestamp
            $this->update($apiKey['id'], ['last_used' => date('Y-m-d H:i:s')]);
            
            return [
                'id' => $apiKey['id'],
                'user_id' => $apiKey['user_id'],
                'name' => $apiKey['name'],
                'permissions' => json_decode($apiKey['permissions'], true),
                'created_at' => $apiKey['created_at']
            ];
        }
        
        return false;
    }
    
    /**
     * Get all API keys for a user
     */
    public function getUserKeys($userId)
    {
        $query = "SELECT id, name, permissions, is_active, last_used, created_at FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        return $this->query($query, [$userId]);
    }
    
    /**
     * Deactivate API key
     */
    public function deactivateKey($keyId, $userId = null)
    {
        $conditions = ['id' => $keyId];
        if ($userId) {
            $conditions['user_id'] = $userId;
        }
        
        return $this->update($keyId, ['is_active' => 0], $conditions);
    }
    
    /**
     * Check if key has permission
     */
    public function hasPermission($keyData, $permission)
    {
        return in_array($permission, $keyData['permissions']);
    }
}