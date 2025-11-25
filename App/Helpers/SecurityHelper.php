<?php

namespace App\Helpers;

use App\Core\Session;

/**
 * Security Helper - Centralized security functions
 */
class SecurityHelper
{
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            Session::start();
        }
        
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf_token');
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRF(?string $token = null): bool
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            Session::start();
        }
        
        $token = $token ?? $_POST['_csrf_token'] ?? $_GET['_csrf_token'] ?? '';
        $sessionToken = Session::get('_csrf_token', '');
        
        if (empty($token) || empty($sessionToken)) {
            error_log('CSRF validation failed: token=' . (!empty($token) ? 'present' : 'missing') . ', session=' . (!empty($sessionToken) ? 'present' : 'missing'));
            return false;
        }
        
        $isValid = hash_equals($sessionToken, $token);
        if (!$isValid) {
            error_log('CSRF token mismatch: provided=' . substr($token, 0, 10) . '..., session=' . substr($sessionToken, 0, 10) . '...');
        }
        return $isValid;
    }

    /**
     * Sanitize string input (XSS protection)
     */
    public static function sanitizeString(string $input, bool $allowHtml = false): string
    {
        if ($allowHtml) {
            // Allow safe HTML but strip dangerous tags
            $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>';
            return strip_tags($input, $allowedTags);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize array of strings
     */
    public static function sanitizeArray(array $data, bool $allowHtml = false): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $allowHtml);
            } else {
                $sanitized[$key] = self::sanitizeString($value, $allowHtml);
            }
        }
        return $sanitized;
    }

    /**
     * Validate email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number (Nepal format)
     */
    public static function validatePhone(string $phone): bool
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return preg_match('/^(\+977)?9[6-9]\d{8}$/', $phone) === 1;
    }

    /**
     * Sanitize phone number
     */
    public static function sanitizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+\-\s()]/', '', trim($phone));
    }

    /**
     * Validate integer
     */
    public static function validateInt($value, int $min = null, int $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $int = (int)$value;
        
        if ($min !== null && $int < $min) {
            return false;
        }
        
        if ($max !== null && $int > $max) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate float
     */
    public static function validateFloat($value, float $min = null, float $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $float = (float)$value;
        
        if ($min !== null && $float < $min) {
            return false;
        }
        
        if ($max !== null && $float > $max) {
            return false;
        }
        
        return true;
    }

    /**
     * Rate limiting check
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $timeWindow = 3600): bool
    {
        $sessionKey = 'rate_limit_' . $key;
        $attempts = Session::get($sessionKey, []);
        $now = time();
        
        // Remove old attempts outside time window
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($attempts) >= $maxAttempts) {
            return false;
        }
        
        $attempts[] = $now;
        Session::set($sessionKey, $attempts);
        
        return true;
    }

    /**
     * Get remaining rate limit time
     */
    public static function getRateLimitRemaining(string $key, int $timeWindow = 3600): int
    {
        $sessionKey = 'rate_limit_' . $key;
        $attempts = Session::get($sessionKey, []);
        
        if (empty($attempts)) {
            return 0;
        }
        
        $oldest = min($attempts);
        $remaining = $timeWindow - (time() - $oldest);
        
        return max(0, $remaining);
    }

    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash password securely
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Escape output for HTML
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Clean input data recursively
     */
    public static function cleanInput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'cleanInput'], $data);
        }
        
        if (is_string($data)) {
            return trim(strip_tags($data));
        }
        
        return $data;
    }
}

