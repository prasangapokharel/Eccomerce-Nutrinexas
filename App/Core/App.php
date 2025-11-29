<?php
namespace App\Core;

use Exception;
use App\Core\Router;

/**
 * Main application class
 */
class App
{
    protected $controller = 'Home\HomeController';
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
        $this->router->get('', 'Home\HomeController@index');
        $this->router->get('/', 'Home\HomeController@index');
        $this->router->get('home', 'Home\HomeController@index');
        // Error routes
        $this->router->get('404', 'Error\ErrorController@notFound');
        $this->router->get('403', 'Error\ErrorController@forbidden');
        $this->router->get('500', 'Error\ErrorController@serverError');
        $this->router->get('about', 'Home\HomeController@about');
        $this->router->get('pages/privacy', 'Home\HomeController@privacy');
        $this->router->get('privacy', 'Home\HomeController@privacy');
        $this->router->get('pages/terms', 'Home\HomeController@terms');
        $this->router->get('pages/faq', 'Home\HomeController@faq');
        $this->router->get('pages/returnPolicy', 'Home\HomeController@returnPolicy');
        $this->router->get('pages/shipping', 'Home\HomeController@shipping');
        $this->router->get('pages/contact', 'Home\HomeController@contact');
        $this->router->get('contact', 'Home\HomeController@contact');
        
        // Auth routes
        $this->router->get('auth/login', 'Auth\AuthController@login');
        $this->router->post('auth/processLogin', 'Auth\AuthController@processLogin');
        $this->router->get('auth/register', 'Auth\AuthController@register');
        $this->router->post('auth/processRegister', 'Auth\AuthController@processRegister');
        $this->router->get('auth/register/auth0', 'Auth\AuthController@registerWithAuth0');
        $this->router->get('auth/logout', 'Auth\AuthController@logout');
        $this->router->get('auth/forgotPassword', 'Auth\AuthController@forgotPassword');
        $this->router->post('auth/forgotPassword', 'Auth\AuthController@forgotPassword');
        $this->router->get('auth/resetPassword/{token}', 'Auth\AuthController@resetPassword');
        $this->router->post('auth/resetPassword/{token}', 'Auth\AuthController@resetPassword');
        
        // OAuth routes
        $this->router->get('oauth/login', 'Auth\OAuthController@login');
        $this->router->get('oauth/callback', 'Auth\OAuthController@callback');
        $this->router->get('oauth/logout', 'Auth\OAuthController@logout');
        $this->router->get('oauth/link', 'Auth\OAuthController@link');
        $this->router->get('oauth/link/callback', 'Auth\OAuthController@linkCallback');
        $this->router->post('oauth/unlink', 'Auth\OAuthController@unlink');
        $this->router->get('oauth/providers', 'Auth\OAuthController@getProviders');
        $this->router->get('oauth/status', 'Auth\OAuthController@getStatus');
        
        // Auth0 routes
        $this->router->get('auth0/login', 'Auth\Auth0Controller@login');
        $this->router->get('auth0/callback', 'Auth\Auth0Controller@callback');
        $this->router->get('auth0/logout', 'Auth\Auth0Controller@logout');
        $this->router->get('auth0/profile', 'Auth\Auth0Controller@profile');
        $this->router->get('auth0/link', 'Auth\Auth0Controller@link');
        $this->router->get('auth0/link/callback', 'Auth\Auth0Controller@linkCallback');
        $this->router->post('auth0/unlink', 'Auth\Auth0Controller@unlink');
        $this->router->get('auth0/status', 'Auth\Auth0Controller@status');
        $this->router->get('auth0/providers', 'Auth\Auth0Controller@providers');
        
        // Guide routes
        $this->router->get('guide', 'Home\GuideController@index');

        // Maintenance route
        $this->router->get('maintenance', 'Error\ErrorController@maintenance');

        // Ads routes
        $this->router->get('ads', 'Ads\AdsController@index');
        
        // Product routes
        $this->router->get('products', 'Product\ProductController@index');
        $this->router->get('products/filter', 'Product\ProductController@filter');
        $this->router->get('products/view/{slug}', 'Product\ProductController@viewProduct');
        $this->router->get('products/category/{category}/{subtype}', 'Product\ProductController@category');
        $this->router->get('products/category/{category}', 'Product\ProductController@category');
        $this->router->get('products/search', 'Product\ProductController@search');
        $this->router->get('products/liveSearch', 'Product\ProductController@liveSearch');
        
