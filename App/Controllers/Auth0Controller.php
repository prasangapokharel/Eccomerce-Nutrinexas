<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\Auth0Service;
use App\Core\Session;
use Exception;

class Auth0Controller extends Controller
{
    private $auth0Service;

    public function __construct()
    {
        parent::__construct();
        $this->auth0Service = new Auth0Service();
    }

    /**
     * Redirect to Auth0 for authentication
     */
    public function login()
    {
        try {
            // Store the intended redirect URL
            if (isset($_GET['redirect'])) {
                Session::set('auth0_redirect', $_GET['redirect']);
            }

            $loginUrl = $this->auth0Service->getLoginUrl();
            $this->externalRedirect($loginUrl);
        } catch (Exception $e) {
            $this->setFlash('error', 'Auth0 authentication failed: ' . $e->getMessage());
            $this->redirect('auth/login');
        }
    }

    /**
     * Handle Auth0 callback
     */
    public function callback()
    {
        try {
            // Handle the Auth0 callback
            $user = $this->auth0Service->handleCallback();

            if ($user) {
                $this->setFlash('success', 'Successfully logged in with Auth0!');
                
                // Redirect to intended URL or dashboard
                $redirectUrl = Session::get('auth0_redirect', '/');
                Session::remove('auth0_redirect');
                
                $this->redirect($redirectUrl);
            } else {
                throw new Exception('Failed to authenticate user');
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Auth0 callback error: ' . $e->getMessage());
            $this->redirect('auth/login');
        }
    }

    /**
     * Logout from Auth0
     */
    public function logout()
    {
        try {
            $logoutUrl = $this->auth0Service->getLogoutUrl();
            
            // Clear local session
            $this->auth0Service->logout();
            
            $this->setFlash('success', 'Successfully logged out');
            $this->externalRedirect($logoutUrl);
        } catch (Exception $e) {
            // Fallback to local logout if Auth0 logout fails
            Session::destroy();
            $this->setFlash('error', 'Logout error: ' . $e->getMessage());
            $this->redirect('/');
        }
    }

    /**
     * Get Auth0 user profile
     */
    public function profile()
    {
        try {
            if (!$this->auth0Service->isAuthenticated()) {
                $this->setFlash('error', 'You must be logged in to view your profile');
                $this->redirect('auth/login');
                return;
            }

            $profile = $this->auth0Service->getUserProfile();
            
            $this->view('user/auth0_profile', [
                'title' => 'Auth0 Profile',
                'profile' => $profile
            ]);
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to load profile: ' . $e->getMessage());
            $this->redirect('/');
        }
    }

    /**
     * Link Auth0 account to existing user
     */
    public function link()
    {
        try {
            // Check if user is logged in locally
            if (!Session::get('logged_in')) {
                $this->setFlash('error', 'You must be logged in to link your Auth0 account');
                $this->redirect('auth/login');
                return;
            }

            // Store linking flag
            Session::set('auth0_linking', true);
            
            $loginUrl = $this->auth0Service->getLoginUrl(['prompt' => 'login']);
            $this->redirect($loginUrl, true);
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to initiate account linking: ' . $e->getMessage());
            $this->redirect('user/profile');
        }
    }

    /**
     * Handle Auth0 account linking callback
     */
    public function linkCallback()
    {
        try {
            if (!Session::get('auth0_linking')) {
                throw new Exception('Invalid linking request');
            }

            $userId = Session::get('user_id');
            if (!$userId) {
                throw new Exception('User not logged in');
            }

            $auth0User = $this->auth0Service->getUser();
            if (!$auth0User) {
                throw new Exception('No Auth0 user data received');
            }

            $result = $this->auth0Service->linkAccount($userId, $auth0User);
            
            if ($result) {
                Session::remove('auth0_linking');
                $this->setFlash('success', 'Auth0 account successfully linked!');
            } else {
                throw new Exception('Failed to link account');
            }

            $this->redirect('user/profile');
        } catch (Exception $e) {
            Session::remove('auth0_linking');
            $this->setFlash('error', 'Account linking failed: ' . $e->getMessage());
            $this->redirect('user/profile');
        }
    }

    /**
     * Unlink Auth0 account
     */
    public function unlink()
    {
        try {
            $userId = Session::get('user_id');
            if (!$userId) {
                throw new Exception('User not logged in');
            }

            $result = $this->auth0Service->unlinkAccount($userId);
            
            if ($result) {
                $this->setFlash('success', 'Auth0 account successfully unlinked!');
            } else {
                throw new Exception('Failed to unlink account');
            }

            $this->redirect('user/profile');
        } catch (Exception $e) {
            $this->setFlash('error', 'Account unlinking failed: ' . $e->getMessage());
            $this->redirect('user/profile');
        }
    }

    /**
     * Check Auth0 authentication status
     */
    public function status()
    {
        try {
            $isAuthenticated = $this->auth0Service->isAuthenticated();
            $user = $isAuthenticated ? $this->auth0Service->getUserProfile() : null;

            $this->jsonResponse([
                'authenticated' => $isAuthenticated,
                'user' => $user
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'error' => 'Failed to check status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available Auth0 providers/connections
     */
    public function providers()
    {
        try {
            // Return Auth0 connection information
            $this->jsonResponse([
                'providers' => [
                    'auth0' => [
                        'name' => 'Auth0',
                        'domain' => $_ENV['AUTH0_DOMAIN'],
                        'available' => !empty($_ENV['AUTH0_CLIENT_ID'])
                    ]
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'error' => 'Failed to get providers: ' . $e->getMessage()
            ], 500);
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