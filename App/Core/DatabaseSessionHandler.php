<?php

namespace App\Core;

class DatabaseSessionHandler implements \SessionHandlerInterface
{
    private $db;
    private $lifetime;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->lifetime = (int) ini_get('session.gc_maxlifetime');
    }
    
    public function open(string $savePath, string $sessionName): bool
    {
        return true;
    }
    
    public function close(): bool
    {
        return true;
    }
    
    public function read(string $sessionId): string
    {
        $result = $this->db->query(
            "SELECT payload FROM sessions WHERE id = ? AND last_activity > ?",
            [$sessionId, time() - $this->lifetime]
        )->single();
        
        return $result ? $result['payload'] : '';
    }
    
    public function write(string $sessionId, string $sessionData): bool
    {
        $userId = $_SESSION['user_id'] ?? null;

        // Keep only essential fields for reliability and performance
        $executed = $this->db->query(
            "INSERT INTO sessions (id, user_id, payload, last_activity)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             user_id = VALUES(user_id),
             payload = VALUES(payload),
             last_activity = VALUES(last_activity)",
            [$sessionId, $userId, $sessionData, time()]
        )->execute();
        
        return (bool) $executed;
    }
    
    public function destroy(string $sessionId): bool
    {
        $result = $this->db->query("DELETE FROM sessions WHERE id = ?", [$sessionId])->execute();
        return (bool) $result;
    }
    
    public function gc(int $maxLifetime): int|false
    {
        $result = $this->db->query("DELETE FROM sessions WHERE last_activity < ?", [time() - $maxLifetime])->execute();
        if ($result === false) {
            return false;
        }
        return $this->db->rowCount();
    }
}