        // Product view tracking routes
        $this->router->post('products/view/record', 'Product\ProductViewController@record');
        $this->router->post('products/view/record/{productId}', 'Product\ProductViewController@record');
        $this->router->get('products/view/count/{productId}', 'Product\ProductViewController@getCount');
        
        // Digital product download routes
        $this->router->get('digitaldownload/{orderId}', 'Product\DigitalProductController@downloadPage');
        $this->router->get('products/digital/download/{productId}', 'Product\DigitalProductController@download');
        
        // Product like/unlike routes
        $this->router->post('products/like', 'Product\ProductLikeController@like');
        $this->router->post('products/like/{productId}', 'Product\ProductLikeController@like');
        $this->router->post('products/unlike', 'Product\ProductLikeController@unlike');
        $this->router->post('products/unlike/{productId}', 'Product\ProductLikeController@unlike');
        $this->router->post('products/like/toggle', 'Product\ProductLikeController@toggle');
        $this->router->post('products/like/toggle/{productId}', 'Product\ProductLikeController@toggle');
        $this->router->get('products/like/count/{productId}', 'Product\ProductLikeController@getCount');
        
        // API routes
        $this->router->get('api/products/infinite', 'Product\ProductController@infiniteScroll');
        $this->router->get('products/infinite', 'Product\ProductController@infiniteScroll');
        $this->router->get('products/view/{slug}', 'Product\ProductController@viewProduct');
        $this->router->get('products/category/{category}/{subtype}', 'Product\ProductController@category');
        $this->router->get('products/category/{category}', 'Product\ProductController@category');
        $this->router->get('products/search', 'Product\ProductController@search');
        $this->router->get('products/liveSearch', 'Product\ProductController@liveSearch');
        
        // Admin Slider routes - Fixed and organized
        $this->router->get('admin/slider', 'Slider\SliderController@index');
        $this->router->get('admin/slider/create', 'Slider\SliderController@create');
        $this->router->post('admin/slider/create', 'Slider\SliderController@create');
        $this->router->get('admin/slider/edit/{id}', 'Slider\SliderController@edit');
        $this->router->post('admin/slider/edit/{id}', 'Slider\SliderController@edit');
        $this->router->post('admin/slider/delete/{id}', 'Slider\SliderController@delete');
        $this->router->post('admin/slider/toggle/{id}', 'Slider\SliderController@toggleStatus');
        
        // Admin Banner routes
        $this->router->get('admin/banners', 'Ads\BannerController@adminIndex');
        $this->router->get('admin/banners/create', 'Ads\BannerController@create');
        $this->router->post('admin/banners/create', 'Ads\BannerController@create');
        $this->router->get('admin/banners/edit/{id}', 'Ads\BannerController@edit');
        $this->router->post('admin/banners/edit/{id}', 'Ads\BannerController@edit');
        $this->router->post('admin/banners/delete/{id}', 'Ads\BannerController@delete');
        $this->router->post('admin/banners/bulk-delete', 'Ads\BannerController@bulkDelete');
        $this->router->post('admin/banners/toggle/{id}', 'Ads\BannerController@toggleStatus');
        
        // Banner click tracking
        $this->router->get('banner/click/{id}', 'Ads\BannerController@trackClick');
        $this->router->post('banner/view/{id}', 'Ads\BannerController@trackView');
        
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
        $this->router->get('seller/orders/print-shipping-label/{id}', 'Billing\ShippingLabelController@print');
        
        // Shipping Label routes
        $this->router->get('billing/shipping-label/print/{id}', 'Billing\ShippingLabelController@print');
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
        $this->router->get('admin/categories', 'Category\CategoryController@index');
        $this->router->get('categories', 'Category\CategoryController@publicIndex');
        $this->router->get('category/{slug}', 'Category\CategoryController@show');
        
        // Review routes
        $this->router->post('products/submitReview', 'Review\ReviewController@submit');
        $this->router->post('reviews/submit', 'Review\ReviewController@submit');
        $this->router->post('reviews/submitAjax', 'Review\ReviewController@submitAjax');
        $this->router->post('reviews/delete', 'Review\ReviewController@delete');
        $this->router->get('reviews/edit/{id}', 'Product\ProductController@editReview');
        $this->router->post('reviews/update/{id}', 'Product\ProductController@updateReview');
        $this->router->post('reviews/delete/{id}', 'Product\ProductController@deleteReview');
        
