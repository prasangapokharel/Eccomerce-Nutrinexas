<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Product;
use Exception;

class AdminSellerProductsController extends Controller
{
    private $productModel;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->db = \App\Core\Database::getInstance();
    }

    /**
     * List seller products for approval
     */
    public function index()
    {
        $this->requireAdmin();
        
        $statusFilter = $_GET['status'] ?? '';
        $approvalFilter = $_GET['approval'] ?? 'pending';
        
        $params = [];
        $where = ["p.seller_id IS NOT NULL", "p.seller_id > 0"];
        
        if ($statusFilter) {
            $where[] = "p.status = ?";
            $params[] = $statusFilter;
        }
        
        if ($approvalFilter === 'pending') {
            $where[] = "(p.approval_status = 'pending' OR p.approval_status IS NULL)";
        } elseif ($approvalFilter === 'approved') {
            $where[] = "p.approval_status = 'approved'";
        } elseif ($approvalFilter === 'rejected') {
            $where[] = "p.approval_status = 'rejected'";
        }
        
        $whereClause = implode(' AND ', $where);
        
        $products = $this->db->query(
            "SELECT p.*, s.name as seller_name, s.email as seller_email,
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
             FROM products p
             LEFT JOIN sellers s ON p.seller_id = s.id
             WHERE {$whereClause}
             ORDER BY p.created_at DESC",
            $params
        )->all();
        
        $stats = $this->getStats();
        
        $this->view('admin/seller/products/index', [
            'title' => 'Seller Products - Approval',
            'products' => $products,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'approvalFilter' => $approvalFilter
        ]);
    }

    /**
     * View product details for approval
     */
    public function detail($id)
    {
        $this->requireAdmin();
        
        $product = $this->db->query(
            "SELECT p.*, s.name as seller_name, s.email as seller_email, s.phone as seller_phone
             FROM products p
             LEFT JOIN sellers s ON p.seller_id = s.id
             WHERE p.id = ? AND p.seller_id IS NOT NULL AND p.seller_id > 0",
            [$id]
        )->single();
        
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('admin/seller/products');
            return;
        }
        
        $images = $this->db->query(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC",
            [$id]
        )->all();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleApproval($id);
            return;
        }
        
        $this->view('admin/seller/products/detail', [
            'title' => 'Review Product - ' . $product['product_name'],
            'product' => $product,
            'images' => $images
        ]);
    }

    /**
     * Handle product approval/rejection
     */
    private function handleApproval($id)
    {
        $action = $_POST['action'] ?? '';
        $notes = trim($_POST['approval_notes'] ?? '');
        
        if (!in_array($action, ['approve', 'reject'])) {
            $this->setFlash('error', 'Invalid action');
            $this->redirect('admin/seller/products/detail/' . $id);
            return;
        }
        
        try {
            $adminId = \App\Core\Session::get('user_id');
            
            if ($action === 'approve') {
                $data = [
                    'approval_status' => 'approved',
                    'status' => 'active',
                    'approval_notes' => $notes,
                    'approved_by' => $adminId,
                    'approved_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $data = [
                    'approval_status' => 'rejected',
                    'status' => 'inactive',
                    'approval_notes' => $notes,
                    'approved_by' => $adminId,
                    'approved_at' => date('Y-m-d H:i:s')
                ];
            }
            
            $result = $this->productModel->updateProduct($id, $data);
            
            if ($result) {
                // Clear homepage cache when product is approved/rejected
                if (class_exists('App\Helpers\PerformanceCache')) {
                    try {
                        // Clear all homepage cache files
                        $cacheDir = ROOT_DIR . '/App/storage/cache/static/';
                        if (is_dir($cacheDir)) {
                            $files = glob($cacheDir . '*');
                            foreach ($files as $file) {
                                if (is_file($file)) {
                                    $content = @file_get_contents($file);
                                    if ($content) {
                                        $data = @unserialize(@gzuncompress($content));
                                        if ($data && isset($data['content']) && is_array($data['content'])) {
                                            // Check if this is homepage data cache
                                            if (isset($data['content']['sliders']) || isset($data['content']['products'])) {
                                                @unlink($file);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // Clear database query cache
                        $dbCacheDir = ROOT_DIR . '/App/storage/cache/database/';
                        if (is_dir($dbCacheDir)) {
                            $files = glob($dbCacheDir . '*');
                            foreach ($files as $file) {
                                if (is_file($file)) {
                                    @unlink($file);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        error_log('Cache clear error: ' . $e->getMessage());
                    }
                }
                
                // Create notification for seller
                $product = $this->productModel->find($id);
                if ($product && $product['seller_id']) {
                    // Use SellerNotificationService for consistency
                    if ($action === 'approve') {
                        try {
                            $notificationService = new \App\Services\SellerNotificationService();
                            $notificationService->notifyAdApproved($product['seller_id'], $product['product_name']);
                        } catch (\Exception $e) {
                            error_log('Product approval notification error: ' . $e->getMessage());
                        }
                    }
                    
                    // Keep existing notification system as backup
                    $this->createNotification(
                        $product['seller_id'],
                        $action === 'approve' ? 'product_approved' : 'product_rejected',
                        $action === 'approve' ? 'Product Approved' : 'Product Rejected',
                        $action === 'approve' 
                            ? "Your product '{$product['product_name']}' has been approved and is now live."
                            : "Your product '{$product['product_name']}' has been rejected. " . ($notes ? "Reason: {$notes}" : ""),
                        'seller/products'
                    );
                }
                
                $this->setFlash('success', 'Product ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully');
            } else {
                $this->setFlash('error', 'Failed to update product');
            }
        } catch (Exception $e) {
            error_log('Product approval error: ' . $e->getMessage());
            $this->setFlash('error', 'Error processing approval');
        }
        
        $this->redirect('admin/seller/products');
    }

    /**
     * Get statistics
     */
    private function getStats()
    {
        $stats = $this->db->query(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN approval_status = 'pending' OR approval_status IS NULL THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected
             FROM products
             WHERE seller_id IS NOT NULL AND seller_id > 0"
        )->single();
        
        return $stats ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    }

    /**
     * Create notification for seller
     */
    private function createNotification($sellerId, $type, $title, $message, $link = '')
    {
        try {
            $this->db->query(
                "INSERT INTO seller_notifications (seller_id, type, title, message, link, icon) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$sellerId, $type, $title, $message, $link, 'fas fa-box']
            )->execute();
        } catch (Exception $e) {
            error_log('Create notification error: ' . $e->getMessage());
        }
    }
}

