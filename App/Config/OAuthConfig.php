<?php

namespace App\Config;

class OAuthConfig
{
    /**
     * Get OAuth configuration for Auth0 (only provider)
     */
    public static function getProviders()
    {
        // Include config to access env() function
        require_once dirname(__DIR__) . '/Config/config.php';
        
        return [
            'auth0' => [
                'domain' => env('AUTH0_DOMAIN', ''),
                'client_id' => env('AUTH0_CLIENT_ID', ''),
                'client_secret' => env('AUTH0_CLIENT_SECRET', ''),
                'callback_url' => env('AUTH0_CALLBACK_URL', ''),
                'logout_url' => env('AUTH0_LOGOUT_URL', ''),
                'cookie_secret' => env('AUTH0_COOKIE_SECRET', ''),
                'scope' => 'openid email profile'
            ]
        ];
    }

    /**
     * Get configuration for a specific provider
     */
    public static function getProvider($provider)
    {
        $providers = self::getProviders();
        return $providers[$provider] ?? null;
    }

    /**
     * Get enabled providers (those with client_id configured)
     */
    public static function getEnabledProviders()
    {
        $providers = self::getProviders();
        $enabled = [];

        foreach ($providers as $key => $config) {
            // Check for valid credentials (not placeholder values)
            $hasValidCredentials = false;
            
            if ($key === 'supabase') {
                $hasValidCredentials = !empty($config['url']) && 
                    !str_contains($config['url'], 'your_supabase');
            } else {
                $hasValidCredentials = !empty($config['client_id']) && 
                    !str_contains($config['client_id'], 'your_') &&
                    !empty($config['client_secret']) && 
                    !str_contains($config['client_secret'], 'your_');
            }
            
            if ($hasValidCredentials) {
                $enabled[$key] = $config;
            }
        }

        return $enabled;
    }

    /**
     * Validate OAuth provider configuration
     */
    public static function validateProvider($provider)
    {
        $config = self::getProvider($provider);
        if (!$config) {
            throw new \Exception("OAuth provider '{$provider}' not configured");
        }

        // Check for placeholder credentials
        if (str_contains($config['client_id'], 'your_') || 
            str_contains($config['client_secret'], 'your_')) {
            throw new \Exception(
                "OAuth provider '{$provider}' is not properly configured. " .
                "Please set up your OAuth credentials in Google Cloud Console and " .
                "update the GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file."
            );
        }

        return true;
    }

    /**
     * Generate OAuth state parameter
     */
    public static function generateState()
    {
        $state = bin2hex(random_bytes(32));
        $_SESSION['oauth_state'] = $state;
        return $state;
    }

    /**
     * Verify OAuth state parameter
     */
    public static function verifyState($state)
    {
        return isset($_SESSION['oauth_state']) && 
               hash_equals($_SESSION['oauth_state'], $state);
    }

    /**
     * Clear OAuth state
     */
    public static function clearState()
    {
        unset($_SESSION['oauth_state']);
    }

    /**
     * Get JWT secret
     */
    public static function getJwtSecret()
    {
        return env('JWT_SECRET', 'default_jwt_secret_change_in_production');
    }

    /**
     * Get OAuth state secret
     */
    public static function getOAuthStateSecret()
    {
        return env('OAUTH_STATE_SECRET', 'default_oauth_state_secret_change_in_production');
    }
}