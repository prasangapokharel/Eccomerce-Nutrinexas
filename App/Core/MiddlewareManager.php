<?php

namespace App\Core;

class MiddlewareManager
{
    private static $middlewares = [];
    
    /**
     * Register middleware for specific routes
     */
    public static function register($route, $middleware)
    {
        if (!isset(self::$middlewares[$route])) {
            self::$middlewares[$route] = [];
        }
        self::$middlewares[$route][] = $middleware;
    }
    
    /**
     * Apply middlewares for a route
     */
    public static function apply($route, $request)
    {
        if (isset(self::$middlewares[$route])) {
            foreach (self::$middlewares[$route] as $middleware) {
                $middlewareClass = new $middleware();
                $request = $middlewareClass->handle($request, function($req) { return $req; });
            }
        }
        return $request;
    }
    
    /**
     * Register global middlewares
     */
    public static function registerGlobal($middleware)
    {
        self::$middlewares['*'][] = $middleware;
    }
    
    /**
     * Apply global middlewares
     */
    public static function applyGlobal($request)
    {
        if (isset(self::$middlewares['*'])) {
            foreach (self::$middlewares['*'] as $middleware) {
                $middlewareClass = new $middleware();
                $request = $middlewareClass->handle($request, function($req) { return $req; });
            }
        }
        return $request;
    }
}


