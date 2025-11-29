<?php

namespace App\Services;

use App\Config\OAuthConfig;
use App\Models\User;
use App\Core\Session;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class OAuthService
{
    private $httpClient;
    private $userModel;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => false // For development only
        ]);
        $this->userModel = new User();
    }

    /**
     * Generate authorization URL for OAuth provider
     */
    public function getAuthorizationUrl($provider)
    {
        // Validate provider configuration
        OAuthConfig::validateProvider($provider);
        
        $config = OAuthConfig::getProvider($provider);
        if (!$config) {
            throw new Exception("OAuth provider '{$provider}' not configured");
        }

        $state = OAuthConfig::generateState();
        
        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $config['scope'],
            'response_type' => 'code',
            'state' => $state
        ];

        // Provider-specific parameters
        if ($provider === 'google') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
        } elseif ($provider === 'facebook') {
            $params['response_type'] = 'code';
        } elseif ($provider === 'github') {
            $params['scope'] = 'user:email';
        }

        return $config['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Handle OAuth callback and exchange code for token
     */
    public function handleCallback($provider, $code, $state)
    {
        // Verify state parameter
        if (!OAuthConfig::verifyState($state)) {
            throw new Exception('Invalid OAuth state parameter');
        }

        $config = OAuthConfig::getProvider($provider);
        if (!$config) {
            throw new Exception("OAuth provider '{$provider}' not configured");
        }

        // Exchange code for access token
        $tokenData = $this->exchangeCodeForToken($provider, $code, $config);
        
        // Get user information
        $userInfo = $this->getUserInfo($provider, $tokenData['access_token'], $config);
        
        // Create or update user
        $user = $this->createOrUpdateUser($provider, $userInfo, $tokenData);
        
        // Clear OAuth state
        OAuthConfig::clearState();
        
        return $user;
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken($provider, $code, $config)
    {
        $params = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect_uri']
        ];

        if ($provider === 'google') {
            $params['grant_type'] = 'authorization_code';
        } elseif ($provider === 'facebook') {
            $params['grant_type'] = 'authorization_code';
        } elseif ($provider === 'github') {
            $params['grant_type'] = 'authorization_code';
        }

        try {
            $response = $this->httpClient->post($config['token_url'], [
                'form_params' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'Nutrinexus-OAuth-Client/1.0'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['access_token'])) {
                throw new Exception('Failed to obtain access token');
            }

            return $data;
        } catch (RequestException $e) {
            throw new Exception('Failed to exchange code for token: ' . $e->getMessage());
        }
    }

    /**
     * Get user information from OAuth provider
     */
    private function getUserInfo($provider, $accessToken, $config)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
            'User-Agent' => 'Nutrinexus-OAuth-Client/1.0'
        ];

        $url = $config['user_info_url'];
        
        // Provider-specific adjustments
        if ($provider === 'facebook') {
            $url .= '?fields=id,name,email,picture';
        } elseif ($provider === 'github') {
            // GitHub requires separate call for email
            $emailUrl = 'https://api.github.com/user/emails';
        }

        try {
            $response = $this->httpClient->get($url, ['headers' => $headers]);
            $userInfo = json_decode($response->getBody(), true);

            // Get email for GitHub (separate API call)
            if ($provider === 'github' && !isset($userInfo['email'])) {
                $emailResponse = $this->httpClient->get($emailUrl, ['headers' => $headers]);
                $emails = json_decode($emailResponse->getBody(), true);
                
                foreach ($emails as $email) {
                    if ($email['primary'] && $email['verified']) {
                        $userInfo['email'] = $email['email'];
                        break;
                    }
                }
            }

            return $this->normalizeUserInfo($provider, $userInfo);
        } catch (RequestException $e) {
            throw new Exception('Failed to get user info: ' . $e->getMessage());
        }
    }

    /**
     * Normalize user information across different providers
     */
    private function normalizeUserInfo($provider, $userInfo)
    {
        $normalized = [
            'provider' => $provider,
            'provider_id' => '',
            'email' => '',
            'name' => '',
            'first_name' => '',
            'last_name' => '',
            'avatar' => '',
            'raw_data' => $userInfo
        ];

        switch ($provider) {
            case 'google':
                $normalized['provider_id'] = $userInfo['id'] ?? '';
                $normalized['email'] = $userInfo['email'] ?? '';
                $normalized['name'] = $userInfo['name'] ?? '';
                $normalized['first_name'] = $userInfo['given_name'] ?? '';
                $normalized['last_name'] = $userInfo['family_name'] ?? '';
                $normalized['avatar'] = $userInfo['picture'] ?? '';
                break;

            case 'facebook':
                $normalized['provider_id'] = $userInfo['id'] ?? '';
                $normalized['email'] = $userInfo['email'] ?? '';
                $normalized['name'] = $userInfo['name'] ?? '';
                $nameParts = explode(' ', $userInfo['name'] ?? '', 2);
                $normalized['first_name'] = $nameParts[0] ?? '';
                $normalized['last_name'] = $nameParts[1] ?? '';
                $normalized['avatar'] = $userInfo['picture']['data']['url'] ?? '';
                break;

            case 'github':
                $normalized['provider_id'] = $userInfo['id'] ?? '';
                $normalized['email'] = $userInfo['email'] ?? '';
                $normalized['name'] = $userInfo['name'] ?? $userInfo['login'] ?? '';
                $nameParts = explode(' ', $userInfo['name'] ?? '', 2);
                $normalized['first_name'] = $nameParts[0] ?? '';
                $normalized['last_name'] = $nameParts[1] ?? '';
                $normalized['avatar'] = $userInfo['avatar_url'] ?? '';
                break;
        }

        return $normalized;
    }

    /**
     * Create or update user in database
     */
    private function createOrUpdateUser($provider, $userInfo, $tokenData)
    {
        // Check if user exists by email
        $existingUser = $this->userModel->findByEmail($userInfo['email']);
        
        $fullName = trim($userInfo['name'] ?? ($userInfo['first_name'] . ' ' . $userInfo['last_name']));
        if ($existingUser) {
            // Update existing user with OAuth info
            $updateData = [
                'oauth_provider' => $provider,
                'oauth_provider_id' => $userInfo['provider_id'],
                'avatar' => $userInfo['avatar'],
                'first_name' => $userInfo['first_name'] ?: ($existingUser['first_name'] ?? ''),
                'last_name' => $userInfo['last_name'] ?: ($existingUser['last_name'] ?? ''),
                'full_name' => $fullName ?: ($existingUser['full_name'] ?? $existingUser['first_name'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->userModel->update($existingUser['id'], $updateData);
            return $this->userModel->find($existingUser['id']);
        } else {
            // Create new user
            $userData = [
                'first_name' => $userInfo['first_name'] ?: ($fullName ?: 'Customer'),
                'last_name' => $userInfo['last_name'] ?? '',
                'full_name' => $fullName ?: trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? '')),
                'email' => $userInfo['email'],
                'username' => $this->generateUsername($userInfo['email']),
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), // Random password
                'role' => 'customer',
                'status' => 'active',
                'oauth_provider' => $provider,
                'oauth_provider_id' => $userInfo['provider_id'],
                'avatar' => $userInfo['avatar'],
                'email_verified' => 1, // OAuth emails are considered verified
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $userId = $this->userModel->create($userData);
            return $this->userModel->find($userId);
        }
    }

    /**
     * Generate unique username from email
     */
    private function generateUsername($email)
    {
        $baseUsername = explode('@', $email)[0];
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
        
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->userModel->findByUsername($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Login user after OAuth authentication
     */
    public function loginUser($user)
    {
        Session::set('user_id', $user['id']);
        $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        Session::set('user_email', $user['email']);
        Session::set('user_name', $fullName ?: ($user['full_name'] ?? $user['email']));
        Session::set('user_avatar', $user['avatar'] ?? null);
        Session::set('user_first_name', $user['first_name'] ?? '');
        Session::set('user_last_name', $user['last_name'] ?? '');
        Session::set('user_role', $user['role'] ?? 'customer');
        Session::set('is_logged_in', true);
        Session::set('logged_in', true);
        Session::set('oauth_login', true);
        
        // Update last login
        $this->userModel->update($user['id'], [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Logout user
     */
    public function logoutUser()
    {
        Session::destroy();
    }

    /**
     * Check if user is logged in via OAuth
     */
    public function isOAuthUser()
    {
        return Session::get('oauth_login') === true;
    }

    /**
     * Get enabled OAuth providers for display
     */
    public function getEnabledProviders()
    {
        return OAuthConfig::getEnabledProviders();
    }
}