        // Cart routes
        $this->router->get('cart', 'Cart\CartController@index');
        $this->router->post('cart/add', 'Cart\CartController@add');
        $this->router->post('cart/update', 'Cart\CartController@update');
        $this->router->get('cart/remove/{id}', 'Cart\CartController@remove');
        $this->router->post('cart/remove', 'Cart\CartController@remove');
        $this->router->get('cart/clear', 'Cart\CartController@clear');
        $this->router->post('cart/clear', 'Cart\CartController@clear');
        $this->router->get('cart/count', 'Cart\CartController@count');
        
        // Wishlist routes
        $this->router->get('wishlist', 'Wishlist\WishlistController@index');
        $this->router->post('wishlist/add', 'Wishlist\WishlistController@add');
        $this->router->post('wishlist/remove', 'Wishlist\WishlistController@remove');
        $this->router->get('wishlist/remove/{id}', 'Wishlist\WishlistController@remove');
        $this->router->post('wishlist/moveToCart', 'Wishlist\WishlistController@moveToCart');
        $this->router->get('wishlist/moveToCart/{id}', 'Wishlist\WishlistController@moveToCart');
        
        // Checkout routes
        $this->router->get('checkout', 'Checkout\CheckoutController@index');
        $this->router->post('checkout/process', 'Checkout\CheckoutController@process');
        $this->router->get('checkout/success/{order_id}', 'Checkout\CheckoutController@success');
        $this->router->post('checkout/validateCoupon', 'Checkout\CheckoutController@validateCoupon');

        // User Orders routes
        $this->router->get('orders', 'Order\OrderController@index');
        $this->router->get('orders/view/{id}', 'Order\OrderController@viewOrder');
        $this->router->get('orders/track', 'Order\OrderController@track');
        $this->router->get('orders/track-result', 'Order\OrderController@trackResult');
        $this->router->post('orders/cancel/{id}', 'Order\OrderController@cancel');
        $this->router->get('orders/reorder/{id}', 'Order\OrderController@reorder');
        $this->router->post('checkout/removeCoupon', 'Checkout\CheckoutController@removeCoupon');
        
        // Khalti payment routes
        $this->router->get('checkout/khalti/{order_id}', 'Checkout\CheckoutController@khalti');
        // Legacy route for backward compatibility - delegates to new KhaltiController
        $this->router->post('checkout/initiateKhalti/{order_id}', 'Payment\KhaltiController@initiate');
        // New dedicated payment controller routes
        $this->router->post('payment/khalti/initiate/{order_id}', 'Payment\KhaltiController@initiate');
        $this->router->post('payment/khalti/verify', 'Payment\KhaltiController@verify');
        $this->router->get('payment/khalti/success/{order_id}', 'Payment\KhaltiController@return');
        $this->router->get('payment/khalti/failure', 'Payment\KhaltiController@failure');
        $this->router->post('payment/khalti/webhook', 'Payment\KhaltiController@webhook');
        
        // Order routes
        $this->router->get('orders', 'Order\OrderController@index');
        $this->router->get('orders/view/{id}', 'Order\OrderController@viewOrder');
        $this->router->get('orders/success/{id}', 'Order\OrderController@success');
        $this->router->get('orders/track', 'Order\OrderController@track');
        $this->router->post('orders/track', 'Order\OrderController@track');
        $this->router->get('orders/cancel/{id}', 'Order\OrderController@cancel');
        $this->router->post('orders/cancel/{id}', 'Order\OrderController@cancel');
        $this->router->post('orders/updateStatus/{id}', 'Order\OrderController@updateStatus');
        
        // User routes
        $this->router->get('user/account', 'UserController@account');
        $this->router->get('user/reviews', 'UserController@reviews');
        $this->router->get('user/invite', 'UserController@invite');
        $this->router->get('user/balance', 'UserController@balance');
        
