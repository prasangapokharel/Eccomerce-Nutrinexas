<?php
namespace App\Controllers\Home;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Slider;
use App\Models\Wishlist;
use App\Models\Review;
use App\Models\Blog;
use App\Models\Seller;
use Exception;

class HomeController extends Controller
{
    private $productModel;
    private $productImageModel;
    private $categoryModel;
    private $sliderModel;
    private $wishlistModel;
    private $reviewModel;
    private $blogModel;
    private $sellerModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
        $this->categoryModel = new Category();
        $this->sliderModel = new Slider();
        $this->wishlistModel = new Wishlist();
        $this->reviewModel = new Review();
        $this->blogModel = new Blog();
        $this->sellerModel = new Seller();
    }

    /**
     * Get the URL for a product's image with proper fallback logic
     * 
     * @param array $product The product data
     * @param array|null $primaryImage The primary image data from product_images
     * @return string The image URL
     */
    private function getProductImageUrl($product, $primaryImage = null)
    {
        // 1. Check if product has direct image URL
        if (!empty($product['image'])) {
            return $product['image'];
        }
        
        // 2. Check for primary image from product_images table
        if ($primaryImage && !empty($primaryImage['image_url'])) {
            return $primaryImage['image_url'];
        }
        
        // 3. Check for any image from product_images table
        $images = $this->productImageModel->getByProductId($product['id']);
        if (!empty($images[0]['image_url'])) {
            return $images[0]['image_url'];
        }
        
        // 4. Fallback to default image
        return \App\Core\View::asset('images/products/default.jpg');
    }

    /**
     * Display home page - TURBO OPTIMIZED VERSION
     */
    public function index()
    {
        try {
            // Initialize performance cache
            if (!class_exists('App\Helpers\PerformanceCache')) {
                require_once ROOT_DIR . '/App/Helpers/PerformanceCache.php';
            }
            \App\Helpers\PerformanceCache::init();
            
            error_log('HomeController: Starting to load data...');
            
            // Get cached data or load fresh
            $cacheKey = 'homepage_data_' . (Session::has('user_id') ? Session::get('user_id') : 'guest');
            $cachedData = \App\Helpers\PerformanceCache::getStaticContent($cacheKey);
            
            if ($cachedData) {
                error_log('HomeController: Using cached data');
                $this->view('home/index', $cachedData);
                return;
            }
            
            error_log('HomeController: Loading fresh data...');
            
            // Get active sliders with caching
            $sliders = $this->getCachedSliders();
            error_log('HomeController: Sliders loaded: ' . count($sliders));
            
            // Get active categories with caching
            $categories = $this->getCachedCategories();
            error_log('HomeController: Categories loaded: ' . count($categories));
            
            // Get latest products with caching
            $products = $this->getCachedProducts();
            error_log('HomeController: Products loaded: ' . count($products));
            
            // Add wishlist status to products
            $products = $this->addWishlistStatus($products);
            
            // Get popular products with caching
            $popular_products = $this->getCachedPopularProducts();
            error_log('HomeController: Popular products loaded: ' . count($popular_products));
            
            // Add wishlist status to popular products
            $popular_products = $this->addWishlistStatus($popular_products);
            
            // Get random reviews with caching
            $reviews = $this->getCachedReviews();
            error_log('HomeController: Reviews loaded: ' . count($reviews));
            
            // Get featured blog posts with caching
            $blog_posts = $this->getCachedBlogPosts();
            error_log('HomeController: Blog posts loaded: ' . count($blog_posts));
            
            // Get active and approved sellers with logos
            $sellers = $this->getCachedSellers();
            error_log('HomeController: Sellers loaded: ' . count($sellers));

            $data = [
                'sliders' => $sliders,
                'categories' => $categories,
                'products' => $products,
                'popular_products' => $popular_products,
                'reviews' => $reviews,
                'blog_posts' => $blog_posts,
                'sellers' => $sellers,
                'title' => 'NutriNexas - Premium Supplements & Nutrition'
            ];
            
            // Cache the data for 1 hour
            \App\Helpers\PerformanceCache::cacheStaticContent($cacheKey, $data, 3600);
            
            error_log('HomeController: All data loaded successfully, rendering view...');
            $this->view('home/index', $data);
            
            error_log('HomeController: View rendered successfully');
        } catch (Exception $e) {
            error_log('HomeController index error: ' . $e->getMessage());
            error_log('HomeController index error trace: ' . $e->getTraceAsString());
            
            // Fallback with minimal data
            $this->view('home/index', [
                'sliders' => [],
                'categories' => [],
                'products' => [],
                'popular_products' => [],
                'reviews' => [],
                'blog_posts' => [],
                'sellers' => [],
                'title' => 'NutriNexas - Premium Supplements & Nutrition'
            ]);
        }
    }

    /**
     * Display about page
     */
    public function about()
    {
        $this->view('home/about', [
            'title' => 'About Us - NutriNexas'
        ]);
    }

    /**
     * Display privacy policy page
     */
    public function privacy()
    {
        $this->view('pages/privacy', [
            'title' => 'Privacy Policy - NutriNexas'
        ]);
    }

    /**
     * Display terms and conditions page
     */
    public function terms()
    {
        $this->view('pages/terms', [
            'title' => 'Terms and Conditions - NutriNexas'
        ]);
    }

    /**
     * Display FAQ page
     */
    public function faq()
    {
        $this->view('pages/faq', [
            'title' => 'Frequently Asked Questions - NutriNexas'
        ]);
    }

    /**
     * Display return policy page
     */
    public function returnPolicy()
    {
        $this->view('pages/return-policy', [
            'title' => 'Return Policy - NutriNexas'
        ]);
    }

    /**
     * Display shipping policy page
     */
    public function shipping()
    {
        $this->view('pages/shipping', [
            'title' => 'Shipping Policy - NutriNexas'
        ]);
    }

    /**
     * Display contact page and handle contact form
     */
    public function contact()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleContactForm();
        } else {
            $this->view('home/contact', [
                'title' => 'Contact Us - NutriNexas'
            ]);
        }
    }

    /**
     * Handle contact form submission
     */
    private function handleContactForm()
    {
        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('contact');
            return;
        }
        
        $name = \App\Helpers\SecurityHelper::sanitizeString($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = \App\Helpers\SecurityHelper::sanitizeString($_POST['subject'] ?? '');
        $message = \App\Helpers\SecurityHelper::sanitizeString($_POST['message'] ?? '', true);

        // Validate input
        $errors = [];

        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!\App\Helpers\SecurityHelper::validateEmail($email)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($subject)) {
            $errors['subject'] = 'Subject is required';
        }

        if (empty($message)) {
            $errors['message'] = 'Message is required';
        }

        if (empty($errors)) {
            // Send email
            $emailSent = $this->sendContactEmail($name, $email, $subject, $message);
            
            if ($emailSent) {
                $this->setFlash('success', 'Your message has been sent successfully. We will get back to you soon!');
                $this->redirect('contact');
            } else {
                $this->setFlash('error', 'Failed to send message. Please try again later.');
                $this->view('home/contact', [
                    'errors' => ['form' => 'Failed to send message'],
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                    'title' => 'Contact Us - NutriNexas'
                ]);
            }
        } else {
            $this->view('home/contact', [
                'errors' => $errors,
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'title' => 'Contact Us - NutriNexas'
            ]);
        }
    }

    /**
     * Send contact email
     */
    private function sendContactEmail($name, $email, $subject, $message)
    {
        try {
            // Prepare email headers
            $headers = "From: $name <$email>\r\n";
            $headers .= "Reply-To: $email\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // Prepare email body
            $emailBody = "<h2>Contact Form Submission</h2>";
            $emailBody .= "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
            $emailBody .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
            $emailBody .= "<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>";
            $emailBody .= "<p><strong>Message:</strong></p>";
            $emailBody .= "<p>" . nl2br(htmlspecialchars($message)) . "</p>";

            // Send email
            $toAddress = defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'admin@nutrinexas.com';
            $result = mail($toAddress, 'Contact Form: ' . $subject, $emailBody, $headers);

            return $result;
        } catch (Exception $e) {
            error_log('Contact email error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Display authenticator page
     */
    public function authenticator()
    {
        $this->view('home/authenticator', [
            'title' => 'Authenticator Wellcore - NutriNexas'
        ]);
    }

    /**
     * Newsletter subscription
     */
    public function newsletter()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->setFlash('error', 'Please enter a valid email address');
                $this->redirect('');
                return;
            }

            // Here you would typically save to database or send to email service
            // For now, just show success message
            $this->setFlash('success', 'Thank you for subscribing to our newsletter!');
            $this->redirect('');
        } else {
            $this->redirect('');
        }
    }

    /**
     * Health check endpoint
     */
    public function health()
    {
        header('Content-Type: application/json');
        
        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'database' => 'connected'
        ];
        
        // Test database connection
        try {
            $this->productModel->getProductCount();
        } catch (Exception $e) {
            $health['status'] = 'error';
            $health['database'] = 'disconnected';
            $health['error'] = $e->getMessage();
        }
        
        echo json_encode($health, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Get cached sliders
     */
    private function getCachedSliders()
    {
        $cacheKey = 'sliders_active';
        $cached = \App\Helpers\PerformanceCache::getCachedDatabaseQuery('SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order ASC', []);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $sliders = $this->sliderModel->getActiveSliders();
            \App\Helpers\PerformanceCache::cacheDatabaseQuery('SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order ASC', [], $sliders, 1800);
            return $sliders;
        } catch (Exception $e) {
            error_log('Error loading sliders: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cached categories
     */
    private function getCachedCategories()
    {
        $cacheKey = 'categories_active';
        $cached = \App\Helpers\PerformanceCache::getCachedDatabaseQuery('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC', []);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $categories = $this->categoryModel->getActiveCategories();
            \App\Helpers\PerformanceCache::cacheDatabaseQuery('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC', [], $categories, 1800);
            return $categories;
        } catch (Exception $e) {
            error_log('Error loading categories: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cached products
     */
    private function getCachedProducts()
    {
        $cacheKey = 'products_ranked_12';
        $cached = \App\Helpers\PerformanceCache::getCachedDatabaseQuery('ranked_products_12', []);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            // Use ranking algorithm instead of simple latest products
            $products = $this->productModel->getRankedProducts(12, 0);
            
            // Add image URLs, scheduled flags, and review data to products (align with ProductController)
            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                
                // Apply sale price calculation
                $product = $this->productModel->applySalePrice($product);

                // Add review statistics
                $reviewStats = $this->productModel->getReviewStats($product['id']);
                $product['review_count'] = $reviewStats['total_reviews'];
                $product['avg_rating'] = $reviewStats['average_rating'];

                // Scheduled product logic
                $product['remaining_days'] = 0;
                if (!empty($product['is_scheduled'])) {
                    $now = new \DateTime();
                    $scheduledDate = null;
                    if (!empty($product['scheduled_date'])) {
                        $scheduledDate = new \DateTime($product['scheduled_date']);
                    } elseif (!empty($product['scheduled_duration']) && !empty($product['created_at'])) {
                        $createdDate = new \DateTime($product['created_at']);
                        $scheduledDate = clone $createdDate;
                        $scheduledDate->add(new \DateInterval('P' . (int)$product['scheduled_duration'] . 'D'));
                    }
                    if ($scheduledDate instanceof \DateTime) {
                        // is_scheduled true only if launch date is in the future
                        $product['is_scheduled'] = $scheduledDate > $now;
                        if ($product['is_scheduled']) {
                            $product['remaining_days'] = $now->diff($scheduledDate)->days;
                        }
                    } else {
                        // No valid schedule date; treat as not scheduled
                        $product['is_scheduled'] = false;
                    }
                }
            }
            
            \App\Helpers\PerformanceCache::cacheDatabaseQuery('ranked_products_12', [], $products, 900);
            return $products;
        } catch (Exception $e) {
            error_log('Error loading products: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cached popular products
     */
    private function getCachedPopularProducts()
    {
        $cacheKey = 'products_popular_12';
        $cached = \App\Helpers\PerformanceCache::getCachedDatabaseQuery('SELECT * FROM products WHERE is_active = 1 AND (is_featured = 1 OR is_bestseller = 1) ORDER BY views DESC LIMIT 12', []);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $products = $this->productModel->getPopularProducts(12);
            
            // Add image URLs, scheduled flags, and review data to products (align with ProductController)
            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                
                // Apply sale price calculation
                $product = $this->productModel->applySalePrice($product);

                // Add review statistics
                $reviewStats = $this->productModel->getReviewStats($product['id']);
                $product['review_count'] = $reviewStats['total_reviews'];
                $product['avg_rating'] = $reviewStats['average_rating'];

                // Scheduled product logic
                $product['remaining_days'] = 0;
                if (!empty($product['is_scheduled'])) {
                    $now = new \DateTime();
                    $scheduledDate = null;
                    if (!empty($product['scheduled_date'])) {
                        $scheduledDate = new \DateTime($product['scheduled_date']);
                    } elseif (!empty($product['scheduled_duration']) && !empty($product['created_at'])) {
                        $createdDate = new \DateTime($product['created_at']);
                        $scheduledDate = clone $createdDate;
                        $scheduledDate->add(new \DateInterval('P' . (int)$product['scheduled_duration'] . 'D'));
                    }
                    if ($scheduledDate instanceof \DateTime) {
                        // is_scheduled true only if launch date is in the future
                        $product['is_scheduled'] = $scheduledDate > $now;
                        if ($product['is_scheduled']) {
                            $product['remaining_days'] = $now->diff($scheduledDate)->days;
                        }
                    } else {
                        // No valid schedule date; treat as not scheduled
                        $product['is_scheduled'] = false;
                    }
                }
            }
            
            \App\Helpers\PerformanceCache::cacheDatabaseQuery('SELECT * FROM products WHERE is_active = 1 AND (is_featured = 1 OR is_bestseller = 1) ORDER BY views DESC LIMIT 12', [], $products, 900);
            return $products;
        } catch (Exception $e) {
            error_log('Error loading popular products: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cached reviews
     */
    private function getCachedReviews()
    {
        $cacheKey = 'reviews_random_6';
        $cached = \App\Helpers\PerformanceCache::getCachedDatabaseQuery('SELECT * FROM reviews WHERE is_approved = 1 ORDER BY RAND() LIMIT 6', []);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $reviews = $this->reviewModel->getRandomReviews(6);
            \App\Helpers\PerformanceCache::cacheDatabaseQuery('SELECT * FROM reviews WHERE is_approved = 1 ORDER BY RAND() LIMIT 6', [], $reviews, 1800);
            return $reviews;
        } catch (Exception $e) {
            error_log('Error loading reviews: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cached blog posts
     */
    private function getCachedBlogPosts()
    {
        $cacheKey = 'blog_featured_6';
        $cached = \App\Helpers\PerformanceCache::getCachedDatabaseQuery('SELECT * FROM blog_posts WHERE is_published = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT 6', []);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $blog_posts = $this->blogModel->getFeaturedPosts(6);
            \App\Helpers\PerformanceCache::cacheDatabaseQuery('SELECT * FROM blog_posts WHERE is_published = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT 6', [], $blog_posts, 1800);
            return $blog_posts;
        } catch (Exception $e) {
            error_log('Error loading blog posts: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cached sellers
     */
    private function getCachedSellers()
    {
        $cacheKey = 'sellers_active_approved_12';
        $cached = \App\Helpers\PerformanceCache::getCachedDatabaseQuery('SELECT id, name, company_name, logo_url, status, is_approved FROM sellers WHERE status = ? AND is_approved = ? AND logo_url IS NOT NULL AND logo_url != ? ORDER BY created_at DESC LIMIT 12', ['active', 1, '']);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $db = \App\Core\Database::getInstance();
            $sellers = $db->query(
                "SELECT id, name, company_name, logo_url, status, is_approved
                 FROM sellers 
                 WHERE status = 'active' 
                 AND is_approved = 1 
                 AND logo_url IS NOT NULL 
                 AND logo_url != ''
                 ORDER BY created_at DESC
                 LIMIT 12"
            )->all();
            
            \App\Helpers\PerformanceCache::cacheDatabaseQuery('SELECT id, name, company_name, logo_url, status, is_approved FROM sellers WHERE status = ? AND is_approved = ? AND logo_url IS NOT NULL AND logo_url != ? ORDER BY created_at DESC LIMIT 12', ['active', 1, ''], $sellers, 1800);
            return $sellers;
        } catch (Exception $e) {
            error_log('Error loading sellers: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add wishlist status to products
     */
    /**
     * Add wishlist status to products - OPTIMIZED to prevent N+1 queries
     */
    private function addWishlistStatus($products)
    {
        if (!Session::has('user_id') || empty($products)) {
            // If user is not logged in or no products, set all products as not in wishlist
            foreach ($products as &$product) {
                $product['in_wishlist'] = false;
            }
            return $products;
        }
        
        $userId = Session::get('user_id');
        
        // OPTIMIZATION: Fetch all wishlist items in one query instead of N queries
        $productIds = array_column($products, 'id');
        if (empty($productIds)) {
            return $products;
        }
        
        // Get all wishlist items for user's products in one query
        $wishlistItems = $this->wishlistModel->getByUserAndProducts($userId, $productIds);
        $wishlistProductIds = array_flip(array_column($wishlistItems, 'product_id'));
        
        // Set wishlist status for each product
        foreach ($products as &$product) {
            $product['in_wishlist'] = isset($wishlistProductIds[$product['id']]);
        }
        
        return $products;
    }
}