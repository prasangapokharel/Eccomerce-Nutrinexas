<?php
namespace App\Core;

/**
 * Session class
 * Handles session management
 */
class Session
{
    /**
     * Start the session
     *
     * @return void
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set session save path to robust file-based storage directory
            $sessionPath = __DIR__ . '/../storage/temporarydatabase/sessions';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0755, true);
            }
            session_save_path($sessionPath);
            
            // Set secure cookie parameters for better security
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', 86400 * 365); // 1 year - no auto logout
            ini_set('session.cookie_lifetime', 86400 * 365); // 1 year - no auto logout
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            
            // Use database-backed sessions for stronger persistence
            $dbSessionHandler = new DatabaseSessionHandler();
            session_set_save_handler($dbSessionHandler, true);
            
            session_start();

            // Auto-login via remember_token cookie if present (works in shared hosting)
            if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
                try {
                    $userModel = new \App\Models\User();
                    $user = $userModel->findByRememberToken($_COOKIE['remember_token']);
                    if ($user) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['first_name'] ?? '';
                        $_SESSION['user_email'] = $user['email'] ?? '';
                        $_SESSION['user_role'] = $user['role'] ?? 'customer';
                        $_SESSION['logged_in'] = true;
                        // Regenerate session ID to prevent fixation when restoring via cookie
                        session_regenerate_id(true);
                        
                        // Refresh the token to extend expiration (ensures user stays logged in)
                        // Don't set cookie here, it's already set - just refresh in DB
                        $newToken = $userModel->refreshRememberToken($user['id'], false);
                        if ($newToken) {
                            // Update cookie with new token
                            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                            setcookie(
                                'remember_token',
                                $newToken,
                                [
                                    'expires' => time() + (365 * 24 * 60 * 60), // 1 year
                                    'path' => '/',
                                    'domain' => '',
                                    'secure' => $secure,
                                    'httponly' => true,
                                    'samesite' => 'Lax'
                                ]
                            );
                        }
                    }
                } catch (\Exception $e) {
                    error_log('Session auto-login error: ' . $e->getMessage());
                }
            }
            
            // If user is logged in via session, refresh their token periodically (every 7 days)
            // This ensures the token stays valid even in shared hosting with session cleanup
            if (!empty($_SESSION['user_id']) && empty($_COOKIE['remember_token'])) {
                try {
                    $userModel = new \App\Models\User();
                    $user = $userModel->find($_SESSION['user_id']);
                    if ($user && empty($user['remember_token'])) {
                        // Create refresh token if missing
                        $userModel->createRememberToken($_SESSION['user_id']);
                    } elseif ($user && !empty($user['remember_token'])) {
                        // Set cookie if token exists but cookie is missing
                        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                        setcookie(
                            'remember_token',
                            $user['remember_token'],
                            [
                                'expires' => time() + (365 * 24 * 60 * 60), // 1 year
                                'path' => '/',
                                'domain' => '',
                                'secure' => $secure,
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    error_log('Session token refresh error: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Set a session variable
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session variable exists
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session variable
     *
     * @param string $key
     * @return void
     */
    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clear all session variables
     *
     * @return void
     */
    public static function clear()
    {
        $_SESSION = [];
    }

    /**
     * Destroy the session
     *
     * @return void
     */
    public static function destroy()
    {
        // Unset all session variables
        $_SESSION = [];
        
        // If it's desired to kill the session, also delete the session cookie.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finally, destroy the session.
        session_destroy();
    }

    /**
     * Regenerate the session ID
     *
     * @param bool $deleteOldSession
     * @return bool
     */
    public static function regenerate($deleteOldSession = true)
    {
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Set a flash message
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public static function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Get and clear flash message
     *
     * @return array|null
     */
    public static function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        
        return null;
    }

    /**
     * Check if a flash message exists
     *
     * @return bool
     */
    public static function hasFlash()
    {
        return isset($_SESSION['flash']);
    }
}