        // Affiliate routes
        $this->router->get('affiliate', 'Affiliate\AffiliateController@products');
        $this->router->get('affiliate/products', 'Affiliate\AffiliateController@products');
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
        $this->router->get('review/{filename}', 'Review\ReviewController@serveReviewImage');
        
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
        $this->router->get('admin', 'Admin\AdminController@index');
        $this->router->get('admin/products', 'Admin\AdminController@products');
        $this->router->post('admin/products/updateStock', 'Admin\AdminController@updateStock');
        $this->router->get('admin/addProduct', 'Admin\AdminController@addProduct');
        $this->router->post('admin/addProduct', 'Admin\AdminController@addProduct');
        $this->router->get('admin/editProduct/{id}', 'Admin\AdminController@editProduct');
        $this->router->post('admin/editProduct/{id}', 'Admin\AdminController@editProduct');
        $this->router->post('admin/updateProduct/{id}', 'Admin\AdminController@updateProduct');
        $this->router->post('admin/deleteProduct/{id}', 'Admin\AdminController@deleteProduct');
        $this->router->get('admin/orders', 'Admin\AdminController@orders');
        // Admin: Create Order page
        $this->router->get('admin/orders/create', 'Admin\AdminController@createOrder');
        $this->router->post('admin/orders/create', 'Admin\AdminController@storeOrder');
        // Alias: some views post to /admin/orders/store
        $this->router->post('admin/orders/store', 'Admin\AdminController@storeOrder');
        $this->router->post('admin/validateOrderCoupon', 'Admin\AdminController@validateOrderCoupon');
        $this->router->get('admin/viewOrder/{id}', 'Admin\AdminController@viewOrder');
        $this->router->post('admin/updateOrderStatus/{id}', 'Admin\AdminController@updateOrderStatus');
        $this->router->post('admin/updateOrderPaymentStatus/{id}', 'Admin\AdminController@updateOrderPaymentStatus');
        $this->router->post('admin/orders/assignCurior', 'Admin\AdminCuriorController@assignCurior');
        $this->router->get('admin/users', 'Admin\AdminController@users');
        $this->router->get('admin/viewUser/{id}', 'Admin\AdminController@viewUser');
        $this->router->post('admin/updateUserRole/{id}', 'Admin\AdminController@updateUserRole');
        $this->router->post('admin/updateUserStatus/{id}', 'Admin\AdminController@updateUserStatus');
        $this->router->post('admin/updateSponsorStatus', 'Admin\AdminController@updateSponsorStatus');
        $this->router->post('admin/updateReferralCode', 'Admin\AdminController@updateReferralCode');
        // Admin Staff and Curior routes (added)
        $this->router->get('admin/staff', 'Staff\AdminStaffController@index');
        $this->router->get('admin/staff/create', 'Staff\AdminStaffController@create');
        $this->router->post('admin/staff/create', 'Staff\AdminStaffController@create');
        $this->router->get('admin/curior', 'Admin\AdminCuriorController@index');
        $this->router->get('admin/curior/create', 'Admin\AdminCuriorController@create');
        $this->router->post('admin/curior/create', 'Admin\AdminCuriorController@create');
        $this->router->get('admin/curior/edit/{id}', 'Admin\AdminCuriorController@edit');
        $this->router->post('admin/curior/edit/{id}', 'Admin\AdminCuriorController@edit');
        $this->router->get('admin/curior/delete/{id}', 'Admin\AdminCuriorController@delete');
        $this->router->post('admin/curior/delete/{id}', 'Admin\AdminCuriorController@delete');
        $this->router->post('admin/curior/toggleStatus/{id}', 'Admin\AdminCuriorController@toggleStatus');
        $this->router->post('admin/curior/reset-password/{id}', 'Admin\AdminCuriorController@sendResetLink');

        // Admin Inventory routes
        $this->router->get('admin/inventory', 'Inventory\InventoryController@index');
        // Use singular path for supplier management as expected by views and redirects
        $this->router->get('admin/inventory/supplier', 'Inventory\InventoryController@suppliers');
        $this->router->get('admin/inventory/products', 'Inventory\InventoryController@products');
        $this->router->post('admin/inventory/scanBarcode', 'Inventory\InventoryController@scanBarcode');
        // Optional extended inventory sections
        $this->router->get('admin/inventory/purchases', 'Inventory\InventoryController@purchases');
        $this->router->get('admin/inventory/payments', 'Inventory\InventoryController@payments');

