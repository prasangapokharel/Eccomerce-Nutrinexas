<?php

namespace App\Services;

use Auth0\SDK\Auth0;
use App\Models\User;
use App\Core\Session;

class Auth0Service
{
    private $auth0;
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->initializeAuth0();
    }

    /**
     * Initialize Auth0 SDK with configuration
     */
    private function initializeAuth0()
    {
        try {
            $this->auth0 = new Auth0([
                'domain' => $_ENV['AUTH0_DOMAIN'],
                'clientId' => $_ENV['AUTH0_CLIENT_ID'],
                'clientSecret' => $_ENV['AUTH0_CLIENT_SECRET'],
                'cookieSecret' => $_ENV['AUTH0_COOKIE_SECRET'],
                'redirectUri' => $_ENV['AUTH0_CALLBACK_URL']
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Failed to initialize Auth0: " . $e->getMessage());
        }
    }

    /**
     * Get Auth0 login URL
     */
    public function getLoginUrl($additionalParams = [])
    {
        try {
            return $this->auth0->login();
        } catch (\Exception $e) {
            throw new \Exception("Failed to generate login URL: " . $e->getMessage());
        }
    }

    /**
     * Handle Auth0 callback
     */
    public function handleCallback()
    {
        try {
            // Exchange authorization code for tokens
            $this->auth0->exchange();
            
            $credentials = $this->auth0->getCredentials();
            if ($credentials && $credentials->user) {
                return $this->processAuth0User($credentials->user);
            }
            
            throw new \Exception("No user data received from Auth0");
        } catch (\Exception $e) {
            throw new \Exception("Auth0 callback error: " . $e->getMessage());
        }
    }

    /**
     * Process Auth0 user data and create/update local user
     */
    private function processAuth0User($auth0User)
    {
        try {
            $email = $auth0User['email'] ?? null;
            $auth0Id = $auth0User['sub'] ?? null;
            
            if (!$email || !$auth0Id) {
                throw new \Exception("Missing required user data from Auth0");
            }

            // Check if user exists by email
            $existingUser = $this->userModel->findByEmail($email);
            
            if ($existingUser) {
                // Update existing user with Auth0 ID if not set
                if (empty($existingUser['auth0_id'])) {
                    $this->userModel->updateAuth0Id($existingUser['id'], $auth0Id);
                }
                $user = $existingUser;
            } else {
                // Create new user
                $name = $auth0User['name'] ?? $auth0User['nickname'] ?? $auth0User['given_name'] ?? $auth0User['email'] ?? 'Auth0 User';
                
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'auth0_id' => $auth0Id,
                    'email_verified' => $auth0User['email_verified'] ?? false,
                    'picture' => $auth0User['picture'] ?? null,
                    'provider' => 'auth0',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $userId = $this->userModel->createOAuthUser($userData);
                $user = $this->userModel->findById($userId);
            }

            // Set session
            Session::set('user_id', $user['id']);
            Session::set('user_email', $user['email']);
            Session::set('user_name', $user['name']);
            Session::set('auth_provider', 'auth0');
            Session::set('logged_in', true);

            return $user;
        } catch (\Exception $e) {
            throw new \Exception("Failed to process Auth0 user: " . $e->getMessage());
        }
    }

    /**
     * Get current authenticated user
     */
    public function getUser()
    {
        try {
            $credentials = $this->auth0->getCredentials();
            return $credentials ? $credentials->user : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        try {
            $credentials = $this->auth0->getCredentials();
            return $credentials !== null && !$credentials->accessTokenExpired;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Auth0 logout URL
     */
    public function getLogoutUrl($returnTo = null)
    {
        try {
            $returnTo = $returnTo ?: $_ENV['AUTH0_LOGOUT_URL'];
            return $this->auth0->logout($returnTo);
        } catch (Auth0Exception $e) {
            throw new Exception("Failed to generate logout URL: " . $e->getMessage());
        }
    }

    /**
     * Clear Auth0 session
     */
    public function logout()
    {
        try {
            $this->auth0->clear();
            
            // Clear application session
            Session::destroy();
            
            return true;
        } catch (Auth0Exception $e) {
            throw new Exception("Failed to logout: " . $e->getMessage());
        }
    }

    /**
     * Get user profile from Auth0
     */
    public function getUserProfile()
    {
        try {
            $user = $this->auth0->getUser();
            if (!$user) {
                return null;
            }

            return [
                'id' => $user['sub'],
                'name' => $user['name'] ?? '',
                'email' => $user['email'] ?? '',
                'picture' => $user['picture'] ?? '',
                'email_verified' => $user['email_verified'] ?? false,
                'given_name' => $user['given_name'] ?? '',
                'family_name' => $user['family_name'] ?? '',
                'nickname' => $user['nickname'] ?? '',
                'updated_at' => $user['updated_at'] ?? ''
            ];
        } catch (Auth0Exception $e) {
            throw new Exception("Failed to get user profile: " . $e->getMessage());
        }
    }

    /**
     * Link Auth0 account to existing user
     */
    public function linkAccount($userId, $auth0User)
    {
        try {
            $auth0Id = $auth0User['sub'] ?? null;
            if (!$auth0Id) {
                throw new Exception("Missing Auth0 ID");
            }

            return $this->userModel->updateAuth0Id($userId, $auth0Id);
        } catch (Exception $e) {
            throw new Exception("Failed to link Auth0 account: " . $e->getMessage());
        }
    }

    /**
     * Unlink Auth0 account from user
     */
    public function unlinkAccount($userId)
    {
        try {
            return $this->userModel->updateAuth0Id($userId, null);
        } catch (Exception $e) {
            throw new Exception("Failed to unlink Auth0 account: " . $e->getMessage());
        }
    }
}