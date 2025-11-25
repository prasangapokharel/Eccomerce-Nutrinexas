<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * IdempotencyKey Model
 * Handles idempotency keys for secure payment processing
 */
class IdempotencyKey extends Model
{
    protected $table = 'idempotency_keys';
    protected $primaryKey = 'id';
    protected $fillable = [
        'key_hash', 'user_id', 'response_data', 'expires_at', 'created_at'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Generate a new idempotency key
     */
    public function generateKey($userId = null, $ttl = 3600)
    {
        $key = bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $key);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

        $this->create([
            'key_hash' => $keyHash,
            'user_id' => $userId,
            'expires_at' => $expiresAt
        ]);

        return $key;
    }

    /**
     * Check if key exists and is valid
     */
    public function validateKey($key, $userId = null)
    {
        $keyHash = hash('sha256', $key);
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE key_hash = ? 
                AND expires_at > NOW()";
        
        $params = [$keyHash];
        
        if ($userId) {
            $sql .= " AND (user_id = ? OR user_id IS NULL)";
            $params[] = $userId;
        }
        
        return $this->db->query($sql, $params)->single();
    }

    /**
     * Store response data for idempotency
     */
    public function storeResponse($key, $responseData)
    {
        $keyHash = hash('sha256', $key);
        
        $sql = "UPDATE {$this->table} 
                SET response_data = ? 
                WHERE key_hash = ?";
        
        return $this->db->query($sql, [json_encode($responseData), $keyHash])->execute();
    }

    /**
     * Get cached response for key
     */
    public function getCachedResponse($key)
    {
        $keyHash = hash('sha256', $key);
        
        $sql = "SELECT response_data FROM {$this->table} 
                WHERE key_hash = ? 
                AND response_data IS NOT NULL 
                AND expires_at > NOW()";
        
        $result = $this->db->query($sql, [$keyHash])->single();
        
        return $result ? json_decode($result['response_data'], true) : null;
    }

    /**
     * Clean up expired keys
     */
    public function cleanupExpired()
    {
        $sql = "DELETE FROM {$this->table} WHERE expires_at < NOW()";
        return $this->db->query($sql)->execute();
    }

    /**
     * Check for duplicate key usage
     */
    public function isDuplicate($key, $userId = null)
    {
        $keyHash = hash('sha256', $key);
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE key_hash = ? 
                AND expires_at > NOW()";
        
        $params = [$keyHash];
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $result = $this->db->query($sql, $params)->single();
        return $result['count'] > 0;
    }

    /**
     * Get key statistics
     */
    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_keys,
                    COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_keys,
                    COUNT(CASE WHEN response_data IS NOT NULL THEN 1 END) as used_keys
                FROM {$this->table}";
        
        return $this->db->query($sql)->single();
    }

    /**
     * Rate limiting for key generation
     */
    public function checkRateLimit($userId, $maxKeys = 10, $windowMinutes = 60)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE user_id = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        
        $result = $this->db->query($sql, [$userId, $windowMinutes])->single();
        
        return $result['count'] < $maxKeys;
    }

    /**
     * Generate secure payment key
     */
    public function generatePaymentKey($userId, $orderId, $amount)
    {
        // Check rate limiting
        if (!$this->checkRateLimit($userId)) {
            throw new \Exception('Rate limit exceeded for key generation');
        }

        // Create unique key based on user, order, and amount
        $uniqueData = $userId . '_' . $orderId . '_' . $amount . '_' . time();
        $key = hash('sha256', $uniqueData . getenv('ENCRYPTION_KEY'));
        
        $keyHash = hash('sha256', $key);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $this->create([
            'key_hash' => $keyHash,
            'user_id' => $userId,
            'expires_at' => $expiresAt
        ]);

        return $key;
    }

    /**
     * Validate payment key
     */
    public function validatePaymentKey($key, $userId, $orderId, $amount)
    {
        $keyHash = hash('sha256', $key);
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE key_hash = ? 
                AND user_id = ? 
                AND expires_at > NOW()";
        
        $result = $this->db->query($sql, [$keyHash, $userId])->single();
        
        if (!$result) {
            return false;
        }

        // Verify the key was generated for this specific payment
        $uniqueData = $userId . '_' . $orderId . '_' . $amount;
        $expectedKey = hash('sha256', $uniqueData . getenv('ENCRYPTION_KEY'));
        
        return hash_equals($key, $expectedKey);
    }
}