        // Admin Delivery Charges Management routes
        $this->router->get('admin/delivery', 'Admin\AdminDeliveryController@index');
        $this->router->get('admin/delivery/create', 'Admin\AdminDeliveryController@create');
        $this->router->post('admin/delivery/create', 'Admin\AdminDeliveryController@create');
        $this->router->get('admin/delivery/edit/{id}', 'Admin\AdminDeliveryController@edit');
        $this->router->post('admin/delivery/edit/{id}', 'Admin\AdminDeliveryController@edit');
        $this->router->get('admin/delivery/delete/{id}', 'Admin\AdminDeliveryController@delete');
        $this->router->post('admin/delivery/delete/{id}', 'Admin\AdminDeliveryController@delete');
        // AJAX helpers
        $this->router->get('admin/delivery/charges', 'Admin\AdminDeliveryController@getCharges');
        $this->router->post('admin/delivery/quickAdd', 'Admin\AdminDeliveryController@quickAdd');
        $this->router->post('admin/delivery/toggleFree', 'Admin\AdminDeliveryController@toggleFreeDelivery');
        $this->router->post('admin/delivery/setDefaultFee', 'Admin\AdminDeliveryController@setDefaultFee');

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
        $this->router->get('admin/sms', 'Sms\SMSController@index');
        $this->router->get('admin/sms/marketing', 'Sms\SMSController@marketing');
        $this->router->get('admin/sms/logs', 'Sms\SMSController@viewLogs');
        $this->router->post('admin/sms/send', 'Sms\SMSController@send');
        $this->router->post('admin/sms/sendAll', 'Sms\SMSController@sendAll');  // ADDED THIS MISSING ROUTE
        $this->router->post('admin/sms/test', 'Sms\SMSController@testSMS');  // ADDED TEST SMS ROUTE
        $this->router->post('admin/sms/create', 'Sms\SMSController@createTemplate');
        $this->router->get('admin/sms/template/{id}', 'Sms\SMSController@getTemplate');  // ADDED FOR AJAX
        $this->router->get('admin/sms/update/{id}', 'Sms\SMSController@updateTemplate');
        $this->router->post('admin/sms/update/{id}', 'Sms\SMSController@updateTemplate');
        $this->router->post('admin/sms/delete/{id}', 'Sms\SMSController@deleteTemplate');
        $this->router->post('admin/sms/toggle/{id}', 'Sms\SMSController@toggleTemplate');
        $this->router->post('admin/sms/duplicate/{id}', 'Sms\SMSController@duplicateTemplate');
        $this->router->get('admin/sms/variables/{id}', 'Sms\SMSController@getVariables');
        $this->router->post('admin/sms/marketing', 'Sms\SMSController@sendLatestProductMarketing');
        $this->router->post('admin/sms/sendRefillReminders', 'Sms\SMSController@sendRefillReminders');
        
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
        $this->router->get('admin/reviews', 'Admin\AdminController@reviews');
        $this->router->post('admin/deleteReview/{id}', 'Admin\AdminController@deleteReview');
        
        // Admin Referral Management routes
        $this->router->get('admin/referrals', 'Admin\AdminController@referrals');
        $this->router->post('admin/updateReferralStatus/{id}', 'Admin\AdminController@updateReferralStatus');
        $this->router->post('admin/processMissingReferrals', 'Admin\AdminController@processMissingReferrals');
        $this->router->get('admin/withdrawals', 'Admin\AdminController@withdrawals');
        $this->router->post('admin/updateWithdrawalStatus/{id}', 'Admin\AdminController@updateWithdrawalStatus');
        
        // Admin Sale Management routes
        $this->router->get('admin/sales', 'Admin\AdminSaleController@index');
        $this->router->get('admin/sales/create', 'Admin\AdminSaleController@create');
        $this->router->post('admin/sales/create', 'Admin\AdminSaleController@create');
        $this->router->get('admin/sales/edit/{id}', 'Admin\AdminSaleController@update');
        $this->router->post('admin/sales/update/{id}', 'Admin\AdminSaleController@update');
        $this->router->post('admin/sales/delete/{id}', 'Admin\AdminSaleController@delete');
        $this->router->post('admin/sales/toggle/{id}', 'Admin\AdminSaleController@toggleStatus');
        $this->router->post('admin/withdrawals/update/{id}', 'Admin\AdminController@updateWithdrawalStatus');
        $this->router->get('admin/withdrawals/view/{id}', 'Withdraw\WithdrawController@details');
        $this->router->get('admin/withdrawal/view/{id}', 'Withdraw\WithdrawController@details');
        $this->router->get('admin/withdrawal/user/{id}', 'Withdraw\WithdrawController@userWithdrawals');
        
        // Admin Product Image Management routes
        $this->router->post('admin/deleteProductImage/{id}', 'Admin\AdminController@deleteProductImage');
        $this->router->post('admin/setPrimaryImage/{id}', 'Admin\AdminController@setPrimaryImage');
        
        // Admin Coupon Management routes
        $this->router->get('admin/coupons', 'Admin\AdminController@coupons');
        $this->router->get('admin/coupons/create', 'Admin\AdminController@createCoupon');
        $this->router->post('admin/coupons/create', 'Admin\AdminController@createCoupon');
        $this->router->get('admin/coupons/edit/{id}', 'Admin\AdminController@editCoupon');
        $this->router->post('admin/coupons/edit/{id}', 'Admin\AdminController@editCoupon');
        $this->router->post('admin/coupons/delete/{id}', 'Admin\AdminController@deleteCoupon');
        $this->router->post('admin/coupons/toggle/{id}', 'Admin\AdminController@toggleCoupon');
        $this->router->post('admin/coupons/toggleVisibility/{id}', 'Admin\AdminController@toggleCouponVisibility');
        $this->router->get('admin/coupons/stats/{id}', 'Admin\AdminController@couponStats');
        
