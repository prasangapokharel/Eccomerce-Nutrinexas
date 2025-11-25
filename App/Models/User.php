<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    private $cache;
    private $cachePrefix = 'user_';
    private $cacheTTL = 3600; // 1 hour

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }

    /**
     * Find user by email with caching
     *
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email)
    {
        $cacheKey = $this->cachePrefix . 'email_' . md5($email);
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $result = $this->db->query($sql)->bind([$email])->single();

        if ($result) {
            // Cache the result
            $this->cache->set($cacheKey, $result, $this->cacheTTL);
            return $result;
        }

        return false;
    }

    /**
     * Find user by username with caching
     *
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username)
    {
        $cacheKey = $this->cachePrefix . 'username_' . md5($username);
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $result = $this->db->query($sql)->bind([$username])->single();

        if ($result) {
            // Cache the result
            $this->cache->set($cacheKey, $result, $this->cacheTTL);
            return $result;
        }

        return false;
    }

    /**
     * Find user by ID with caching
     *
     * @param int $id
     * @return array|false
     */
    public function findById($id)
    {
        $cacheKey = $this->cachePrefix . 'id_' . $id;
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->db->query($sql)->bind([$id])->single();

        if ($result) {
            // Cache the result
            $this->cache->set($cacheKey, $result, $this->cacheTTL);
            return $result;
        }

        return false;
    }

    /**
     * Find user by column value with caching
     *
     * @param string $column
     * @param mixed $value
     * @return array|false
     */
    public function findOneBy($column, $value)
    {
        $cacheKey = $this->cachePrefix . 'column_' . $column . '_' . md5(serialize($value));
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        $result = $this->db->query($sql)->bind([$value])->single();

        if ($result) {
            // Cache the result
            $this->cache->set($cacheKey, $result, $this->cacheTTL);
            return $result;
        }

        return false;
    }

    /**
     * Find user by phone number with caching
     *
     * @param string $phone
     * @return array|false
     */
    public function findByPhone($phone)
    {
        $cacheKey = $this->cachePrefix . 'phone_' . md5($phone);
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE phone = ?";
        $result = $this->db->query($sql)->bind([$phone])->single();

        if ($result) {
            // Cache the result
            $this->cache->set($cacheKey, $result, $this->cacheTTL);
            return $result;
        }

        return false;
    }

    /**
     * Find user by reset token with caching
     *
     * @param string $token
     * @return array|false
     */
    public function findByResetToken($token)
    {
        $cacheKey = $this->cachePrefix . 'reset_token_' . md5($token);
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE reset_token = ? AND reset_expires > NOW()";
        $result = $this->db->query($sql)->bind([$token])->single();

        if ($result) {
            // Cache the result with shorter TTL for security
            $this->cache->set($cacheKey, $result, 300); // 5 minutes
            return $result;
        }

        return false;
    }

    /**
     * Find user by referral code with caching
     *
     * @param string $referralCode
     * @return array|false
     */
    public function findByReferralCode($referralCode)
    {
        $cacheKey = $this->cachePrefix . 'referral_code_' . md5($referralCode);
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT *, sponsor_status FROM {$this->table} WHERE referral_code = ?";
        $result = $this->db->query($sql)->bind([$referralCode])->single();

        if ($result) {
            // Cache the result
            $this->cache->set($cacheKey, $result, $this->cacheTTL);
            return $result;
        }

        return false;
    }

    /**
     * Authenticate user with caching and persistence
     *
     * @param string $email
     * @param string $password
     * @return array|false
     */
    public function authenticate($email, $password)
    {
        $user = $this->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            // Check if user account is active
            $status = $user['status'] ?? 'active';
            if ($status !== 'active') {
                // Log attempted login for inactive user
                error_log("Login attempt for inactive user: {$email} (Status: {$status})");
                return false;
            }
            
            // Update last login time
            $this->updateLastLogin($user['id']);
            
            // Create persistent login token
            $this->createRememberToken($user['id']);
            
            return $user;
        }

        return false;
    }

    /**
     * Create remember token for persistent login
     *
     * @param int $userId
     * @return bool
     */
    public function createRememberToken($userId, $setCookie = false)
    {
        try {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            
            // Store token in database
            $sql = "UPDATE {$this->table} SET remember_token = ? WHERE id = ?";
            $result = $this->db->query($sql)->bind([$token, $userId])->execute();
            
            if ($result) {
                // Only set cookie if explicitly requested (usually handled by AuthController)
                if ($setCookie) {
                    // Determine remember duration from settings (fallback 30 days)
                    try {
                        $settingModel = new \App\Models\Setting();
                        $days = (int)($settingModel->get('remember_token_days', 30));
                        $enable = $settingModel->get('enable_remember_me', 'true');
                    } catch (\Exception $e) {
                        $days = 30;
                        $enable = 'true';
                    }

                    if ($enable === 'true') {
                        // Set secure cookie for configured days
                        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                        setcookie(
                            "remember_token", 
                            $token, 
                            [
                                'expires' => time() + (86400 * max(1, $days)),
                                'path' => '/',
                                'domain' => '',
                                'secure' => $secure,
                                'httponly' => true,
                                'samesite' => 'Lax' // Lax for better shared hosting compatibility
                            ]
                        );
                    }
                }
                
                // Invalidate user cache
                $this->invalidateUserCaches($userId);
                
                return $token; // Return token so caller can set cookie with custom duration
            }
            
            return false;
        } catch (\Exception $e) {
            error_log('Error creating remember token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is logged in (session or remember token)
     *
     * @return array|false
     */
    public function checkLogin()
    {
        // Check session first
        if (!empty($_SESSION['user_id'])) {
            $user = $this->findById($_SESSION['user_id']);
            if ($user) {
                // Refresh remember token for security
                $this->refreshRememberToken($user['id']);
                return $user;
            }
        }

        // Check remember token
        if (!empty($_COOKIE['remember_token'])) {
            $user = $this->findByRememberToken($_COOKIE['remember_token']);
            if ($user) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                
                // Refresh remember token for security
                $this->refreshRememberToken($user['id']);
                
                return $user;
            }
        }

        return false;
    }

    /**
     * Find user by remember token
     *
     * @param string $token
     * @return array|false
     */
    public function findByRememberToken($token)
    {
        $cacheKey = $this->cachePrefix . 'remember_token_' . md5($token);
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE remember_token = ?";
        $result = $this->db->query($sql)->bind([$token])->single();

        if ($result) {
            // Cache the result with shorter TTL for security
            $this->cache->set($cacheKey, $result, 300); // 5 minutes
            return $result;
        }

        return false;
    }

    /**
     * Refresh remember token for security
     *
     * @param int $userId
     * @return bool
     */
    public function refreshRememberToken($userId, $setCookie = false)
    {
        try {
            // Generate new token
            $newToken = bin2hex(random_bytes(32));
            
            // Update database
            $sql = "UPDATE {$this->table} SET remember_token = ? WHERE id = ?";
            $result = $this->db->query($sql)->bind([$newToken, $userId])->execute();
            
            if ($result) {
                // Only set cookie if explicitly requested (usually handled by Session class)
                if ($setCookie) {
                    try {
                        $settingModel = new \App\Models\Setting();
                        $days = (int)($settingModel->get('remember_token_days', 30));
                        $enable = $settingModel->get('enable_remember_me', 'true');
                    } catch (\Exception $e) {
                        $days = 30;
                        $enable = 'true';
                    }

                    if ($enable === 'true') {
                        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                        setcookie(
                            "remember_token", 
                            $newToken, 
                            [
                                'expires' => time() + (86400 * max(1, $days)),
                                'path' => '/',
                                'domain' => '',
                                'secure' => $secure,
                                'httponly' => true,
                                'samesite' => 'Lax' // Lax for better shared hosting compatibility
                            ]
                        );
                    }
                }
                
                // Invalidate user cache
                $this->invalidateUserCaches($userId);
                
                return $newToken; // Return token so caller can set cookie with custom duration
            }
            
            return false;
        } catch (\Exception $e) {
            error_log('Error refreshing remember token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear remember token for a specific user
     *
     * @param int $userId
     * @return bool
     */
    public function clearRememberToken($userId)
    {
        try {
            // Clear remember token from database
            $sql = "UPDATE {$this->table} SET remember_token = NULL WHERE id = ?";
            $result = $this->db->query($sql)->bind([$userId])->execute();
            
            // Clear remember token cookie
            setcookie("remember_token", "", time() - 3600, "/");
            
            // Invalidate user cache
            $this->invalidateUserCaches($userId);
            
            return $result;
        } catch (\Exception $e) {
            error_log('Error clearing remember token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get remember token for user
     */
    public function getRememberToken($userId)
    {
        try {
            $sql = "SELECT remember_token FROM {$this->table} WHERE id = ?";
            $result = $this->db->query($sql)->bind([$userId])->single();
            return $result['remember_token'] ?? null;
        } catch (\Exception $e) {
            error_log('Error getting remember token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set remember token for user
     */
    public function setRememberToken($userId, $token, $expires)
    {
        try {
            $sql = "UPDATE {$this->table} SET remember_token = ?, remember_token_expires = ? WHERE id = ?";
            $result = $this->db->query($sql)->bind([$token, $expires, $userId])->execute();
            
            // Invalidate user cache
            $this->invalidateUserCaches($userId);
            
            return $result;
        } catch (\Exception $e) {
            error_log('Error setting remember token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout user and clear all tokens
     *
     * @return bool
     */
    public function logout()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            // Clear session
            session_destroy();
            
            // Clear remember token cookie
            setcookie("remember_token", "", time() - 3600, "/");
            
            // Clear remember token from database
            if ($userId) {
                $sql = "UPDATE {$this->table} SET remember_token = NULL WHERE id = ?";
                $this->db->query($sql)->bind([$userId])->execute();
                
                // Invalidate user cache
                $this->invalidateUserCaches($userId);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Error during logout: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Register new user with simplified fields (used for full registration flow)
     *
     * @param array $data
     * @return int|bool
     */
    public function register($data)
    {
        // Extract first and last name from full name
        $nameParts = explode(' ', $data['full_name'], 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        // Generate username from full name
        $username = $this->generateUsername($data['full_name']);

        // Create user data array
        $userData = [
            'username' => $username,
            'full_name' => $data['full_name'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'customer',
            'referral_earnings' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Add email if provided
        if (isset($data['email']) && !empty($data['email'])) {
            $userData['email'] = $data['email'];
        }

        // Generate referral code
        $userData['referral_code'] = $this->generateReferralCode();

        // Add referred_by if provided
        if (isset($data['referred_by']) && !empty($data['referred_by'])) {
            $userData['referred_by'] = $data['referred_by'];
        }

        $userId = $this->create($userData);

        // Clear user cache if caching is enabled
        if ($userId && class_exists('App\Core\Cache')) {
            $cache = new Cache();
            $cache->delete($cache->generateKey('user_count', []));
        }

        return $userId;
    }

    /**
     * NEW: Create a user with specific fields (suitable for guest checkout registration)
     *
     * @param array $data Contains 'email', 'password' (hashed), 'username', 'role', 'status', and optionally 'first_name', 'last_name', 'full_name', 'phone'.
     * @return int|bool The ID of the newly created user, or false on failure.
     */
    public function createUser($data)
    {
        $email = $data['email'];
        $password = $data['password']; // Assumed to be already hashed
        $username = $data['username'] ?? explode('@', $email)[0];
        $role = $data['role'] ?? 'customer';
        $status = $data['status'] ?? 'active';

        // Derive first_name, last_name, full_name if not explicitly provided
        $firstName = $data['first_name'] ?? (explode('@', $email)[0]);
        $lastName = $data['last_name'] ?? '';
        $fullName = $data['full_name'] ?? trim($firstName . ' ' . $lastName);
        $phone = $data['phone'] ?? null;

        // Generate referral code
        $referralCode = $this->generateReferralCode();

        $sql = "INSERT INTO {$this->table} (username, email, password, full_name, first_name, last_name, phone, role, referral_code, status, created_at, updated_at)
                VALUES (:username, :email, :password, :full_name, :first_name, :last_name, :phone, :role, :referral_code, :status, NOW(), NOW())";

        $this->db->query($sql);
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $email);
        $this->db->bind(':password', $password);
        $this->db->bind(':full_name', $fullName);
        $this->db->bind(':first_name', $firstName);
        $this->db->bind(':last_name', $lastName);
        $this->db->bind(':phone', $phone);
        $this->db->bind(':role', $role);
        $this->db->bind(':referral_code', $referralCode);
        $this->db->bind(':status', $status);

        if ($this->db->execute()) {
            // Clear user count cache
            if (class_exists('App\Core\Cache')) {
                $cache = new Cache();
                $cache->delete($cache->generateKey('user_count', []));
            }
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Generate a username from full name
     *
     * @param string $fullName
     * @return string
     */
    private function generateUsername($fullName)
    {
        // Remove special characters and spaces
        $username = preg_replace('/[^a-zA-Z0-9]/', '', $fullName);

        // Convert to lowercase
        $username = strtolower($username);

        // Add random numbers to ensure uniqueness
        $username .= rand(100, 999);

        // Check if username already exists
        $existingUser = $this->findByUsername($username);

        // If username exists, generate a new one
        if ($existingUser) {
            return $this->generateUsername($fullName);
        }

        return $username;
    }

    /**
     * Save reset token
     *
     * @param int $userId
     * @param string $token
     * @param string $expires
     * @return bool
     */
    public function saveResetToken($userId, $token, $expires)
    {
        $sql = "UPDATE {$this->table} SET reset_token = ?, reset_expires = ? WHERE id = ?";
        return $this->db->query($sql)->bind([$token, $expires, $userId])->execute();
    }

    /**
     * Clear reset token
     *
     * @param int $userId
     * @return bool
     */
    public function clearResetToken($userId)
    {
        $sql = "UPDATE {$this->table} SET reset_token = NULL, reset_expires = NULL WHERE id = ?";
        return $this->db->query($sql)->bind([$userId])->execute();
    }

    /**
     * Update password
     *
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function updatePassword($userId, $password)
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql)->bind([$hashedPassword, $userId])->execute();
    }

    /**
     * Get user count
     *
     * @return int
     */
    public function getUserCount()
    {
        // Try to get from cache if available
        if (class_exists('App\Core\Cache')) {
            $cache = new Cache();
            $cacheKey = $cache->generateKey('user_count', []);
            $count = $cache->get($cacheKey);

            if ($count !== null) {
                return $count;
            }
        }

        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->query($sql)->single();
        $count = $result ? (int)$result['count'] : 0;

        // Store in cache if available
        if (isset($cache) && isset($cacheKey)) {
            $cache->set($cacheKey, $count, 3600); // Cache for 1 hour
        }

        return $count;
    }

    /**
     * Get users referred by a user
     *
     * @param int $userId
     * @return array
     */
    public function getReferrals($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE referred_by = ? ORDER BY created_at DESC";
        return $this->db->query($sql)->bind([$userId])->all();
    }

    /**
     * Get referral count for a user
     *
     * @param int $userId
     * @return int
     */
    public function getReferralCount($userId)
    {
        // Try to get from cache if available
        if (class_exists('App\Core\Cache')) {
            $cache = new Cache();
            $cacheKey = $cache->generateKey('user_referral_count', ['user_id' => $userId]);
            $count = $cache->get($cacheKey);

            if ($count !== null) {
                return $count;
            }
        }

        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE referred_by = ?";
        $result = $this->db->query($sql)->bind([$userId])->single();
        $count = $result ? (int)$result['count'] : 0;

        // Store in cache if available
        if (isset($cache) && isset($cacheKey)) {
            $cache->set($cacheKey, $count, 1800); // Cache for 30 minutes
        }

        return $count;
    }

    /**
     * Add referral earnings to user's balance
     *
     * @param int $userId
     * @param float $amount
     * @return bool
     */
    public function addReferralEarnings($userId, $amount)
    {
        // Get current user data
        $user = $this->find($userId);

        if (!$user) {
            error_log("Failed to add referral earnings: User ID {$userId} not found");
            return false;
        }

        // Calculate new earnings
        $currentEarnings = (float)($user['referral_earnings'] ?? 0);
        $newEarnings = $currentEarnings + $amount;

        // Update user's referral earnings
        $data = [
            'referral_earnings' => $newEarnings,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->update($userId, $data);

        if (!$result) {
            error_log("Failed to update referral earnings for User ID {$userId}");
            return false;
        }

        // Clear user cache if caching is enabled
        if (class_exists('App\Core\Cache')) {
            $cache = new Cache();
            $cache->delete($cache->generateKey('user', ['id' => $userId]));
            $cache->delete($cache->generateKey('user_referral_stats', ['user_id' => $userId]));
        }

        return true;
    }

    /**
     * Deduct referral earnings from user's balance
     *
     * @param int $userId
     * @param float $amount
     * @return bool
     */
    public function deductReferralEarnings($userId, $amount)
    {
        // Get current user data
        $user = $this->find($userId);

        if (!$user) {
            error_log("Failed to deduct referral earnings: User ID {$userId} not found");
            return false;
        }

        // Calculate new earnings (ensure it doesn't go below zero)
        $currentEarnings = (float)($user['referral_earnings'] ?? 0);
        $newEarnings = max(0, $currentEarnings - $amount);

        // Update user's referral earnings
        $data = [
            'referral_earnings' => $newEarnings,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->update($userId, $data);

        if (!$result) {
            error_log("Failed to update referral earnings for User ID {$userId}");
            return false;
        }

        // Clear user cache if caching is enabled
        if (class_exists('App\Core\Cache')) {
            $cache = new Cache();
            $cache->delete($cache->generateKey('user', ['id' => $userId]));
            $cache->delete($cache->generateKey('user_referral_stats', ['user_id' => $userId]));
        }

        return true;
    }

    /**
     * Get user's referral statistics
     *
     * @param int $userId
     * @return array
     */
    public function getReferralStats($userId)
    {
        // Try to get from cache if available
        if (class_exists('App\Core\Cache')) {
            $cache = new Cache();
            $cacheKey = $cache->generateKey('user_referral_stats', ['user_id' => $userId]);
            $stats = $cache->get($cacheKey);

            if ($stats !== null) {
                return $stats;
            }
        }

        // Get user
        $user = $this->find($userId);

        if (!$user) {
            return [
                'total_referrals' => 0,
                'total_earnings' => 0,
                'available_balance' => 0,
                'referral_code' => '',
                'referral_link' => ''
            ];
        }

        // Get referral count
        $referralCount = $this->getReferralCount($userId);

        // Get total earnings (from ReferralEarning model if available)
        $totalEarnings = 0;
        if (class_exists('App\Models\ReferralEarning')) {
            $referralEarningModel = new ReferralEarning();
            $totalEarnings = $referralEarningModel->getTotalEarnings($userId);
        } else {
            $totalEarnings = (float)($user['referral_earnings'] ?? 0);
        }

        // Get available balance
        $availableBalance = (float)($user['referral_earnings'] ?? 0);

        // Get referral code
        $referralCode = $user['referral_code'] ?? '';

        // Generate referral link
        $baseUrl = $this->getBaseUrl();
        $referralLink = $baseUrl . '/register?ref=' . $referralCode;

        $stats = [
            'total_referrals' => $referralCount,
            'total_earnings' => $totalEarnings,
            'available_balance' => $availableBalance,
            'referral_code' => $referralCode,
            'referral_link' => $referralLink
        ];

        // Store in cache if available
        if (isset($cache) && isset($cacheKey)) {
            $cache->set($cacheKey, $stats, 1800); // Cache for 30 minutes
        }

        return $stats;
    }
    /**
     * Generate a unique referral code based on username
     *
     * @return string
     */
    public function generateReferralCode()
    {
        // First try to get the username if user is being created
        $username = $_POST['username'] ?? '';

        // If username is not available in POST, generate a random one
        if (empty($username)) {
            $username = substr(str_shuffle(md5(time())), 0, 5);
        }

        // Clean the username to create a valid referral code
        $code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $username));

        // Ensure the code is at least 6 characters long
        if (strlen($code) < 6) {
            $code .= substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 6 - strlen($code));
        }

        // Check if code already exists
        $existingUser = $this->findOneBy('referral_code', $code);

        // If code exists, append random digits and check again
        if ($existingUser) {
            $code .= rand(100, 999);
            $existingUser = $this->findOneBy('referral_code', $code);

            // If still exists, generate completely random code
            if ($existingUser) {
                $code = substr(str_shuffle(md5(time())), 0, 8);
            }
        }

        return $code;
    }



    /**
     * Get user's referrals with details
     *
     * @param int $userId
     * @return array
     */
    public function getUserReferrals($userId)
    {
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.created_at,
                 (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'paid') as order_count,
                 (SELECT SUM(amount) FROM referral_earnings WHERE user_id = ? AND order_id IN
                    (SELECT id FROM orders WHERE user_id = u.id)
                 ) as earnings
                 FROM {$this->table} u
                 WHERE u.referred_by = ?
                 ORDER BY u.created_at DESC";

        return $this->db->query($sql)->bind([$userId, $userId])->all();
    }

    /**
     * Count user's referrals
     *
     * @param int $userId
     * @return int
     */
    public function countUserReferrals($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE referred_by = ?";
        $result = $this->db->query($sql)->bind([$userId])->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Fix zero ID issue
     *
     * @param int $userId
     * @return int|bool
     */
    public function fixZeroId($userId)
    {
        if ($userId !== 0) {
            return $userId;
        }

        // Get the last inserted ID
        $sql = "SELECT MAX(id) as max_id FROM {$this->table}";
        $result = $this->db->query($sql)->single();

        if (!$result || !isset($result['max_id'])) {
            return false;
        }

        return (int)$result['max_id'];
    }

    /**
     * Get base URL for the application
     *
     * @return string
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script_name = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        // Fix for localhost - ensure the path is correct
        if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
            // For localhost, construct the path based on the current directory structure
            $base_path = $protocol . "://" . $host;

            // If script_name is just a slash, don't add it to avoid double slashes
            if ($script_name !== '/' && $script_name !== '\\') {
                // Remove any trailing slashes
                $script_name = rtrim($script_name, '/\\');
                $base_path .= $script_name;
            }

            $base_url = $base_path;
        } else {
            // For production servers
            $base_url = $protocol . "://" . $host . $script_name;
        }

        // Ensure base_url doesn't have a trailing slash
        return rtrim($base_url, '/');
    }

    /**
     * Get all users with optional filtering
     *
     * @param array $filters
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT u.* FROM {$this->table} u";
        $params = [];

        // Apply filters if provided
        if (!empty($filters)) {
            $conditions = [];

            if (isset($filters['sms_consent']) && $filters['sms_consent'] === true) {
                $sql .= " LEFT JOIN user_sms_preferences usp ON u.id = usp.user_id";
                $conditions[] = "usp.is_subscribed = 1 AND usp.marketing_consent = 1";
            }

            // Add more filter conditions as needed (e.g., role, phone, etc.)
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
        }

        // Order by created_at descending by default
        $sql .= " ORDER BY u.created_at DESC";

        // Execute the query
        $result = $this->db->query($sql)->bind($params)->all();

        // Ensure result is an array, even if empty
        return is_array($result) ? $result : [];
    }

    /**
     * Get all users with phone numbers for SMS
     *
     * @return array
     */
    public function getUsersWithPhones(): array
    {
        $sql = "SELECT id, username, email, full_name, first_name, last_name, phone, role
                 FROM {$this->table}
                 WHERE phone IS NOT NULL AND phone != ''
                 ORDER BY created_at DESC";

        $result = $this->db->query($sql)->all();
        return is_array($result) ? $result : [];
    }

    /**
     * Get unique phone numbers from orders
     *
     * @return array
     */
    public function getOrderPhoneNumbers(): array
    {
        $sql = "SELECT DISTINCT o.contact_no as phone, o.user_id, o.customer_name as name
                 FROM orders o
                 WHERE o.contact_no IS NOT NULL AND o.contact_no != ''
                 AND o.contact_no REGEXP '^[+]?[0-9]{10,15}$'
                 ORDER BY o.created_at DESC";

        $result = $this->db->query($sql)->all();
        return is_array($result) ? $result : [];
    }


    /**
     * Find a user by email address.
     * @param string $email
     * @return array|null
     */
    public function findUserByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->db->query($sql)->bind([$email])->single();
        return $stmt ?: null;
    }

    /**
     * Update user profile image
     *
     * @param int $userId
     * @param string $profileImage
     * @return bool
     */
    public function updateProfileImage($userId, $profileImage)
    {
        $sql = "UPDATE {$this->table} SET profile_image = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql)->bind([$profileImage, $userId])->execute();
    }

    /**
     * Get user profile image URL
     *
     * @param array $user
     * @return string
     */
    public function getProfileImageUrl($user)
    {
        if (!empty($user['profile_image'])) {
            return URLROOT . '/profileimage/' . $user['profile_image'];
        }
        return URLROOT . '/images/default-avatar.png';
    }

    /**
     * Update last login time
     *
     * @param int $id
     * @return bool
     */
    public function updateLastLogin($id)
    {
        // Check if last_login column exists
        $columns = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'last_login'")->single();
        if (!$columns) {
            // Column doesn't exist, skip update
            return true;
        }
        
        $sql = "UPDATE {$this->table} SET last_login = ? WHERE id = ?";
        $result = $this->db->query($sql)->bind([date('Y-m-d H:i:s'), $id])->execute();

        if ($result) {
            // Invalidate related caches
            $this->invalidateUserCaches($id);
            return true;
        }

        return false;
    }

    /**
     * Invalidate user-specific caches
     *
     * @param int $userId
     */
    private function invalidateUserCaches($userId)
    {
        // Get user to invalidate email and username caches
        $user = $this->findById($userId);
        if ($user) {
            $this->cache->delete($this->cachePrefix . 'email_' . md5($user['email']));
            $this->cache->delete($this->cachePrefix . 'username_' . md5($user['username']));
            if (!empty($user['phone'])) {
                $this->cache->delete($this->cachePrefix . 'phone_' . md5($user['phone']));
            }
        }
        
        $this->cache->delete($this->cachePrefix . 'id_' . $userId);
    }

    /**
     * Invalidate list caches
     */
    private function invalidateListCaches()
    {
        // Delete pattern-based caches
        $this->cache->deletePattern($this->cachePrefix . 'all_*');
        $this->cache->deletePattern($this->cachePrefix . 'role_*');
        $this->cache->deletePattern($this->cachePrefix . 'count_*');
    }

    /**
     * Create OAuth user account
     *
     * @param array $userData
     * @return int User ID
     */
    public function createOAuthUser($userData)
    {
        $sql = "INSERT INTO {$this->table} (name, email, auth0_id, email_verified, picture, provider, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $userData['name'] ?? 'Unknown User',
            $userData['email'] ?? '',
            $userData['auth0_id'] ?? null,
            isset($userData['email_verified']) && $userData['email_verified'] ? 1 : 0,
            $userData['picture'] ?? null,
            $userData['provider'] ?? 'auth0',
            $userData['created_at'] ?? date('Y-m-d H:i:s'),
            $userData['updated_at'] ?? date('Y-m-d H:i:s')
        ];

        $this->db->query($sql)->bind($params)->execute();
        $userId = $this->db->lastInsertId();

        // Invalidate caches
        $this->invalidateUserCaches($userId);
        $this->invalidateListCaches();

        return $userId;
    }

    /**
     * Update user's Auth0 ID
     *
     * @param int $userId
     * @param string|null $auth0Id
     * @return bool
     */
    public function updateAuth0Id($userId, $auth0Id)
    {
        $sql = "UPDATE {$this->table} SET auth0_id = ?, updated_at = ? WHERE id = ?";
        $params = [$auth0Id, date('Y-m-d H:i:s'), $userId];

        $result = $this->db->query($sql)->bind($params)->execute();

        if ($result) {
            // Invalidate caches
            $this->invalidateUserCaches($userId);
            return true;
        }

        return false;
    }

    /**
     * Find user by Auth0 ID
     *
     * @param string $auth0Id
     * @return array|false
     */
    public function findByAuth0Id($auth0Id)
    {
        $cacheKey = $this->cachePrefix . 'auth0_' . md5($auth0Id);
        
        // Try to get from cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE auth0_id = ?";
        $result = $this->db->query($sql)->bind([$auth0Id])->single();

        if ($result) {
            // Cache the result
            $this->cache->set($cacheKey, $result, $this->cacheTTL);
            return $result;
        }

        return false;
    }
}