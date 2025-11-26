<?php

namespace App\Controllers;

use App\Core\Controller;

class ErrorController extends Controller
{
    /**
     * Handle 404 Not Found errors
     */
    public function notFound()
    {
        http_response_code(404);
        $this->view('errors/404', [
            'title' => 'Page Not Found - 404',
            'error_code' => 404,
            'error_message' => 'The page you are looking for could not be found.',
            'suggestions' => [
                'Check the URL for typos',
                'Go back to the homepage',
                'Use the search function',
                'Browse our products'
            ]
        ]);
    }

    /**
     * Handle 403 Forbidden errors
     */
    public function forbidden()
    {
        http_response_code(403);
        $this->view('errors/403', [
            'title' => 'Access Forbidden - 403',
            'error_code' => 403,
            'error_message' => 'You do not have permission to access this resource.',
            'suggestions' => [
                'Check if you are logged in',
                'Contact administrator for access',
                'Go back to the homepage',
                'Try logging in again'
            ]
        ]);
    }

    /**
     * Handle 500 Internal Server errors
     */
    public function serverError()
    {
        http_response_code(500);
        $this->view('errors/500', [
            'title' => 'Server Error - 500',
            'error_code' => 500,
            'error_message' => 'Something went wrong on our end. We are working to fix it.',
            'suggestions' => [
                'Try refreshing the page',
                'Go back to the homepage',
                'Contact support if the problem persists',
                'Check back later'
            ]
        ]);
    }

    /**
     * Handle maintenance mode
     */
    public function maintenance()
    {
        http_response_code(503);
        $maintenanceView = dirname(dirname(__DIR__)) . '/App/views/errors/maintenance.php';
        if (file_exists($maintenanceView)) {
            include $maintenanceView;
        } else {
            echo '<h1>Maintenance Mode</h1><p>The site is currently under maintenance. Please check back later.</p>';
        }
        exit;
    }

    /**
     * Generic error handler
     */
    public function error($code = 500, $message = 'An error occurred')
    {
        http_response_code($code);
        
        $errorData = [
            'title' => "Error {$code}",
            'error_code' => $code,
            'error_message' => $message,
            'suggestions' => [
                'Try refreshing the page',
                'Go back to the homepage',
                'Contact support if the problem persists'
            ]
        ];

        switch ($code) {
            case 404:
                $this->view('errors/404', $errorData);
                break;
            case 403:
                $this->view('errors/403', $errorData);
                break;
            case 500:
            default:
                $this->view('errors/500', $errorData);
                break;
        }
    }
}