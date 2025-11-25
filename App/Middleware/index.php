<?php

namespace App\Middleware;

use App\Core\Session;

class Middleware
{
    /**
     * Protected routes that require authentication
     * Add routes here that need user to be logged in
     */
    private static $protectedRoutes = [
        // User routes
        'user/account',
        'user/profile',
        'user/orders',
        'user/orders/view',
        'user/orders/view/',
        'user/invite',
        'user/balance',
        'user/withdraw',
        'user/withdraw/request',
        'user/settings',
        
        // Cart and checkout
        // 'cart',
        // 'cart/add',
        // 'cart/remove',
        // 'cart/update',
        // 'cart/clear',
        // 'checkout',
        // 'checkout/process',
        // 'checkout/khalti',
        // 'checkout/khalti/verify',
        // 'checkout/success',
        // 'checkout/cancel',
        
        // // Wishlist
        // 'wishlist',
        // 'wishlist/add',
        // 'wishlist/remove',
        // 'wishlist/toggle',
        
        // Admin routes
        'admin',
        'admin/dashboard',
        'admin/orders',
        'admin/orders/view',
        'admin/orders/update-status',
        'admin/products',
        'admin/products/add',
        'admin/products/edit',
        'admin/products/delete',
        'admin/users',
        'admin/users/view',
        'admin/users/edit',
        'admin/users/delete',
        'admin/coupons',
        'admin/coupons/add',
        'admin/coupons/edit',
        'admin/coupons/delete',
        'admin/blog',
        'admin/blog/create',
        'admin/blog/edit',
        'admin/blog/delete',
        'admin/blog/bulkDelete',
        'admin/refer',
        'admin/refer/save-settings',
        'admin/withdrawals',
        'admin/withdrawals/approve',
        'admin/withdrawals/reject',
        'admin/settings',
        'admin/settings/update',
        'admin/settings/optimize-db',
        'admin/settings/backup-db',
        'admin/settings/download-backup',
        'admin/logs',
        
        // API routes that need authentication
        'api/user',
        'api/orders',
        'api/cart',
        'api/wishlist',
        'api/profile',
        'api/withdraw',
        'api/referrals',
    ];
    
    /**
     * Admin-only routes
     */
    private static $adminRoutes = [
        'admin',
        'admin/dashboard',
        'admin/orders',
        'admin/orders/view',
        'admin/orders/update-status',
        'admin/products',
        'admin/products/add',
        'admin/products/edit',
        'admin/products/delete',
        'admin/users',
        'admin/users/view',
        'admin/users/edit',
        'admin/users/delete',
        'admin/coupons',
        'admin/coupons/add',
        'admin/coupons/edit',
        'admin/coupons/delete',
        'admin/blog',
        'admin/blog/create',
        'admin/blog/edit',
        'admin/blog/delete',
        'admin/blog/bulkDelete',
        'admin/refer',
        'admin/refer/save-settings',
        'admin/withdrawals',
        'admin/withdrawals/approve',
        'admin/withdrawals/reject',
        'admin/settings',
        'admin/settings/update',
        'admin/settings/optimize-db',
        'admin/settings/backup-db',
        'admin/settings/download-backup',
        'admin/settings/export-db-xls',
        'admin/logs',
    ];
    
    /**
     * Check if route is protected
     */
    public static function isProtectedRoute($route)
    {
        // Remove leading slash if present
        $route = ltrim($route, '/');
        
        // Check exact match
        if (in_array($route, self::$protectedRoutes)) {
            return true;
        }
        
        // Check pattern matches (for dynamic routes like orders/view/123)
        foreach (self::$protectedRoutes as $protectedRoute) {
            if (self::matchesPattern($route, $protectedRoute)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if route requires admin access
     */
    public static function isAdminRoute($route)
    {
        $route = ltrim($route, '/');
        
        // Check exact match
        if (in_array($route, self::$adminRoutes)) {
            return true;
        }
        
        // Check pattern matches
        foreach (self::$adminRoutes as $adminRoute) {
            if (self::matchesPattern($route, $adminRoute)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated()
    {
        return Session::has('user_id') && !empty(Session::get('user_id'));
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userRole = Session::get('user_role', 'user');
        return $userRole === 'admin';
    }
    
    /**
     * Handle authentication check
     */
    public static function handleAuth($route)
    {
        // Check if route is protected
        if (!self::isProtectedRoute($route)) {
            return true; // Route is not protected, allow access
        }
        
        // Check if user is authenticated
        if (!self::isAuthenticated()) {
            self::redirectToLogin($route);
            return false;
        }
        
        // Check if route requires admin access
        if (self::isAdminRoute($route)) {
            if (!self::isAdmin()) {
                self::redirectToUnauthorized();
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Redirect to login page
     */
    private static function redirectToLogin($returnUrl = null)
    {
        $loginUrl = '/auth/login';
        
        if ($returnUrl) {
            $loginUrl .= '?return=' . urlencode($returnUrl);
        }
        
        header('Location: ' . $loginUrl);
        exit;
    }
    
    /**
     * Redirect to unauthorized page
     */
    private static function redirectToUnauthorized()
    {
        http_response_code(403);
        
        // You can create a custom 403 page or redirect to home
        header('Location: /?error=unauthorized');
        exit;
    }
    
    /**
     * Check if route matches pattern (supports wildcards)
     */
    private static function matchesPattern($route, $pattern)
    {
        // Convert pattern to regex
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        return preg_match($pattern, $route);
    }
    
    /**
     * Add a protected route
     */
    public static function addProtectedRoute($route)
    {
        if (!in_array($route, self::$protectedRoutes)) {
            self::$protectedRoutes[] = $route;
        }
    }
    
    /**
     * Add an admin route
     */
    public static function addAdminRoute($route)
    {
        if (!in_array($route, self::$adminRoutes)) {
            self::$adminRoutes[] = $route;
        }
    }
    
    /**
     * Remove a protected route
     */
    public static function removeProtectedRoute($route)
    {
        $key = array_search($route, self::$protectedRoutes);
        if ($key !== false) {
            unset(self::$protectedRoutes[$key]);
        }
    }
    
    /**
     * Get all protected routes
     */
    public static function getProtectedRoutes()
    {
        return self::$protectedRoutes;
    }
    
    /**
     * Get all admin routes
     */
    public static function getAdminRoutes()
    {
        return self::$adminRoutes;
    }
}
