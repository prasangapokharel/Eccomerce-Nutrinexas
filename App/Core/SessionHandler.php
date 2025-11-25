<?php

namespace App\Core;

/**
 * Clean SessionHandler
 * - Uses file-based storage under App/storage/temporarydatabase/sessions
 * - Provides helpers for remember-token lifecycle via User model
 */
class SessionHandler
{
    private $sessionPath;

    public function __construct()
    {
        // Robust file-based session storage path
        $this->sessionPath = __DIR__ . '/../storage/temporarydatabase/sessions';

        if (!is_dir($this->sessionPath)) {
            mkdir($this->sessionPath, 0755, true);
        }

        // Configure session to persist long-term and avoid unwanted logouts
        session_save_path($this->sessionPath);
        ini_set('session.gc_maxlifetime', 86400 * 365); // 1 year
        ini_set('session.cookie_lifetime', 86400 * 365); // 1 year
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');

        // Use secure cookie when HTTPS is enabled
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        ini_set('session.cookie_secure', $secure ? '1' : '0');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Auto-restore session from remember token if present and session missing
        if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
            try {
                $userModel = new \App\Models\User();
                $user = $userModel->findByRememberToken($_COOKIE['remember_token']);
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] ?? '';
                    $_SESSION['user_email'] = $user['email'] ?? '';
                    $_SESSION['user_role'] = $user['role'] ?? 'customer';
                    session_regenerate_id(true);
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    // Basic session APIs
    public function set($key, $value) { $_SESSION[$key] = $value; }
    public function get($key, $default = null) { return $_SESSION[$key] ?? $default; }
    public function has($key) { return isset($_SESSION[$key]); }
    public function remove($key) { unset($_SESSION[$key]); }

    public function destroy()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function regenerate() { session_regenerate_id(true); }

    // Flash helpers
    public function setFlash($key, $message) { $_SESSION['flash'][$key] = $message; }
    public function getFlash($key) { $m = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $m; }
    public function hasFlash($key) { return isset($_SESSION['flash'][$key]); }

    // Maintenance: clean old session files
    public function cleanOldSessions()
    {
        $files = glob($this->sessionPath . '/sess_*');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > 86400 * 30) {
                @unlink($file);
            }
        }
    }

    // Remember token lifecycle helpers
    public function setRememberFor(int $userId): bool
    {
        try {
            $userModel = new \App\Models\User();
            return $userModel->createRememberToken($userId);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function refreshRememberFor(int $userId): bool
    {
        try {
            $userModel = new \App\Models\User();
            return $userModel->refreshRememberToken($userId);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function clearRemember(): bool
    {
        try {
            if (!empty($_SESSION['user_id'])) {
                $userModel = new \App\Models\User();
                $userModel->clearRememberToken($_SESSION['user_id']);
            }
            // Clear cookie proactively
            setcookie('remember_token', '', time() - 3600, '/');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}