        // Public Coupon routes
        $this->router->get('coupons', 'Coupon\CouponController@index');
        $this->router->post('coupons/validate', 'Coupon\CouponController@validate');
        $this->router->post('coupons/apply', 'Coupon\CouponController@apply');
        $this->router->post('coupons/remove', 'Coupon\CouponController@remove');
        $this->router->post('coupons/debug', 'Coupon\CouponController@debug');
        $this->router->post('coupons/getCouponDetails', 'Coupon\CouponController@getCouponDetails');
        
        // API routes
        $this->router->post('api/cart/add', 'Cart\CartController@add');
        $this->router->post('api/cart/update', 'Cart\CartController@update');
        $this->router->post('api/cart/remove', 'Cart\CartController@remove');
        $this->router->get('api/cart/count', 'Cart\CartController@count');
        $this->router->post('api/wishlist/add', 'Wishlist\WishlistController@add');
        $this->router->post('api/wishlist/remove', 'Wishlist\WishlistController@remove');
        
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
        $this->router->get('api/generate', 'Api\ApiKeyController@generate');
        $this->router->get('api/manage', 'Api\ApiKeyController@manage');
        $this->router->post('api/create', 'Api\ApiKeyController@create');
        $this->router->post('api/deactivate/{id}', 'Api\ApiKeyController@deactivate');
        
        // API Tester route
        $this->router->get('api/test', 'ApiController@showTester');
        $this->router->get('api/test-key', 'ApiController@showTestKey');
        
        // API Key Management routes
        $this->router->post('api/keys/generate', 'Api\ApiKeyController@generateKey');
        $this->router->get('api/keys/list', 'Api\ApiKeyController@listKeys');
        $this->router->post('api/keys/update', 'Api\ApiKeyController@updateKey');
        $this->router->post('api/keys/revoke', 'Api\ApiKeyController@revokeKey');
        $this->router->get('api/keys/stats', 'Api\ApiKeyController@getStats');
        
        // Payment gateway routes
        // eSewa routes: add checkout page and dynamic success/failure with order id
        $this->router->get('checkout/esewa/{order_id}', 'Checkout\CheckoutController@esewa');
        // Legacy route for backward compatibility - delegates to new EsewaController
        $this->router->post('checkout/initiateEsewa/{order_id}', 'Payment\EsewaController@initiate');
        // New dedicated payment controller routes
        $this->router->post('payment/esewa/initiate/{order_id}', 'Payment\EsewaController@initiate');
        $this->router->post('payment/esewa/verify', 'Payment\EsewaController@verify');
        $this->router->post('payment/esewa/check-status', 'Payment\EsewaController@checkStatus');
        $this->router->get('payment/esewa/success/{order_id}', 'Payment\EsewaController@success');
        $this->router->get('payment/esewa/failure/{order_id}', 'Payment\EsewaController@failure');
        $this->router->post('payment/esewa/webhook', 'Payment\EsewaController@webhook');
        
        // COD (Cash on Delivery) payment routes
        $this->router->post('payment/cod/initiate/{order_id}', 'Payment\CODController@initiate');
        $this->router->post('payment/cod/confirm/{order_id}', 'Payment\CODController@confirm');
        $this->router->post('payment/cod/cancel/{order_id}', 'Payment\CODController@cancel');
        $this->router->get('payment/cod/status/{order_id}', 'Payment\CODController@status');
        
        // Utility routes
       // SEO routes
        $this->router->get('sitemap.xml', 'Seo\SeoController@sitemapIndex');
        $this->router->get('page-sitemap.xml', 'Seo\SeoController@pageSitemap');
        $this->router->get('category-sitemap.xml', 'Seo\SeoController@categorySitemap');
        $this->router->get('product-sitemap.xml', 'Seo\SeoController@productSitemap');
        $this->router->get('robots.txt', 'Seo\SeoController@robots');
        
