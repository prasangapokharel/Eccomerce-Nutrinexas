<?php
namespace App\Core;

/**
 * Lightweight Response helper
 */
class Response
{
    /**
     * Send JSON response
     */
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Redirect helper
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
    }
}