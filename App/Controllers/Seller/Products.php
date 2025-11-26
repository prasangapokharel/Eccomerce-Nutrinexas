<?php

namespace App\Controllers\Seller;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Core\Cache;
use Exception;
use App\Helpers\CategoryHelper;

class Products extends BaseSellerController
{
    private $productModel;
    private $productImageModel;
    private $settingModel;
    private $cache;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
        $this->settingModel = new Setting();
        $this->cache = new Cache();
    }

    /**
     * List all products
     */
    public function index()
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $cacheKey = 'seller_products_' . $this->sellerId . '_' . $page;
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            $this->view('seller/products/index', $cached);
            return;
        }

        $products = $this->productModel->getProductsBySellerId($this->sellerId, $limit, $offset);
        
        // Ensure images are loaded with default fallback
        foreach ($products as &$product) {
            $product['image_url'] = $this->getProductImageUrl($product);
        }
        
        // Cache product count for 5 minutes
        $countCacheKey = 'seller_product_count_' . $this->sellerId;
        $total = $this->cache->remember($countCacheKey, function() {
            return $this->productModel->getProductCountBySeller($this->sellerId);
        }, 300);
        
        $totalPages = ceil($total / $limit);

        $viewData = [
            'title' => 'My Products',
            'products' => $products,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ];
        
        // Cache for 3 minutes
        $this->cache->set($cacheKey, $viewData, 180);
        
        $this->view('seller/products/index', $viewData);
    }

    /**
     * Create product form
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }

        // Get default commission rate from settings
        $defaultCommissionRate = (float)$this->settingModel->get('commission_rate', 10);

        $this->view('seller/products/create', [
            'title' => 'Add New Product',
            'defaultCommissionRate' => $defaultCommissionRate
        ]);
    }

    /**
     * Bulk upload page
     */
    public function bulkUpload()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleBulkUpload();
            return;
        }

        $this->view('seller/products/bulk-upload', [
            'title' => 'Bulk Upload Products'
        ]);
    }

    /**
     * Handle bulk CSV upload
     */
    private function handleBulkUpload()
    {
        try {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                $this->setFlash('error', 'Please upload a valid CSV file');
                $this->redirect('seller/products/bulk-upload');
                return;
            }

            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, 'r');
            
            if ($handle === false) {
                throw new Exception('Failed to open CSV file');
            }

            // Read header row
            $headers = fgetcsv($handle);
            if (!$headers) {
                throw new Exception('CSV file is empty or invalid');
            }

            // Normalize headers (lowercase, trim)
            $headers = array_map(function($h) {
                return strtolower(trim($h));
            }, $headers);

            $requiredFields = ['product_name', 'price', 'category', 'stock_quantity'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!in_array($field, $headers)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                throw new Exception('Missing required columns: ' . implode(', ', $missingFields));
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            // Read data rows
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                
                if (count($row) !== count($headers)) {
                    $errors[] = "Row {$rowNum}: Column count mismatch";
                    $errorCount++;
                    continue;
                }

                $data = array_combine($headers, $row);
                
                // Prepare product data
                $productData = [
                    'product_name' => trim($data['product_name'] ?? ''),
                    'description' => trim($data['description'] ?? ''),
                    'short_description' => trim($data['short_description'] ?? ''),
                    'price' => (float)($data['price'] ?? 0),
                    'sale_price' => !empty($data['sale_price']) ? (float)$data['sale_price'] : null,
                    'stock_quantity' => (int)($data['stock_quantity'] ?? 0),
                    'category' => trim($data['category'] ?? ''),
                    'subcategory' => trim($data['subcategory'] ?? ''),
                    'product_type_main' => trim($data['product_type_main'] ?? ''),
                    'product_type' => trim($data['product_type'] ?? ''),
                    'is_digital' => isset($data['is_digital']) && strtolower($data['is_digital']) === 'yes' ? 1 : 0,
                    'colors' => trim($data['colors'] ?? ''),
                    'weight' => trim($data['weight'] ?? ''),
                    'serving' => trim($data['serving'] ?? ''),
                    'flavor' => trim($data['flavor'] ?? ''),
                    'status' => 'pending',
                    'approval_status' => 'pending',
                    'seller_id' => $this->sellerId
                ];

                // Validate required fields
                if (empty($productData['product_name']) || $productData['price'] <= 0 || empty($productData['category'])) {
                    $errors[] = "Row {$rowNum}: Missing required fields";
                    $errorCount++;
                    continue;
                }

                // Generate slug
                $productData['slug'] = $this->productModel->generateSlug($productData['product_name']);

                // Create product
                $productId = $this->productModel->createProduct($productData);

                if ($productId) {
                    // Handle image URL if provided
                    if (!empty($data['image_url'])) {
                        $imageUrl = trim($data['image_url']);
                        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                            $this->productImageModel->create([
                                'product_id' => $productId,
                                'image_url' => $imageUrl,
                                'is_primary' => 1
                            ]);
                        }
                    }
                    $successCount++;
                } else {
                    $errors[] = "Row {$rowNum}: Failed to create product";
                    $errorCount++;
                }
            }

            fclose($handle);

            $message = "Upload complete: {$successCount} products created";
            if ($errorCount > 0) {
                $message .= ", {$errorCount} errors";
            }
            $this->setFlash($errorCount > 0 ? 'warning' : 'success', $message);

            if (!empty($errors)) {
                error_log('Bulk upload errors: ' . implode(', ', $errors));
            }

        } catch (Exception $e) {
            error_log('Bulk upload error: ' . $e->getMessage());
            $this->setFlash('error', 'Bulk upload failed: ' . $e->getMessage());
        }

        $this->redirect('seller/products');
    }

    /**
     * Handle product creation
     */
    private function handleCreate()
    {
        try {
            $data = $this->prepareProductData();
            $productId = $this->productModel->createProduct($data);

            if ($productId) {
                $this->handleProductImages($productId);
                // Clear product caches
                $this->cache->deletePattern('seller_products_' . $this->sellerId . '_*');
                $this->cache->delete('seller_product_count_' . $this->sellerId);
                $this->setFlash('success', 'Product created successfully');
                $this->redirect('seller/products');
            } else {
                $this->setFlash('error', 'Failed to create product');
                $this->redirect('seller/products/create');
            }
        } catch (Exception $e) {
            error_log('Create product error: ' . $e->getMessage());
            $this->setFlash('error', 'Error creating product: ' . $e->getMessage());
            $this->redirect('seller/products/create');
        }
    }

    /**
     * Edit product form
     */
    public function edit($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdate($id);
            return;
        }

        $product = $this->productModel->find($id);
        
        if (!$product || $product['seller_id'] != $this->sellerId) {
            // Log unauthorized access attempt
            $securityLog = new \App\Services\SecurityLogService();
            $securityLog->logUnauthorizedAccess(
                'unauthorized_product_edit',
                $this->sellerId,
                $id,
                'product',
                [
                    'product_exists' => !empty($product),
                    'product_seller_id' => $product['seller_id'] ?? null,
                    'attempted_seller_id' => $this->sellerId
                ]
            );
            
            $this->setFlash('error', 'Product not found');
            $this->redirect('seller/products');
            return;
        }

        $images = $this->productImageModel->getByProductId($id);

        $this->view('seller/products/edit', [
            'title' => 'Edit Product',
            'product' => $product,
            'images' => $images
        ]);
    }

    /**
     * Handle product update
     */
    private function handleUpdate($id)
    {
        try {
            $product = $this->productModel->find($id);
            
            if (!$product || $product['seller_id'] != $this->sellerId) {
                // Log unauthorized access attempt
                $securityLog = new \App\Services\SecurityLogService();
                $securityLog->logUnauthorizedAccess(
                    'unauthorized_product_edit',
                    $this->sellerId,
                    $id,
                    'product',
                    [
                        'product_exists' => !empty($product),
                        'product_seller_id' => $product['seller_id'] ?? null,
                        'attempted_seller_id' => $this->sellerId,
                        'request_method' => 'POST',
                        'post_data_keys' => array_keys($_POST ?? [])
                    ]
                );
                
                $this->setFlash('error', 'Product not found');
                $this->redirect('seller/products');
                return;
            }

            $data = $this->prepareProductDataForUpdate();
            $result = $this->productModel->updateProduct($id, $data);

            if ($result) {
                $this->handleProductImages($id);
                // Clear product caches
                $this->cache->deletePattern('seller_products_' . $this->sellerId . '_*');
                $this->cache->delete('seller_product_count_' . $this->sellerId);
                $this->setFlash('success', 'Product updated successfully');
                $this->redirect('seller/products');
            } else {
                $this->setFlash('error', 'Failed to update product');
                $this->redirect('seller/products/edit/' . $id);
            }
        } catch (Exception $e) {
            error_log('Update product error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating product: ' . $e->getMessage());
            $this->redirect('seller/products/edit/' . $id);
        }
    }

    /**
     * Download CSV template
     */
    public function downloadCsvTemplate()
    {
        $filename = 'product_upload_template.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'product_name',
            'price',
            'category',
            'stock_quantity',
            'description',
            'short_description',
            'sale_price',
            'subcategory',
            'product_type_main',
            'product_type',
            'colors',
            'weight',
            'serving',
            'flavor',
            'image_url',
            'is_digital'
        ]);
        
        // Sample rows
        fputcsv($output, [
            'Energy Bar',
            '250.00',
            'Energy Bars',
            '100',
            'High energy protein bar for active lifestyle',
            'Boost your energy',
            '220.00',
            'Protein Bars',
            'Supplement',
            'Energy',
            'Red,Blue,Green',
            '50g',
            '1 bar',
            'Chocolate',
            'https://example.com/image.jpg',
            'no'
        ]);
        
        fclose($output);
        exit;
    }

    /**
     * Delete product
     */
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/products');
            return;
        }

        try {
            $product = $this->productModel->find($id);
            
            if (!$product || $product['seller_id'] != $this->sellerId) {
                $this->setFlash('error', 'Product not found');
                $this->redirect('seller/products');
                return;
            }

            $result = $this->productModel->deleteProduct($id);
            
            if ($result) {
                // Clear product caches
                $this->cache->deletePattern('seller_products_' . $this->sellerId . '_*');
                $this->cache->delete('seller_product_count_' . $this->sellerId);
                $this->setFlash('success', 'Product deleted successfully');
            } else {
                $this->setFlash('error', 'Failed to delete product');
            }
        } catch (Exception $e) {
            error_log('Delete product error: ' . $e->getMessage());
            $this->setFlash('error', 'Error deleting product');
        }

        $this->redirect('seller/products');
    }

    /**
     * Bulk delete products
     */
    public function bulkDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $productIds = $input['product_ids'] ?? $_POST['product_ids'] ?? [];
            
            // Handle both array and comma-separated string
            if (is_string($productIds)) {
                $productIds = explode(',', $productIds);
            }
            
            if (empty($productIds) || !is_array($productIds)) {
                $this->jsonResponse(['success' => false, 'message' => 'No products selected'], 400);
                return;
            }
            
            // Convert to integers and filter
            $productIds = array_map('intval', $productIds);
            $productIds = array_filter($productIds, function($id) { return $id > 0; });

            if (empty($productIds)) {
                $this->jsonResponse(['success' => false, 'message' => 'No valid product IDs provided'], 400);
                return;
            }

            // Validate all products belong to this seller
            $validIds = [];
            $invalidCount = 0;
            
            foreach ($productIds as $id) {
                $product = $this->productModel->find($id);
                if ($product && isset($product['seller_id']) && $product['seller_id'] == $this->sellerId) {
                    $validIds[] = $id;
                } else {
                    $invalidCount++;
                }
            }

            if (empty($validIds)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'No valid products to delete. All selected products do not belong to you.'
                ], 403);
                return;
            }

            // Delete product images and variants first
            $db = $this->productModel->getDb();
            foreach ($validIds as $id) {
                try {
                    $db->query("DELETE FROM product_images WHERE product_id = ?", [$id])->execute();
                    $db->query("DELETE FROM product_variants WHERE product_id = ?", [$id])->execute();
                } catch (\Exception $e) {
                    error_log('Bulk delete product relations error: ' . $e->getMessage());
                }
            }

            // Use BulkActionService to delete products
            $bulkService = new \App\Services\BulkActionService();
            $result = $bulkService->bulkDelete(
                \App\Models\Product::class,
                $validIds,
                ['seller_id' => $this->sellerId] // Ensure only seller's products are deleted
            );

            if ($result['success']) {
                $message = $result['message'];
                if ($invalidCount > 0) {
                    $message .= " {$invalidCount} product(s) were skipped (not yours).";
                }

                $this->jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'deleted' => $result['count'],
                    'skipped' => $invalidCount
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to delete products'
                ], 400);
            }
        } catch (Exception $e) {
            error_log('Bulk delete error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error deleting products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product image URL with default fallback
     */
    private function getProductImageUrl($product)
    {
        // Check if product has direct image URL
        if (!empty($product['image'])) {
            return $product['image'];
        }
        
        // Check for primary image from product_images table
        if (!empty($product['primary_image_url'])) {
            return $product['primary_image_url'];
        }
        
        if (!empty($product['image_url'])) {
            return $product['image_url'];
        }
        
        // Try to get primary image from database
        $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
        if ($primaryImage && !empty($primaryImage['image_url'])) {
            return $primaryImage['image_url'];
        }
        
        // Fallback to default image
        return \App\Core\View::asset('images/product_default.jpg');
    }

    /**
     * Prepare product data from POST
     */
    private function prepareProductData()
    {
        $selectedCategory = trim($_POST['category'] ?? '');
        if (!CategoryHelper::isValidMainCategory($selectedCategory)) {
            $selectedCategory = array_key_first(CategoryHelper::getMainCategories());
        }

        $data = [
            'product_name' => trim($_POST['product_name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0),
            'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
            'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
            'category' => $selectedCategory,
            'subcategory' => trim($_POST['subcategory'] ?? ''),
            'product_type_main' => trim($_POST['product_type_main'] ?? ''),
            'product_type' => trim($_POST['product_type'] ?? ''),
            'is_digital' => isset($_POST['is_digital']) ? 1 : 0,
            'colors' => trim($_POST['colors'] ?? ''),
            'weight' => trim($_POST['weight'] ?? ''),
            'serving' => trim($_POST['serving'] ?? ''),
            'flavor' => trim($_POST['flavor'] ?? ''),
            'is_featured' => 0,
            'status' => 'pending', // Seller products must be approved by admin
            'approval_status' => 'pending',
            'seller_id' => $this->sellerId
        ];
        
        // Add affiliate_commission if provided (optional, defaults to NULL to use system default)
        // Validate range: 0-50 (as per requirements)
        if (isset($_POST['affiliate_commission']) && $_POST['affiliate_commission'] !== '') {
            $affiliateCommission = (float)$_POST['affiliate_commission'];
            // Validate range: 0-50
            if ($affiliateCommission >= 0 && $affiliateCommission <= 50) {
                $data['affiliate_commission'] = $affiliateCommission;
            } else {
                // Invalid range, use NULL to fall back to default
                error_log("Product creation: Invalid affiliate_commission {$affiliateCommission}% (must be 0-50), using default");
            }
        }
        
        return $data;
    }

    /**
     * Prepare product data for update (preserves existing status and approval)
     */
    private function prepareProductDataForUpdate()
    {
        $selectedCategory = trim($_POST['category'] ?? '');
        if (!CategoryHelper::isValidMainCategory($selectedCategory)) {
            $selectedCategory = array_key_first(CategoryHelper::getMainCategories());
        }

        $data = [
            'product_name' => trim($_POST['product_name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0),
            'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
            'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
            'category' => $selectedCategory,
            'status' => trim($_POST['status'] ?? 'active')
        ];
        
        // Handle affiliate_commission: if empty string, set to NULL; if provided, validate and set
        if (isset($_POST['affiliate_commission'])) {
            if ($_POST['affiliate_commission'] === '' || $_POST['affiliate_commission'] === null) {
                // Empty string means use default, set to NULL
                $data['affiliate_commission'] = null;
            } else {
                $affiliateCommission = (float)$_POST['affiliate_commission'];
                // Validate range: 0-50
                if ($affiliateCommission >= 0 && $affiliateCommission <= 50) {
                    $data['affiliate_commission'] = $affiliateCommission;
                } else {
                    // Invalid range, set to NULL to fall back to default
                    error_log("Product update: Invalid affiliate_commission {$affiliateCommission}% (must be 0-50), using default");
                    $data['affiliate_commission'] = null;
                }
            }
        }
        
        return $data;
    }

    /**
     * Toggle product status
     */
    public function toggleStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            $product = $this->productModel->find($id);
            
            if (!$product || $product['seller_id'] != $this->sellerId) {
                $this->jsonResponse(['success' => false, 'message' => 'Product not found']);
                return;
            }

            $status = $_POST['status'] ?? '';
            if (!in_array($status, ['active', 'inactive'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $result = $this->productModel->setStatus($id, $status);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Product status updated successfully',
                    'status' => $status
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update product status']);
            }
        } catch (Exception $e) {
            error_log('Toggle product status error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error updating product status']);
        }
    }

    /**
     * Handle product image URLs (CDN)
     */
    private function handleProductImages($productId)
    {
        if (!empty($_POST['image_url'])) {
            $imageUrl = trim($_POST['image_url']);
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $this->productImageModel->create([
                    'product_id' => $productId,
                    'image_url' => $imageUrl,
                    'is_primary' => 1
                ]);
            }
        }
        
        // Handle additional image URLs if provided
        if (!empty($_POST['additional_images'])) {
            $additionalImagesInput = $_POST['additional_images'];
            $additionalImages = is_array($additionalImagesInput) 
                ? $additionalImagesInput
                : preg_split('/[\r\n,]+/', $additionalImagesInput);
            
            foreach ($additionalImages as $imageUrl) {
                $imageUrl = trim($imageUrl);
                if ($imageUrl !== '' && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $this->productImageModel->create([
                        'product_id' => $productId,
                        'image_url' => $imageUrl,
                        'is_primary' => 0
                    ]);
                }
            }
        }
    }
}