        // Admin settings routes
        $this->router->get('admin/settings', 'Admin\AdminSettingController@index');
        $this->router->post('admin/settings/update', 'Admin\AdminSettingController@update');
        $this->router->post('admin/settings/optimize-db', 'Admin\AdminSettingController@optimizeDb');
        $this->router->post('admin/settings/backup-db', 'Admin\AdminSettingController@backupDb');
        $this->router->get('admin/settings/download-backup', 'Admin\AdminSettingController@downloadBackup');
        $this->router->get('admin/settings/export-db-xls', 'Admin\AdminSettingController@exportDbXls');
        $this->router->get('admin/analytics', 'Admin\AdminController@analytics');
        $this->router->get('admin/reports/best-selling', 'Admin\AdminController@bestSellingProducts');
        $this->router->get('admin/reports/low-stock', 'Admin\AdminController@lowStockAlerts');
        $this->router->get('admin/reports', 'Admin\AdminController@reports');
        $this->router->get('admin/notifications', 'Admin\AdminController@notifications');
        $this->router->post('admin/notifications/markRead/{id}', 'Admin\AdminController@markNotificationRead');
        
        // Admin referral management routes
        $this->router->get('admin/refer', 'Admin\AdminReferController@index');
        $this->router->post('admin/refer/save-settings', 'Admin\AdminReferController@saveSettings');
        
        // Bulk operations routes
        $this->router->post('admin/orders/bulkUpdate', 'Admin\AdminController@bulkUpdateOrders');
        $this->router->post('admin/products/bulkUpdate', 'Admin\AdminController@bulkUpdateProducts');
        $this->router->post('admin/users/bulkUpdate', 'Admin\AdminController@bulkUpdateUsers');
        $this->router->get('admin/editUser/{id}', 'Admin\AdminController@editUser');
        $this->router->post('admin/editUser/{id}', 'Admin\AdminController@updateUser');
        $this->router->post('admin/deleteUser/{id}', 'Admin\AdminController@deleteUser');
        
        // Export/Import routes
        $this->router->get('admin/export/orders', 'Admin\AdminController@exportOrders');
        $this->router->get('admin/export/products', 'Admin\AdminController@exportProducts');
        $this->router->get('admin/export/users', 'Admin\AdminController@exportUsers');
        $this->router->post('admin/import/products', 'Admin\AdminController@importProducts');
        
        // Additional order management routes
        $this->router->get('admin/orders/search', 'Admin\AdminController@searchOrders');
        $this->router->get('admin/orders/filter/{status}', 'Admin\AdminController@filterOrdersByStatus');
        $this->router->post('admin/orders/addNote/{id}', 'Admin\AdminController@addOrderNote');
        $this->router->post('admin/deleteOrder/{id}', 'Admin\AdminController@deleteOrder');
        
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
        $this->router->get('admin/seller', 'Admin\AdminSellerController@index');
        $this->router->get('admin/seller/create', 'Admin\AdminSellerController@create');
        $this->router->post('admin/seller/create', 'Admin\AdminSellerController@create');
        $this->router->get('admin/seller/details/{id}', 'Admin\AdminSellerController@details');
        $this->router->get('admin/seller/edit/{id}', 'Admin\AdminSellerController@edit');
        $this->router->post('admin/seller/edit/{id}', 'Admin\AdminSellerController@edit');
        $this->router->post('admin/seller/approve/{id}', 'Admin\AdminSellerController@approve');
        $this->router->post('admin/seller/reject/{id}', 'Admin\AdminSellerController@reject');
        $this->router->get('admin/seller/withdraws', 'Admin\AdminSellerController@withdraws');
        $this->router->get('admin/seller/withdraws/{id}', 'Admin\AdminSellerController@withdraws');
        $this->router->post('admin/seller/withdraws/approve/{id}', 'Admin\AdminSellerController@approveWithdraw');
        $this->router->post('admin/seller/withdraws/reject/{id}', 'Admin\AdminSellerController@rejectWithdraw');
        $this->router->post('admin/seller/withdraws/complete/{id}', 'Admin\AdminSellerController@completeWithdraw');
        
        // Admin Seller Products Approval routes
        $this->router->get('admin/seller/products', 'Admin\AdminSellerProductsController@index');
        $this->router->get('admin/seller/products/detail/{id}', 'Admin\AdminSellerProductsController@detail');
        $this->router->post('admin/seller/products/detail/{id}', 'Admin\AdminSellerProductsController@detail');
        
