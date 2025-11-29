<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Product;
use App\Models\ProductImage;

/**
 * Admin Product Controller
 * Handles all product-related admin operations
 * Extracted from AdminController for better code organization
 */
class AdminProductController extends Controller
{
    private $productModel;
    private $productImageModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
    }

    /**
     * List products
     */
    public function index()
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
    public function create()
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->addProduct();
    }

    /**
     * Edit product
     */
    public function edit($id = null)
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->editProduct($id);
    }

    /**
     * Update product
     */
    public function update($id = null)
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->updateProduct($id);
    }

    /**
     * Delete product
     */
    public function delete($id = null)
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->deleteProduct($id);
    }

    /**
     * Bulk delete products
     */
    public function bulkDelete()
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->bulkDeleteProducts();
    }

    /**
     * Update stock (AJAX)
     */
    public function updateStock()
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->updateStock();
    }

    /**
     * Delete product image
     */
    public function deleteImage($imageId = null)
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->deleteProductImage($imageId);
    }

    /**
     * Set primary image
     */
    public function setPrimaryImage($imageId = null)
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->setPrimaryImage($imageId);
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate()
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->bulkUpdateProducts();
    }

    /**
     * Export products
     */
    public function export()
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->exportProducts();
    }

    /**
     * Import products
     */
    public function import()
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->importProducts();
    }

    /**
     * Best selling products
     */
    public function bestSelling()
    {
        // Delegate to AdminController for now (maintain compatibility)
        $adminController = new AdminController();
        return $adminController->bestSellingProducts();
    }
}

