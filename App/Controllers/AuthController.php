<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Helpers\EmailHelper;
use App\Helpers\SessionRecoveryHelper;
use App\Services\EmailQueueService;
use Exception;

class AuthController extends Controller
{
    private $userModel;
    private $loginAttempts = [];
    private $attemptFile = 'login_attempts.dat';
    private $emailQueueService;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->emailQueueService = new EmailQueueService();
        
        // Initialize login attempts tracking
        $this->loadLoginAttempts();
    }
    
    /**
     * Display login page
     */
    public function login()
    {
        // Check if already logged in
        if (Session::has('user_id')) {
            $this->redirect('');
        }
        
        $this->view('auth/login', [
            'title' => 'Login'
        ]);
    }
    
    /**
     * Process login form submission
     */
    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login');
            return;
        }
        
        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('auth/login');
            return;
        }
        
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!\App\Helpers\SecurityHelper::checkRateLimit('login_' . $ip, 5, 900)) {
            $remaining = \App\Helpers\SecurityHelper::getRateLimitRemaining('login_' . $ip, 900);
            $this->setFlash('error', "Too many login attempts. Please try again in " . ceil($remaining / 60) . " minutes.");
            $this->redirect('auth/login');
            return;
        }
        
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        $errors = [];
        
        if (empty($identifier)) {
            $errors[] = 'Email, phone, or username is required';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        if (!empty($errors)) {
            $this->view('auth/login', [
                'title' => 'Login',
                'errors' => $errors
            ]);
            return;
        }
        
        // Check rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($this->isRateLimited($ip)) {
            $remainingMinutes = $this->getRemainingLockoutTime($ip);
            $this->setFlash('error', "Too many login attempts. Please try again in {$remainingMinutes} minutes.");
            $this->redirect('auth/login');
            return;
        }
        
        // Authenticate user
        $user = $this->authenticateUser($identifier, $password);
        
        if ($user) {
            // Reset login attempts
            $this->resetLoginAttempts($ip);
            
            // Set session based on role
            $userRole = $user['role'] ?? 'customer';
            
            Session::set('user_id', $user['id']);
            Session::set('user_email', $user['email'] ?? '');
            Session::set('user_name', $user['first_name'] ?? '');
            Session::set('user_role', $userRole);
            Session::set('logged_in', true);
            
            // Set role-specific session variables
            if ($userRole === 'staff') {
                Session::set('staff_id', $user['id']);
                Session::set('staff_name', $user['first_name'] ?? '');
            } elseif ($userRole === 'curior') {
                Session::set('curior_id', $user['id']);
                Session::set('curior_name', $user['first_name'] ?? '');
            }
            
            // Always create/update refresh token for persistent login (works in shared hosting)
            // This ensures users never get logged out, even in shared hosting environments
            $existingToken = $this->userModel->getRememberToken($user['id']);
            $token = null;
            
            if (empty($existingToken)) {
                // Create new refresh token (don't set cookie here, we'll do it below)
                $token = $this->userModel->createRememberToken($user['id'], false);
            } else {
                // Refresh existing token to extend expiration
                $this->userModel->refreshRememberToken($user['id']);
                $token = $existingToken; // Use existing token
            }
            
            // Always set cookie with refresh token (ensures user stays logged in - NO LOGOUT)
            // Extended duration: 2 years if remember_me, 1 year otherwise (ensures persistent login)
            if ($token) {
                $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                $cookieDuration = $rememberMe ? (730 * 24 * 60 * 60) : (365 * 24 * 60 * 60); // 2 years or 1 year
                
                setcookie(
                    'remember_token', 
                    $token, 
                    [
                        'expires' => time() + $cookieDuration,
                        'path' => '/',
                        'domain' => '', // Empty domain works better in shared hosting
                        'secure' => $secure,
                        'httponly' => true,
                        'samesite' => 'Lax' // Lax for better compatibility in shared hosting
                    ]
                );
                
                // Extend session lifetime for persistent login (only if headers not sent)
                if (!headers_sent()) {
                    @ini_set('session.gc_maxlifetime', $cookieDuration);
                    @session_set_cookie_params([
                        'lifetime' => $cookieDuration,
                        'path' => '/',
                        'domain' => '',
                        'secure' => $secure,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }
            }
            
            // Migrate guest cart if exists
            $cartMiddleware = new \App\Middleware\CartMiddleware();
            $cartMiddleware->migrateGuestCartToUser($user['id']);
            
            // Email notifications removed for faster login - emails can be sent asynchronously via queue if needed
            // Login notification and welcome emails are non-critical and should not block user login
            
            // Redirect based on user role
            $userRole = $user['role'] ?? 'customer';
            
            if ($userRole === 'admin') {
                $this->redirect('admin');
            } elseif ($userRole === 'staff') {
                $this->redirect('staff/dashboard');
            } elseif ($userRole === 'curior') {
                $this->redirect('curior/dashboard');
            } else {
                $this->setFlash('success', 'Welcome back, ' . ($user['first_name'] ?? 'User') . '!');
                $this->redirect('');
            }
        } else {
            // Record failed attempt
            $this->recordLoginAttempt($ip, $identifier, time());
            $this->setFlash('error', 'Invalid credentials. Please try again.');
            $this->redirect('auth/login');
        }
    }
    
    /**
     * Display registration form
     */
    public function register()
    {
        // Check if already logged in
        if (Session::has('user_id')) {
            $this->redirect('');
        }
        
        // Get referral code from URL or cookies
        $referralCode = $_GET['ref'] ?? '';
        
        // If no referral code in URL, check cookies
        if (empty($referralCode) && isset($_COOKIE['referral_code'])) {
            $referralCode = trim($_COOKIE['referral_code']);
        }
        
        // Get inviter information if referral code exists
        $inviter = null;
        if (!empty($referralCode)) {
            // Get fresh data including sponsor_status
            $inviter = $this->userModel->findByReferralCode($referralCode);
            
            // If inviter found but sponsor_status missing, get fresh from DB
            if ($inviter && !isset($inviter['sponsor_status'])) {
                $db = \App\Core\Database::getInstance();
                $freshData = $db->query(
                    "SELECT *, sponsor_status FROM users WHERE referral_code = ?",
                    [$referralCode]
                )->single();
                if ($freshData) {
                    $inviter = array_merge($inviter, $freshData);
                }
            }
        }
        
        $this->view('auth/register', [
            'title' => 'Register',
            'referralCode' => $referralCode,
            'inviter' => $inviter
        ]);
    }
    
    /**
     * Process registration form submission
     */
    public function processRegister()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/register');
            return;
        }
        
        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('auth/register');
            return;
        }
        
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!\App\Helpers\SecurityHelper::checkRateLimit('register_' . $ip, 3, 3600)) {
            $remaining = \App\Helpers\SecurityHelper::getRateLimitRemaining('register_' . $ip, 3600);
            $this->setFlash('error', "Too many registration attempts. Please try again in " . ceil($remaining / 60) . " minutes.");
            $this->redirect('auth/register');
            return;
        }
        
        $fullName = \App\Helpers\SecurityHelper::sanitizeString($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = \App\Helpers\SecurityHelper::sanitizePhone($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $referralCode = \App\Helpers\SecurityHelper::sanitizeString($_POST['referral_code'] ?? '');
        
        $errors = [];
        
        // Validation
        if (empty($fullName)) {
            $errors[] = 'Full name is required';
        }
        
        if (empty($phone)) {
            $errors[] = 'Phone number is required';
        } elseif (!\App\Helpers\SecurityHelper::validatePhone($phone)) {
            $errors[] = 'Invalid phone number format';
        }
        
        if (!empty($email) && !\App\Helpers\SecurityHelper::validateEmail($email)) {
            $errors[] = 'Invalid email format';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // Check if phone already exists
        if (!empty($phone)) {
            $existingUser = $this->userModel->findByPhone($phone);
            if ($existingUser) {
                $errors[] = 'An account with this phone number already exists';
            }
        }
        
        // Check if email already exists (if provided)
        if (!empty($email)) {
            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser) {
                $errors[] = 'An account with this email already exists';
            }
        }
        
        // Validate referral code if provided
        if (!empty($referralCode)) {
            $inviter = $this->userModel->findByReferralCode($referralCode);
            if (!$inviter) {
                $errors[] = 'Invalid referral code';
            }
        }
        
        if (!empty($errors)) {
            $inviter = null;
            if (!empty($referralCode)) {
                $inviter = $this->userModel->findByReferralCode($referralCode);
            }
            
            $this->view('auth/register', [
                'title' => 'Register',
                'errors' => $errors,
                'referralCode' => $referralCode,
                'inviter' => $inviter
            ]);
            return;
        }
        
        // Register user
        try {
            $userData = [
                'full_name' => $fullName,
                'phone' => $phone,
                'password' => $password,
                'referred_by' => !empty($referralCode) && isset($inviter) ? $inviter['id'] : null
            ];
            
            if (!empty($email)) {
                $userData['email'] = $email;
            }
            
            $userId = $this->userModel->register($userData);
            
            if ($userId) {
                // Auto login after registration
                $user = $this->userModel->find($userId);
                if ($user) {
                    Session::set('user_id', $user['id']);
                    Session::set('user_email', $user['email'] ?? '');
                    Session::set('user_name', $user['first_name'] ?? '');
                    Session::set('user_role', $user['role'] ?? 'customer');
                    Session::set('logged_in', true);
                    
                    // Migrate guest cart if exists
                    $cartMiddleware = new \App\Middleware\CartMiddleware();
                    $cartMiddleware->migrateGuestCartToUser($user['id']);
                    
                    // Email notifications removed for faster registration - emails can be sent asynchronously via queue if needed
                    // Welcome emails are non-critical and should not block user registration
                    
                    $this->setFlash('success', 'Account created successfully! Welcome to NutriNexus!');
                    $this->redirect('');
                } else {
                    throw new Exception('Failed to retrieve user after registration');
                }
            } else {
                throw new Exception('Failed to create account');
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $this->setFlash('error', 'Registration failed: ' . $e->getMessage());
            $this->redirect('auth/register');
        }
    }
    
    /**
     * Display forgot password form
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = trim($_POST['identifier']); // Can be username, email, or phone
            
            if (empty($identifier)) {
                $this->setFlash('error', 'Please enter your username, email, or phone number');
                $this->view('auth/forgot-password', [
                    'title' => 'Forgot Password'
                ]);
                return;
            }
            
            // Try to find user by email, username, or phone using existing methods
            $user = $this->userModel->findByEmail($identifier);
            if (!$user) {
                $user = $this->userModel->findByUsername($identifier);
            }
            if (!$user) {
                $user = $this->userModel->findByPhone($identifier);
            }
            
            if ($user) {
                // Check if user has email
                if (empty($user['email'])) {
                    $this->setFlash('error', 'No email associated with this account. Please contact support.');
                    $this->view('auth/forgot-password', [
                        'identifier' => $identifier,
                        'title' => 'Forgot Password'
                    ]);
                    return;
                }
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
                
                // Save token to database using existing method
                $this->userModel->saveResetToken($user['id'], $token, $expires);
                
                try {
                    // Send reset email
                    error_log("AuthController: Attempting to send password reset email to: " . $user['email']);
                    $emailResult = $this->sendPasswordResetEmail($user['email'], $user['first_name'] ?? '', $token);
                    
                    if ($emailResult) {
                        error_log("AuthController: Password reset email sent successfully");
                        $this->setFlash('success', 'Password reset instructions have been sent to your email');
                        $this->redirect('auth/login');
                    } else {
                        error_log("AuthController: Password reset email failed to send");
                        $this->setFlash('error', 'Failed to send password reset email. Please try again later.');
                    }
                } catch (Exception $e) {
                    error_log('AuthController: Failed to send password reset email: ' . $e->getMessage());
                    $this->setFlash('error', 'Failed to send password reset email. Please try again later.');
                }
            } else {
                $this->setFlash('error', 'No account found with that username, email, or phone number');
            }
            
            $this->view('auth/forgot-password', [
                'identifier' => $identifier,
                'title' => 'Forgot Password'
            ]);
        } else {
            $this->view('auth/forgot-password', [
                'title' => 'Forgot Password'
            ]);
        }
    }
    
    /**
     * Display reset password form
     */
    public function resetPassword($token = null)
    {
        if (!$token) {
            $this->redirect('auth/login');
        }
        
        // Verify token using existing method
        $user = $this->userModel->findByResetToken($token);
        
        if (!$user || strtotime($user['reset_expires']) < time()) {
            $this->setFlash('error', 'Invalid or expired reset token');
            $this->redirect('auth/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate input
            $errors = [];
            
            if (empty($password)) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'Passwords do not match';
            }
            
            if (empty($errors)) {
                // Update password using existing method
                $this->userModel->updatePassword($user['id'], $password);
                
                // Clear reset token using existing method
                $this->userModel->clearResetToken($user['id']);
                
                // Send password changed confirmation email
                if (!empty($user['email'])) {
                    try {
                        error_log("AuthController: Attempting to send password changed email to: " . $user['email']);
                        $emailResult = $this->sendPasswordChangedEmail($user['email'], $user['first_name'] ?? '');
                        if ($emailResult) {
                            error_log("AuthController: Password changed email sent successfully");
                        } else {
                            error_log("AuthController: Password changed email failed to send");
                        }
                    } catch (Exception $e) {
                        // Log error but continue with password reset
                        error_log('AuthController: Failed to send password changed email: ' . $e->getMessage());
                    }
                }
                
                $this->setFlash('success', 'Password has been reset successfully');
                $this->redirect('auth/login');
            }
            
            $this->view('auth/reset-password', [
                'token' => $token,
                'errors' => $errors,
                'title' => 'Reset Password'
            ]);
        } else {
            $this->view('auth/reset-password', [
                'token' => $token,
                'title' => 'Reset Password'
            ]);
        }
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        // Clear remember token if user is logged in
        if (Session::has('user_id')) {
            $this->userModel->clearRememberToken(Session::get('user_id'));
        }
        
        Session::clear();
        Session::destroy();
        $this->redirect('auth/login');
    }
    
    /**
     * Authenticate user by phone number and password
     */
    private function authenticateUserByPhone($phone, $password)
    {
        // Find user by phone
        $user = $this->userModel->findByPhone($phone);
        if (!$user) {
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        // Check if user account is active - improved status checking
        $status = strtolower(trim($user['status'] ?? 'active'));
        
        if ($status === 'suspended') {
            error_log("Login attempt for suspended user: {$phone}");
            $this->setFlash('error', 'Your account has been suspended. Please contact support.');
            return false;
        }
        
        if ($status === 'inactive' || $status === 'deactive') {
            error_log("Login attempt for inactive user: {$phone}");
            $this->setFlash('error', 'Your account is inactive. Please contact support to activate your account.');
            return false;
        }
        
        if ($status === 'banned') {
            error_log("Login attempt for banned user: {$phone}");
            $this->setFlash('error', 'Your account has been banned. Please contact support.');
            return false;
        }
        
        // Additional check for sponsor_status if exists
        if (isset($user['sponsor_status']) && $user['sponsor_status'] === 'inactive') {
            // Allow login but log sponsor status
            error_log("User {$phone} logged in with inactive sponsor status");
        }
        
        return $user;
    }
    
    /**
     * Authenticate user by trying multiple methods (optimized for speed)
     * Uses single query with OR conditions for faster lookup
     */
    private function authenticateUser($identifier, $password)
    {
        // Sanitize input to prevent SQL injection
        $identifier = trim($identifier);
        $identifier = \App\Helpers\SecurityHelper::sanitizeString($identifier);
        
        // Validate password is not empty
        if (empty($password)) {
            return false;
        }
        
        $user = null;
        
        // Optimized: Try single query with OR conditions first (faster than multiple queries)
        // Note: We check status AFTER password verification to avoid revealing user existence
        try {
            $db = \App\Core\Database::getInstance();
            $sql = "SELECT * FROM users WHERE (email = ? OR phone = ? OR username = ?) LIMIT 1";
            $user = $db->query($sql)->bind([$identifier, $identifier, $identifier])->single();
            
            if ($user && password_verify($password, $user['password'])) {
                // Password verified, now check status
                $status = strtolower(trim($user['status'] ?? 'active'));
                if (in_array($status, ['suspended', 'inactive', 'deactive', 'banned'])) {
                    error_log("Login attempt for {$status} user: {$identifier}");
                    $this->setFlash('error', $this->getStatusErrorMessage($status));
                    return false;
                }
                // User is active, proceed
            } else {
                $user = null;
            }
        } catch (\Exception $e) {
            error_log('Fast auth query failed: ' . $e->getMessage());
            $user = null;
        }
        
        // Fallback: Try authenticate method if optimized query didn't work
        if (!$user) {
            $user = $this->userModel->authenticate($identifier, $password);
            if ($user) {
                $status = strtolower(trim($user['status'] ?? 'active'));
                if (in_array($status, ['suspended', 'inactive', 'deactive', 'banned'])) {
                    error_log("Login attempt for {$status} user: {$identifier}");
                    $this->setFlash('error', $this->getStatusErrorMessage($status));
                    return false;
                }
            }
        }
        
        // If still not found, try by username
        if (!$user) {
            $user = $this->userModel->findByUsername($identifier);
            if ($user && (!isset($user['password']) || !password_verify($password, $user['password']))) {
                $user = null;
            } elseif ($user) {
                $status = strtolower(trim($user['status'] ?? 'active'));
                if (in_array($status, ['suspended', 'inactive', 'deactive', 'banned'])) {
                    error_log("Login attempt for {$status} user: {$identifier}");
                    $this->setFlash('error', $this->getStatusErrorMessage($status));
                    return false;
                }
            }
        }
        
        // If still not found, try by phone
        if (!$user) {
            $user = $this->userModel->findByPhone($identifier);
            if ($user && (!isset($user['password']) || !password_verify($password, $user['password']))) {
                $user = null;
            } elseif ($user) {
                $status = strtolower(trim($user['status'] ?? 'active'));
                if (in_array($status, ['suspended', 'inactive', 'deactive', 'banned'])) {
                    error_log("Login attempt for {$status} user: {$identifier}");
                    $this->setFlash('error', $this->getStatusErrorMessage($status));
                    return false;
                }
            }
        }
        
        // Also check for staff and curior roles
        if (!$user) {
            // Check staff table
            try {
                $staffModel = new \App\Models\Staff();
                $staff = $staffModel->findByEmail($identifier);
                if (!$staff) {
                    $staff = $staffModel->findByPhone($identifier);
                }
                if ($staff && password_verify($password, $staff['password'])) {
                    // Convert staff to user format for compatibility
                    $user = [
                        'id' => $staff['id'],
                        'email' => $staff['email'] ?? '',
                        'phone' => $staff['phone'] ?? '',
                        'first_name' => $staff['name'] ?? '',
                        'last_name' => '',
                        'role' => 'staff',
                        'status' => $staff['status'] ?? 'active',
                        'created_at' => $staff['created_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
            } catch (\Exception $e) {
                // Staff model might not exist, continue
            }
        }
        
        if (!$user) {
            // Check curior table
            try {
                $curiorModel = new \App\Models\Curior();
                $curior = $curiorModel->getByPhone($identifier);
                if (!$curior) {
                    $curior = $curiorModel->getByEmail($identifier);
                }
                if ($curior && password_verify($password, $curior['password'])) {
                    // Convert curior to user format for compatibility
                    $user = [
                        'id' => $curior['id'],
                        'email' => $curior['email'] ?? '',
                        'phone' => $curior['phone'] ?? '',
                        'first_name' => $curior['name'] ?? '',
                        'last_name' => '',
                        'role' => 'curior',
                        'status' => $curior['status'] ?? 'active',
                        'created_at' => $curior['created_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
            } catch (\Exception $e) {
                // Curior model might not exist, continue
            }
        }
        
        // Final status check for staff and curior
        if ($user) {
            $status = strtolower(trim($user['status'] ?? 'active'));
            
            if (in_array($status, ['suspended', 'inactive', 'deactive', 'banned'])) {
                error_log("Login attempt for {$status} user: {$identifier}");
                $this->setFlash('error', $this->getStatusErrorMessage($status));
                return false;
            }
            
            return $user;
        }
        
        return false;
    }

    /**
     * Get error message for user status
     */
    private function getStatusErrorMessage($status)
    {
        switch ($status) {
            case 'suspended':
                return 'Your account has been suspended. Please contact support.';
            case 'inactive':
            case 'deactive':
                return 'Your account is inactive. Please contact support to activate your account.';
            case 'banned':
                return 'Your account has been banned. Please contact support.';
            default:
                return 'Your account is not active. Please contact support.';
        }
    }
    
    /**
     * Send login notification email
     */
    private function sendLoginNotificationEmail($email, $firstName, $ipAddress)
    {
        try {
            $templateData = [
                'first_name' => $firstName,
                'login_time' => date('Y-m-d H:i:s'),
                'ip_address' => $ipAddress,
                'site_name' => $this->getSiteName(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device'
            ];
            
            // Send email directly using EmailHelper
            return EmailHelper::sendTemplate(
                $email,
                'New Login to Your ' . $this->getSiteName() . ' Account',
                'login',
                $templateData,
                $firstName
            );
        } catch (Exception $e) {
            error_log('AuthController: Failed to send login notification email: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send welcome email - FIXED
     */
    private function sendWelcomeEmail($email, $firstName, $lastName)
    {
        try {
            $templateData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => trim($firstName . ' ' . $lastName),
                'site_name' => $this->getSiteName(),
                'site_url' => $this->getBaseUrl()
            ];
            
            error_log('AuthController: Sending welcome email with data: ' . json_encode($templateData));
            
            // Send email directly using EmailHelper
            $result = EmailHelper::sendTemplate(
                $email,
                'Welcome to ' . $this->getSiteName() . '!',
                'register',
                $templateData,
                $firstName
            );
            
            if ($result) {
                error_log('AuthController: Welcome email sent successfully to: ' . $email);
            } else {
                error_log('AuthController: Welcome email failed to send to: ' . $email);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('AuthController: Failed to send welcome email: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $firstName, $token)
    {
        try {
            $resetUrl = $this->getBaseUrl() . '/auth/resetPassword/' . $token;
        
            $templateData = [
                'first_name' => $firstName,
                'reset_url' => $resetUrl,
                'site_name' => $this->getSiteName()
            ];
            
            // Send email directly using EmailHelper
            return EmailHelper::sendTemplate(
                $email,
                'Reset Your ' . $this->getSiteName() . ' Password',
                'forgot-password',
                $templateData,
                $firstName
            );
        } catch (Exception $e) {
            error_log('AuthController: Failed to send password reset email: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Queue login notification email
     */
    private function queueLoginNotificationEmail($email, $firstName, $ipAddress)
    {
        try {
            $templateData = [
                'first_name' => $firstName,
                'login_date' => date('Y-m-d'),
                'login_time' => date('H:i:s'),
                'ip_address' => $ipAddress,
                'site_name' => $this->getSiteName(),
                'device_info' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device',
                'dashboard_url' => $this->getBaseUrl() . '/user/account'
            ];
            
            return $this->emailQueueService->addToQueue(
                $email,
                $firstName,
                'New Login to Your ' . $this->getSiteName() . ' Account',
                'login',
                $templateData
            );
        } catch (Exception $e) {
            error_log('AuthController: Failed to queue login notification email: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Queue welcome email
     */
    private function queueWelcomeEmail($email, $firstName, $lastName)
    {
        try {
            $templateData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => trim($firstName . ' ' . $lastName),
                'site_name' => $this->getSiteName(),
                'site_url' => $this->getBaseUrl()
            ];
            
            return $this->emailQueueService->addToQueue(
                $email,
                $firstName,
                'Welcome to ' . $this->getSiteName() . '!',
                'register',
                $templateData
            );
        } catch (Exception $e) {
            error_log('AuthController: Failed to queue welcome email: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Queue password reset email
     */
    private function queuePasswordResetEmail($email, $firstName, $token)
    {
        try {
            $resetUrl = $this->getBaseUrl() . '/auth/resetPassword/' . $token;
        
            $templateData = [
                'first_name' => $firstName,
                'reset_url' => $resetUrl,
                'site_name' => $this->getSiteName()
            ];
            
            return $this->emailQueueService->addToQueue(
                $email,
                $firstName,
                'Reset Your ' . $this->getSiteName() . ' Password',
                'forgot-password',
                $templateData
            );
        } catch (Exception $e) {
            error_log('AuthController: Failed to queue password reset email: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send password changed confirmation email
     */
    private function sendPasswordChangedEmail($email, $firstName)
    {
        try {
            $templateData = [
                'first_name' => $firstName,
                'site_name' => $this->getSiteName(),
                'change_date' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ];
            
            // Send email directly using EmailHelper
            return EmailHelper::sendTemplate(
                $email,
                'Your ' . $this->getSiteName() . ' Password Has Been Changed',
                'password-changed',
                $templateData,
                $firstName
            );
        } catch (Exception $e) {
            error_log('AuthController: Failed to send password changed email: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get site name with fallback
     */
    private function getSiteName()
    {
        if (defined('SITENAME')) {
            return SITENAME;
        }
        return 'NutriNexus';
    }
    
    /**
     * Get base URL with fallback
     */
    private function getBaseUrl()
    {
        if (defined('BASE_URL')) {
            return BASE_URL ?: 'http://localhost';
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . "://" . $host;
    }
    
    /**
     * Record a login attempt
     */
    private function recordLoginAttempt($ip, $username, $time)
    {
        $this->loginAttempts[] = [
            'ip' => $ip,
            'username' => $username,
            'time' => $time,
            'success' => false
        ];
        
        $this->saveLoginAttempts();
    }
    
    /**
     * Reset login attempts for an IP
     */
    private function resetLoginAttempts($ip)
    {
        $this->loginAttempts = array_filter($this->loginAttempts, function($attempt) use ($ip) {
            return $attempt['ip'] !== $ip;
        });
        
        $this->saveLoginAttempts();
    }
    
    /**
     * Check if an IP is rate limited
     */
    private function isRateLimited($ip)
    {
        $now = time();
        $attempts = array_filter($this->loginAttempts, function($attempt) use ($ip, $now) {
            return $attempt['ip'] === $ip && ($now - $attempt['time']) < 3600; // 1 hour window
        });
        
        if (count($attempts) >= 5) { // 5 attempts allowed
            // Check if last attempt was more than 15 minutes ago
            $lastAttempt = end($attempts);
            if (($now - $lastAttempt['time']) > 900) { // 15 minutes
                return false;
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Get remaining lockout time in minutes
     */
    private function getRemainingLockoutTime($ip)
    {
        $now = time();
        $attempts = array_filter($this->loginAttempts, function($attempt) use ($ip, $now) {
            return $attempt['ip'] === $ip && ($now - $attempt['time']) < 3600; // 1 hour window
        });
        
        if (count($attempts) >= 5) {
            $lastAttempt = end($attempts);
            $remainingSeconds = 900 - ($now - $lastAttempt['time']); // 15 minutes lockout
            return ceil($remainingSeconds / 60);
        }
        
        return 0;
    }
    
    /**
     * Load login attempts from file
     */
    private function loadLoginAttempts()
    {
        if (file_exists($this->attemptFile)) {
            $content = file_get_contents($this->attemptFile);
            if ($content) {
                $this->loginAttempts = unserialize($content);
            }
        }
    }
    
    /**
     * Save login attempts to file
     */
    private function saveLoginAttempts()
    {
        file_put_contents($this->attemptFile, serialize($this->loginAttempts));
    }
    
    /**
     * Recover session using persistent token
     */
    public function recoverSession()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('');
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['persistent_token'] ?? '';
        
        if (empty($token)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No token provided'
            ]);
            return;
        }
        
        $userData = SessionRecoveryHelper::validatePersistentToken($token);
        
        if ($userData) {
            // Restore session
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['user_name'] = $userData['first_name'] ?? '';
            $_SESSION['user_email'] = $userData['email'] ?? '';
            $_SESSION['user_role'] = $userData['role'] ?? 'customer';
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Session recovered successfully'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]);
        }
    }
    
    /**
     * Handle Auth0 registration
     */
    public function registerWithAuth0()
    {
        try {
            // Initialize Auth0Service
            $auth0Service = new \App\Services\Auth0Service();
            
            // Store the intended redirect URL and registration flag
            Session::set('auth0_redirect', 'auth/register');
            Session::set('auth0_action', 'register');
            
            // Get Auth0 login URL (which will be used for registration)
            $loginUrl = $auth0Service->getLoginUrl();
            $this->externalRedirect($loginUrl);
        } catch (Exception $e) {
            $this->setFlash('error', 'Auth0 registration failed: ' . $e->getMessage());
            $this->redirect('auth/register');
        }
    }

    /**
     * Redirect to external URL (for Auth0 authentication)
     */
    private function externalRedirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
}