        // Admin Ads routes
        $this->router->get('admin/ads', 'Ads\AdminAdsController@index');
        $this->router->get('admin/ads/show/{id}', 'Ads\AdminAdsController@show');
        $this->router->post('admin/ads/update-status/{id}', 'Ads\AdminAdsController@updateStatus');
        $this->router->post('admin/ads/approve/{id}', 'Ads\AdminAdsController@approve');
        $this->router->post('admin/ads/reject/{id}', 'Ads\AdminAdsController@rejectAd');
        $this->router->get('admin/ads/costs', 'Ads\AdminAdsController@costs');
        $this->router->post('admin/ads/costs/create', 'Ads\AdminAdsController@createCost');
        $this->router->get('admin/ads/costs/edit/{id}', 'Ads\AdminAdsController@editCost');
        $this->router->post('admin/ads/costs/update/{id}', 'Ads\AdminAdsController@updateCost');
        $this->router->post('admin/ads/costs/delete/{id}', 'Ads\AdminAdsController@deleteCost');
        $this->router->post('admin/ads/settings/update', 'Ads\AdminAdsController@updateSettings');
        $this->router->get('admin/ads/admin-settings', 'Ads\AdminAdsController@adminSettings');
        $this->router->post('admin/ads/admin-settings/update', 'Ads\AdminAdsController@updateAdminSettings');
        $this->router->post('admin/ads/payment/update-status/{id}', 'Ads\AdminAdsController@updatePaymentStatus');


// Blog routes
        // Blog routes
        $this->router->get('blog', 'Blogs\BlogController@index');
        // Alias routes to support plural '/blogs' paths
        $this->router->get('blogs', 'Blogs\BlogController@index');
        $this->router->get('blog/page/{page}', 'Blogs\BlogController@index');
        $this->router->get('blog/view/{slug}', 'Blogs\BlogController@show');  // Changed from @view to @show
        $this->router->get('blog/category/{slug}', 'Blogs\BlogController@category');
        $this->router->get('blog/category/{slug}/page/{page}', 'Blogs\BlogController@category');
        $this->router->get('blog/search', 'Blogs\BlogController@search');
        
        // Admin Blog routes
        $this->router->get('admin/blog', 'Blogs\BlogController@adminIndex');
        $this->router->get('admin/blog/create', 'Blogs\BlogController@create');
        $this->router->post('admin/blog/create', 'Blogs\BlogController@create');
        $this->router->get('admin/blog/edit/{id}', 'Blogs\BlogController@edit');
        $this->router->post('admin/blog/edit/{id}', 'Blogs\BlogController@edit');
        $this->router->post('admin/blog/delete/{id}', 'Blogs\BlogController@delete');
        $this->router->post('admin/blog/bulkDelete', 'Blogs\BlogController@bulkDelete');


        // Customer service routes
        $this->router->get('support', 'SupportController@index');
        $this->router->post('support/ticket', 'SupportController@createTicket');
        $this->router->get('support/ticket/{id}', 'SupportController@viewTicket');
        
        // Newsletter routes
        $this->router->post('newsletter/subscribe', 'NewsletterController@subscribe');
        $this->router->get('newsletter/unsubscribe/{token}', 'NewsletterController@unsubscribe');
        
        // Social media integration routes
        $this->router->get('auth/google', 'Auth\AuthController@googleLogin');
        $this->router->get('auth/google/callback', 'Auth\AuthController@googleCallback');
        $this->router->get('auth/facebook', 'Auth\AuthController@facebookLogin');
        $this->router->get('auth/facebook/callback', 'Auth\AuthController@facebookCallback');
        
        // Debug routes
        $this->router->get('debug', 'Debug\DebugController@index');
        $this->router->get('debug/test', 'Debug\DebugController@test');
        
        
        // Order Processing routes for seller earnings
        $this->router->post('admin/orders/deliver/{id}', 'Order\OrderProcessor@processDelivery');
        $this->router->post('admin/orders/cancel/{id}', 'Order\OrderProcessor@processCancellation');
        $this->router->post('admin/orders/return/{id}', 'Order\OrderProcessor@processReturn');
        
        // Email queue management
        $this->router->get('admin/email-queue', 'Admin\AdminEmailQueueController@index');
        $this->router->post('admin/email-queue/process', 'Admin\AdminEmailQueueController@process');
        $this->router->post('admin/email-queue/clean', 'Admin\AdminEmailQueueController@clean');
        
        // Ad tracking routes
        $this->router->post('ads/click', 'Ads\AdTrackingController@logClick');
        $this->router->post('ads/reach', 'Ads\AdTrackingController@logReach');
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
                        
                        // Show error page - use ErrorController
                        $errorController = new \App\Controllers\Error\ErrorController();
                        if (defined('ENVIRONMENT') && constant('ENVIRONMENT') === 'development') {
                            $errorController->serverError();
                        } else {
                            $errorController->serverError();
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
        
        // Enhanced 404 handling - use ErrorController
        $errorController = new \App\Controllers\Error\ErrorController();
        $errorController->notFound();
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
