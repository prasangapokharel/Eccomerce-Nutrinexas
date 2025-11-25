<?php
namespace App\Core;

/**
 * Lightweight Request helper
 */
class Request
{
    /**
     * Get HTTP method
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get a value from $_GET
     */
    public static function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get a value from $_POST
     */
    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Parse JSON body into array
     */
    public static function json(): array
    {
        $input = file_get_contents('php://input');
        if (!$input) {
            return [];
        }
        $decoded = json_decode($input, true);
        return is_array($decoded) ? $decoded : [];
    }
}