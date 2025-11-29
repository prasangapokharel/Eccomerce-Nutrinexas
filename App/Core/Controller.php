<?php
namespace App\Core;

/**
* Base controller class
* 
* All controllers extend this class
*/
class Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize any common controller functionality
    }

    /**
     * Load model
     *
     * @param string $model
     * @return object
     */
    public function model($model)
    {
        $modelClass = 'App\\Models\\' . $model;
        return new $modelClass();
    }

    /**
     * Load view
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    public function view($view, $data = [])
    {
        if (file_exists(dirname(dirname(__FILE__)) . '/views/' . $view . '.php')) {
            extract($data);
            require_once dirname(dirname(__FILE__)) . '/views/' . $view . '.php';
        } else {
            die('View does not exist');
        }
    }

    /**
     * Redirect to a page
     *
     * @param string $page
     * @return void
     */
    public function redirect($page)
    {
        header('Location: ' . BASE_URL . '/' . $page);
        exit;
    }

    /**
     * Set flash message
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public function setFlash($type, $message)
    {
        Session::setFlash($type, $message);
    }

    /**
     * Get POST data (sanitized)
     *
     * @param string $key
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function post($key, $default = null, $sanitize = true)
    {
        $value = $_POST[$key] ?? $default;
        
        if ($sanitize && is_string($value)) {
            return \App\Helpers\SecurityHelper::sanitizeString($value);
        }
        
        return $value;
    }

    /**
     * Get GET data (sanitized)
     *
     * @param string $key
     * @param mixed $default
     * @param bool $sanitize
     * @return mixed
     */
    public function get($key, $default = null, $sanitize = true)
    {
        $value = $_GET[$key] ?? $default;
        
        if ($sanitize && is_string($value)) {
            return \App\Helpers\SecurityHelper::sanitizeString($value);
        }
        
        return $value;
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCSRF(): bool
    {
        return \App\Helpers\SecurityHelper::validateCSRF();
    }
    
    /**
     * Get CSRF token for forms
     */
    protected function getCSRFToken(): string
    {
        return \App\Helpers\SecurityHelper::generateCSRFToken();
    }

    /**
     * Require user to be logged in
     * Also checks refresh token to restore session if needed (works in shared hosting)
     * Checks if user is suspended and blocks access
     *
     * @return void
     */
    public function requireLogin()
    {
        $userId = null;
        
        // First check session
        if (Session::has('user_id')) {
            $userId = Session::get('user_id');
        }
        
        // If no session, check refresh token (for shared hosting compatibility)
        if (!$userId && !empty($_COOKIE['remember_token'])) {
            try {
                $userModel = new \App\Models\User();
                $user = $userModel->findByRememberToken($_COOKIE['remember_token']);
                if ($user) {
                    $userId = $user['id'];
                    // Restore session from refresh token
                    Session::set('user_id', $user['id']);
                    Session::set('user_email', $user['email'] ?? '');
                    Session::set('user_name', $user['first_name'] ?? '');
                    Session::set('user_role', $user['role'] ?? 'customer');
                    Session::set('logged_in', true);
                    
                    // Refresh token to extend expiration
                    $userModel->refreshRememberToken($user['id'], false);
                }
            } catch (\Exception $e) {
                error_log('Refresh token check error in requireLogin: ' . $e->getMessage());
            }
        }
        
        // Check if user is logged in
        if (!$userId) {
            $this->setFlash('error', 'Please log in to access this page');
            $this->redirect('auth/login');
            return;
        }
        
        // Check if user is suspended or inactive
        $userModel = new \App\Models\User();
        $user = $userModel->find($userId);
        if ($user) {
            $userStatus = $user['status'] ?? 'active';
            if ($userStatus === 'suspended') {
                Session::destroy();
                $suspensionReason = $user['suspension_reason'] ?? 'Your account has been suspended';
                $this->setFlash('error', "Account suspended: {$suspensionReason}. Please contact support.");
                $this->redirect('auth/login');
                exit;
            }
            if ($userStatus === 'inactive') {
                Session::destroy();
                $this->setFlash('error', 'Your account is inactive. Please contact support to reactivate.');
                $this->redirect('auth/login');
                exit;
            }
        }
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    protected function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Send JSON response
     *
     * @param array $data
     * @return void
     */
    protected function jsonResponse($data)
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        $data['timestamp'] = time();
        echo json_encode($data);
        exit;
    }

    /**
     * Require user to be admin
     *
     * @return void
     */
    public function requireAdmin()
    {
        if (!Session::has('user_id') || Session::get('user_role') !== 'admin') {
            $this->setFlash('error', 'You do not have permission to access this page');
            $this->redirect('');
        }
    }
}
