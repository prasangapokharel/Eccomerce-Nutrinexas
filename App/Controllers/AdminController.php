<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Order;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\ReferralEarning;
use App\Models\Setting;
use App\Models\Coupon;
use App\Models\KhaltiPayment;
use App\Models\EsewaPayment;
use App\Models\DeliveryCharge;
use App\Helpers\EmailHelper;
use App\Models\Transaction;
use App\Services\OrderCalculationService;
use Exception;

class AdminController extends Controller
{
    protected $db;
    private $productModel;
    private $productImageModel;
    private $orderModel;
    private $userModel;
    private $paymentMethodModel;
    private $orderItemModel;
    private $referralEarningModel;
    private $couponModel;
    private $khaltiPaymentModel;
    private $esewaPaymentModel;
    private $deliveryModel;
    private $reviewModel;
    private $settingModel;
    private $transactionModel;
    private $curiorModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = \App\Core\Database::getInstance();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
        $this->orderModel = new Order();
        $this->userModel = new User();
        $this->paymentMethodModel = new PaymentMethod();
        $this->orderItemModel = new OrderItem();
        $this->referralEarningModel = new ReferralEarning();
        $this->couponModel = new Coupon();
        $this->khaltiPaymentModel = new KhaltiPayment();
        $this->esewaPaymentModel = new EsewaPayment();
        $this->deliveryModel = new DeliveryCharge();
        $this->reviewModel = new Review();
        $this->settingModel = new Setting();
        $this->transactionModel = new Transaction();
        $this->curiorModel = new \App\Models\Curior\Curior();

        // Check if user is admin
        $this->requireAdmin();

