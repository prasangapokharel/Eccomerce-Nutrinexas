<?php

namespace App\Controllers\Error;

use App\Core\Controller;

class ErrorController extends Controller
{
    /**
     * Handle 404 Not Found errors
     */
    public function notFound()
    {
        http_response_code(404);
        $error_message = 'The page you are looking for could not be found.';
        $viewPath = __DIR__ . '/../../views/errors/404.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404</title></head><body style="font-family:sans-serif;text-align:center;padding:40px;"><h1>404</h1><p>Page Not Found</p></body></html>';
        }
        exit;
    }

    /**
     * Handle 403 Forbidden errors
     */
    public function forbidden()
    {
        http_response_code(403);
        $error_message = 'You do not have permission to access this resource.';
        $viewPath = __DIR__ . '/../../views/errors/403.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>403</title></head><body style="font-family:sans-serif;text-align:center;padding:40px;"><h1>403</h1><p>Access Forbidden</p></body></html>';
        }
        exit;
    }

    /**
     * Handle 500 Internal Server errors
     */
    public function serverError()
    {
        http_response_code(500);
        $error_message = 'Something went wrong on our end. We are working to fix it.';
        $viewPath = __DIR__ . '/../../views/errors/500.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>500</title></head><body style="font-family:sans-serif;text-align:center;padding:40px;"><h1>500</h1><p>Internal Server Error</p></body></html>';
        }
        exit;
    }

    /**
     * Handle maintenance mode
     */
    public function maintenance()
    {
        http_response_code(503);
        $maintenanceView = __DIR__ . '/../../views/errors/maintenance.php';
        if (file_exists($maintenanceView)) {
            include $maintenanceView;
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Maintenance</title></head><body style="font-family:sans-serif;text-align:center;padding:40px;"><h1>Under Maintenance</h1><p>We\'re currently performing scheduled maintenance. Please check back later.</p></body></html>';
        }
        exit;
    }

    /**
     * Generic error handler
     */
    public function error($code = 500, $message = 'An error occurred')
    {
        http_response_code($code);
        $error_message = $message;
        
        switch ($code) {
            case 404:
                $viewPath = __DIR__ . '/../../views/errors/404.php';
                break;
            case 403:
                $viewPath = __DIR__ . '/../../views/errors/403.php';
                break;
            case 500:
            default:
                $viewPath = __DIR__ . '/../../views/errors/500.php';
                break;
        }
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>{$code}</title></head><body style=\"font-family:sans-serif;text-align:center;padding:40px;\"><h1>{$code}</h1><p>" . htmlspecialchars($message) . "</p></body></html>";
        }
        exit;
    }
}