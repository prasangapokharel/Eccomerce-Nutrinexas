<?php
namespace App\Core;

use Exception;
use App\Core\Router;

/**
 * Main application class
 */
class App
{
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    protected $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
        $this->resolveRoute();
    }

    private function registerRoutes()
    {
        // Home routes
        $this->router->get('', 'HomeController@index');
        $this->router->get('/', 'HomeController@index');
        $this->router->get('home', 'HomeController@index');
        // Error routes (to handle redirects from global handlers)
        $this->router->get('500', 'ErrorController@serverError');
        $this->router->get('404', 'ErrorController@notFound');
        $this->router->get('about', 'HomeController@about');
        $this->router->get('pages/privacy', 'HomeController@privacy');
        $this->router->get('privacy', 'HomeController@privacy');
        $this->router->get('pages/terms', 'HomeController@terms');
        $this->router->get('pages/faq', 'HomeController@faq');
        $this->router->get('pages/returnPolicy', 'HomeController@returnPolicy');
        $this->router->get('pages/shipping', 'HomeController@shipping');
        $this->router->get('pages/contact', 'HomeController@contact');
        $this->router->get('contact', 'HomeController@contact');
        
        // Auth routes
        $this->router->get('auth/login', 'AuthController@login');
        $this->router->post('auth/processLogin', 'AuthController@processLogin');
        $this->router->get('auth/register', 'AuthController@register');
        $this->router->post('auth/processRegister', 'AuthController@processRegister');
        $this->router->get('auth/register/auth0', 'AuthController@registerWithAuth0');
        $this->router->get('auth/logout', 'AuthController@logout');
        $this->router->get('auth/forgotPassword', 'AuthController@forgotPassword');
        $this->router->post('auth/forgotPassword', 'AuthController@forgotPassword');
        $this->router->get('auth/resetPassword/{token}', 'AuthController@resetPassword');
        $this->router->post('auth/resetPassword/{token}', 'AuthController@resetPassword');
        
        // OAuth routes
        $this->router->get('oauth/login', 'OAuthController@login');
        $this->router->get('oauth/callback', 'OAuthController@callback');
        $this->router->get('oauth/logout', 'OAuthController@logout');
        $this->router->get('oauth/link', 'OAuthController@link');
        $this->router->get('oauth/link/callback', 'OAuthController@linkCallback');
        $this->router->post('oauth/unlink', 'OAuthController@unlink');
        $this->router->get('oauth/providers', 'OAuthController@getProviders');
        $this->router->get('oauth/status', 'OAuthController@getStatus');
        
        // Auth0 routes
        $this->router->get('auth0/login', 'Auth0Controller@login');
        $this->router->get('auth0/callback', 'Auth0Controller@callback');
        $this->router->get('auth0/logout', 'Auth0Controller@logout');
        $this->router->get('auth0/profile', 'Auth0Controller@profile');
        $this->router->get('auth0/link', 'Auth0Controller@link');
        $this->router->get('auth0/link/callback', 'Auth0Controller@linkCallback');
        $this->router->post('auth0/unlink', 'Auth0Controller@unlink');
        $this->router->get('auth0/status', 'Auth0Controller@status');
        $this->router->get('auth0/providers', 'Auth0Controller@providers');
        
        // Guide routes
        $this->router->get('guide', 'GuideController@index');

        // Maintenance route
        $this->router->get('maintenance', 'ErrorController@maintenance');

        // Ads routes
        $this->router->get('ads', 'AdsController@index');
        
        // Product routes
        $this->router->get('products', 'ProductController@index');
        $this->router->get('products/filter', 'ProductController@filter');
        $this->router->get('products/view/{slug}', 'ProductController@viewProduct');
        $this->router->get('products/category/{category}/{subtype}', 'ProductController@category');
        $this->router->get('products/category/{category}', 'ProductController@category');
        $this->router->get('products/search', 'ProductController@search');
        $this->router->get('products/liveSearch', 'ProductController@liveSearch');
        
        // API routes
        $this->router->get('api/products/infinite', 'ProductController@infiniteScroll');
        $this->router->get('products/infinite', 'ProductController@infiniteScroll');
        $this->router->get('products/view/{slug}', 'ProductController@viewProduct');
        $this->router->get('products/category/{category}/{subtype}', 'ProductController@category');
        $this->router->get('products/category/{category}', 'ProductController@category');
        $this->router->get('products/search', 'ProductController@search');
        $this->router->get('products/liveSearch', 'ProductController@liveSearch');
        
        // Admin Slider routes - Fixed and organized
        $this->router->get('admin/slider', 'SliderController@index');
        $this->router->get('admin/slider/create', 'SliderController@create');
        $this->router->post('admin/slider/create', 'SliderController@create');
        $this->router->get('admin/slider/edit/{id}', 'SliderController@edit');
        $this->router->post('admin/slider/edit/{id}', 'SliderController@edit');
        $this->router->post('admin/slider/delete/{id}', 'SliderController@delete');
        $this->router->post('admin/slider/toggle/{id}', 'SliderController@toggleStatus');
        
        // Admin Banner routes
        $this->router->get('admin/banners', 'BannerController@adminIndex');
        $this->router->get('admin/banners/create', 'BannerController@create');
        $this->router->post('admin/banners/create', 'BannerController@create');
        $this->router->get('admin/banners/edit/{id}', 'BannerController@edit');
        $this->router->post('admin/banners/edit/{id}', 'BannerController@edit');
        $this->router->post('admin/banners/delete/{id}', 'BannerController@delete');
        $this->router->post('admin/banners/bulk-delete', 'BannerController@bulkDelete');
        $this->router->post('admin/banners/toggle/{id}', 'BannerController@toggleStatus');
        
        // Banner click tracking
        $this->router->get('banner/click/{id}', 'BannerController@trackClick');
        $this->router->post('banner/view/{id}', 'BannerController@trackView');
        
        // Admin Cancel routes
        $this->router->get('admin/cancels', 'CancelController@adminIndex');
        $this->router->get('admin/cancels/view/{id}', 'CancelController@viewCancel');
        $this->router->post('admin/cancels/update/{id}', 'CancelController@updateStatus');
        
        // Seller routes
        $this->router->get('seller/login', 'Seller\Auth@login');
        $this->router->post('seller/login', 'Seller\Auth@login');
        $this->router->get('seller/register', 'Seller\Auth@register');
        $this->router->post('seller/register', 'Seller\Auth@register');
        $this->router->get('seller/logout', 'Seller\Auth@logout');
        $this->router->get('seller/dashboard', 'Seller\Dashboard@index');
        $this->router->get('seller/products', 'Seller\Products@index');
        $this->router->get('seller/products/create', 'Seller\Products@create');
        $this->router->post('seller/products/create', 'Seller\Products@create');
        $this->router->get('seller/products/edit/{id}', 'Seller\Products@edit');
        $this->router->post('seller/products/edit/{id}', 'Seller\Products@edit');
        $this->router->post('seller/products/delete/{id}', 'Seller\Products@delete');
        $this->router->post('seller/products/toggle-status/{id}', 'Seller\Products@toggleStatus');
        $this->router->get('seller/products/bulk-upload', 'Seller\Products@bulkUpload');
        $this->router->post('seller/products/bulk-upload', 'Seller\Products@bulkUpload');
        $this->router->get('seller/products/download-csv-template', 'Seller\Products@downloadCsvTemplate');
        $this->router->get('seller/orders', 'Seller\Orders@index');
        $this->router->get('seller/orders/detail/{id}', 'Seller\Orders@detail');
        $this->router->post('seller/orders/update-status/{id}', 'Seller\Orders@updateStatus');
        $this->router->post('seller/orders/accept/{id}', 'Seller\Orders@accept');
        $this->router->post('seller/orders/reject/{id}', 'Seller\Orders@reject');
        $this->router->get('seller/orders/print-invoice/{id}', 'Seller\Orders@printInvoice');
        $this->router->get('seller/orders/print-shipping-label/{id}', 'Seller\Orders@printShippingLabel');
        $this->router->get('seller/orders/bulk-print-labels', 'Seller\Orders@bulkPrintLabels');
        $this->router->get('seller/stock-movement', 'Seller\StockMovement@index');
        $this->router->get('seller/inventory', 'Seller\Inventory@index');
        $this->router->post('seller/inventory/update-stock/{id}', 'Seller\Inventory@updateStock');
        $this->router->get('seller/customers', 'Seller\Customers@index');
        $this->router->get('seller/customers/detail/{id}', 'Seller\Customers@detail');
        $this->router->get('seller/analytics', 'Seller\Analytics@index');
        $this->router->get('seller/reports', 'Seller\Reports@index');
        $this->router->get('seller/marketing', 'Seller\Marketing@index');
        $this->router->get('seller/marketing/create-coupon', 'Seller\Marketing@createCoupon');
        $this->router->post('seller/marketing/create-coupon', 'Seller\Marketing@createCoupon');
        $this->router->get('seller/wallet', 'Seller\Wallet@index');
        $this->router->get('seller/wallet/transactions', 'Seller\Wallet@transactions');
        $this->router->get('seller/withdraw-requests', 'Seller\WithdrawRequests@index');
        $this->router->get('seller/withdraw-requests/create', 'Seller\WithdrawRequests@create');
        $this->router->post('seller/withdraw-requests/create', 'Seller\WithdrawRequests@create');
        $this->router->get('seller/withdraw-requests/detail/{id}', 'Seller\WithdrawRequests@detail');
        $this->router->get('seller/support', 'Seller\Support@index');
        $this->router->get('seller/support/create', 'Seller\Support@create');
        $this->router->post('seller/support/create', 'Seller\Support@create');
        $this->router->get('seller/support/detail/{id}', 'Seller\Support@detail');
        $this->router->post('seller/support/detail/{id}', 'Seller\Support@detail');
        $this->router->get('seller/notifications', 'Seller\Notifications@index');
        $this->router->post('seller/notifications/mark-read/{id}', 'Seller\Notifications@markRead');
        $this->router->post('seller/notifications/mark-all-read', 'Seller\Notifications@markAllRead');
        $this->router->get('seller/reviews', 'Seller\Reviews@index');
        $this->router->post('seller/reviews/reply/{id}', 'Seller\Reviews@reply');
        $this->router->get('seller/settings', 'Seller\Settings@index');
        $this->router->post('seller/settings', 'Seller\Settings@index');
        $this->router->get('seller/cancellations', 'Seller\Cancellations@index');
        $this->router->get('seller/cancellations/detail/{id}', 'Seller\Cancellations@detail');
        $this->router->post('seller/cancellations/update/{id}', 'Seller\Cancellations@updateStatus');
        $this->router->get('seller/profile', 'Seller\Profile@index');
        $this->router->post('seller/profile', 'Seller\Profile@index');
        $this->router->get('seller/ads', 'Seller\Ads@index');
        $this->router->get('seller/ads/create', 'Seller\Ads@create');
        $this->router->post('seller/ads/create', 'Seller\Ads@store');
        $this->router->get('seller/ads/get-costs', 'Seller\Ads@getCosts');
        $this->router->get('seller/ads/show/{id}', 'Seller\Ads@show');
        $this->router->post('seller/ads/update-status/{id}', 'Seller\Ads@updateStatus');
        $this->router->post('seller/ads/delete/{id}', 'Seller\Ads@delete');
        
        // Public sellers listing
        $this->router->get('sellers', 'SellerPublicController@index');
        
        // Public seller profile (must be last to avoid route conflicts)
        $this->router->get('seller/{companyName}', 'SellerPublicController@profile');
        
        $this->router->get('seller/bank-account', 'Seller\BankAccount@index');
        $this->router->post('seller/bank-account', 'Seller\BankAccount@index');
        $this->router->post('seller/bank-account/delete/{id}', 'Seller\BankAccount@delete');
        
        // Category routes
        $this->router->get('admin/categories', 'CategoryController@index');
        $this->router->get('categories', 'CategoryController@publicIndex');
        $this->router->get('category/{slug}', 'CategoryController@show');
        
        // Review routes
        $this->router->post('products/submitReview', 'ReviewController@submit');
        $this->router->post('reviews/submit', 'ReviewController@submit');
        $this->router->post('reviews/submitAjax', 'ReviewController@submitAjax');
        $this->router->post('reviews/delete', 'ReviewController@delete');
        $this->router->get('reviews/edit/{id}', 'ProductController@editReview');
        $this->router->post('reviews/update/{id}', 'ProductController@updateReview');
        $this->router->post('reviews/delete/{id}', 'ProductController@deleteReview');
        
        // Cart routes
        $this->router->get('cart', 'CartController@index');
        $this->router->post('cart/add', 'CartController@add');
        $this->router->post('cart/update', 'CartController@update');
        $this->router->get('cart/remove/{id}', 'CartController@remove');
        $this->router->post('cart/remove', 'CartController@remove');
        $this->router->get('cart/clear', 'CartController@clear');
        $this->router->post('cart/clear', 'CartController@clear');
        $this->router->get('cart/count', 'CartController@count');
        
        // Wishlist routes
        $this->router->get('wishlist', 'WishlistController@index');
        $this->router->post('wishlist/add', 'WishlistController@add');
        $this->router->post('wishlist/remove', 'WishlistController@remove');
        $this->router->get('wishlist/remove/{id}', 'WishlistController@remove');
        $this->router->post('wishlist/moveToCart', 'WishlistController@moveToCart');
        $this->router->get('wishlist/moveToCart/{id}', 'WishlistController@moveToCart');
        
        // Checkout routes
        $this->router->get('checkout', 'CheckoutController@index');
        $this->router->post('checkout/process', 'CheckoutController@process');
        $this->router->get('checkout/success/{order_id}', 'CheckoutController@success');
        $this->router->post('checkout/validateCoupon', 'CheckoutController@validateCoupon');

        // User Orders routes
        $this->router->get('orders', 'OrderController@index');
        $this->router->get('orders/view/{id}', 'OrderController@viewOrder');
        $this->router->get('orders/track', 'OrderController@track');
        $this->router->get('orders/track-result', 'OrderController@trackResult');
        $this->router->post('orders/cancel/{id}', 'OrderController@cancel');
        $this->router->get('orders/reorder/{id}', 'OrderController@reorder');
        $this->router->post('checkout/removeCoupon', 'CheckoutController@removeCoupon');
        
        // Khalti payment routes
        $this->router->get('checkout/khalti/{order_id}', 'CheckoutController@khalti');
        $this->router->post('checkout/initiateKhalti/{order_id}', 'CheckoutController@initiateKhalti');
        $this->router->post('checkout/verifyKhalti', 'CheckoutController@verifyKhalti');
        // Use dynamic return route with order id and correct controller
        $this->router->get('payment/khalti/success/{order_id}', 'PaymentController@khaltiReturn');
        // Omnipay routes for multi-gateway support
        $this->router->post('checkout/initiateOmnipay/{slug}/{order_id}', 'PaymentController@initiateOmnipay');
        $this->router->get('payment/omnipay/return/{slug}/{order_id}', 'PaymentController@completeOmnipay');
        $this->router->get('payment/omnipay/cancel/{slug}/{order_id}', 'PaymentController@cancelOmnipay');
        $this->router->post('payment/omnipay/webhook/{slug}', 'PaymentController@webhookOmnipay');
        
        // Order routes
        $this->router->get('orders', 'OrderController@index');
        $this->router->get('orders/view/{id}', 'OrderController@viewOrder');
        $this->router->get('orders/success/{id}', 'OrderController@success');
        $this->router->get('orders/track', 'OrderController@track');
        $this->router->post('orders/track', 'OrderController@track');
        $this->router->get('orders/cancel/{id}', 'OrderController@cancel');
        $this->router->post('orders/cancel/{id}', 'OrderController@cancel');
        $this->router->post('orders/updateStatus/{id}', 'OrderController@updateStatus');
        
        // User routes
        $this->router->get('user/account', 'UserController@account');
        $this->router->get('user/invite', 'UserController@invite');
        $this->router->get('user/balance', 'UserController@balance');
        
        // Affiliate routes
        $this->router->get('affiliate', 'AffiliateController@products');
        $this->router->get('affiliate/products', 'AffiliateController@products');
        $this->router->get('user/profile', 'UserController@profile');
        $this->router->post('user/updateProfile', 'UserController@updateProfile');
        $this->router->get('user/api-keys', 'UserController@apiKeys');
        $this->router->get('user/addresses', 'UserController@addresses');
        $this->router->get('user/address', 'UserController@address');
        $this->router->post('user/address', 'UserController@address');
        $this->router->get('user/address/{id}', 'UserController@address');
        $this->router->post('user/address/{id}', 'UserController@address');
        $this->router->get('user/deleteAddress/{id}', 'UserController@deleteAddress');
        $this->router->get('user/setDefaultAddress/{id}', 'UserController@setDefaultAddress');
        $this->router->get('user/balance', 'UserController@balance');
        $this->router->get('user/invite', 'UserController@invite');
        $this->router->get('user/withdraw', 'UserController@withdraw');
        $this->router->post('user/requestWithdrawal', 'UserController@requestWithdrawal');
        $this->router->get('user/notifications', 'UserController@notifications');
        $this->router->get('user/transactions', 'UserController@transactions');
        $this->router->get('user/payments', 'UserController@payments');
        $this->router->get('user/settings', 'UserController@settings');
        
        // Profile image route
        $this->router->get('profileimage/{filename}', 'UserController@serveProfileImage');
        
        // Review image route
        $this->router->get('review/{filename}', 'ReviewController@serveReviewImage');
        
        // Payment Gateway routes
        $this->router->get('admin/payment', 'GatewayController@index');
        $this->router->get('admin/payment/manual', 'GatewayController@manual');
        $this->router->get('admin/payment/merchant', 'GatewayController@merchant');
        $this->router->get('admin/payment/create', 'GatewayController@create');
        $this->router->post('admin/payment/create', 'GatewayController@create');
        $this->router->get('admin/payment/edit/{id}', 'GatewayController@edit');
        $this->router->post('admin/payment/edit/{id}', 'GatewayController@edit');
        $this->router->get('admin/payment/toggle/{id}', 'GatewayController@toggleStatus');
        $this->router->post('admin/payment/toggle/{id}', 'GatewayController@toggleStatus');
        $this->router->post('admin/payment/toggleStatus/{id}', 'GatewayController@toggleStatus');
        $this->router->post('admin/payment/toggleTestMode/{id}', 'GatewayController@toggleTestMode');
        $this->router->get('admin/payment/delete/{id}', 'GatewayController@delete');
        $this->router->post('admin/payment/delete/{id}', 'GatewayController@delete');
        $this->router->get('gateway/active', 'GatewayController@getActiveGateways');
        
        // Admin routes
        $this->router->get('admin', 'AdminController@index');
        $this->router->get('admin/products', 'AdminController@products');
        $this->router->post('admin/products/updateStock', 'AdminController@updateStock');
        $this->router->get('admin/addProduct', 'AdminController@addProduct');
        $this->router->post('admin/addProduct', 'AdminController@addProduct');
        $this->router->get('admin/editProduct/{id}', 'AdminController@editProduct');
        $this->router->post('admin/editProduct/{id}', 'AdminController@editProduct');
        $this->router->post('admin/updateProduct/{id}', 'AdminController@updateProduct');
        $this->router->post('admin/deleteProduct/{id}', 'AdminController@deleteProduct');
        $this->router->get('admin/orders', 'AdminController@orders');
        // Admin: Create Order page
        $this->router->get('admin/orders/create', 'AdminController@createOrder');
        $this->router->post('admin/orders/create', 'AdminController@storeOrder');
        // Alias: some views post to /admin/orders/store
        $this->router->post('admin/orders/store', 'AdminController@storeOrder');
        $this->router->post('admin/validateOrderCoupon', 'AdminController@validateOrderCoupon');
        $this->router->get('admin/viewOrder/{id}', 'AdminController@viewOrder');
        $this->router->post('admin/updateOrderStatus/{id}', 'AdminController@updateOrderStatus');
        $this->router->post('admin/updateOrderPaymentStatus/{id}', 'AdminController@updateOrderPaymentStatus');
        $this->router->post('admin/orders/assignCurior', 'AdminCuriorController@assignCurior');
        $this->router->get('admin/users', 'AdminController@users');
        $this->router->get('admin/viewUser/{id}', 'AdminController@viewUser');
        $this->router->post('admin/updateUserRole/{id}', 'AdminController@updateUserRole');
        $this->router->post('admin/updateUserStatus/{id}', 'AdminController@updateUserStatus');
        $this->router->post('admin/updateSponsorStatus', 'AdminController@updateSponsorStatus');
        $this->router->post('admin/updateReferralCode', 'AdminController@updateReferralCode');
        // Admin Staff and Curior routes (added)
        $this->router->get('admin/staff', 'AdminStaffController@index');
        $this->router->get('admin/staff/create', 'AdminStaffController@create');
        $this->router->post('admin/staff/create', 'AdminStaffController@create');
        $this->router->get('admin/curior', 'AdminCuriorController@index');
        $this->router->get('admin/curior/create', 'AdminCuriorController@create');
        $this->router->post('admin/curior/create', 'AdminCuriorController@create');
        $this->router->get('admin/curior/edit/{id}', 'AdminCuriorController@edit');
        $this->router->post('admin/curior/edit/{id}', 'AdminCuriorController@edit');
        $this->router->get('admin/curior/delete/{id}', 'AdminCuriorController@delete');
        $this->router->post('admin/curior/delete/{id}', 'AdminCuriorController@delete');
        $this->router->post('admin/curior/toggleStatus/{id}', 'AdminCuriorController@toggleStatus');
        $this->router->post('admin/curior/reset-password/{id}', 'AdminCuriorController@sendResetLink');

        // Admin Inventory routes
        $this->router->get('admin/inventory', 'InventoryController@index');
        // Use singular path for supplier management as expected by views and redirects
        $this->router->get('admin/inventory/supplier', 'InventoryController@suppliers');
        $this->router->get('admin/inventory/products', 'InventoryController@products');
        $this->router->post('admin/inventory/scanBarcode', 'InventoryController@scanBarcode');
        // Optional extended inventory sections
        $this->router->get('admin/inventory/purchases', 'InventoryController@purchases');
        $this->router->get('admin/inventory/payments', 'InventoryController@payments');

        // Admin Delivery Charges Management routes
        $this->router->get('admin/delivery', 'AdminDeliveryController@index');
        $this->router->get('admin/delivery/create', 'AdminDeliveryController@create');
        $this->router->post('admin/delivery/create', 'AdminDeliveryController@create');
        $this->router->get('admin/delivery/edit/{id}', 'AdminDeliveryController@edit');
        $this->router->post('admin/delivery/edit/{id}', 'AdminDeliveryController@edit');
        $this->router->get('admin/delivery/delete/{id}', 'AdminDeliveryController@delete');
        $this->router->post('admin/delivery/delete/{id}', 'AdminDeliveryController@delete');
        // AJAX helpers
        $this->router->get('admin/delivery/charges', 'AdminDeliveryController@getCharges');
        $this->router->post('admin/delivery/quickAdd', 'AdminDeliveryController@quickAdd');
        $this->router->post('admin/delivery/toggleFree', 'AdminDeliveryController@toggleFreeDelivery');
        $this->router->post('admin/delivery/setDefaultFee', 'AdminDeliveryController@setDefaultFee');

        // Public Curior routes - Module based
        $this->router->get('curior', 'Curior\Auth@login');
        $this->router->get('curior/login', 'Curior\Auth@login');
        $this->router->post('curior/login', 'Curior\Auth@login');
        $this->router->get('curior/forgot-password', 'Curior\Auth@forgotPassword');
        $this->router->post('curior/forgot-password', 'Curior\Auth@forgotPassword');
        $this->router->get('curior/reset-password/{token}', 'Curior\Auth@resetPassword');
        $this->router->post('curior/reset-password/{token}', 'Curior\Auth@resetPassword');
        $this->router->get('curior/logout', 'Curior\Auth@logout');
        
        // Curior Dashboard routes
        $this->router->get('curior/dashboard', 'Curior\Dashboard@index');
        $this->router->get('curior/dashboard/stats', 'Curior\Dashboard@getStats');
        
        // Curior Order routes
        $this->router->get('curior/order/view/{id}', 'Curior\Order@viewOrderDetails');
        $this->router->get('curior/orders', 'Curior\Orders@index');
        $this->router->post('curior/order/pickup', 'Curior\Order@confirmPickup');
        $this->router->post('curior/order/transit', 'Curior\Order@updateTransit');
        $this->router->post('curior/order/attempt', 'Curior\Order@attemptDelivery');
        $this->router->post('curior/order/deliver', 'Curior\Order@confirmDelivery');
        $this->router->post('curior/order/cod', 'Curior\Order@handleCODCollection');
        $this->router->post('curior/order/location', 'Curior\Order@updateLocation');
        $this->router->post('curior/order/return/pickup', 'Curior\Order@acceptReturn');
        $this->router->post('curior/order/return/transit', 'Curior\Order@updateReturnTransit');
        $this->router->post('curior/order/return/complete', 'Curior\Order@completeReturn');
        
        // Curior Pickup routes
        $this->router->get('curior/pickup', 'Curior\Pickup@index');
        $this->router->post('curior/pickup/mark-picked', 'Curior\Pickup@markPicked');
        
        // Curior Delivery routes
        $this->router->get('curior/delivery', 'Curior\Delivery@index');
        $this->router->post('curior/delivery/out-for-delivery', 'Curior\Delivery@outForDelivery');
        
        // Curior Returns routes
        $this->router->get('curior/returns', 'Curior\Returns@index');
        
        // Curior Location routes
        $this->router->post('curior/location/update', 'Curior\Location@update');
        $this->router->get('curior/location/history/{id}', 'Curior\Location@getHistory');
        $this->router->get('curior/location/latest', 'Curior\Location@getLatest');
        
        // Curior Settlement routes
        $this->router->get('curior/settlement', 'Curior\Settlement@index');
        $this->router->get('curior/settlements', 'Curior\Settlement@index');
        $this->router->post('curior/settlement/collect', 'Curior\Settlement@collectCod');
        
        // Curior Performance routes
        $this->router->get('curior/performance', 'Curior\Performance@index');
        $this->router->get('curior/performance/data', 'Curior\Performance@getData');
        
        // Curior Profile routes
        $this->router->get('curior/profile', 'Curior\Profile@index');
        $this->router->post('curior/profile/update', 'Curior\Profile@update');
        $this->router->post('curior/profile/change-password', 'Curior\Profile@changePassword');
        
        // Curior Notifications routes
        $this->router->get('curior/notifications', 'Curior\Notifications@index');
        $this->router->post('curior/notifications/mark-read', 'Curior\Notifications@markRead');
        
        // Curior Support routes
        $this->router->get('curior/support', 'Curior\Support@index');
        $this->router->post('curior/support/submit', 'Curior\Support@submit');
        
        // Curior History routes
        $this->router->get('curior/history', 'Curior\History@index');
        
        // SMS routes - FIXED AND COMPLETE
        $this->router->get('admin/sms', 'SMSController@index');
        $this->router->get('admin/sms/marketing', 'SMSController@marketing');
        $this->router->get('admin/sms/logs', 'SMSController@viewLogs');
        $this->router->post('admin/sms/send', 'SMSController@send');
        $this->router->post('admin/sms/sendAll', 'SMSController@sendAll');  // ADDED THIS MISSING ROUTE
        $this->router->post('admin/sms/test', 'SMSController@testSMS');  // ADDED TEST SMS ROUTE
        $this->router->post('admin/sms/create', 'SMSController@createTemplate');
        $this->router->get('admin/sms/template/{id}', 'SMSController@getTemplate');  // ADDED FOR AJAX
        $this->router->get('admin/sms/update/{id}', 'SMSController@updateTemplate');
        $this->router->post('admin/sms/update/{id}', 'SMSController@updateTemplate');
        $this->router->post('admin/sms/delete/{id}', 'SMSController@deleteTemplate');
        $this->router->post('admin/sms/toggle/{id}', 'SMSController@toggleTemplate');
        $this->router->post('admin/sms/duplicate/{id}', 'SMSController@duplicateTemplate');
        $this->router->get('admin/sms/variables/{id}', 'SMSController@getVariables');
        $this->router->post('admin/sms/marketing', 'SMSController@sendLatestProductMarketing');
        $this->router->post('admin/sms/sendRefillReminders', 'SMSController@sendRefillReminders');
        
        // Receipt routes
        // Public receipt routes
        $this->router->get('orders/receipt/{id}', 'ReceiptController@previewReceipt');
        $this->router->get('receipt/{id}', 'ReceiptController@previewReceipt');
        $this->router->get('receipt/downloadReceipt/{id}', 'ReceiptController@downloadReceipt');
        $this->router->get('receipt/previewReceipt/{id}', 'ReceiptController@previewReceipt');
        $this->router->get('receipt/download/{id}', 'ReceiptController@downloadReceipt');
        $this->router->get('admin/receipt/download/{id}', 'ReceiptController@downloadReceipt');
        $this->router->get('admin/receipt/preview/{id}', 'ReceiptController@previewReceipt');
        
        // Admin Review Management routes
        $this->router->get('admin/reviews', 'AdminController@reviews');
        $this->router->post('admin/deleteReview/{id}', 'AdminController@deleteReview');
        
        // Admin Referral Management routes
        $this->router->get('admin/referrals', 'AdminController@referrals');
        $this->router->post('admin/updateReferralStatus/{id}', 'AdminController@updateReferralStatus');
        $this->router->post('admin/processMissingReferrals', 'AdminController@processMissingReferrals');
        $this->router->get('admin/withdrawals', 'AdminController@withdrawals');
        $this->router->post('admin/updateWithdrawalStatus/{id}', 'AdminController@updateWithdrawalStatus');
        
        // Admin Sale Management routes
        $this->router->get('admin/sales', 'AdminSaleController@index');
        $this->router->get('admin/sales/create', 'AdminSaleController@create');
        $this->router->post('admin/sales/create', 'AdminSaleController@create');
        $this->router->get('admin/sales/edit/{id}', 'AdminSaleController@update');
        $this->router->post('admin/sales/update/{id}', 'AdminSaleController@update');
        $this->router->post('admin/sales/delete/{id}', 'AdminSaleController@delete');
        $this->router->post('admin/sales/toggle/{id}', 'AdminSaleController@toggleStatus');
        $this->router->post('admin/withdrawals/update/{id}', 'AdminController@updateWithdrawalStatus');
        $this->router->get('admin/withdrawals/view/{id}', 'WithdrawController@details');
        $this->router->get('admin/withdrawal/view/{id}', 'WithdrawController@details');
        $this->router->get('admin/withdrawal/user/{id}', 'WithdrawController@userWithdrawals');
        
        // Admin Product Image Management routes
        $this->router->post('admin/deleteProductImage/{id}', 'AdminController@deleteProductImage');
        $this->router->post('admin/setPrimaryImage/{id}', 'AdminController@setPrimaryImage');
        
        // Admin Coupon Management routes
        $this->router->get('admin/coupons', 'AdminController@coupons');
        $this->router->get('admin/coupons/create', 'AdminController@createCoupon');
        $this->router->post('admin/coupons/create', 'AdminController@createCoupon');
        $this->router->get('admin/coupons/edit/{id}', 'AdminController@editCoupon');
        $this->router->post('admin/coupons/edit/{id}', 'AdminController@editCoupon');
        $this->router->post('admin/coupons/delete/{id}', 'AdminController@deleteCoupon');
        $this->router->post('admin/coupons/toggle/{id}', 'AdminController@toggleCoupon');
        $this->router->post('admin/coupons/toggleVisibility/{id}', 'AdminController@toggleCouponVisibility');
        $this->router->get('admin/coupons/stats/{id}', 'AdminController@couponStats');
        
        // Public Coupon routes
        $this->router->get('coupons', 'CouponController@index');
        $this->router->post('coupons/validate', 'CouponController@validate');
        $this->router->post('coupons/apply', 'CouponController@apply');
        $this->router->post('coupons/remove', 'CouponController@remove');
        $this->router->post('coupons/debug', 'CouponController@debug');
        $this->router->post('coupons/getCouponDetails', 'CouponController@getCouponDetails');
        
        // API routes
        $this->router->post('api/cart/add', 'CartController@add');
        $this->router->post('api/cart/update', 'CartController@update');
        $this->router->post('api/cart/remove', 'CartController@remove');
        $this->router->get('api/cart/count', 'Api\CartController@count');
        $this->router->post('api/wishlist/add', 'WishlistController@add');
        $this->router->post('api/wishlist/remove', 'WishlistController@remove');
        
        // Notification API routes
        $this->router->get('api/notifications/count', 'Api\NotificationsController@count');
        $this->router->get('api/notifications', 'Api\NotificationsController@index');
        $this->router->post('api/notifications/mark-read', 'Api\NotificationsController@markRead');
        $this->router->post('api/notifications/mark-all-read', 'Api\NotificationsController@markAllRead');
        
        // New API Routes
        // Products API
        $this->router->get('api/products', 'Api\ProductsApiController@index');
        $this->router->get('api/products/{id}', 'Api\ProductsApiController@show');
        $this->router->get('api/products/category/{category}', 'Api\ProductsApiController@category');
        $this->router->get('api/products/search', 'Api\ProductsApiController@search');
        $this->router->get('api/categories', 'Api\ProductsApiController@categories');
        
        // Cart API
        $this->router->get('api/cart', 'Api\CartApiController@index');
        $this->router->post('api/cart/add', 'Api\CartApiController@add');
        $this->router->put('api/cart/update', 'Api\CartApiController@update');
        $this->router->delete('api/cart/remove', 'Api\CartApiController@remove');
        $this->router->delete('api/cart/clear', 'Api\CartApiController@clear');
        
        // Orders API
        $this->router->get('api/orders', 'Api\OrdersApiController@index');
        $this->router->get('api/orders/{id}', 'Api\OrdersApiController@show');
        $this->router->post('api/orders', 'Api\OrdersApiController@create');
        $this->router->put('api/orders/{id}/status', 'Api\OrdersApiController@updateStatus');
        $this->router->put('api/orders/{id}/cancel', 'Api\OrdersApiController@cancel');
        
        // Auth API
        $this->router->post('api/auth/register', 'Api\AuthApiController@register');
        $this->router->post('api/auth/login', 'Api\AuthApiController@login');
        $this->router->post('api/auth/logout', 'Api\AuthApiController@logout');
        $this->router->get('api/auth/profile', 'Api\AuthApiController@profile');
        $this->router->put('api/auth/profile', 'Api\AuthApiController@updateProfile');
        $this->router->put('api/auth/change-password', 'Api\AuthApiController@changePassword');
        $this->router->get('api/auth/api-keys', 'Api\AuthApiController@getApiKeys');
        $this->router->post('api/auth/api-keys', 'Api\AuthApiController@generateApiKey');
        
        // API Key Management
        $this->router->get('api/generate', 'ApiKeyController@generate');
        $this->router->get('api/manage', 'ApiKeyController@manage');
        $this->router->post('api/create', 'ApiKeyController@create');
        $this->router->post('api/deactivate/{id}', 'ApiKeyController@deactivate');
        
        // API Tester route
        $this->router->get('api/test', 'ApiController@showTester');
        $this->router->get('api/test-key', 'ApiController@showTestKey');
        
        // API Key Management routes
        $this->router->post('api/keys/generate', 'ApiKeyController@generateKey');
        $this->router->get('api/keys/list', 'ApiKeyController@listKeys');
        $this->router->post('api/keys/update', 'ApiKeyController@updateKey');
        $this->router->post('api/keys/revoke', 'ApiKeyController@revokeKey');
        $this->router->get('api/keys/stats', 'ApiKeyController@getStats');
        
        // Payment gateway routes
        // eSewa routes: add checkout page and dynamic success/failure with order id
        $this->router->get('checkout/esewa/{order_id}', 'CheckoutController@esewa');
        $this->router->post('checkout/initiateEsewa/{order_id}', 'CheckoutController@initiateEsewa');
        $this->router->post('checkout/checkEsewaStatus', 'CheckoutController@checkEsewaStatus');
        $this->router->get('payment/esewa/success/{order_id}', 'PaymentController@esewaSuccess');
        $this->router->get('payment/esewa/failure/{order_id}', 'PaymentController@esewaFailure');
        $this->router->get('payment/khalti/failure', 'PaymentController@khaltiFailure');
        $this->router->post('payment/esewa/webhook', 'PaymentController@esewaWebhook');
        $this->router->post('payment/khalti/webhook', 'PaymentController@khaltiWebhook');
        
        // Utility routes
       // SEO routes
        $this->router->get('sitemap.xml', 'SeoController@sitemapIndex');
        $this->router->get('page-sitemap.xml', 'SeoController@pageSitemap');
        $this->router->get('category-sitemap.xml', 'SeoController@categorySitemap');
        $this->router->get('product-sitemap.xml', 'SeoController@productSitemap');
        $this->router->get('robots.txt', 'SeoController@robots');
        
        // Admin settings routes
        $this->router->get('admin/settings', 'AdminSettingController@index');
        $this->router->post('admin/settings/update', 'AdminSettingController@update');
        $this->router->post('admin/settings/optimize-db', 'AdminSettingController@optimizeDb');
        $this->router->post('admin/settings/backup-db', 'AdminSettingController@backupDb');
        $this->router->get('admin/settings/download-backup', 'AdminSettingController@downloadBackup');
        $this->router->get('admin/settings/export-db-xls', 'AdminSettingController@exportDbXls');
        $this->router->get('admin/analytics', 'AdminController@analytics');
        $this->router->get('admin/reports/best-selling', 'AdminController@bestSellingProducts');
        $this->router->get('admin/reports/low-stock', 'AdminController@lowStockAlerts');
        $this->router->get('admin/reports', 'AdminController@reports');
        $this->router->get('admin/notifications', 'AdminController@notifications');
        $this->router->post('admin/notifications/markRead/{id}', 'AdminController@markNotificationRead');
        
        // Admin referral management routes
        $this->router->get('admin/refer', 'AdminReferController@index');
        $this->router->post('admin/refer/save-settings', 'AdminReferController@saveSettings');
        
        // Bulk operations routes
        $this->router->post('admin/orders/bulkUpdate', 'AdminController@bulkUpdateOrders');
        $this->router->post('admin/products/bulkUpdate', 'AdminController@bulkUpdateProducts');
        $this->router->post('admin/users/bulkUpdate', 'AdminController@bulkUpdateUsers');
        $this->router->get('admin/editUser/{id}', 'AdminController@editUser');
        $this->router->post('admin/editUser/{id}', 'AdminController@updateUser');
        $this->router->post('admin/deleteUser/{id}', 'AdminController@deleteUser');
        
        // Export/Import routes
        $this->router->get('admin/export/orders', 'AdminController@exportOrders');
        $this->router->get('admin/export/products', 'AdminController@exportProducts');
        $this->router->get('admin/export/users', 'AdminController@exportUsers');
        $this->router->post('admin/import/products', 'AdminController@importProducts');
        
        // Additional order management routes
        $this->router->get('admin/orders/search', 'AdminController@searchOrders');
        $this->router->get('admin/orders/filter/{status}', 'AdminController@filterOrdersByStatus');
        $this->router->post('admin/orders/addNote/{id}', 'AdminController@addOrderNote');
        $this->router->post('admin/deleteOrder/{id}', 'AdminController@deleteOrder');
        
        // Customer management routes
        $this->router->get('admin/customers', 'CustomerController@index');
        $this->router->get('admin/customers/create', 'CustomerController@create');
        $this->router->post('admin/customers/create', 'CustomerController@create');
        $this->router->get('admin/customers/edit/{id}', 'CustomerController@edit');
        $this->router->post('admin/customers/edit/{id}', 'CustomerController@edit');
        $this->router->get('admin/customers/view/{id}', 'CustomerController@view');
        $this->router->post('admin/customers/delete/{id}', 'CustomerController@delete');
        $this->router->get('admin/customers/search', 'CustomerController@search');
        
        // Admin Seller management routes
        $this->router->get('admin/seller', 'AdminSellerController@index');
        $this->router->get('admin/seller/create', 'AdminSellerController@create');
        $this->router->post('admin/seller/create', 'AdminSellerController@create');
        $this->router->get('admin/seller/details/{id}', 'AdminSellerController@details');
        $this->router->get('admin/seller/edit/{id}', 'AdminSellerController@edit');
        $this->router->post('admin/seller/edit/{id}', 'AdminSellerController@edit');
        $this->router->post('admin/seller/approve/{id}', 'AdminSellerController@approve');
        $this->router->post('admin/seller/reject/{id}', 'AdminSellerController@reject');
        $this->router->get('admin/seller/withdraws', 'AdminSellerController@withdraws');
        $this->router->get('admin/seller/withdraws/{id}', 'AdminSellerController@withdraws');
        $this->router->post('admin/seller/withdraws/approve/{id}', 'AdminSellerController@approveWithdraw');
        $this->router->post('admin/seller/withdraws/reject/{id}', 'AdminSellerController@rejectWithdraw');
        $this->router->post('admin/seller/withdraws/complete/{id}', 'AdminSellerController@completeWithdraw');
        
        // Admin Seller Products Approval routes
        $this->router->get('admin/seller/products', 'AdminSellerProductsController@index');
        $this->router->get('admin/seller/products/detail/{id}', 'AdminSellerProductsController@detail');
        $this->router->post('admin/seller/products/detail/{id}', 'AdminSellerProductsController@detail');
        
        // Admin Ads routes
        $this->router->get('admin/ads', 'AdminAdsController@index');
        $this->router->get('admin/ads/show/{id}', 'AdminAdsController@show');
        $this->router->post('admin/ads/update-status/{id}', 'AdminAdsController@updateStatus');
        $this->router->post('admin/ads/approve/{id}', 'AdminAdsController@approve');
        $this->router->post('admin/ads/reject/{id}', 'AdminAdsController@rejectAd');
        $this->router->get('admin/ads/costs', 'AdminAdsController@costs');
        $this->router->post('admin/ads/costs/create', 'AdminAdsController@createCost');
        $this->router->get('admin/ads/costs/edit/{id}', 'AdminAdsController@editCost');
        $this->router->post('admin/ads/costs/update/{id}', 'AdminAdsController@updateCost');
        $this->router->post('admin/ads/costs/delete/{id}', 'AdminAdsController@deleteCost');
        $this->router->post('admin/ads/settings/update', 'AdminAdsController@updateSettings');
        $this->router->get('admin/ads/admin-settings', 'AdminAdsController@adminSettings');
        $this->router->post('admin/ads/admin-settings/update', 'AdminAdsController@updateAdminSettings');
        $this->router->post('admin/ads/payment/update-status/{id}', 'AdminAdsController@updatePaymentStatus');


// Blog routes
        // Blog routes
        $this->router->get('blog', 'BlogController@index');
        // Alias routes to support plural '/blogs' paths
        $this->router->get('blogs', 'BlogController@index');
        $this->router->get('blog/page/{page}', 'BlogController@index');
        $this->router->get('blog/view/{slug}', 'BlogController@show');  // Changed from @view to @show
        $this->router->get('blog/category/{slug}', 'BlogController@category');
        $this->router->get('blog/category/{slug}/page/{page}', 'BlogController@category');
        $this->router->get('blog/search', 'BlogController@search');
        
        // Admin Blog routes
        $this->router->get('admin/blog', 'BlogController@adminIndex');
        $this->router->get('admin/blog/create', 'BlogController@create');
        $this->router->post('admin/blog/create', 'BlogController@create');
        $this->router->get('admin/blog/edit/{id}', 'BlogController@edit');
        $this->router->post('admin/blog/edit/{id}', 'BlogController@edit');
        $this->router->post('admin/blog/delete/{id}', 'BlogController@delete');
        $this->router->post('admin/blog/bulkDelete', 'BlogController@bulkDelete');


        // Customer service routes
        $this->router->get('support', 'SupportController@index');
        $this->router->post('support/ticket', 'SupportController@createTicket');
        $this->router->get('support/ticket/{id}', 'SupportController@viewTicket');
        
        // Newsletter routes
        $this->router->post('newsletter/subscribe', 'NewsletterController@subscribe');
        $this->router->get('newsletter/unsubscribe/{token}', 'NewsletterController@unsubscribe');
        
        // Social media integration routes
        $this->router->get('auth/google', 'AuthController@googleLogin');
        $this->router->get('auth/google/callback', 'AuthController@googleCallback');
        $this->router->get('auth/facebook', 'AuthController@facebookLogin');
        $this->router->get('auth/facebook/callback', 'AuthController@facebookCallback');
        
        // Debug routes
        $this->router->get('debug', 'DebugController@index');
        $this->router->get('debug/test', 'DebugController@test');
        
        
        // Order Processing routes for seller earnings
        $this->router->post('admin/orders/deliver/{id}', 'OrderProcessor@processDelivery');
        $this->router->post('admin/orders/cancel/{id}', 'OrderProcessor@processCancellation');
        $this->router->post('admin/orders/return/{id}', 'OrderProcessor@processReturn');
        
        // Email queue management
        $this->router->get('admin/email-queue', 'AdminEmailQueueController@index');
        $this->router->post('admin/email-queue/process', 'AdminEmailQueueController@process');
        $this->router->post('admin/email-queue/clean', 'AdminEmailQueueController@clean');
        
        // Ad tracking routes
        $this->router->post('ads/click', 'AdTrackingController@logClick');
        $this->router->post('ads/reach', 'AdTrackingController@logReach');
    }

    private function resolveRoute()
    {
        // Check maintenance mode before routing
        $this->checkMaintenanceMode();
        
        $route = $this->router->resolve();
        
        if ($route) {
            list($controller, $method, $params) = $route;
            $controller = 'App\\Controllers\\' . $controller;
            
            // Get current route for middleware check
            $currentRoute = $this->router->getCurrentRoute();
            
            // Apply middleware
            if (!$this->applyMiddleware($currentRoute)) {
                return; // Middleware handled the response
            }
            
            if (class_exists($controller)) {
                $controllerInstance = new $controller();
                
                if (method_exists($controllerInstance, $method)) {
                    try {
                        call_user_func_array([$controllerInstance, $method], $params);
                        return;
                    } catch (Exception $e) {
                        error_log('Route execution error: ' . $e->getMessage());
                        error_log('Stack trace: ' . $e->getTraceAsString());
                        
                        // Show error page in development, generic error in production
                        // Use constant() to satisfy static analysis when ENVIRONMENT may not be defined at parse time
                        if (defined('ENVIRONMENT') && constant('ENVIRONMENT') === 'development') {
                            echo '<h1>Application Error</h1>';
                            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                        } else {
                            header("HTTP/1.0 500 Internal Server Error");
                            echo "Internal Server Error";
                        }
                        return;
                    }
                } else {
                    error_log("Method '$method' not found in controller '$controller'");
                }
            } else {
                error_log("Controller '$controller' not found");
            }
        }
        
        // Enhanced 404 handling
        header("HTTP/1.0 404 Not Found");
        
        // Try to show a custom 404 page if it exists
        $notFoundView = dirname(dirname(__DIR__)) . '/App/views/errors/404.php';
        if (file_exists($notFoundView)) {
            include $notFoundView;
        } else {
            echo "Page not found";
        }
    }
    
    /**
     * Get current route info for debugging
     */
    public function getCurrentRoute()
    {
        return [
            'controller' => $this->controller,
            'method' => $this->method,
            'params' => $this->params,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method_type' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
        ];
    }
    
    /**
     * Check maintenance mode
     */
    private function checkMaintenanceMode()
    {
        try {
            $settingModel = new \App\Models\Setting();
            $maintenanceMode = $settingModel->get('maintenance_mode', 'false');
            
            if ($maintenanceMode === 'true' || $maintenanceMode === true) {
                $routePath = $_SERVER['REQUEST_URI'] ?? '';
                $routePath = strtok($routePath, '?');
                $routePath = ltrim($routePath, '/');
                
                if (strpos($routePath, 'admin') === 0) {
                    return;
                }
                
                if ($routePath === 'maintenance') {
                    return;
                }
                
                header('Location: /maintenance');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Maintenance mode check error: ' . $e->getMessage());
        }
    }
    
    /**
     * Apply middleware to the current route
     */
    private function applyMiddleware($currentRoute)
    {
        // Get the route path from the current route
        $routePath = $currentRoute['uri'] ?? '';
        
        // Remove query parameters
        $routePath = strtok($routePath, '?');
        
        // Remove leading slash
        $routePath = ltrim($routePath, '/');
        
        // Include middleware
        require_once __DIR__ . '/../Middleware/index.php';
        
        // Apply middleware
        return \App\Middleware\Middleware::handleAuth($routePath);
    }
}
