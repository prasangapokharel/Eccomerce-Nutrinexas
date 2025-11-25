<?php

namespace App\Helpers;

use App\Models\User;
use App\Core\Database;

/**
 * Session Recovery Helper
 * Provides multiple ways to maintain user sessions even after cookie clearing
 */
class SessionRecoveryHelper
{
    /**
     * Generate a persistent session token
     *
     * @param int $userId
     * @return string
     */
    public static function generatePersistentToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        
        // Store in database with long expiration
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO user_persistent_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())",
            [$userId, $hashedToken, date('Y-m-d H:i:s', time() + (86400 * 365))] // 1 year
        );
        
        return $token;
    }
    
    /**
     * Validate persistent token and restore session
     *
     * @param string $token
     * @return array|false
     */
    public static function validatePersistentToken($token)
    {
        $hashedToken = hash('sha256', $token);
        
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT ut.*, u.id, u.first_name, u.last_name, u.email, u.role 
             FROM user_persistent_tokens ut 
             JOIN users u ON ut.user_id = u.id 
             WHERE ut.token_hash = ? AND ut.expires_at > NOW() AND ut.is_active = 1",
            [$hashedToken]
        )->single();
        
        if ($result) {
            // Update last used
            $db->query(
                "UPDATE user_persistent_tokens SET last_used = NOW() WHERE token_hash = ?",
                [$hashedToken]
            );
            
            return [
                'user_id' => $result['user_id'],
                'first_name' => $result['first_name'],
                'last_name' => $result['last_name'],
                'email' => $result['email'],
                'role' => $result['role']
            ];
        }
        
        return false;
    }
    
    /**
     * Store persistent token in localStorage via JavaScript
     *
     * @param string $token
     * @return string
     */
    public static function getLocalStorageScript($token)
    {
        return "
        <script>
        // Store persistent token in localStorage
        if (typeof(Storage) !== 'undefined') {
            localStorage.setItem('nutrinexus_persistent_token', '" . $token . "');
        }
        </script>";
    }
    
    /**
     * Get persistent token from localStorage via JavaScript
     *
     * @return string
     */
    public static function getTokenRecoveryScript()
    {
        return "
        <script>
        // Check for persistent token in localStorage
        if (typeof(Storage) !== 'undefined') {
            const persistentToken = localStorage.getItem('nutrinexus_persistent_token');
            if (persistentToken && !document.cookie.includes('PHPSESSID')) {
                // Send token to server for validation
                fetch('" . \App\Core\View::url('auth/recover-session') . "', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        persistent_token: persistentToken
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page to restore session
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.log('Session recovery failed:', error);
                });
            }
        }
        </script>";
    }
    
    /**
     * Clean up expired tokens
     */
    public static function cleanupExpiredTokens()
    {
        $db = Database::getInstance();
        $db->query("DELETE FROM user_persistent_tokens WHERE expires_at < NOW()");
    }
    
    /**
     * Revoke all persistent tokens for a user
     *
     * @param int $userId
     */
    public static function revokeAllTokens($userId)
    {
        $db = Database::getInstance();
        $db->query("UPDATE user_persistent_tokens SET is_active = 0 WHERE user_id = ?", [$userId]);
    }
}