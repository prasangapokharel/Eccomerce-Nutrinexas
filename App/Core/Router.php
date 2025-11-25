<?php
namespace App\Core;

/**
 * Router class
 */
class Router
{
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'HEAD' => []
    ];

    /**
     * Register a GET route
     */
    public function get($uri, $controller)
    {
        $this->routes['GET'][$uri] = $controller;
    }

    /**
     * Register a POST route
     */
    public function post($uri, $controller)
    {
        $this->routes['POST'][$uri] = $controller;
    }

    /**
     * Register a PUT route
     */
    public function put($uri, $controller)
    {
        $this->routes['PUT'][$uri] = $controller;
    }

    /**
     * Register a DELETE route
     */
    public function delete($uri, $controller)
    {
        $this->routes['DELETE'][$uri] = $controller;
    }

    /**
     * Register a HEAD route
     */
    public function head($uri, $controller)
    {
        $this->routes['HEAD'][$uri] = $controller;
    }

    /**
     * Resolve the current route
     */
    public function resolve()
    {
        $uri = $this->getUri();
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Handle PUT and DELETE requests from POST with _method parameter
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        // Handle HEAD requests by treating them as GET requests
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        
        // Check if method is supported
        if (!isset($this->routes[$method])) {
            return null; // Method not supported
        }
        
        // Check for exact match
        if (isset($this->routes[$method][$uri])) {
            return $this->parseController($this->routes[$method][$uri]);
        }
        
        // Check for routes with parameters
        foreach ($this->routes[$method] as $route => $controller) {
            if (strpos($route, '{') !== false) {
                $pattern = $this->convertRouteToRegex($route);
                
                if (preg_match($pattern, $uri, $matches)) {
                    array_shift($matches); // Remove the full match
                    
                    list($controller, $method) = $this->parseController($controller);
                    
                    return [$controller, $method, $matches];
                }
            }
        }
        
        return null;
    }

    /**
     * Convert route with parameters to regex pattern
     */
    private function convertRouteToRegex($route)
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        return '#^' . $pattern . '$#';
    }

    /**
     * Parse controller string (Controller@method)
     */
    private function parseController($controller)
    {
        $segments = explode('@', $controller);
        
        return [$segments[0], $segments[1], []];
    }

    /**
     * Get the current URI
     */
    private function getUri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        
        // Remove base path
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && $basePath !== '\\') {
            $uri = substr($uri, strlen($basePath));
        }
        
        // Remove leading and trailing slashes
        return trim($uri, '/');
    }
    
    /**
     * Get current route information
     */
    public function getCurrentRoute()
    {
        return [
            'uri' => $this->getUri(),
            'method' => $_SERVER['REQUEST_METHOD'],
            'full_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? ''
        ];
    }
}