        // Set CORS headers for AJAX requests
        $this->setCorsHeaders();
    }

    /**
     * Set CORS headers for AJAX requests
     */
    private function setCorsHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Admin dashboard
     */
    public function index()
    {
        $totalProducts = $this->productModel->getProductCount();
        $totalOrders = $this->orderModel->getOrderCount();
        $totalUsers = $this->userModel->getUserCount();
        $totalSales = $this->orderModel->getTotalSales();
        $totalCoupons = $this->couponModel->getTotalCoupons();
        $activeCoupons = $this->couponModel->getActiveCouponsCount();

        $recentOrders = $this->orderModel->getRecentOrders(5);
        $lowStockProducts = $this->productModel->getLowStockProducts(5);
        $recentCoupons = $this->couponModel->getRecentCoupons(5);

        $this->view('admin/dashboard', [
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalUsers' => $totalUsers,
            'totalSales' => $totalSales,
            'totalCoupons' => $totalCoupons,
            'activeCoupons' => $activeCoupons,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
            'recentCoupons' => $recentCoupons,
            'title' => 'Admin Dashboard'
        ]);
    }

    /**
     * Manage products
     */
    public function products()
    {
        // Apply optional status filter via query param
        $filters = [];
        $status = $_GET['status'] ?? '';
        if (in_array($status, ['active', 'inactive'])) {
            $filters['status'] = $status;
        }

        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Get total count for pagination
        $totalCount = $this->productModel->getDb()->query(
            "SELECT COUNT(*) as count FROM products" . 
            (!empty($filters['status']) ? " WHERE status = ?" : ""),
            !empty($filters['status']) ? [$filters['status']] : []
        )->single()['count'];
        $totalPages = ceil($totalCount / $perPage);

        // Load products with pagination
        $products = $this->productModel->getAllProducts($perPage, $offset, $filters);

        // Add primary image to each product
        foreach ($products as &$product) {
            $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
            $product['primary_image'] = $primaryImage;
            $product['image_count'] = $this->productImageModel->getImageCount($product['id']);
        }

        $this->view('admin/products/index', [
            'products' => $products,
            'title' => 'Manage Products',
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'perPage' => $perPage
        ]);
    }

    /**
     * Add product form
     */
    public function addProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process form
            $data = [
                'product_name' => trim($_POST['product_name'] ?? ''),
                'price' => (float)($_POST['price'] ?? 0),
                'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
                'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
                'description' => trim($_POST['description'] ?? ''),
                'short_description' => trim($_POST['short_description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'subcategory' => trim($_POST['subcategory'] ?? ''),
                'weight' => trim($_POST['weight'] ?? ''),
                'serving' => trim($_POST['serving'] ?? ''),
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'flavor' => trim($_POST['flavor'] ?? ''),
                'material' => trim($_POST['material'] ?? ''),
                'ingredients' => trim($_POST['ingredients'] ?? ''),
                'meta_title' => trim($_POST['meta_title'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'tags' => trim($_POST['tags'] ?? ''),
                'cost_price' => !empty($_POST['cost_price']) ? (float)$_POST['cost_price'] : null,
                'compare_price' => !empty($_POST['compare_price']) ? (float)$_POST['compare_price'] : null,
                'optimal_weight' => trim($_POST['optimal_weight'] ?? ''),
                'serving_size' => trim($_POST['serving_size'] ?? ''),
                'capsule' => isset($_POST['capsule']) ? 1 : 0,
                'status' => trim($_POST['status'] ?? 'active'),
                'commission_rate' => !empty($_POST['commission_rate']) ? (float)$_POST['commission_rate'] : 10.00,
                'seller_commission' => !empty($_POST['seller_commission']) ? (float)$_POST['seller_commission'] : 10.00
            ];

            // Generate unique slug from product name to satisfy NOT NULL constraint
            if (!empty($data['product_name'])) {
                $data['slug'] = $this->productModel->generateSlug($data['product_name']);
            }

            // Validate data
            $errors = [];

            if (empty($data['product_name'])) {
                $errors['product_name'] = 'Product name is required';
            }

            if ($data['price'] <= 0) {
                $errors['price'] = 'Price must be greater than zero';
            }

            if ($data['stock_quantity'] < 0) {
                $errors['stock_quantity'] = 'Stock quantity cannot be negative';
            }

            if ($data['sale_price'] !== null && $data['sale_price'] <= 0) {
                $errors['sale_price'] = 'Sale price must be greater than zero';
            }

            if (empty($data['category'])) {
                $errors['category'] = 'Category is required';
            }

            // Validate sale price vs regular price
            if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price']) {
                $errors['sale_price'] = 'Sale price must be less than regular price';
            }

            // Check if at least one image is provided
            $hasImages = false;
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $hasImages = true;
            }
            if (!empty($_POST['image_urls'])) {
                $imageUrls = array_filter(array_map('trim', explode("\n", $_POST['image_urls'])));
                if (!empty($imageUrls)) {
                    $hasImages = true;
                }
            }

            if (!$hasImages) {
                $errors['images'] = 'At least one product image is required';
            }

            if (empty($errors)) {
                // Map field names for database compatibility
                $productData = $data;
                $productData['subtype'] = $data['subcategory'] ?? null; // Map subcategory to subtype
                
                // Generate slug from product name
                if (!empty($data['product_name'])) {
                    $productData['slug'] = $this->productModel->generateSlug($data['product_name']);
                }
                
                // Add additional fields from POST
                $productData['is_digital'] = isset($_POST['is_digital']) ? 1 : 0;
                $productData['product_type_main'] = trim($_POST['product_type_main'] ?? '');
                $productData['product_type'] = trim($_POST['product_type'] ?? '');
                
                // Process colors - convert comma-separated string to JSON array
                if (!empty($_POST['colors'])) {
                    $colorArray = array_map('trim', explode(',', $_POST['colors']));
                    $productData['colors'] = json_encode($colorArray);
                } else {
                    $productData['colors'] = null;
                }
                
                // Process size_available - convert to JSON if provided
                if (!empty($_POST['size_available'])) {
                    if (is_string($_POST['size_available'])) {
                        $sizeArray = array_map('trim', explode(',', $_POST['size_available']));
                        $productData['size_available'] = json_encode($sizeArray);
                    } else {
                        $productData['size_available'] = json_encode($_POST['size_available']);
                    }
                } else {
                    $productData['size_available'] = null;
                }
                
                // Remove subcategory as we've mapped it to subtype
                unset($productData['subcategory']);
                
                // Process scheduling fields
                $productData['is_scheduled'] = isset($_POST['is_scheduled']) ? 1 : 0;
                if (!empty($_POST['scheduled_date'])) {
                    $productData['scheduled_date'] = date('Y-m-d H:i:s', strtotime($_POST['scheduled_date']));
                } else {
                    $productData['scheduled_date'] = null;
                }
                if (!empty($_POST['scheduled_end_date'])) {
                    $productData['scheduled_end_date'] = date('Y-m-d H:i:s', strtotime($_POST['scheduled_end_date']));
                } else {
                    $productData['scheduled_end_date'] = null;
                }
                if (!empty($_POST['scheduled_duration'])) {
                    $productData['scheduled_duration'] = (int)$_POST['scheduled_duration'];
                } else {
                    $productData['scheduled_duration'] = null;
                }
                if (!empty($_POST['scheduled_message'])) {
                    $productData['scheduled_message'] = trim($_POST['scheduled_message']);
                } else {
                    $productData['scheduled_message'] = null;
                }
                
                // Whitelist to match actual DB columns for products table - using ALL maximum columns
                $allowedFields = [
                    'product_name','slug','description','short_description','price','sale_price',
                    'stock_quantity','category','subtype','weight','serving','is_featured',
                    'is_digital','product_type_main','product_type','colors','flavor','material',
                    'ingredients','size_available','status','commission_rate','seller_commission',
                    'meta_title','meta_description','tags','cost_price','compare_price',
                    'optimal_weight','serving_size','capsule','is_scheduled','scheduled_date',
                    'scheduled_end_date','scheduled_duration','scheduled_message'
                ];
                $filtered = [];
                foreach ($allowedFields as $field) {
                    if (array_key_exists($field, $productData)) {
                        // Normalize empty strings to NULL for nullable fields
                        $nullable = ['description','short_description','sale_price','subtype','weight','serving',
                                    'product_type_main','product_type','colors','flavor','material','ingredients',
                                    'size_available','meta_title','meta_description','tags','cost_price','compare_price',
                                    'optimal_weight','serving_size'];
                        $filtered[$field] = ($productData[$field] === '' && in_array($field, $nullable, true)) ? null : $productData[$field];
                    }
                }

                // Add product with filtered columns only
                $productId = $this->productModel->create($filtered);

                if ($productId) {
                    $this->handleProductImages($productId, true);

                    // Generate sitemaps automatically
                    $this->generateSitemaps();

                    $this->setFlash('success', 'Product added successfully');
                    $this->redirect('admin/products');
                } else {
                    $this->setFlash('error', 'Failed to add product');
                }
            }

            $this->view('admin/products/add', [
                'data' => $data,
                'errors' => $errors,
                'title' => 'Add Product'
            ]);
        } else {
            $this->view('admin/products/add', [
                'title' => 'Add Product'
            ]);
        }
    }

    /**
     * Quick stock update (AJAX)
     */
    public function updateStock()
    {
        // More flexible AJAX check - also accept Content-Type header
        $isAjax = $this->isAjaxRequest() || 
                  (!empty($_SERVER['HTTP_CONTENT_TYPE']) && strpos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) ||
                  (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token'], 403);
            return;
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);

        if ($productId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
            return;
        }

        if ($stockQuantity < 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Stock quantity cannot be negative'], 400);
            return;
        }

        $result = $this->productModel->updateStock($productId, $stockQuantity);

        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Stock updated successfully']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update stock'], 500);
        }
    }

    /**
     * Edit product
     */
    public function editProduct($id = null)
    {
        if (!$id) {
            $this->redirect('admin/products');
        }

        $product = $this->productModel->findWithImages($id);

        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('admin/products');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Process form - ALL maximum fields
            $data = [
                'product_name' => trim($_POST['product_name'] ?? ''),
                'price' => (float)($_POST['price'] ?? 0),
                'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
                'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
                'description' => trim($_POST['description'] ?? ''),
                'short_description' => trim($_POST['short_description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'subcategory' => trim($_POST['subcategory'] ?? ''),
                'weight' => trim($_POST['weight'] ?? ''),
                'serving' => trim($_POST['serving'] ?? ''),
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'is_digital' => isset($_POST['is_digital']) ? 1 : 0,
                'product_type_main' => trim($_POST['product_type_main'] ?? ''),
                'product_type' => trim($_POST['product_type'] ?? ''),
                'colors' => trim($_POST['colors'] ?? ''),
                'flavor' => trim($_POST['flavor'] ?? ''),
                'material' => trim($_POST['material'] ?? ''),
                'ingredients' => trim($_POST['ingredients'] ?? ''),
                'meta_title' => trim($_POST['meta_title'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'tags' => trim($_POST['tags'] ?? ''),
                'cost_price' => !empty($_POST['cost_price']) ? (float)$_POST['cost_price'] : null,
                'compare_price' => !empty($_POST['compare_price']) ? (float)$_POST['compare_price'] : null,
                'optimal_weight' => trim($_POST['optimal_weight'] ?? ''),
                'serving_size' => trim($_POST['serving_size'] ?? ''),
                'capsule' => isset($_POST['capsule']) ? 1 : 0,
                'status' => trim($_POST['status'] ?? 'active'),
                'commission_rate' => !empty($_POST['commission_rate']) ? (float)$_POST['commission_rate'] : 10.00
            ];

            // Process colors - convert comma-separated string to JSON array
            if (!empty($data['colors'])) {
                $colorArray = array_map('trim', explode(',', $data['colors']));
                $data['colors'] = json_encode($colorArray);
            } else {
                $data['colors'] = null;
            }
            
            // Process size_available - convert to JSON if provided
            if (!empty($_POST['size_available'])) {
                $sizeArray = array_map('trim', explode(',', $_POST['size_available']));
                $data['size_available'] = json_encode($sizeArray);
            } else {
                $data['size_available'] = null;
            }

            // Validate data
            $errors = [];

            if (empty($data['product_name'])) {
                $errors['product_name'] = 'Product name is required';
            }

            if ($data['price'] <= 0) {
                $errors['price'] = 'Price must be greater than zero';
            }

            if ($data['stock_quantity'] < 0) {
                $errors['stock_quantity'] = 'Stock quantity cannot be negative';
            }

            if ($data['sale_price'] !== null && $data['sale_price'] <= 0) {
                $errors['sale_price'] = 'Sale price must be greater than zero';
            }

            if (empty($data['category'])) {
                $errors['category'] = 'Category is required';
            }

            // Validate sale price vs regular price
            if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price']) {
                $errors['sale_price'] = 'Sale price must be less than regular price';
            }

            if (empty($errors)) {
                // Map field names for database compatibility
                $productData = $data;
                $productData['subtype'] = $data['subcategory'] ?? null; // Map subcategory to subtype
                
                // Generate slug from product name if not provided
                if (!empty($data['product_name'])) {
                    $productData['slug'] = $this->productModel->generateSlug($data['product_name']);
                }
                
                // Remove subcategory as we've mapped it to subtype
                unset($productData['subcategory']);
                
                // Process scheduling fields
                $productData['is_scheduled'] = isset($_POST['is_scheduled']) ? 1 : 0;
                if (!empty($_POST['scheduled_date'])) {
                    $productData['scheduled_date'] = date('Y-m-d H:i:s', strtotime($_POST['scheduled_date']));
                } else {
                    $productData['scheduled_date'] = null;
                }
                if (!empty($_POST['scheduled_end_date'])) {
                    $productData['scheduled_end_date'] = date('Y-m-d H:i:s', strtotime($_POST['scheduled_end_date']));
                } else {
                    $productData['scheduled_end_date'] = null;
                }
                if (!empty($_POST['scheduled_duration'])) {
                    $productData['scheduled_duration'] = (int)$_POST['scheduled_duration'];
                } else {
                    $productData['scheduled_duration'] = null;
                }
                if (!empty($_POST['scheduled_message'])) {
                    $productData['scheduled_message'] = trim($_POST['scheduled_message']);
                } else {
                    $productData['scheduled_message'] = null;
                }
                
                // Whitelist to match actual DB columns for products table - using ALL maximum columns
                $allowedFields = [
                    'product_name','slug','description','short_description','price','sale_price',
                    'stock_quantity','category','subtype','weight','serving','is_featured',
                    'is_digital','product_type_main','product_type','colors','flavor','material',
                    'ingredients','size_available','status','commission_rate','seller_commission',
                    'meta_title','meta_description','tags','cost_price','compare_price',
                    'optimal_weight','serving_size','capsule','is_scheduled','scheduled_date',
                    'scheduled_end_date','scheduled_duration','scheduled_message'
                ];
                
                $filtered = [];
                foreach ($allowedFields as $field) {
                    if (array_key_exists($field, $productData)) {
                        // Normalize empty strings to NULL for nullable fields
                        $nullable = ['description','short_description','sale_price','subtype','weight','serving',
                                    'product_type_main','product_type','colors','flavor','material','ingredients',
                                    'size_available','meta_title','meta_description','tags','cost_price','compare_price',
                                    'optimal_weight','serving_size'];
                        $filtered[$field] = ($productData[$field] === '' && in_array($field, $nullable, true)) ? null : $productData[$field];
                    }
                }
                
                // Update product with filtered columns only
                $result = $this->productModel->update($id, $filtered);

                if ($result) {
                    // Handle product images if any new ones were uploaded
                    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                        $this->handleProductImages($id, false);
                    }

                    $this->setFlash('success', 'Product updated successfully');
                    $this->redirect('admin/products');
                } else {
                    $this->setFlash('error', 'Failed to update product');
                }
            }

            $this->view('admin/products/edit', [
                'product' => $product,
                'data' => $data,
                'errors' => $errors,
                'title' => 'Edit Product'
            ]);
        } else {
            $this->view('admin/products/edit', [
                'product' => $product,
                'errors' => [],
                'title' => 'Edit Product'
            ]);
        }
    }

    /**
     * Update product (separate method for form submission)
     */
    public function updateProduct($id = null)
    {
        if (!$id) {
            $this->redirect('admin/products');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/editProduct/' . $id);
        }

        $product = $this->productModel->findWithImages($id);

        if (!$product) {
            $this->redirect('admin/products');
        }

        // Process form data - ALL maximum fields
        $data = [
            'product_name' => trim($_POST['product_name'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0),
            'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
            'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'subcategory' => trim($_POST['subcategory'] ?? ''),
            'weight' => trim($_POST['weight'] ?? ''),
            'serving' => trim($_POST['serving'] ?? ''),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'flavor' => trim($_POST['flavor'] ?? ''),
            'material' => trim($_POST['material'] ?? ''),
            'ingredients' => trim($_POST['ingredients'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'tags' => trim($_POST['tags'] ?? ''),
            'cost_price' => !empty($_POST['cost_price']) ? (float)$_POST['cost_price'] : null,
            'compare_price' => !empty($_POST['compare_price']) ? (float)$_POST['compare_price'] : null,
            'optimal_weight' => trim($_POST['optimal_weight'] ?? ''),
            'serving_size' => trim($_POST['serving_size'] ?? ''),
            'capsule' => isset($_POST['capsule']) ? 1 : 0,
            'status' => trim($_POST['status'] ?? 'active'),
            'commission_rate' => !empty($_POST['commission_rate']) ? (float)$_POST['commission_rate'] : 10.00,
            'is_digital' => isset($_POST['is_digital']) ? 1 : 0,
            'product_type_main' => trim($_POST['product_type_main'] ?? ''),
            'product_type' => trim($_POST['product_type'] ?? ''),
            'colors' => trim($_POST['colors'] ?? '')
        ];
        
        // Process colors - convert comma-separated string to JSON array
        if (!empty($data['colors'])) {
            $colorArray = array_map('trim', explode(',', $data['colors']));
            $data['colors'] = json_encode($colorArray);
        } else {
            $data['colors'] = null;
        }
        
        // Process size_available - convert to JSON if provided
        if (!empty($_POST['size_available'])) {
            $sizeArray = array_map('trim', explode(',', $_POST['size_available']));
            $data['size_available'] = json_encode($sizeArray);
        } else {
            $data['size_available'] = null;
        }

        // Validate data
        $errors = [];

        if (empty($data['product_name'])) {
            $errors['product_name'] = 'Product name is required';
        }

        if ($data['price'] <= 0) {
            $errors['price'] = 'Price must be greater than zero';
        }

        if ($data['stock_quantity'] < 0) {
            $errors['stock_quantity'] = 'Stock quantity cannot be negative';
        }

        if ($data['sale_price'] !== null && $data['sale_price'] <= 0) {
            $errors['sale_price'] = 'Sale price must be greater than zero';
        }

        if (empty($data['category'])) {
            $errors['category'] = 'Category is required';
        }

        // Validate sale price vs regular price
        if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price']) {
            $errors['sale_price'] = 'Sale price must be less than regular price';
        }

        if (empty($errors)) {
            // Map field names for database compatibility
            $productData = $data;
            $productData['subtype'] = $data['subcategory'] ?? null; // Map subcategory to subtype
            
            // Generate slug from product name if not provided
            if (!empty($data['product_name'])) {
                $productData['slug'] = $this->productModel->generateSlug($data['product_name']);
            }
            
            // Remove subcategory as we've mapped it to subtype
            unset($productData['subcategory']);
            
            // Whitelist to match actual DB columns for products table - using ALL maximum columns
            $allowedFields = [
                'product_name','slug','description','short_description','price','sale_price',
                'stock_quantity','category','subtype','weight','serving','is_featured',
                'is_digital','product_type_main','product_type','colors','flavor','material',
                'ingredients','size_available','status','commission_rate','seller_commission',
                'meta_title','meta_description','tags','cost_price','compare_price',
                'optimal_weight','serving_size','capsule'
            ];
            
            $filtered = [];
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $productData)) {
                    // Normalize empty strings to NULL for nullable fields
                    $nullable = ['description','short_description','sale_price','subtype','weight','serving',
                                'product_type_main','product_type','colors','flavor','material','ingredients',
                                'size_available','meta_title','meta_description','tags','cost_price','compare_price',
                                'optimal_weight','serving_size'];
                    $filtered[$field] = ($productData[$field] === '' && in_array($field, $nullable, true)) ? null : $productData[$field];
                }
            }
            
            // Update product with filtered columns only
            $result = $this->productModel->update($id, $filtered);

                            if ($result) {
                    // Handle product images
                    $this->handleProductImages($id, false);

                    // Generate sitemaps automatically
                    $this->generateSitemaps();

                    $this->setFlash('success', 'Product updated successfully');
                    $this->redirect('admin/products');
                } else {
                    $this->setFlash('error', 'Failed to update product');
                    $this->redirect('admin/editProduct/' . $id);
                }
        } else {
            // Redirect back to edit form with errors
            $this->setFlash('error', 'Please fix the errors below');
            $this->redirect('admin/editProduct/' . $id);
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct($id = null)
    {
        if (!$id) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Product ID is required'], 400);
            } else {
                $this->redirect('admin/products');
            }
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Check if product exists
                $product = $this->productModel->find($id);
                if (!$product) {
                    $this->jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
                    return;
                }

                // Check if product is referenced in orders (for warning message only)
                $db = $this->productModel->getDb();
                $orderCheck = $db->query("SELECT COUNT(*) AS cnt FROM order_items WHERE product_id = ?", [$id])->single();
                $orderCount = is_array($orderCheck) && isset($orderCheck['cnt']) ? (int)$orderCheck['cnt'] : 0;

                $warningMessage = '';
                if ($orderCount > 0) {
                    $warningMessage = ' Note: This product was referenced in ' . $orderCount . ' order(s) and will be removed from order history.';
                }

                // Delete product images first
                $db->query("DELETE FROM product_images WHERE product_id = ?", [$id])->execute();
                
                // Delete product variants if any
                $db->query("DELETE FROM product_variants WHERE product_id = ?", [$id])->execute();
                
                // Delete product
                // Note: Database CASCADE will automatically delete order_items when product is deleted
                $result = $this->productModel->deleteProduct($id);

                if ($result) {
                    $message = 'Product deleted successfully' . $warningMessage;
                    $this->jsonResponse(['success' => true, 'message' => $message]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to delete product'], 500);
                }
            } catch (Exception $e) {
                error_log('Delete product error: ' . $e->getMessage());
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while deleting the product'], 500);
            }
        } else {
            $this->redirect('admin/products');
        }
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Bulk delete products
     */
    public function bulkDeleteProducts()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/products');
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? $_POST['ids'] ?? [];
        
        if (!is_array($ids) || empty($ids)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'No products selected for deletion'], 400);
            } else {
                $this->setFlash('error', 'No products selected for deletion');
                $this->redirect('admin/products');
            }
            return;
        }

        try {
            $bulkService = new \App\Services\BulkActionService();
            
            // Delete product images first
            $db = $this->productModel->getDb();
            foreach ($ids as $id) {
                $id = (int)$id;
                if ($id > 0) {
                    try {
                        $db->query("DELETE FROM product_images WHERE product_id = ?", [$id])->execute();
                        $db->query("DELETE FROM product_variants WHERE product_id = ?", [$id])->execute();
                    } catch (\Exception $e) {
                        error_log('Bulk delete product relations error: ' . $e->getMessage());
                    }
                }
            }
            
            // Delete products using BulkActionService
            $result = $bulkService->bulkDelete(\App\Models\Product::class, $ids);

            if ($result['success']) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $result['message'],
                        'deleted_count' => $result['count']
                    ]);
                } else {
                    $this->setFlash('success', $result['message']);
                    $this->redirect('admin/products');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => $result['message']], 400);
                } else {
                    $this->setFlash('error', $result['message']);
                    $this->redirect('admin/products');
                }
            }
        } catch (Exception $e) {
            error_log('Bulk delete products error: ' . $e->getMessage());
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while deleting products'], 500);
            } else {
                $this->setFlash('error', 'An error occurred while deleting products');
                $this->redirect('admin/products');
            }
        }
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Delete product image with enhanced error handling
     */
    public function deleteProductImage($imageId = null)
    {
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

        try {
            if (!$imageId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid request method or missing image ID']);
                exit;
            }

            // Validate image ID
            if (!is_numeric($imageId) || $imageId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
                exit;
            }

            $image = $this->productImageModel->find($imageId);

            if (!$image) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Image not found']);
                exit;
            }

            // Check if this is the only image for the product
            $imageCount = $this->productImageModel->getImageCount($image['product_id']);

            if ($imageCount <= 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete the only image. Product must have at least one image.']);
                exit;
            }

            // Delete the image
            $result = $this->productImageModel->deleteImage($imageId);

            if ($result) {
                // If deleted image was primary, set another image as primary
                if ($image['is_primary']) {
                    $remainingImages = $this->productImageModel->getByProductId($image['product_id']);
                    if (!empty($remainingImages)) {
                        $this->productImageModel->updateImage($remainingImages[0]['id'], ['is_primary' => 1]);
                    }
                }

                // Delete physical file if it's not a URL
                if (!filter_var($image['image_url'], FILTER_VALIDATE_URL)) {
                    $filePath = 'uploads/images/' . $image['image_url'];
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }

                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete image from database']);
            }

        } catch (Exception $e) {
            error_log('Delete image error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
        }
        exit;
    }

    /**
     * Set primary image with enhanced error handling
     */
    public function setPrimaryImage($imageId = null)
    {
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

        try {
            if (!$imageId || $_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid request method or missing image ID']);
                exit;
            }

            // Validate image ID
            if (!is_numeric($imageId) || $imageId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
                exit;
            }

            $image = $this->productImageModel->find($imageId);

            if (!$image) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Image not found']);
                exit;
            }

            // Check if image is already primary
            if ($image['is_primary']) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Image is already set as primary']);
                exit;
            }

            $result = $this->productImageModel->setPrimaryImage($image['product_id'], $imageId);

            if ($result) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Primary image updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update primary image']);
            }

        } catch (Exception $e) {
            error_log('Set primary image error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
        }
        exit;
    }

    /**
     * Generate sitemaps automatically
     */
    private function generateSitemaps()
    {
        try {
            $seoController = new \App\Controllers\SeoController();
            $seoController->generateAllSitemaps();
        } catch (\Exception $e) {
            error_log('Error generating sitemaps: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle product images (file uploads and URLs) with primary image selection
     */
    private function handleProductImages($productId, $isNewProduct = false)
    {
        $uploadDir = 'uploads/images/';

        // Ensure upload directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $imageUrls = [];
        $uploadedFiles = [];
        $primaryImageUrl = null;

        // Handle file uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $files = $_FILES['images'];
            $fileCount = count($files['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileName = $this->generateUniqueFileName($files['name'][$i]);
                    $uploadPath = $uploadDir . $fileName;

                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $fileType = mime_content_type($files['tmp_name'][$i]);

                    if (in_array($fileType, $allowedTypes)) {
                        // Validate file size (max 5MB)
                        if ($files['size'][$i] <= 5 * 1024 * 1024) {
                            if (move_uploaded_file($files['tmp_name'][$i], $uploadPath)) {
                                $uploadedFiles[] = $fileName;
                                $imageUrls[] = $fileName;

                                // First uploaded file is primary for file uploads
                                if ($i === 0) {
                                    $primaryImageUrl = $fileName;
                                }
                            }
                        }
                    }
                }
            }
        }
        // Handle CDN/External URLs
        if (!empty($_POST['image_urls'])) {
            $urls = array_filter(array_map('trim', explode("\n", $_POST['image_urls'])));
            $selectedPrimaryUrl = trim($_POST['primary_image_url'] ?? '');

            foreach ($urls as $url) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    // Validate if URL is accessible (skip validation for external URLs to avoid timeout)
                        $imageUrls[] = $url;

                        // Set primary image based on user selection
                        if ($url === $selectedPrimaryUrl) {
                            $primaryImageUrl = $url;
                    }
                }
            }

            // If no primary was selected or invalid, use first URL
            if (!$primaryImageUrl && !empty($imageUrls)) {
                $primaryImageUrl = $imageUrls[0];
            }
        }
        
        // Default images for Accessories if no images provided
        if (empty($imageUrls) && !empty($uploadedFiles)) {
            // Use uploaded files
        } elseif (empty($imageUrls) && empty($uploadedFiles)) {
            // Check if product type is Accessories and use default images
            $product = $this->productModel->find($productId);
            if ($product && ($product['product_type_main'] === 'Accessories' || $product['category'] === 'Accessories')) {
                $defaultImages = [
                    'https://apparel.goldsgym.com/media/image/38/4b/0b/Vorschauq2YVQ1o02K49b_1142x1142@2x.jpg',
                    'https://apparel.goldsgym.com/media/image/a0/c2/02/221205_GG_Classic_MEN_Heavy_Weight_Classic_Joe_Grau-1446_1142x1142@2x.jpg',
                    'https://apparel.goldsgym.com/media/image/68/b3/fd/221205_GG_Classic_MEN_Heavy_Weight_Classic_Joe_Grau-1441_1142x1142@2x.jpg'
                ];
                $imageUrls = $defaultImages;
                $primaryImageUrl = $defaultImages[0];
            }
        }

        // Add images to database
        foreach ($imageUrls as $index => $imageUrl) {
            $isPrimary = ($imageUrl === $primaryImageUrl);
            $this->productImageModel->addImage($productId, $imageUrl, $isPrimary, $index);
        }

        // Handle primary image selection for existing products
        if (!$isNewProduct && isset($_POST['primary_image_id'])) {
            $primaryImageId = (int)$_POST['primary_image_id'];
            if ($primaryImageId > 0) {
                $this->productImageModel->setPrimaryImage($productId, $primaryImageId);
            }
        }
    }

    /**
     * Generate unique filename for uploaded images
     */
    private function generateUniqueFileName($originalName)
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '', $baseName);

        return time() . '_' . uniqid() . '_' . $baseName . '.' . $extension;
    }

    /**
     * Manage orders
     */
    public function orders()
    {
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countSql = "SELECT COUNT(*) as count FROM orders";
        $countParams = [];
        if ($status) {
            $countSql .= " WHERE status = ?";
            $countParams[] = $status;
        }
        $totalCount = $this->orderModel->getDb()->query($countSql, $countParams)->single()['count'];
        $totalPages = ceil($totalCount / $perPage);

        // Get orders with pagination
        $allOrders = $this->orderModel->getAllOrders($status);
        $orders = array_slice($allOrders, $offset, $perPage);

        foreach ($orders as &$order) {
            $subtotal = $this->orderItemModel->getSubtotalByOrderId($order['id']);
            $discount = $order['discount_amount'] ?? 0;
            $tax = $order['tax_amount'] ?? 0;
            $delivery = $order['delivery_fee'] ?? 0;
            $order['calculated_total'] = $subtotal - $discount + $tax + $delivery;
        }
        unset($order);

        $this->view('admin/orders/index', [
            'orders' => $orders,
            'status' => $status,
            'title' => 'Manage Orders',
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'perPage' => $perPage
        ]);
    }

    

    /**
     * View order details
     */
    public function viewOrder($id = null)
    {
        if (!$id) {
            $this->redirect('admin/orders');
        }

        $order = $this->orderModel->getOrderById($id);

        if (!$order) {
            $this->redirect('admin/orders');
        }

        $orderItems = $this->orderItemModel->getByOrderId($id);
        
        // Get curiors for assignment
        $curiors = $this->curiorModel->getAllCuriors();
        
        // Get assigned curior info if exists
        $assignedCurior = null;
        if (!empty($order['curior_id'])) {
            $assignedCurior = $this->curiorModel->getById($order['curior_id']);
        }

        $this->view('admin/orders/view', [
            'order' => $order,
            'orderItems' => $orderItems,
            'curiors' => $curiors,
            'assignedCurior' => $assignedCurior,
            'title' => 'Order Details'
        ]);
    }

    /**
     * Update order status with referral processing and email notifications
     */
    public function updateOrderStatus($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/orders');
        }

        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['paid', 'unpaid', 'cancelled', 'processing', 'shipped', 'delivered'])) {
            $this->setFlash('error', 'Invalid status');
            $this->redirect('admin/orders');
        }

        $order = $this->orderModel->getOrderById($id);
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('admin/orders');
        }

        try {
            $oldStatus = $order['status'] ?? 'pending';
            
            // Update order status using the existing method
            $result = $this->orderModel->updateOrderStatus($id, $status);

            if ($result) {
                // Update payment status based on payment method
                $this->updatePaymentStatus($order, $status);

                // Stock management: Reduce stock when order is confirmed/paid
                if (in_array($status, ['paid', 'processing', 'confirmed']) && !in_array($oldStatus, ['paid', 'processing', 'confirmed'])) {
                    $this->reduceOrderStock($order['id']);
                }
                
                // Stock management: Restore stock when order is cancelled
                if ($status === 'cancelled' && !in_array($oldStatus, ['cancelled'])) {
                    $this->restoreOrderStock($order['id']);
                }

                // Referral earnings handling
                $referralService = new \App\Services\ReferralEarningService();

                // 1) If delivered now, mark earning as paid (or create paid if missing)
                if ($status === 'delivered' && $oldStatus !== 'delivered') {
                    // Set delivered_at timestamp
                    $this->db->query(
                        "UPDATE orders SET delivered_at = NOW() WHERE id = ?",
                        [$order['id']]
                    )->execute();
                    
                    $referralService->processReferralEarning($order['id']);
                    
                    // NOTE: Seller balance release is NOT triggered when admin sets status to delivered
                    // Balance release only happens when courier marks order as delivered (via processPostDelivery)
                    // This ensures balance is only released after actual delivery, not just admin status change
                }

                // 2) If moved to an in-progress state, ensure a pending earning exists once per order
                if (in_array($status, ['paid', 'processing', 'shipped'], true) && $oldStatus !== $status) {
                    $referralService->createPendingReferralEarning($order['id']);
                }

                // 3) If cancelled now, cancel any existing earning and reverse balance if needed
                if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                    $referralService->cancelReferralEarning($order['id']);
                    
                    // Notify sellers about cancellation with fund info
                    try {
                        $sellerNotificationService = new \App\Services\SellerNotificationService();
                        $sellerNotificationService->notifyOrderCancelled($order['id']);
                    } catch (\Exception $e) {
                        error_log('Order cancellation notification error: ' . $e->getMessage());
                    }
                }

                // Send SMS notification for order status change
                if ($oldStatus !== $status) {
                    try {
                        $notificationService = new \App\Services\OrderNotificationService();
                        $smsResult = $notificationService->sendStatusChangeSMS($id, $oldStatus, $status);
                        
                        if (!$smsResult['success']) {
                            error_log("SMS notification failed for order #{$id}: " . ($smsResult['message'] ?? 'Unknown error'));
                        }
                    } catch (\Exception $smsException) {
                        error_log("SMS notification exception for order #{$id}: " . $smsException->getMessage());
                    }
                    
                    // Notify seller about order status change
                    try {
                        $sellerNotificationService = new \App\Services\SellerNotificationService();
                        $sellerNotificationService->notifyOrderStatusChange($id, $oldStatus, $status);
                    } catch (Exception $sellerNotifException) {
                        error_log("Seller notification error for order #{$id}: " . $sellerNotifException->getMessage());
                        // Don't fail the order update if seller notification fails
                    }
                }

                $this->setFlash('success', 'Order status updated successfully' . (isset($smsResult) && $smsResult['success'] ? ' and SMS sent' : ''));
            } else {
                $this->setFlash('error', 'Failed to update order status');
            }
        } catch (Exception $e) {
            error_log('Update order status error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating order status: ' . $e->getMessage());
        }

        $this->redirect('admin/orders');
    }

    /**
     * Update payment status based on payment method
     */
    private function updatePaymentStatus($order, $status)
    {
        try {
            $paymentMethodId = $order['payment_method_id'];

            // Convert order status to payment status
            $paymentStatus = $this->getPaymentStatusFromOrderStatus($status);

            switch ($paymentMethodId) {
                case 2: // Khalti (assuming ID 2 for Khalti)
                    $this->khaltiPaymentModel->updateStatusByOrderId($order['id'], $paymentStatus);
                    break;

                case 3: // eSewa (assuming ID 3 for eSewa)
                    $this->esewaPaymentModel->updateStatusByOrderId($order['id'], $paymentStatus);
                    break;

                case 1: // Cash on Delivery (assuming ID 1 for COD)
                case 4: // Bank Transfer (assuming ID 4 for Bank Transfer)
                default:
                    // For COD and Bank Transfer, payment info is stored in orders table
                    // No additional payment table update needed
                    break;
            }

            error_log("Payment status updated for order {$order['id']} with payment method {$paymentMethodId}");

        } catch (Exception $e) {
            error_log('Error updating payment status: ' . $e->getMessage());
        }
    }

    /**
     * Convert order status to payment status
     */
    private function getPaymentStatusFromOrderStatus($orderStatus)
    {
        switch ($orderStatus) {
            case 'paid':
                return 'completed';
            case 'cancelled':
                return 'failed';
            case 'pending':
            case 'processing':
            case 'shipped':
            case 'delivered':
            default:
                return 'pending';
        }
    }

    /**
     * Update payment status directly (public method for admin)
     */
    public function updateOrderPaymentStatus($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
                return;
            }
            $this->setFlash('error', 'Invalid request');
            $this->redirect('admin/orders');
            return;
        }

        if (!isset($_POST['_csrf_token']) || !\App\Helpers\SecurityHelper::validateCSRF($_POST['_csrf_token'])) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid security token'], 403);
                return;
            }
            $this->setFlash('error', 'Invalid security token');
            $this->redirect('admin/orders/view/' . $id);
            return;
        }

        $paymentStatus = $_POST['payment_status'] ?? '';

        // Database enum values: 'pending','paid','failed','refunded'
        $validStatuses = ['pending', 'paid', 'failed', 'refunded'];
        if (!in_array($paymentStatus, $validStatuses)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid payment status'], 400);
                return;
            }
            $this->setFlash('error', 'Invalid payment status');
            $this->redirect('admin/orders/view/' . $id);
            return;
        }

        $order = $this->orderModel->getOrderById($id);
        if (!$order) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
                return;
            }
            $this->setFlash('error', 'Order not found');
            $this->redirect('admin/orders');
            return;
        }

        try {
            $oldPaymentStatus = $order['payment_status'] ?? 'pending';
            
            $result = $this->orderModel->update($id, [
                'payment_status' => $paymentStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                // Also update payment gateway tables if applicable
                $this->updatePaymentGatewayStatus($order, $paymentStatus);
                
                // Send SMS notification for payment status change
                if ($oldPaymentStatus !== $paymentStatus) {
                    try {
                        $notificationService = new \App\Services\OrderNotificationService();
                        $smsResult = $notificationService->sendPaymentStatusChangeSMS($id, $oldPaymentStatus, $paymentStatus);
                        
                        if ($smsResult['success']) {
                            error_log("SMS notification sent successfully for payment status change on order #{$id}");
                        } else {
                            error_log("SMS notification failed for payment status change on order #{$id}: " . ($smsResult['message'] ?? 'Unknown error'));
                        }
                    } catch (Exception $smsException) {
                        error_log("SMS notification error for payment status change on order #{$id}: " . $smsException->getMessage());
                        // Don't fail the payment update if SMS fails
                    }
                    
                    // If payment status is set to "paid" and order is already delivered, trigger balance release
                    // This represents admin verifying payment/delivery (admin auto-verifies or manually verifies)
                    // Balance release will only proceed if:
                    // - Order status is "delivered" (courier must have marked it first)
                    // - Wait period (24 hours) has passed
                    // - No active return/refund exists
                    if ($paymentStatus === 'paid' && $order['status'] === 'delivered') {
                        try {
                            $sellerBalanceService = new \App\Services\SellerBalanceService();
                            $balanceResult = $sellerBalanceService->processBalanceRelease($id);
                            
                            if ($balanceResult['success']) {
                                error_log("Seller balance released for order #{$id} after admin payment verification: रु " . ($balanceResult['total_released'] ?? 0));
                            } else {
                                error_log("Seller balance release pending for order #{$id} after admin payment verification: " . ($balanceResult['message'] ?? 'Wait period not complete'));
                            }
                        } catch (\Exception $balanceException) {
                            error_log("Seller balance service error for order #{$id} after admin payment verification: " . $balanceException->getMessage());
                        }
                    }
                }
                
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => true, 
                        'message' => 'Payment status updated successfully',
                        'payment_status' => $paymentStatus,
                        'payment_status_display' => ucfirst($paymentStatus)
                    ]);
                    return;
                }
                
                $this->setFlash('success', 'Payment status updated successfully');
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to update payment status'], 500);
                    return;
                }
                $this->setFlash('error', 'Failed to update payment status');
            }
        } catch (Exception $e) {
            error_log('Error updating payment status: ' . $e->getMessage());
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while updating payment status'], 500);
                return;
            }
            $this->setFlash('error', 'An error occurred while updating payment status');
        }

        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/orders/view/' . $id);
        }
    }

    /**
     * Update payment gateway status tables
     */
    private function updatePaymentGatewayStatus($order, $paymentStatus)
    {
        try {
            $paymentMethodId = $order['payment_method_id'] ?? null;
            
            // Map payment_status to gateway status format
            // Gateway tables might use 'completed' instead of 'paid'
            $gatewayStatus = $paymentStatus;
            if ($paymentStatus === 'paid') {
                $gatewayStatus = 'completed';
            }

            switch ($paymentMethodId) {
                case 2: // Khalti
                    if (isset($this->khaltiPaymentModel)) {
                        $this->khaltiPaymentModel->updateStatusByOrderId($order['id'], $gatewayStatus);
                    }
                    break;

                case 3: // eSewa
                    if (isset($this->esewaPaymentModel)) {
                        $this->esewaPaymentModel->updateStatusByOrderId($order['id'], $gatewayStatus);
                    }
                    break;

                default:
                    // For COD and Bank Transfer, payment info is stored in orders table only
                    break;
            }
        } catch (Exception $e) {
            error_log('Error updating payment gateway status: ' . $e->getMessage());
        }
    }

    // Referral processing is centralized in App\Services\ReferralEarningService.
    // Controller should call that service from `updateOrderStatus` instead of using a duplicate implementation here.

    /**
     * Cancel referral earning for an order
     */
    private function cancelReferralEarning($order)
    {
        try {
            // Find existing referral earning for this order
            $earning = $this->referralEarningModel->findByOrderId($order['id']);

            if (!$earning || $earning['status'] === 'cancelled') {
                return; // No earning to cancel
            }

            // Update earning status to cancelled
            $this->referralEarningModel->update($earning['id'], ['status' => 'cancelled']);

            // Deduct from user's referral balance
            $this->userModel->deductReferralEarnings($earning['user_id'], $earning['amount']);

            error_log("Referral earning cancelled: User {$earning['user_id']} lost {$earning['amount']} from order {$order['id']}");

        } catch (Exception $e) {
            error_log('Error cancelling referral earning: ' . $e->getMessage());
        }
    }

    /**
     * Manage users
     */
    public function users()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 20;
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $totalUsers = $this->userModel->getDb()->query("SELECT COUNT(*) as count FROM users")->single();
        $total = (int)($totalUsers['count'] ?? 0);
        
        // Get users with pagination
        $users = $this->userModel->getDb()->query(
            "SELECT u.*, u.sponsor_status 
             FROM users u 
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        )->all();
        
        // Format users for table
        $tableData = [];
        foreach ($users as $user) {
            $userModel = new \App\Models\User();
            $referralCount = $userModel->getReferralCount($user['id']);
            
            $tableData[] = [
                'id' => $user['id'],
                'user' => [
                    'name' => ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''),
                    'id' => $user['id'],
                    'username' => $user['username'] ?? '',
                    'image' => !empty($user['profile_image']) ? ASSETS_URL . '/profileimage/' . $user['profile_image'] : null,
                    'sponsor_status' => $user['sponsor_status'] ?? 'inactive'
                ],
                'contact' => [
                    'email' => $user['email'] ?? '',
                    'phone' => $user['phone'] ?? ''
                ],
                'role' => $user['role'] ?? 'customer',
                'status' => $user['status'] ?? 'active',
                'referrals' => $referralCount,
                'earnings' => $user['referral_earnings'] ?? 0,
                'joined' => $user['created_at'] ?? ''
            ];
        }

        $this->view('admin/users/index', [
            'users' => $users,
            'tableData' => $tableData,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage),
            'total' => $total,
            'perPage' => $perPage,
            'title' => 'Manage Users'
        ]);
    }

    /**
     * Edit user form
     */
    public function editUser($id = null)
    {
        if (!$id) {
            $this->redirect('admin/users');
            return;
        }

        $this->requireAdmin();

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->setFlash('error', 'User not found');
            $this->redirect('admin/users');
            return;
        }

        $this->view('admin/users/edit', [
            'user' => $user,
            'title' => 'Edit User'
        ]);
    }

    /**
     * View user details
     */
    public function viewUser($id = null)
    {
        if (!$id) {
            $this->redirect('admin/users');
        }

        $user = $this->userModel->find($id);

        if (!$user) {
            $this->redirect('admin/users');
        }

        $orders = $this->orderModel->getOrdersByUserId($id);
        $referrals = $this->userModel->getReferrals($id);
        $referralEarnings = $this->referralEarningModel->getByUserIdWithFullDetails($id);

        $this->view('admin/users/view', [
            'user' => $user,
            'orders' => $orders,
            'referrals' => $referrals,
            'referralEarnings' => $referralEarnings,
            'title' => 'User Details'
        ]);
    }

    /**
     * Delete a user
     */
    public function deleteUser($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            } else {
                $this->redirect('admin/users');
            }
            return;
        }

        $this->requireAdmin();

        $user = $this->userModel->find($id);
        if (!$user) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'User not found'], 404);
            } else {
                $this->setFlash('error', 'User not found');
                $this->redirect('admin/users');
            }
            return;
        }

        // Prevent admin from deleting themselves
        if ($user['id'] == $_SESSION['user_id']) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'You cannot delete your own account'], 400);
            } else {
                $this->setFlash('error', 'You cannot delete your own account');
                $this->redirect('admin/users');
            }
            return;
        }

        try {
            $result = $this->userModel->delete($id);
            
            if ($result) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => true, 'message' => 'User deleted successfully']);
                } else {
                    $this->setFlash('success', 'User deleted successfully');
                    $this->redirect('admin/users');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to delete user'], 500);
                } else {
                    $this->setFlash('error', 'Failed to delete user');
                    $this->redirect('admin/users');
                }
            }
        } catch (Exception $e) {
            error_log('Error deleting user: ' . $e->getMessage());
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while deleting the user'], 500);
            } else {
                $this->setFlash('error', 'An error occurred while deleting the user');
                $this->redirect('admin/users');
            }
        }
    }

    /**
     * Update user status (activate/deactivate/suspend)
     */
    public function updateUserStatus($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/users');
            return;
        }

        $this->requireAdmin();

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->setFlash('error', 'User not found');
            $this->redirect('admin/users');
            return;
        }

        // Prevent admin from deactivating themselves
        if ($user['id'] == $_SESSION['user_id']) {
            $this->setFlash('error', 'You cannot deactivate your own account');
            $this->redirect('admin/users');
            return;
        }

        $status = $_POST['status'] ?? '';
        $reason = $_POST['reason'] ?? '';

        // Validate status
        $allowedStatuses = ['active', 'inactive', 'suspended'];
        if (!in_array($status, $allowedStatuses)) {
            $this->setFlash('error', 'Invalid status');
            $this->redirect('admin/users');
            return;
        }

        // Update user status
        $updateData = ['status' => $status];
        
        // Add suspension reason if provided
        if ($status === 'suspended' && !empty($reason)) {
            $updateData['suspension_reason'] = $reason;
            $updateData['suspended_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'active') {
            // Clear suspension data when reactivating
            $updateData['suspension_reason'] = null;
            $updateData['suspended_at'] = null;
        }

        $result = $this->userModel->update($id, $updateData);

        if ($result) {
            // Revoke all persistent tokens for deactivated/suspended users
            if ($status === 'inactive' || $status === 'suspended') {
                \App\Helpers\SessionRecoveryHelper::revokeAllTokens($id);
            }

            $statusMessages = [
                'active' => 'User activated successfully',
                'inactive' => 'User deactivated successfully',
                'suspended' => 'User suspended successfully'
            ];

            $this->setFlash('success', $statusMessages[$status]);
        } else {
            $this->setFlash('error', 'Failed to update user status');
        }

        $this->redirect('admin/users');
    }

    /**
     * Update user (edit form submission)
     */
    public function updateUser($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/users');
            return;
        }

        $this->requireAdmin();

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->setFlash('error', 'User not found');
            $this->redirect('admin/users');
            return;
        }

        $updateData = [];
        
        if (isset($_POST['first_name'])) {
            $updateData['first_name'] = $_POST['first_name'];
        }
        if (isset($_POST['last_name'])) {
            $updateData['last_name'] = $_POST['last_name'];
        }
        if (isset($_POST['email'])) {
            $updateData['email'] = $_POST['email'];
        }
        if (isset($_POST['phone'])) {
            $updateData['phone'] = $_POST['phone'];
        }
        if (isset($_POST['username'])) {
            $updateData['username'] = $_POST['username'];
        }
        if (isset($_POST['role'])) {
            $updateData['role'] = $_POST['role'];
        }
        if (isset($_POST['status'])) {
            $updateData['status'] = $_POST['status'];
        }

        if (empty($updateData)) {
            $this->setFlash('error', 'No data to update');
            $this->redirect('admin/editUser/' . $id);
            return;
        }

        $result = $this->userModel->update($id, $updateData);

        if ($result) {
            $this->setFlash('success', 'User updated successfully');
            $this->redirect('admin/users');
        } else {
            $this->setFlash('error', 'Failed to update user');
            $this->redirect('admin/editUser/' . $id);
        }
    }

    /**
     * Update user role
     */
    public function updateUserRole($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/users');
        }

        $role = $_POST['role'] ?? '';

        if (!in_array($role, ['admin', 'customer'])) {
            $this->setFlash('error', 'Invalid role');
            $this->redirect('admin/users');
        }

        $result = $this->userModel->update($id, ['role' => $role]);

        if ($result) {
            $this->setFlash('success', 'User role updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update user role');
        }

        $this->redirect('admin/users');
    }

    /**
     * Manage referrals
     */
    public function referrals()
    {
        $referralEarnings = $this->referralEarningModel->getAllWithDetails();

        $this->view('admin/referrals/index', [
            'referralEarnings' => $referralEarnings,
            'title' => 'Manage Referrals'
        ]);
    }

    /**
     * Update referral earning status
     */
    public function updateReferralStatus($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/referrals');
        }

        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['pending', 'paid', 'cancelled'])) {
            $this->setFlash('error', 'Invalid status');
            $this->redirect('admin/referrals');
        }

        $earning = $this->referralEarningModel->find($id);

        if (!$earning) {
            $this->setFlash('error', 'Referral earning not found');
            $this->redirect('admin/referrals');
            return;
        }

        // If cancelling a previously pending/paid earning, adjust user balance
        if ($status === 'cancelled' && $earning['status'] !== 'cancelled') {
            $user = $this->userModel->find($earning['user_id']);
            if ($user) {
                $newBalance = max(0, ($user['referral_earnings'] ?? 0) - $earning['amount']);
                $this->userModel->update($earning['user_id'], ['referral_earnings' => $newBalance]);
            }
        }

        // If marking as paid a previously cancelled earning, adjust user balance
        if ($status === 'paid' && $earning['status'] === 'cancelled') {
            $user = $this->userModel->find($earning['user_id']);
            if ($user) {
                $newBalance = ($user['referral_earnings'] ?? 0) + $earning['amount'];
                $this->userModel->update($earning['user_id'], ['referral_earnings' => $newBalance]);
            }
        }

        $data = [
            'status' => $status,
        ];

        $result = $this->referralEarningModel->update($id, $data);

        if ($result) {
            $this->setFlash('success', 'Referral status updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update referral status');
        }

        $this->redirect('admin/referrals');
    }


    // ==================== COUPON MANAGEMENT METHODS ====================
    /**
     * Manage coupons
     */
    public function coupons()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $coupons = $this->couponModel->getAllCoupons($limit, $offset);
        $totalCoupons = $this->couponModel->getTotalCoupons();
        $totalPages = ceil($totalCoupons / $limit);

        $this->view('admin/coupons/index', [
            'coupons' => $coupons,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCoupons' => $totalCoupons,
            'title' => 'Manage Coupons'
        ]);
    }

    /**
     * Create coupon - FIXED: Handle applicable_products array properly
     */
    public function createCoupon()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            $data = $_POST;

            // Validation
            if (empty($data['code'])) {
                $errors['code'] = 'Coupon code is required';
            } elseif (strlen($data['code']) < 3) {
                $errors['code'] = 'Coupon code must be at least 3 characters';
            } else {
                // Check if code already exists
                $existingCoupon = $this->couponModel->getCouponByCode($data['code']);
                if ($existingCoupon) {
                    $errors['code'] = 'Coupon code already exists';
                }
            }

            if (empty($data['discount_type']) || !in_array($data['discount_type'], ['percentage', 'fixed'])) {
                $errors['discount_type'] = 'Valid discount type is required';
            }

            if (empty($data['discount_value']) || $data['discount_value'] <= 0) {
                $errors['discount_value'] = 'Discount value must be greater than 0';
            }

            if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
                $errors['discount_value'] = 'Percentage discount cannot exceed 100%';
            }

            if (!empty($data['expires_at'])) {
                $expiryDate = strtotime($data['expires_at']);
                if ($expiryDate <= time()) {
                    $errors['expires_at'] = 'Expiry date must be in the future';
                }
            }

            if (!empty($data['min_order_amount']) && $data['min_order_amount'] < 0) {
                $errors['min_order_amount'] = 'Minimum order amount cannot be negative';
            }

            if (!empty($data['usage_limit_per_user']) && $data['usage_limit_per_user'] < 1) {
                $errors['usage_limit_per_user'] = 'Usage limit per user must be at least 1';
            }

            if (!empty($data['usage_limit_global']) && $data['usage_limit_global'] < 1) {
                $errors['usage_limit_global'] = 'Global usage limit must be at least 1';
            }

            if (empty($errors)) {
                // FIXED: Handle applicable products array properly
                if (!empty($data['applicable_products']) && is_array($data['applicable_products'])) {
                    // Filter out empty values and convert to integers
                    $productIds = array_filter(array_map('intval', $data['applicable_products']));
                    $data['applicable_products'] = json_encode($productIds);
                } else {
                    $data['applicable_products'] = null;
                }

                if ($this->couponModel->createCoupon($data)) {
                    $this->setFlash('success', 'Coupon created successfully');
                    $this->redirect('admin/coupons');
                } else {
                    $errors['general'] = 'Failed to create coupon';
                }
            }

            $this->view('admin/coupons/create', [
                'errors' => $errors,
                'data' => $data,
                'products' => $this->productModel->all(),
                'title' => 'Create Coupon'
            ]);
        } else {
            $this->view('admin/coupons/create', [
                'products' => $this->productModel->all(),
                'title' => 'Create Coupon'
            ]);
        }
    }

    /**
     * Edit coupon - FIXED: Handle applicable_products array properly
     */
    public function editCoupon($id = null)
    {
        if (!$id) {
            $this->redirect('admin/coupons');
        }
        $coupon = $this->couponModel->getCouponById($id);

        if (!$coupon) {
            $this->setFlash('error', 'Coupon not found');
            $this->redirect('admin/coupons');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            $data = $_POST;

            // Validation (similar to create)
            if (empty($data['code'])) {
                $errors['code'] = 'Coupon code is required';
            } elseif (strlen($data['code']) < 3) {
                $errors['code'] = 'Coupon code must be at least 3 characters';
            } else {
                // Check if code already exists (excluding current coupon)
                $existingCoupon = $this->couponModel->getCouponByCode($data['code']);
                if ($existingCoupon && $existingCoupon['id'] != $id) {
                    $errors['code'] = 'Coupon code already exists';
                }
            }

            if (empty($data['discount_type']) || !in_array($data['discount_type'], ['percentage', 'fixed'])) {
                $errors['discount_type'] = 'Valid discount type is required';
            }

            if (empty($data['discount_value']) || $data['discount_value'] <= 0) {
                $errors['discount_value'] = 'Discount value must be greater than 0';
            }

            if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
                $errors['discount_value'] = 'Percentage discount cannot exceed 100%';
            }

            if (!empty($data['expires_at'])) {
                $expiryDate = strtotime($data['expires_at']);
                if ($expiryDate <= time()) {
                    $errors['expires_at'] = 'Expiry date must be in the future';
                }
            }

            if (empty($errors)) {
                // FIXED: Handle applicable products array properly
                if (!empty($data['applicable_products']) && is_array($data['applicable_products'])) {
                    // Filter out empty values and convert to integers
                    $productIds = array_filter(array_map('intval', $data['applicable_products']));
                    $data['applicable_products'] = json_encode($productIds);
                } else {
                    $data['applicable_products'] = null;
                }

                if ($this->couponModel->updateCoupon($id, $data)) {
                    $this->setFlash('success', 'Coupon updated successfully');
                    $this->redirect('admin/coupons');
                } else {
                    $errors['general'] = 'Failed to update coupon';
                }
            }

            $this->view('admin/coupons/edit', [
                'coupon' => $coupon,
                'errors' => $errors,
                'data' => $data,
                'products' => $this->productModel->all(),
                'title' => 'Edit Coupon'
            ]);
        } else {
            // Prepare data for form
            $coupon['applicable_products_array'] = [];
            if ($coupon['applicable_products']) {
                $coupon['applicable_products_array'] = json_decode($coupon['applicable_products'], true) ?: [];
            }

            $this->view('admin/coupons/edit', [
                'coupon' => $coupon,
                'products' => $this->productModel->all(),
                'title' => 'Edit Coupon'
            ]);
        }
    }

    /**
     * Delete coupon
     */
    public function deleteCoupon($id = null)
    {
        if (!$id) {
            $this->redirect('admin/coupons');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $coupon = $this->couponModel->getCouponById($id);

            if (!$coupon) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Coupon not found']);
                    return;
                }
                $this->setFlash('error', 'Coupon not found');
                $this->redirect('admin/coupons');
                return;
            }

            if ($this->couponModel->deleteCoupon($id)) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Coupon deleted successfully']);
                    return;
                }
                $this->setFlash('success', 'Coupon deleted successfully');
            } else {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to delete coupon']);
                    return;
                }
                $this->setFlash('error', 'Failed to delete coupon');
            }

            $this->redirect('admin/coupons');
        }
    }

    /**
     * Toggle coupon status
     */
    public function toggleCoupon($id = null)
    {
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon ID']);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $coupon = $this->couponModel->getCouponById($id);

            if (!$coupon) {
                $this->jsonResponse(['success' => false, 'message' => 'Coupon code not found']);
                return;
            }

            $newStatus = $coupon['is_active'] ? 0 : 1;

            if ($this->couponModel->updateCoupon($id, ['is_active' => $newStatus])) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Coupon status updated successfully',
                    'new_status' => $newStatus
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update coupon status']);
            }
        }
    }

    /**
     * Toggle coupon visibility (public/private)
     */
    public function toggleCouponVisibility($id = null)
    {
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon ID']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $newStatus = $input['status'] ?? null;
            
            if (!in_array($newStatus, ['public', 'private'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid status value']);
                return;
            }
            
            $coupon = $this->couponModel->getCouponById($id);
            if (!$coupon) {
                $this->jsonResponse(['success' => false, 'message' => 'Coupon not found']);
                return;
            }

            if ($this->couponModel->updateCoupon($id, ['status' => $newStatus])) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Coupon visibility updated successfully',
                    'new_status' => $newStatus
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update coupon visibility']);
            }
        }
    }

    /**
     * Coupon statistics
     */
    public function couponStats($id = null)
    {
        if (!$id) {
            $this->redirect('admin/coupons');
        }
        $stats = $this->couponModel->getCouponStats($id);

        if (!$stats) {
            $this->setFlash('error', 'Coupon not found');
            $this->redirect('admin/coupons');
            return;
        }

        $this->view('admin/coupons/stats', [
            'stats' => $stats,
            'title' => 'Coupon Statistics'
        ]);
    }

    /**
     * Add coupon status field migration
     */
    public function addCouponStatusField()
    {
        try {
            $pdo = $this->db->getPdo();
            
            // Check if status column already exists
            $checkSql = "SHOW COLUMNS FROM coupons LIKE 'status'";
            $stmt = $pdo->prepare($checkSql);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Add status column
                $sql = "ALTER TABLE coupons ADD COLUMN status ENUM('public', 'private') DEFAULT 'private' AFTER is_active";
                $pdo->exec($sql);
                
                // Update existing coupons to be public by default
                $sql = "UPDATE coupons SET status = 'public' WHERE is_active = 1";
                $pdo->exec($sql);
                
                $this->setFlash('success', 'Status field added to coupons table successfully!');
            } else {
                $this->setFlash('info', 'Status field already exists in coupons table.');
            }
            
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error adding status field: ' . $e->getMessage());
        }
        
        $this->redirect('admin/coupons');
    }

    /**
     * Add users.sponsor_status column (active/inactive)
     */
    public function addSponsorStatusField()
    {
        try {
            $pdo = $this->db->getPdo();

            // Check if sponsor_status column already exists
            $checkSql = "SHOW COLUMNS FROM users LIKE 'sponsor_status'";
            $stmt = $pdo->prepare($checkSql);
            $stmt->execute();

            if ($stmt->rowCount() == 0) {
                // Add sponsor_status column with default 'inactive'
                $sql = "ALTER TABLE users ADD COLUMN sponsor_status ENUM('active','inactive') NOT NULL DEFAULT 'inactive' AFTER referred_by";
                $pdo->exec($sql);

                $this->setFlash('success', 'sponsor_status field added to users table successfully.');
            } else {
                $this->setFlash('info', 'sponsor_status field already exists on users table.');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error adding sponsor_status field: ' . $e->getMessage());
        }

        $this->redirect('admin/users');
    }

    /**
     * Search orders
     */
    public function searchOrders()
    {
        $searchTerm = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? null;
        
        if ($searchTerm) {
            $orders = $this->orderModel->searchOrders($searchTerm, $status);
        } else {
            $orders = $this->orderModel->getAllOrders($status);
        }
        
        $this->view('admin/orders/index', [
            'orders' => $orders,
            'searchTerm' => $searchTerm,
            'status' => $status,
            'title' => 'Search Orders'
        ]);
    }

    /**
     * Filter orders by status
     */
    public function filterOrdersByStatus($status)
    {
        $orders = $this->orderModel->getAllOrders($status);
        
        $this->view('admin/orders/index', [
            'orders' => $orders,
            'status' => $status,
            'title' => ucfirst($status) . ' Orders'
        ]);
    }

    /**
     * Add note to order
     */
    public function addOrderNote($id)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/orders');
        }
        
        $note = trim($_POST['note'] ?? '');
        if (empty($note)) {
            $this->setFlash('error', 'Note cannot be empty');
            $this->redirect('admin/viewOrder/' . $id);
            return;
        }
        
        // Update order with note
        if ($this->orderModel->updateOrder($id, ['order_notes' => $note])) {
            $this->setFlash('success', 'Note added successfully');
        } else {
            $this->setFlash('error', 'Failed to add note');
        }
        
        $this->redirect('admin/viewOrder/' . $id);
    }

    /**
     * Bulk update orders
     */
    public function bulkUpdateOrders()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            } else {
                $this->redirect('admin/orders');
            }
            return;
        }
        
        // Handle both AJAX and form submissions
        $input = json_decode(file_get_contents('php://input'), true);
        $orderIds = $input['order_ids'] ?? $_POST['order_ids'] ?? [];
        $status = $input['status'] ?? $_POST['status'] ?? '';
        $action = $input['action'] ?? $_POST['action'] ?? '';
        
        if (empty($orderIds) || (empty($status) && empty($action))) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Please select orders and action'], 400);
            } else {
                $this->setFlash('error', 'Please select orders and action');
                $this->redirect('admin/orders');
            }
            return;
        }

        // Convert to integers
        $orderIds = array_map('intval', $orderIds);
        $orderIds = array_filter($orderIds, function($id) { return $id > 0; });

        if (empty($orderIds)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'No valid order IDs provided'], 400);
            } else {
                $this->setFlash('error', 'No valid order IDs provided');
                $this->redirect('admin/orders');
            }
            return;
        }

        try {
            $bulkService = new \App\Services\BulkActionService();
            $result = null;

            if ($action === 'delete') {
                // Delete orders using BulkActionService
                $result = $bulkService->bulkDelete(\App\Models\Order::class, $orderIds);
            } else {
                // Update order status using BulkActionService
                $result = $bulkService->bulkUpdateStatus(
                    \App\Models\Order::class,
                    $orderIds,
                    $status,
                    'status'
                );
            }

            if ($result && $result['success']) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $result['message'],
                        'count' => $result['count']
                    ]);
                } else {
                    $this->setFlash('success', $result['message']);
                    $this->redirect('admin/orders');
                }
            } else {
                $errorMessage = $result['message'] ?? 'Operation failed';
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => $errorMessage], 400);
                } else {
                    $this->setFlash('error', $errorMessage);
                    $this->redirect('admin/orders');
                }
            }
        } catch (Exception $e) {
            error_log('Bulk update orders error: ' . $e->getMessage());
            $errorMessage = 'An error occurred: ' . $e->getMessage();
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMessage], 500);
            } else {
                $this->setFlash('error', $errorMessage);
                $this->redirect('admin/orders');
            }
        }
    }

    /**
     * Bulk update products
     */
    public function bulkUpdateProducts()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            } else {
                $this->redirect('admin/products');
            }
            return;
        }
        
        // Handle both AJAX and form submissions
        $input = json_decode(file_get_contents('php://input'), true);
        $productIds = $input['product_ids'] ?? $_POST['product_ids'] ?? [];
        $action = $input['action'] ?? $_POST['action'] ?? '';
        
        if (empty($productIds) || empty($action)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Please select products and action'], 400);
            } else {
                $this->setFlash('error', 'Please select products and action');
                $this->redirect('admin/products');
            }
            return;
        }

        // Convert to integers
        $productIds = array_map('intval', $productIds);
        $productIds = array_filter($productIds, function($id) { return $id > 0; });

        if (empty($productIds)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'No valid product IDs provided'], 400);
            } else {
                $this->setFlash('error', 'No valid product IDs provided');
                $this->redirect('admin/products');
            }
            return;
        }

        try {
            $bulkService = new \App\Services\BulkActionService();
            $result = null;
            $skippedCount = 0;

            switch ($action) {
                case 'activate':
                    $result = $bulkService->bulkActivate(\App\Models\Product::class, $productIds);
                    break;
                case 'deactivate':
                    $result = $bulkService->bulkDeactivate(\App\Models\Product::class, $productIds);
                    break;
                case 'feature_on':
                    $result = $bulkService->bulkUpdate(
                        \App\Models\Product::class,
                        $productIds,
                        ['is_featured' => 1]
                    );
                    break;
                case 'feature_off':
                    $result = $bulkService->bulkUpdate(
                        \App\Models\Product::class,
                        $productIds,
                        ['is_featured' => 0]
                    );
                    break;
                case 'delete':
                    // Check for products referenced in orders (for warning message only)
                    $db = $this->productModel->getDb();
                    $productsInOrders = [];
                    foreach ($productIds as $productId) {
                        try {
                            $row = $db->query("SELECT COUNT(*) AS cnt FROM order_items WHERE product_id = ?", [$productId])->single();
                            $count = is_array($row) && isset($row['cnt']) ? (int)$row['cnt'] : 0;
                            
                            if ($count > 0) {
                                $productsInOrders[] = $productId;
                            }
                        } catch (\Exception $e) {
                            // If check fails, continue with deletion
                        }
                    }

                    // Delete product images and variants first
                    foreach ($productIds as $id) {
                        try {
                            $db->query("DELETE FROM product_images WHERE product_id = ?", [$id])->execute();
                            $db->query("DELETE FROM product_variants WHERE product_id = ?", [$id])->execute();
                        } catch (\Exception $e) {
                            error_log('Bulk delete product relations error: ' . $e->getMessage());
                        }
                    }

                    // Delete products using BulkActionService
                    // Note: Database CASCADE will automatically delete order_items when products are deleted
                    $result = $bulkService->bulkDelete(\App\Models\Product::class, $productIds);
                    
                    // Add warning if some products were in orders
                    if ($result['success'] && count($productsInOrders) > 0) {
                        $result['message'] .= ' Note: ' . count($productsInOrders) . ' product(s) were referenced in orders and have been removed from order history.';
                    }
                    break;
                default:
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
                    } else {
                        $this->setFlash('error', 'Invalid action');
                        $this->redirect('admin/products');
                    }
                    return;
            }

            if ($result && $result['success']) {
                $message = $result['message'];
                if ($skippedCount > 0) {
                    $message .= " Skipped {$skippedCount} product(s) due to existing order references.";
                }

                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $message,
                        'count' => $result['count'],
                        'skipped' => $skippedCount
                    ]);
                } else {
                    $this->setFlash('success', $message);
                    $this->redirect('admin/products');
                }
            } else {
                $errorMessage = $result['message'] ?? 'Operation failed';
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => $errorMessage], 400);
                } else {
                    $this->setFlash('error', $errorMessage);
                    $this->redirect('admin/products');
                }
            }
        } catch (Exception $e) {
            error_log('Bulk update products error: ' . $e->getMessage());
            $errorMessage = 'An error occurred: ' . $e->getMessage();
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMessage], 500);
            } else {
                $this->setFlash('error', $errorMessage);
                $this->redirect('admin/products');
            }
        }
    }

    /**
     * Bulk update users
     */
    public function bulkUpdateUsers()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            } else {
                $this->redirect('admin/users');
            }
            return;
        }
        
        // Handle both AJAX and form submissions
        $input = json_decode(file_get_contents('php://input'), true);
        $userIds = $input['user_ids'] ?? $_POST['user_ids'] ?? [];
        $action = $input['action'] ?? $_POST['action'] ?? '';
        
        if (empty($userIds) || empty($action)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Please select users and action'], 400);
            } else {
                $this->setFlash('error', 'Please select users and action');
                $this->redirect('admin/users');
            }
            return;
        }

        // Convert to integers
        $userIds = array_map('intval', $userIds);
        $userIds = array_filter($userIds, function($id) { return $id > 0; });

        if (empty($userIds)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'No valid user IDs provided'], 400);
            } else {
                $this->setFlash('error', 'No valid user IDs provided');
                $this->redirect('admin/users');
            }
            return;
        }

        try {
            $bulkService = new \App\Services\BulkActionService();
            $result = null;

            switch ($action) {
                case 'activate':
                    $result = $bulkService->bulkUpdate(
                        \App\Models\User::class,
                        $userIds,
                        ['is_active' => 1]
                    );
                    break;
                case 'deactivate':
                    $result = $bulkService->bulkUpdate(
                        \App\Models\User::class,
                        $userIds,
                        ['is_active' => 0]
                    );
                    break;
                case 'delete':
                    $result = $bulkService->bulkDelete(\App\Models\User::class, $userIds);
                    break;
                default:
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
                    } else {
                        $this->setFlash('error', 'Invalid action');
                        $this->redirect('admin/users');
                    }
                    return;
            }

            if ($result && $result['success']) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $result['message'],
                        'count' => $result['count']
                    ]);
                } else {
                    $this->setFlash('success', $result['message']);
                    $this->redirect('admin/users');
                }
            } else {
                $errorMessage = $result['message'] ?? 'Operation failed';
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => $errorMessage], 400);
                } else {
                    $this->setFlash('error', $errorMessage);
                    $this->redirect('admin/users');
                }
            }
        } catch (Exception $e) {
            error_log('Bulk update users error: ' . $e->getMessage());
            $errorMessage = 'An error occurred: ' . $e->getMessage();
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMessage], 500);
            } else {
                $this->setFlash('error', $errorMessage);
                $this->redirect('admin/users');
            }
        }
    }

    /**
     * Export orders
     */
    public function exportOrders()
    {
        $orders = $this->orderModel->getAllOrders();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['ID', 'Invoice', 'Customer Name', 'Status', 'Total Amount', 'Created At']);
        
        // CSV data
        foreach ($orders as $order) {
            fputcsv($output, [
                $order['id'],
                $order['invoice'],
                $order['customer_name'],
                $order['status'],
                $order['total_amount'],
                $order['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export products
     */
    public function exportProducts()
    {
        $products = $this->productModel->getAllProducts();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['ID', 'Product Name', 'Price', 'Stock', 'Status', 'Created At']);
        
        // CSV data
        foreach ($products as $product) {
            fputcsv($output, [
                $product['id'],
                $product['product_name'],
                $product['price'],
                $product['stock'],
                $product['is_active'] ? 'Active' : 'Inactive',
                $product['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export users
     */
    public function exportUsers()
    {
        $users = $this->userModel->all();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Status', 'Created At']);
        
        // CSV data
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['first_name'],
                $user['last_name'],
                $user['email'],
                $user['phone'],
                $user['is_active'] ? 'Active' : 'Inactive',
                $user['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Import products
     */
    public function importProducts()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/products');
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'Please select a valid CSV file');
            $this->redirect('admin/products');
            return;
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            $this->setFlash('error', 'Failed to open CSV file');
            $this->redirect('admin/products');
            return;
        }
        
        // Skip header row
        $headers = fgetcsv($handle);
        $successCount = 0;
        $errorCount = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 4) {
                $productData = [
                    'product_name' => trim($data[0]),
                    'description' => trim($data[1]),
                    'price' => (float)trim($data[2]),
                    'stock' => (int)trim($data[3]),
                    'is_active' => 1
                ];
                
                if ($this->productModel->createProduct($productData)) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
        }
        
        fclose($handle);
        
        if ($successCount > 0) {
            $this->setFlash('success', "Successfully imported {$successCount} products");
        }
        if ($errorCount > 0) {
            $this->setFlash('error', "Failed to import {$errorCount} products");
        }
        
        $this->redirect('admin/products');
    }

    /**
     * Admin settings
     */
    public function settings()
    {
        $settings = $this->settingModel->getAll();
        $this->view('admin/settings/index', [
            'title' => 'Admin Settings',
            'settings' => $settings
        ]);
    }

    /**
     * Update admin settings
     */
    public function updateSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/settings');
        }
        
        // Parse JSON payload if provided
        $input = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true) ?: [];
        } else {
            $input = $_POST;
        }

        // Normalize and validate inputs - only update provided fields
        if (isset($input['website_url'])) {
            $websiteUrl = trim($input['website_url']);
            if ($websiteUrl !== '') {
                $this->settingModel->set('website_url', $websiteUrl);
            }
        }
        if (isset($input['min_withdrawal'])) {
            $minWithdrawal = (int)$input['min_withdrawal'];
            $this->settingModel->set('min_withdrawal', $minWithdrawal);
        }
        if (isset($input['commission_rate'])) {
            $commissionRate = (float)$input['commission_rate'];
            $this->settingModel->set('commission_rate', $commissionRate);
        }
        if (isset($input['remember_token_days'])) {
            $rememberDays = (int)$input['remember_token_days'];
            $this->settingModel->set('remember_token_days', $rememberDays);
        }
        if (isset($input['tax_rate'])) {
            $taxRate = (float)$input['tax_rate'];
            $this->settingModel->set('tax_rate', $taxRate);
        }
        // Store booleans as 'true'/'false' strings for consistency with views
        if (isset($input['auto_approve'])) {
            $autoApprove = (bool)$input['auto_approve'];
            $this->settingModel->set('auto_approve', $autoApprove ? 'true' : 'false');
        }
        if (isset($input['enable_remember_me'])) {
            $enableRemember = (bool)$input['enable_remember_me'];
            $this->settingModel->set('enable_remember_me', $enableRemember ? 'true' : 'false');
        }
        if (isset($input['maintenance_mode'])) {
            $maintenanceMode = (bool)$input['maintenance_mode'];
            $this->settingModel->set('maintenance_mode', $maintenanceMode ? 'true' : 'false');
        }

        // Return JSON response for AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || stripos($contentType, 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            return;
        }

        $this->setFlash('success', 'Settings updated successfully');
        $this->redirect('admin/settings');
    }

    /**
     * Admin analytics
     */
    public function analytics()
    {
        $this->requireAdmin();
        
        // Get analytics data
        $revenueStats = $this->db->query("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN payment_status = 'paid' AND DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue,
                SUM(CASE WHEN payment_status = 'paid' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN total_amount ELSE 0 END) as month_revenue,
                AVG(CASE WHEN payment_status = 'paid' THEN total_amount ELSE NULL END) as avg_order_value
            FROM orders
        ")->single();

        $orderStatusBreakdown = $this->db->query("
            SELECT status, COUNT(*) as count
            FROM orders
            GROUP BY status
        ")->all();

        $paymentMethodBreakdown = $this->db->query("
            SELECT pm.name, COUNT(o.id) as count, SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END) as revenue
            FROM orders o
            LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
            GROUP BY pm.name
            ORDER BY count DESC
        ")->all();

        $topProducts = $this->db->query("
            SELECT p.product_name, SUM(oi.quantity) as total_sold, SUM(oi.total) as revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.payment_status = 'paid'
            GROUP BY p.id, p.product_name
            ORDER BY total_sold DESC
            LIMIT 10
        ")->all();

        $recentOrders = $this->db->query("
            SELECT o.*, u.first_name, u.last_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 10
        ")->all();

        $customerStats = $this->db->query("
            SELECT 
                COUNT(DISTINCT user_id) as total_customers,
                COUNT(DISTINCT CASE WHEN DATE(created_at) = CURDATE() THEN user_id END) as new_today,
                COUNT(DISTINCT CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN user_id END) as new_month
            FROM orders
            WHERE user_id IS NOT NULL
        ")->single();

        $salesTrend = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue
            FROM orders
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ")->all();
        
        $this->view('admin/analytics', [
            'title' => 'Analytics Dashboard',
            'revenueStats' => $revenueStats,
            'orderStatusBreakdown' => $orderStatusBreakdown,
            'paymentMethodBreakdown' => $paymentMethodBreakdown,
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
            'customerStats' => $customerStats,
            'salesTrend' => $salesTrend
        ]);
    }

    /**
     * Admin reports
     */
    /**
     * Best Selling Products Report
     */
    public function bestSellingProducts()
    {
        $period = $_GET['period'] ?? 'all';
        $limit = (int)($_GET['limit'] ?? 10);
        
        $bestSellingService = new \App\Services\BestSellingProductsService();
        $products = $bestSellingService->getBestSellingProducts($limit, $period);
        $trends = $bestSellingService->getSalesTrends(30);
        
        $this->view('admin/reports/best-selling', [
            'products' => $products,
            'trends' => $trends,
            'period' => $period,
            'limit' => $limit,
            'title' => 'Best Selling Products'
        ]);
    }

    /**
     * Low Stock Alerts
     */
    public function lowStockAlerts()
    {
        $lowStockService = new \App\Services\LowStockAlertService();
        $lowStockProducts = $lowStockService->getLowStockProducts();
        $outOfStockProducts = $lowStockService->getOutOfStockProducts();
        
        // Send alerts if needed
        $alertResults = $lowStockService->sendLowStockAlerts();
        
        $this->view('admin/reports/low-stock', [
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'alertResults' => $alertResults,
            'title' => 'Low Stock Alerts'
        ]);
    }

    public function reports()
    {
        $this->view('admin/reports', [
            'title' => 'Reports'
        ]);
    }

    /**
     * Admin notifications
     */
    public function notifications()
    {
        $this->view('admin/notifications', [
            'title' => 'Notifications'
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/notifications');
        }
        
        // Handle marking notification as read
        $this->setFlash('success', 'Notification marked as read');
        $this->redirect('admin/notifications');
    }

    /**
     * Manage reviews
     */
    public function reviews()
    {
        $reviews = $this->reviewModel->getAllReviews();
        
        $this->view('admin/reviews', [
            'reviews' => $reviews,
            'title' => 'Manage Reviews'
        ]);
    }

    /**
     * Delete review
     */
    public function deleteReview($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/reviews');
        }
        
        if ($this->reviewModel->deleteReview($id)) {
            $this->setFlash('success', 'Review deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete review');
        }
        
        $this->redirect('admin/reviews');
    }

    /**
     * Delete order
     */
    public function deleteOrder($id = null)
    {
        if (!$id) {
            $this->redirect('admin/orders');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Log the deletion attempt
            error_log("Attempting to delete order ID: $id");
            
            $order = $this->orderModel->getOrderById($id);

            if (!$order) {
                error_log("Order not found for ID: $id");
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Order not found']);
                    return;
                }
                $this->setFlash('error', 'Order not found');
                $this->redirect('admin/orders');
                return;
            }

            error_log("Found order: " . json_encode($order));

            // Delete the order (order items will be automatically deleted due to CASCADE)
            $deleteResult = $this->orderModel->deleteOrder($id);
            error_log("Delete result: " . ($deleteResult ? 'true' : 'false'));
            
            if ($deleteResult) {
                error_log("Order $id deleted successfully");
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
                    return;
                }
                $this->setFlash('success', 'Order deleted successfully');
            } else {
                error_log("Failed to delete order $id");
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
                    return;
                }
                $this->setFlash('error', 'Failed to delete order');
            }

            $this->redirect('admin/orders');
        } else {
            // If not POST request, redirect to orders page
            $this->redirect('admin/orders');
        }
    }

    /**
     * Bulk delete orders
     */
    public function bulkDeleteOrders()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/orders');
            return;
        }

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            $this->setFlash('error', 'No orders selected for deletion');
            $this->redirect('admin/orders');
            return;
        }

        $deleted = 0;
        $db = $this->orderModel->getDb();
        
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                try {
                    // Delete order items first
                    $db->query("DELETE FROM order_items WHERE order_id = ?", [$id])->execute();
                    // Delete order
                    if ($this->orderModel->delete($id)) {
                        $deleted++;
                    }
                } catch (\Exception $e) {
                    error_log('Bulk delete order error: ' . $e->getMessage());
                }
            }
        }

        if ($deleted > 0) {
            $this->setFlash('success', "$deleted order(s) deleted successfully");
        } else {
            $this->setFlash('error', 'No orders were deleted');
        }

        $this->redirect('admin/orders');
    }

    /**
     * Show create order form
     */
    public function createOrder()
    {
        try {
            // Get all products without limit for admin order creation
            $products = $this->productModel->getAllProducts(1000, 0, []);
            $deliveryCharges = $this->deliveryModel->getAllCharges();
            
            $this->view('admin/orders/create', [
                'products' => $products,
                'deliveryCharges' => $deliveryCharges,
                'title' => 'Create New Order'
            ]);
        } catch (Exception $e) {
            error_log('AdminController createOrder error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load order creation page');
            $this->redirect('admin/orders');
        }
    }

    /**
     * Validate coupon for admin create-order (AJAX)
     */
    public function validateOrderCoupon()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isAjaxRequest()) {
            $this->redirect('admin/orders/create');
            return;
        }

        header('Content-Type: application/json');
        try {
            $code = trim($_POST['coupon_code'] ?? '');
            $items = $_POST['items'] ?? [];

            if ($code === '' || empty($items) || !is_array($items)) {
                echo json_encode(['success' => false, 'message' => 'Coupon and items are required']);
                return;
            }

            // Build subtotal and productIds
            $subtotal = 0.0;
            $productIds = [];
            foreach ($items as $it) {
                $price = isset($it['price']) ? (float)$it['price'] : 0.0;
                $qty = isset($it['quantity']) ? (int)$it['quantity'] : 0;
                $subtotal += $price * max(0, $qty);
                if (isset($it['product_id'])) $productIds[] = (int)$it['product_id'];
            }

            $validation = $this->couponModel->validateCoupon($code, null, $subtotal, $productIds);
            if (!$validation['valid']) {
                echo json_encode(['success' => false, 'message' => $validation['message'] ?? 'Invalid coupon']);
                return;
            }

            $discount = $this->couponModel->calculateDiscount($validation['coupon'], $subtotal);
            echo json_encode([
                'success' => true,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'final_amount' => max(0, $subtotal + (float)($_POST['delivery_fee'] ?? 0) - $discount),
                'coupon' => [
                    'id' => $validation['coupon']['id'],
                    'code' => $validation['coupon']['code'],
                    'type' => $validation['coupon']['discount_type'],
                    'value' => $validation['coupon']['discount_value']
                ]
            ]);
        } catch (Exception $e) {
            error_log('Admin validateOrderCoupon error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }

    /**
     * Process order creation
     */
    public function storeOrder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/orders/create');
            return;
        }

        try {
            // Validate required fields
            $requiredFields = ['customer_name', 'phone', 'address', 'products'];
            $errors = [];

            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }

            // Validate products
            $products = $_POST['products'] ?? [];
            if (empty($products) || !is_array($products)) {
                $errors[] = 'At least one product is required';
            }

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('admin/orders/create');
                return;
            }

            // Calculate totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($products as $productData) {
                $productId = $productData['product_id'];
                $quantity = (int)$productData['quantity'];
                
                if ($quantity <= 0) continue;

                $product = $this->productModel->find($productId);
                if (!$product) {
                    $this->setFlash('error', 'Invalid product selected');
                    $this->redirect('admin/orders/create');
                    return;
                }

                $price = $product['sale_price'] ?? $product['price'];
                $itemTotal = $price * $quantity;
                $subtotal += $itemTotal;

                $orderItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $itemTotal
                ];
            }

            if (empty($orderItems)) {
                $this->setFlash('error', 'No valid products selected');
                $this->redirect('admin/orders/create');
                return;
            }

            // Calculate delivery fee
            $deliveryFee = 0;
            $city = $_POST['city'] ?? '';
            if (!empty($city)) {
                $deliveryCharge = $this->deliveryModel->getChargeByLocation($city);
                if ($deliveryCharge) {
                    $deliveryFee = $deliveryCharge['charge'];
                } else {
                    $deliveryFee = 300; // Default fee
                }
            }

            // Calculate tax on subtotal (after discount if any)
            $taxRate = $this->settingModel->get('tax_rate', 13) / 100;
            
            // Apply coupon if provided
            $couponCode = trim($_POST['coupon_code'] ?? '');
            $discountAmount = 0;
            $appliedCoupon = null;
            if ($couponCode !== '') {
                $productIds = array_column($orderItems, 'product_id');
                $validation = $this->couponModel->validateCoupon($couponCode, null, $subtotal, $productIds);
                if ($validation['valid']) {
                    $appliedCoupon = $validation['coupon'];
                    $discountAmount = $this->couponModel->calculateDiscount($appliedCoupon, $subtotal);
                } else {
                    // Non-blocking: show warning and continue without coupon
                    $this->setFlash('error', 'Coupon not applied: ' . ($validation['message'] ?? 'Invalid coupon'));
                }
            }
            
            // Calculate amounts: subtotal - discount + tax + delivery
            $subtotalAfterDiscount = max(0, $subtotal - $discountAmount);
            $taxAmount = $subtotalAfterDiscount * $taxRate;
            $finalAmount = $subtotalAfterDiscount + $taxAmount + $deliveryFee;

            // Create order
            $orderData = [
                'user_id' => null, // Admin created order
                'total_amount' => $finalAmount, // Final total amount
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'delivery_fee' => $deliveryFee,
                'final_amount' => $finalAmount,
                'coupon_code' => $appliedCoupon ? $appliedCoupon['code'] : null,
                'payment_method_id' => 1, // Default to COD
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'recipient_name' => $_POST['customer_name'],
                'phone' => $_POST['phone'],
                'address_line1' => $_POST['address'],
                'city' => $city,
                'state' => $_POST['state'] ?? 'Nepal',
                'country' => 'Nepal',
                'order_notes' => $_POST['order_notes'] ?? '',
                'created_by_admin' => true
            ];

            $orderId = $this->orderModel->createOrder($orderData, $orderItems);

            if ($orderId) {
                // Record coupon usage if applied
                if ($appliedCoupon) {
                    try {
                        $this->couponModel->recordCouponUsage($appliedCoupon['id'], 0, $orderId, $discountAmount);
                    } catch (Exception $e) {
                        error_log('AdminController: Failed to record coupon usage: ' . $e->getMessage());
                    }
                }
                $this->setFlash('success', 'Order created successfully! Order ID: #' . $orderId);
                $this->redirect('admin/orders');
            } else {
                $this->setFlash('error', 'Failed to create order');
                $this->redirect('admin/orders/create');
            }

        } catch (Exception $e) {
            error_log('AdminController storeOrder error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to create order: ' . $e->getMessage());
            $this->redirect('admin/orders/create');
        }
    }

    /**
     * Display withdrawals management page
     */
    public function withdrawals()
    {
        try {
            // Get all withdrawal requests
            $withdrawals = $this->db->query(
                "SELECT w.*, u.first_name, u.last_name, u.email, u.phone 
                 FROM withdrawals w 
                 LEFT JOIN users u ON w.user_id = u.id 
                 ORDER BY w.created_at DESC"
            )->all();

            $this->view('admin/withdrawals/index', [
                'withdrawals' => $withdrawals,
                'title' => 'Withdrawals Management'
            ]);
        } catch (Exception $e) {
            error_log('AdminController withdrawals error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load withdrawals page');
            $this->redirect('admin');
        }
    }

    /**
     * Update withdrawal status
     */
    public function updateWithdrawal($id)
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/withdrawals');
            return;
        }

        try {
            $status = $_POST['status'] ?? '';
            
            // Map frontend status to database ENUM values
            $statusMapping = [
                'approved' => 'processing',  // Map 'approved' to 'processing' 
                'rejected' => 'rejected',
                'completed' => 'completed',
                'processing' => 'processing',
                'pending' => 'pending'
            ];
            
            if (!isset($statusMapping[$status])) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid status']);
                return;
            }
            
            $dbStatus = $statusMapping[$status];

            // Update withdrawal status - FIX: Add execute() call
            $updateResult = $this->db->query(
                "UPDATE withdrawals SET status = ?, updated_at = NOW() WHERE id = ?",
                [$dbStatus, $id]
            )->execute();

            if (!$updateResult) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update withdrawal status in database']);
                return;
            }

            // Get withdrawal details for further processing
            $withdrawal = $this->db->query("SELECT * FROM withdrawals WHERE id = ?", [$id])->single();
            
            if (!$withdrawal) {
                $this->jsonResponse(['success' => false, 'message' => 'Withdrawal not found after update']);
                return;
            }

            // Process based on status
            if ($status === 'approved' || $status === 'completed') {
                try {
                    // Record transaction (negative amount) for approved withdrawals
                    if ($status === 'approved') {
                        $this->transactionModel->recordWithdrawal($withdrawal['user_id'], $withdrawal['amount'], $id);
                    }

                    // Queue email notification for both approved and completed status
                    if (!empty($withdrawal['user_id'])) {
                        $user = $this->userModel->find($withdrawal['user_id']);
                        if ($user && !empty($user['email'])) {
                            try {
                                // Initialize EmailQueue model
                                $emailQueue = new \App\Models\EmailQueue();
                                
                                // Prepare email data
                                $templateData = [
                                    'user_name' => $user['first_name'] ?? 'User',
                                    'amount' => number_format($withdrawal['amount'], 2),
                                    'withdrawal_id' => $id,
                                    'payment_method' => $withdrawal['payment_method'] ?? 'N/A',
                                    'processed_date' => date('F j, Y'),
                                    'processed_time' => date('g:i A'),
                                    'transaction_id' => 'TXN' . str_pad($id, 6, '0', STR_PAD_LEFT)
                                ];
                                
                                $subject = '✅ Withdrawal ' . ucfirst($status) . ' - Rs. ' . number_format($withdrawal['amount'], 2) . ' Processed';
                                
                                // Queue the email with high priority
                                $emailQueue->queueEmail(
                                    $user['email'],
                                    $subject,
                                    '', // Body will be generated from template
                                    $user['first_name'] ?? 'User',
                                    1, // High priority
                                    [
                                        'template' => 'withdrawcompleted',
                                        'template_data' => $templateData
                                    ]
                                );
                                
                                error_log("Withdrawal {$status} email queued for user: {$user['email']}");
                                
                            } catch (\Exception $e) {
                                error_log('Error queuing withdrawal notification email: ' . $e->getMessage());
                                // Don't fail the entire operation if email queuing fails
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log('AdminController: Error processing withdrawal status change: ' . $e->getMessage());
                    // Don't fail the status update if additional processing fails
                }
            }

            $message = $status === 'approved' ? 'Withdrawal approved successfully' : 
                      ($status === 'rejected' ? 'Withdrawal rejected successfully' : 'Withdrawal marked as completed');

            $this->jsonResponse(['success' => true, 'message' => $message]);

        } catch (Exception $e) {
            error_log('AdminController updateWithdrawal error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update withdrawal status: ' . $e->getMessage()]);
        }
    }

    /**
     * Update withdrawal status (alias for updateWithdrawal)
     * This method is called from the JavaScript in withdrawal views
     */
    public function updateWithdrawalStatus($id)
    {
        // Call the existing updateWithdrawal method
        return $this->updateWithdrawal($id);
    }

    /**
     * View withdrawal details
     */
    public function viewWithdrawal($id)
    {
        try {
            $withdrawal = $this->db->query(
                "SELECT w.*, u.first_name, u.last_name, u.email, u.phone 
                 FROM withdrawals w 
                 LEFT JOIN users u ON w.user_id = u.id 
                 WHERE w.id = ?",
                [$id]
            )->single();

            if (!$withdrawal) {
                $this->setFlash('error', 'Withdrawal not found');
                $this->redirect('admin/withdrawals');
                return;
            }

            $this->view('admin/withdrawals/view', [
                'withdrawal' => $withdrawal,
                'title' => 'Withdrawal Details'
            ]);
        } catch (Exception $e) {
            error_log('AdminController viewWithdrawal error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load withdrawal details');
            $this->redirect('admin/withdrawals');
        }
    }

    /**
     * Apply coupon to a specific user
     */


    /**
     * Update user sponsor status
     */
    public function updateSponsorStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $this->requireAdmin();

        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['user_id'] ?? null;
            $sponsorStatus = $input['sponsor_status'] ?? '';

            if (!$userId || !in_array($sponsorStatus, ['active', 'inactive'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                return;
            }

            // Validate user exists
            $user = $this->userModel->find($userId);
            if (!$user) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            // Update sponsor status
            $updateResult = $this->userModel->update($userId, ['sponsor_status' => $sponsorStatus]);

            if ($updateResult) {
                // Log the action
                error_log("Admin updated sponsor status for user {$userId} to {$sponsorStatus}");
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => "Sponsor status updated to {$sponsorStatus} successfully"
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update sponsor status']);
            }

        } catch (Exception $e) {
            error_log('AdminController updateSponsorStatus error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'An error occurred while updating sponsor status']);
        }
    }

    /**
     * Update user referral code
     */
    public function updateReferralCode()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        $this->requireAdmin();

        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['user_id'] ?? null;
            $referralCode = $input['referral_code'] ?? '';

            if (!$userId || empty(trim($referralCode))) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                return;
            }

            // Validate user exists
            $user = $this->userModel->find($userId);
            if (!$user) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            // Check if referral code already exists for another user
            $existingUser = $this->userModel->findByReferralCode(trim($referralCode));
            if ($existingUser && $existingUser['id'] != $userId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Referral code already exists for another user']);
                return;
            }

            // Update referral code
            $updateResult = $this->userModel->update($userId, ['referral_code' => trim($referralCode)]);

            if ($updateResult) {
                // Log the action
                error_log("Admin updated referral code for user {$userId} to " . trim($referralCode));
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => 'Referral code updated successfully'
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update referral code']);
            }

        } catch (Exception $e) {
            error_log('AdminController updateReferralCode error: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'An error occurred while updating referral code']);
        }
    }
    
    /**
     * Reduce stock for all items in an order
     */
    private function reduceOrderStock($orderId)
    {
        $orderItems = $this->orderItemModel->getByOrderId($orderId);
        
        foreach ($orderItems as $item) {
            $productId = $item['product_id'];
            $quantity = (int)$item['quantity'];
            
            // Get current stock
            $product = $this->productModel->find($productId);
            if ($product) {
                $currentStock = (int)$product['stock_quantity'];
                $newStock = max(0, $currentStock - $quantity);
                
                // Update stock
                $this->productModel->updateStock($productId, $newStock);
                error_log("Reduced stock for product {$productId}: {$currentStock} -> {$newStock} (order #{$orderId})");
            }
        }
    }
    
    /**
     * Restore stock for all items in a cancelled order
     */
    private function restoreOrderStock($orderId)
    {
        $orderItems = $this->orderItemModel->getByOrderId($orderId);
        
        foreach ($orderItems as $item) {
            $productId = $item['product_id'];
            $quantity = (int)$item['quantity'];
            
            // Get current stock
            $product = $this->productModel->find($productId);
            if ($product) {
                $currentStock = (int)$product['stock_quantity'];
                $newStock = $currentStock + $quantity;
                
                // Update stock
                $this->productModel->updateStock($productId, $newStock);
                error_log("Restored stock for product {$productId}: {$currentStock} -> {$newStock} (order #{$orderId})");
            }
        }
    }
}
