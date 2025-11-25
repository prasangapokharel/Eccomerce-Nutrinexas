<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\OAuthService;
use App\Core\Session;
use Exception;

class OAuthController extends Controller
{
    private $oauthService;

    public function __construct()
    {
        parent::__construct();
        $this->oauthService = new OAuthService();
    }

    /**
     * Redirect to OAuth provider for authentication
     */
    public function login($provider = null)
    {
        if (!$provider) {
            $this->setFlash('error', 'OAuth provider not specified');
            $this->redirect('auth/login');
            return;
        }

        try {
            // Store the intended redirect URL
            if (isset($_GET['redirect'])) {
                Session::set('oauth_redirect', $_GET['redirect']);
            }

            $authUrl = $this->oauthService->getAuthorizationUrl($provider);
            $this->redirect($authUrl, true); // External redirect
        } catch (Exception $e) {
            // Check if this is a configuration error
            if (str_contains($e->getMessage(), 'not properly configured')) {
                // Show the OAuth configuration help page
                $this->view('errors/oauth_config', [
                    'provider' => $provider,
                    'error' => $e->getMessage()
                ]);
                return;
            }
            
            $this->setFlash('error', 'OAuth authentication failed: ' . $e->getMessage());
            $this->redirect('auth/login');
        }
    }

    /**
     * Handle OAuth callback
     */
    public function callback($provider = null)
    {
        if (!$provider) {
            $this->setFlash('error', 'OAuth provider not specified');
            $this->redirect('auth/login');
            return;
        }

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        // Check for OAuth errors
        if ($error) {
            $errorDescription = $_GET['error_description'] ?? 'OAuth authentication failed';
            $this->setFlash('error', 'OAuth Error: ' . $errorDescription);
            $this->redirect('auth/login');
            return;
        }

        if (!$code) {
            $this->setFlash('error', 'Authorization code not received');
            $this->redirect('auth/login');
            return;
        }

        try {
            // Handle the OAuth callback
            $user = $this->oauthService->handleCallback($provider, $code, $state);
            
            // Login the user
            $this->oauthService->loginUser($user);
            
            // Set success message
            $this->setFlash('success', 'Successfully logged in with ' . ucfirst($provider));
            
            // Redirect to intended page or dashboard
            $redirectUrl = Session::get('oauth_redirect') ?? 'dashboard';
            Session::remove('oauth_redirect');
            
            $this->redirect($redirectUrl);
        } catch (Exception $e) {
            $this->setFlash('error', 'OAuth authentication failed: ' . $e->getMessage());
            $this->redirect('auth/login');
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        $this->oauthService->logoutUser();
        $this->setFlash('success', 'Successfully logged out');
        $this->redirect('home');
    }

    /**
     * Link OAuth account to existing user
     */
    public function link($provider = null)
    {
        // Check if user is already logged in
        if (!Session::get('is_logged_in')) {
            $this->setFlash('error', 'You must be logged in to link an OAuth account');
            $this->redirect('auth/login');
            return;
        }

        if (!$provider) {
            $this->setFlash('error', 'OAuth provider not specified');
            $this->redirect('user/profile');
            return;
        }

        try {
            // Store that this is a linking operation
            Session::set('oauth_linking', true);
            Session::set('oauth_link_provider', $provider);
            
            $authUrl = $this->oauthService->getAuthorizationUrl($provider);
            $this->redirect($authUrl, true); // External redirect
        } catch (Exception $e) {
            $this->setFlash('error', 'OAuth linking failed: ' . $e->getMessage());
            $this->redirect('user/profile');
        }
    }

    /**
     * Handle OAuth linking callback
     */
    public function linkCallback($provider = null)
    {
        if (!Session::get('oauth_linking') || Session::get('oauth_link_provider') !== $provider) {
            $this->setFlash('error', 'Invalid OAuth linking request');
            $this->redirect('user/profile');
            return;
        }

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        // Check for OAuth errors
        if ($error) {
            $errorDescription = $_GET['error_description'] ?? 'OAuth linking failed';
            $this->setFlash('error', 'OAuth Error: ' . $errorDescription);
            $this->redirect('user/profile');
            return;
        }

        if (!$code) {
            $this->setFlash('error', 'Authorization code not received');
            $this->redirect('user/profile');
            return;
        }

        try {
            // Handle the OAuth callback for linking
            $oauthUser = $this->oauthService->handleCallback($provider, $code, $state);
            
            // Link the OAuth account to current user
            $currentUserId = Session::get('user_id');
            $userModel = new \App\Models\User();
            
            $userModel->update($currentUserId, [
                'oauth_provider' => $provider,
                'oauth_provider_id' => $oauthUser['oauth_provider_id'],
                'avatar' => $oauthUser['avatar'] ?: null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Clear linking session data
            Session::remove('oauth_linking');
            Session::remove('oauth_link_provider');
            
            $this->setFlash('success', ucfirst($provider) . ' account linked successfully');
            $this->redirect('user/profile');
        } catch (Exception $e) {
            Session::remove('oauth_linking');
            Session::remove('oauth_link_provider');
            $this->setFlash('error', 'OAuth linking failed: ' . $e->getMessage());
            $this->redirect('user/profile');
        }
    }

    /**
     * Unlink OAuth account
     */
    public function unlink()
    {
        if (!Session::get('is_logged_in')) {
            $this->setFlash('error', 'You must be logged in to unlink an OAuth account');
            $this->redirect('auth/login');
            return;
        }

        try {
            $currentUserId = Session::get('user_id');
            $userModel = new \App\Models\User();
            
            $userModel->update($currentUserId, [
                'oauth_provider' => null,
                'oauth_provider_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->setFlash('success', 'OAuth account unlinked successfully');
            $this->redirect('user/profile');
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to unlink OAuth account: ' . $e->getMessage());
            $this->redirect('user/profile');
        }
    }

    /**
     * Get OAuth providers for AJAX requests
     */
    public function providers()
    {
        header('Content-Type: application/json');
        
        try {
            $providers = $this->oauthService->getEnabledProviders();
            echo json_encode([
                'success' => true,
                'providers' => $providers
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * OAuth status check for AJAX
     */
    public function status()
    {
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => true,
            'is_logged_in' => Session::get('is_logged_in', false),
            'is_oauth_user' => $this->oauthService->isOAuthUser(),
            'user_id' => Session::get('user_id'),
            'user_name' => Session::get('user_name'),
            'user_email' => Session::get('user_email')
        ]);
    }
}