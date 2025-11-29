<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Seller;
use Exception;

class AdminSellerController extends Controller
{
    private $sellerModel;

    public function __construct()
    {
        parent::__construct();
        $this->sellerModel = new Seller();
    }

    /**
     * List all sellers
     */
    public function index()
    {
        $this->requireAdmin();
        
        $statusFilter = $_GET['status'] ?? '';
        $approvalFilter = $_GET['approval'] ?? '';
        
        $sellers = $this->getSellers($statusFilter, $approvalFilter);
        $stats = $this->getSellerStats();
        
        $this->view('admin/seller/index', [
            'sellers' => $sellers,
            'stats' => $stats,
            'statusFilter' => $statusFilter,
            'approvalFilter' => $approvalFilter,
            'title' => 'Seller Management'
        ]);
    }

    /**
     * Create new seller
     */
    public function create()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $this->view('admin/seller/create', [
            'title' => 'Create New Seller'
        ]);
    }

    /**
     * Handle seller creation
     */
    private function handleCreate()
    {
        try {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
                'phone' => trim($_POST['phone'] ?? ''),
                'company_name' => trim($_POST['company_name'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'logo_url' => trim($_POST['logo_url'] ?? ''),
                'citizenship_document_url' => trim($_POST['citizenship_document_url'] ?? ''),
                'pan_vat_type' => $_POST['pan_vat_type'] ?? null,
                'pan_vat_number' => trim($_POST['pan_vat_number'] ?? ''),
                'pan_vat_document_url' => trim($_POST['pan_vat_document_url'] ?? ''),
                'cheque_qr_url' => trim($_POST['cheque_qr_url'] ?? ''),
                'payment_method' => $_POST['payment_method'] ?? null,
                'payment_details' => trim($_POST['payment_details'] ?? ''),
                'status' => $_POST['status'] ?? 'inactive',
                'is_approved' => isset($_POST['is_approved']) ? (int)$_POST['is_approved'] : 0,
                'commission_rate' => (float)($_POST['commission_rate'] ?? 10.00)
            ];
            
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($_POST['password'])) {
                $this->setFlash('error', 'Name, email, and password are required');
                $this->redirect('admin/seller/create');
                return;
            }
            
            // Check if email already exists
            $existing = $this->sellerModel->findByEmail($data['email']);
            if ($existing) {
                $this->setFlash('error', 'Email already exists');
                $this->redirect('admin/seller/create');
                return;
            }
            
            $sellerId = $this->sellerModel->create($data);
            
            if ($sellerId) {
                $this->setFlash('success', 'Seller created successfully');
                $this->redirect('admin/seller/details/' . $sellerId);
            } else {
                $this->setFlash('error', 'Failed to create seller');
                $this->redirect('admin/seller/create');
            }
        } catch (Exception $e) {
            error_log('Create seller error: ' . $e->getMessage());
            $this->setFlash('error', 'Error creating seller: ' . $e->getMessage());
            $this->redirect('admin/seller/create');
        }
    }

    /**
     * View seller details
     */
    public function details($id)
    {
        $this->requireAdmin();
        
        $seller = $this->sellerModel->find($id);
        
        if (!$seller) {
            $this->setFlash('error', 'Seller not found');
            $this->redirect('admin/seller');
            return;
        }

        $stats = $this->getSellerDetailedStats($id);
        
        // Get withdraw stats
        $db = \App\Core\Database::getInstance();
        $withdrawStats = $db->query(
            "SELECT 
                COUNT(*) as total_withdraws,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_withdraws,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_withdraws,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_withdraws,
                COALESCE(SUM(CASE WHEN status IN ('pending', 'approved') THEN amount ELSE 0 END), 0) as pending_amount
            FROM seller_withdraw_requests 
            WHERE seller_id = ?",
            [$id]
        )->single();
        
        // Get wallet balance
        $wallet = $db->query(
            "SELECT balance FROM seller_wallet WHERE seller_id = ?",
            [$id]
        )->single();
        
        // Get seller products
        $products = $db->query(
            "SELECT p.*, 
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                    COUNT(DISTINCT oi.order_id) as order_count,
                    SUM(CASE WHEN o.payment_status = 'paid' THEN oi.total ELSE 0 END) as total_sales
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id
             WHERE p.seller_id = ?
             GROUP BY p.id
             ORDER BY p.created_at DESC",
            [$id]
        )->all();
        
        // Get orders for this seller
        $orders = $db->query(
            "SELECT DISTINCT o.*, 
                    u.first_name, u.last_name, u.email as customer_email,
                    pm.name as payment_method_name,
                    COUNT(DISTINCT oi.id) as item_count,
                    SUM(oi.total) as seller_order_total
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             LEFT JOIN users u ON o.user_id = u.id
             LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
             WHERE oi.seller_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC
             LIMIT 50",
            [$id]
        )->all();
        
        // Get order statistics
        $orderStats = $db->query(
            "SELECT 
                COUNT(DISTINCT CASE WHEN o.status != 'delivered' AND o.status != 'cancelled' THEN o.id END) as not_delivered,
                COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as delivered,
                COUNT(DISTINCT o.id) as total_orders,
                SUM(CASE WHEN o.status = 'delivered' AND o.payment_status = 'paid' AND DATE(o.updated_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN oi.total ELSE 0 END) as week_sales,
                SUM(CASE WHEN o.status = 'delivered' AND o.payment_status = 'paid' AND DATE(o.updated_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN oi.total ELSE 0 END) as month_sales
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             WHERE oi.seller_id = ?",
            [$id]
        )->single();
        
        // Get seller coupons
        $coupons = $db->query(
            "SELECT c.*, 
                    COUNT(cu.id) as usage_count
             FROM coupons c
             LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
             WHERE c.seller_id = ?
             GROUP BY c.id
             ORDER BY c.created_at DESC
             LIMIT 20",
            [$id]
        )->all();
        
        // Get withdraw requests
        $withdrawRequests = $db->query(
            "SELECT wr.*, ba.account_holder_name, ba.bank_name, ba.account_number
             FROM seller_withdraw_requests wr
             LEFT JOIN seller_bank_accounts ba ON wr.bank_account_id = ba.id
             WHERE wr.seller_id = ?
             ORDER BY wr.requested_at DESC
             LIMIT 20",
            [$id]
        )->all();
        
        // Get bank accounts
        $bankAccounts = $db->query(
            "SELECT * FROM seller_bank_accounts 
             WHERE seller_id = ?
             ORDER BY is_default DESC, created_at DESC",
            [$id]
        )->all();
        
        $this->view('admin/seller/details', [
            'seller' => $seller,
            'stats' => $stats,
            'withdrawStats' => $withdrawStats ?? [],
            'walletBalance' => $wallet['balance'] ?? 0,
            'products' => $products ?? [],
            'orders' => $orders ?? [],
            'orderStats' => $orderStats ?? [],
            'coupons' => $coupons ?? [],
            'withdrawRequests' => $withdrawRequests ?? [],
            'bankAccounts' => $bankAccounts ?? [],
            'title' => 'Seller Details - ' . $seller['name']
        ]);
    }

    /**
     * Edit seller
     */
    public function edit($id)
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdate($id);
            return;
        }
        
        $seller = $this->sellerModel->find($id);
        
        if (!$seller) {
            $this->setFlash('error', 'Seller not found');
            $this->redirect('admin/seller');
            return;
        }
        
        $this->view('admin/seller/edit', [
            'seller' => $seller,
            'title' => 'Edit Seller - ' . $seller['name']
        ]);
    }

    /**
     * Approve seller
     */
    public function approve($id)
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/seller');
            return;
        }
        
        try {
            $seller = $this->sellerModel->find($id);
            
            if (!$seller) {
                $this->setFlash('error', 'Seller not found');
                $this->redirect('admin/seller');
                return;
            }
            
            $data = [
                'is_approved' => 1,
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $_SESSION['user_id'] ?? null,
                'status' => 'active',
                'rejection_reason' => null
            ];
            
            $result = $this->sellerModel->update($id, $data);
            
            if ($result) {
                // Send notification to seller
                try {
                    $notificationService = new \App\Services\SellerNotificationService();
                    $notificationService->notifySellerApproval($id, $seller['name'] ?? 'Seller');
                } catch (\Exception $e) {
                    error_log('Seller approval notification error: ' . $e->getMessage());
                }
                
                $this->setFlash('success', 'Seller approved successfully. They can now login.');
            } else {
                $this->setFlash('error', 'Failed to approve seller');
            }
        } catch (Exception $e) {
            error_log('Approve seller error: ' . $e->getMessage());
            $this->setFlash('error', 'Error approving seller');
        }
        
        $this->redirect('admin/seller/details/' . $id);
    }

    /**
     * Reject seller
     */
    public function reject($id)
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/seller');
            return;
        }
        
        try {
            $rejectionReason = trim($_POST['rejection_reason'] ?? '');
            
            if (empty($rejectionReason)) {
                $this->setFlash('error', 'Rejection reason is required');
                $this->redirect('admin/seller/details/' . $id);
                return;
            }
            
            $data = [
                'is_approved' => 0,
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => $rejectionReason
            ];
            
            $result = $this->sellerModel->update($id, $data);
            
            if ($result) {
                $this->setFlash('success', 'Seller rejected. They cannot login until approved.');
            } else {
                $this->setFlash('error', 'Failed to reject seller');
            }
        } catch (Exception $e) {
            error_log('Reject seller error: ' . $e->getMessage());
            $this->setFlash('error', 'Error rejecting seller');
        }
        
        $this->redirect('admin/seller/details/' . $id);
    }

    /**
     * Handle seller update
     */
    private function handleUpdate($id)
    {
        try {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'company_name' => trim($_POST['company_name'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'logo_url' => trim($_POST['logo_url'] ?? ''),
                'citizenship_document_url' => trim($_POST['citizenship_document_url'] ?? ''),
                'pan_vat_type' => $_POST['pan_vat_type'] ?? null,
                'pan_vat_number' => trim($_POST['pan_vat_number'] ?? ''),
                'pan_vat_document_url' => trim($_POST['pan_vat_document_url'] ?? ''),
                'cheque_qr_url' => trim($_POST['cheque_qr_url'] ?? ''),
                'payment_method' => $_POST['payment_method'] ?? null,
                'payment_details' => trim($_POST['payment_details'] ?? ''),
                'status' => $_POST['status'] ?? 'active',
                'commission_rate' => (float)($_POST['commission_rate'] ?? 10.00)
            ];
            
            $result = $this->sellerModel->update($id, $data);
            
            if ($result) {
                $this->setFlash('success', 'Seller updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update seller');
            }
        } catch (Exception $e) {
            error_log('Update seller error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating seller');
        }
        
        $this->redirect('admin/seller/details/' . $id);
    }

    /**
     * Get sellers with filters
     */
    private function getSellers($statusFilter = '', $approvalFilter = '')
    {
        $db = \App\Core\Database::getInstance();
        $params = [];
        $where = [];
        
        if ($statusFilter) {
            $where[] = "s.status = ?";
            $params[] = $statusFilter;
        }
        
        if ($approvalFilter === 'approved') {
            $where[] = "s.is_approved = 1";
        } elseif ($approvalFilter === 'pending') {
            $where[] = "(s.is_approved = 0 OR s.is_approved IS NULL)";
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT s.*, 
                       COUNT(DISTINCT p.id) as total_products,
                       COUNT(DISTINCT o.id) as total_orders,
                       COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END), 0) as total_revenue
                FROM sellers s
                LEFT JOIN products p ON s.id = p.seller_id
                LEFT JOIN orders o ON s.id = o.seller_id
                {$whereClause}
                GROUP BY s.id
                ORDER BY s.created_at DESC";
        
        return $db->query($sql, $params)->all();
    }

    /**
     * Get seller statistics
     */
    private function getSellerStats()
    {
        $db = \App\Core\Database::getInstance();
        
        $stats = [
            'total' => 0,
            'approved' => 0,
            'pending' => 0,
            'active' => 0,
            'inactive' => 0
        ];
        
        $result = $db->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN is_approved = 0 OR is_approved IS NULL THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status != 'active' THEN 1 ELSE 0 END) as inactive
            FROM sellers")->single();
        
        return array_merge($stats, $result ?? []);
    }

    /**
     * Get detailed stats for a seller
     */
    private function getSellerDetailedStats($sellerId)
    {
        $db = \App\Core\Database::getInstance();
        
        $stats = [
            'total_products' => 0,
            'active_products' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'pending_orders' => 0
        ];
        
        $result = $db->query("SELECT 
            COUNT(DISTINCT p.id) as total_products,
            SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_products,
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END), 0) as total_revenue,
            SUM(CASE WHEN o.status IN ('pending', 'processing') THEN 1 ELSE 0 END) as pending_orders
            FROM sellers s
            LEFT JOIN products p ON s.id = p.seller_id
            LEFT JOIN orders o ON s.id = o.seller_id
            WHERE s.id = ?", [$sellerId])->single();
        
        return array_merge($stats, $result ?? []);
    }

    /**
     * View seller withdraw requests
     */
    public function withdraws($sellerId = null)
    {
        $this->requireAdmin();
        
        $db = \App\Core\Database::getInstance();
        
        if ($sellerId) {
            // View withdraws for specific seller
            $seller = $this->sellerModel->find($sellerId);
            if (!$seller) {
                $this->setFlash('error', 'Seller not found');
                $this->redirect('admin/seller');
                return;
            }
            
            $withdraws = $db->query(
                "SELECT wr.*, ba.account_holder_name, ba.bank_name, ba.account_number
                 FROM seller_withdraw_requests wr
                 LEFT JOIN seller_bank_accounts ba ON wr.bank_account_id = ba.id
                 WHERE wr.seller_id = ?
                 ORDER BY wr.requested_at DESC",
                [$sellerId]
            )->all();
            
            $this->view('admin/seller/withdraws', [
                'seller' => $seller,
                'withdraws' => $withdraws,
                'title' => 'Seller Withdrawals - ' . $seller['name']
            ]);
        } else {
            // View all seller withdraws
            $statusFilter = $_GET['status'] ?? '';
            $params = [];
            $where = [];
            
            if ($statusFilter) {
                $where[] = "wr.status = ?";
                $params[] = $statusFilter;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $withdraws = $db->query(
                "SELECT wr.*, s.name as seller_name, s.company_name, s.email as seller_email,
                        ba.account_holder_name, ba.bank_name, ba.account_number
                 FROM seller_withdraw_requests wr
                 LEFT JOIN sellers s ON wr.seller_id = s.id
                 LEFT JOIN seller_bank_accounts ba ON wr.bank_account_id = ba.id
                 {$whereClause}
                 ORDER BY wr.requested_at DESC",
                $params
            )->all();
            
            $stats = $this->getWithdrawStats();
            
            $this->view('admin/seller/withdraws-all', [
                'withdraws' => $withdraws,
                'stats' => $stats,
                'statusFilter' => $statusFilter,
                'title' => 'All Seller Withdrawals'
            ]);
        }
    }

    /**
     * Approve seller withdraw request
     */
    public function approveWithdraw($id)
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/seller/withdraws');
            return;
        }
        
        try {
            $db = \App\Core\Database::getInstance();
            
            $withdraw = $db->query(
                "SELECT wr.*, s.id as seller_id 
                 FROM seller_withdraw_requests wr
                 LEFT JOIN sellers s ON wr.seller_id = s.id
                 WHERE wr.id = ?",
                [$id]
            )->single();
            
            if (!$withdraw) {
                $this->setFlash('error', 'Withdrawal request not found');
                $this->redirect('admin/seller/withdraws');
                return;
            }
            
            $adminComment = trim($_POST['admin_comment'] ?? trim($_POST['admin_notes'] ?? ''));
            
            $result = $db->query(
                "UPDATE seller_withdraw_requests 
                 SET status = 'approved', 
                     processed_at = NOW(),
                     admin_comment = ?
                 WHERE id = ?",
                [$adminComment, $id]
            )->execute();
            
            if ($result) {
                // Deduct from seller wallet
                $walletModel = new \App\Models\SellerWallet();
                $wallet = $walletModel->getWalletBySellerId($withdraw['seller_id']);
                
                if ($wallet && $wallet['balance'] >= $withdraw['amount']) {
                    // Update wallet: deduct balance, update total_withdrawals, reduce pending_withdrawals
                    $newBalance = $wallet['balance'] - $withdraw['amount'];
                    $newTotalWithdrawals = ($wallet['total_withdrawals'] ?? 0) + $withdraw['amount'];
                    $newPending = max(0, ($wallet['pending_withdrawals'] ?? 0) - $withdraw['amount']);
                    
                    $db->query(
                        "UPDATE seller_wallet 
                         SET balance = ?, 
                             total_withdrawals = ?,
                             pending_withdrawals = ?,
                             updated_at = NOW() 
                         WHERE seller_id = ?",
                        [$newBalance, $newTotalWithdrawals, $newPending, $withdraw['seller_id']]
                    )->execute();
                    
                    // Record wallet transaction
                    $walletModel->addTransaction([
                        'seller_id' => $withdraw['seller_id'],
                        'type' => 'debit',
                        'amount' => $withdraw['amount'],
                        'description' => 'Withdrawal approved - Bank Transfer',
                        'withdraw_request_id' => $id,
                        'balance_after' => $newBalance,
                        'status' => 'completed'
                    ]);
                    
                    // Notify seller about withdrawal approval
                    try {
                        $notificationService = new \App\Services\SellerNotificationService();
                        $notificationService->notifyWithdrawalApproved($id, $withdraw['seller_id'], $withdraw['amount']);
                    } catch (\Exception $e) {
                        error_log('Withdrawal approval notification error: ' . $e->getMessage());
                    }
                } else {
                    $this->setFlash('error', 'Insufficient wallet balance');
                }
                
                $this->setFlash('success', 'Withdrawal approved successfully');
            } else {
                $this->setFlash('error', 'Failed to approve withdrawal');
            }
        } catch (Exception $e) {
            error_log('Approve withdraw error: ' . $e->getMessage());
            $this->setFlash('error', 'Error approving withdrawal');
        }
        
        $redirectUrl = !empty($withdraw['seller_id']) 
            ? 'admin/seller/withdraws/' . $withdraw['seller_id']
            : 'admin/seller/withdraws';
        $this->redirect($redirectUrl);
    }

    /**
     * Reject seller withdraw request
     */
    public function rejectWithdraw($id)
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/seller/withdraws');
            return;
        }
        
        try {
            $db = \App\Core\Database::getInstance();
            
            $withdraw = $db->query(
                "SELECT wr.*, s.id as seller_id 
                 FROM seller_withdraw_requests wr
                 LEFT JOIN sellers s ON wr.seller_id = s.id
                 WHERE wr.id = ?",
                [$id]
            )->single();
            
            if (!$withdraw) {
                $this->setFlash('error', 'Withdrawal request not found');
                $this->redirect('admin/seller/withdraws');
                return;
            }
            
            $rejectionReason = trim($_POST['rejection_reason'] ?? '');
            
            if (empty($rejectionReason)) {
                $this->setFlash('error', 'Rejection reason is required');
                $redirectUrl = !empty($withdraw['seller_id']) 
                    ? 'admin/seller/withdraws/' . $withdraw['seller_id']
                    : 'admin/seller/withdraws';
                $this->redirect($redirectUrl);
                return;
            }
            
            $result = $db->query(
                "UPDATE seller_withdraw_requests 
                 SET status = 'rejected', 
                     processed_at = NOW(),
                     admin_comment = ?
                 WHERE id = ?",
                [$rejectionReason, $id]
            )->execute();
            
            if ($result) {
                // Return pending amount to available balance (reduce pending_withdrawals)
                $walletModel = new \App\Models\SellerWallet();
                $wallet = $walletModel->getWalletBySellerId($withdraw['seller_id']);
                
                if ($wallet) {
                    // Reduce pending_withdrawals (money back to available)
                    $newPending = max(0, ($wallet['pending_withdrawals'] ?? 0) - $withdraw['amount']);
                    
                    $db->query(
                        "UPDATE seller_wallet 
                         SET pending_withdrawals = ?,
                             updated_at = NOW() 
                         WHERE seller_id = ?",
                        [$newPending, $withdraw['seller_id']]
                    )->execute();
                }
                
                $this->setFlash('success', 'Withdrawal rejected');
            } else {
                $this->setFlash('error', 'Failed to reject withdrawal');
            }
        } catch (Exception $e) {
            error_log('Reject withdraw error: ' . $e->getMessage());
            $this->setFlash('error', 'Error rejecting withdrawal');
        }
        
        $redirectUrl = !empty($withdraw['seller_id']) 
            ? 'admin/seller/withdraws/' . $withdraw['seller_id']
            : 'admin/seller/withdraws';
        $this->redirect($redirectUrl);
    }

    /**
     * Complete seller withdraw (mark as paid)
     */
    public function completeWithdraw($id)
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/seller/withdraws');
            return;
        }
        
        try {
            $db = \App\Core\Database::getInstance();
            
            $withdraw = $db->query(
                "SELECT wr.*, s.id as seller_id 
                 FROM seller_withdraw_requests wr
                 LEFT JOIN sellers s ON wr.seller_id = s.id
                 WHERE wr.id = ?",
                [$id]
            )->single();
            
            if (!$withdraw) {
                $this->setFlash('error', 'Withdrawal request not found');
                $this->redirect('admin/seller/withdraws');
                return;
            }
            
            if ($withdraw['status'] !== 'approved') {
                $this->setFlash('error', 'Withdrawal must be approved first');
                $redirectUrl = !empty($withdraw['seller_id']) 
                    ? 'admin/seller/withdraws/' . $withdraw['seller_id']
                    : 'admin/seller/withdraws';
                $this->redirect($redirectUrl);
                return;
            }
            
            $paymentNotes = trim($_POST['payment_notes'] ?? trim($_POST['admin_comment'] ?? ''));
            
            $result = $db->query(
                "UPDATE seller_withdraw_requests 
                 SET status = 'completed', 
                     processed_at = NOW(),
                     admin_comment = ?
                 WHERE id = ?",
                [$paymentNotes, $id]
            )->execute();
            
            if ($result) {
                $this->setFlash('success', 'Withdrawal marked as completed');
            } else {
                $this->setFlash('error', 'Failed to complete withdrawal');
            }
        } catch (Exception $e) {
            error_log('Complete withdraw error: ' . $e->getMessage());
            $this->setFlash('error', 'Error completing withdrawal');
        }
        
        $redirectUrl = !empty($withdraw['seller_id']) 
            ? 'admin/seller/withdraws/' . $withdraw['seller_id']
            : 'admin/seller/withdraws';
        $this->redirect($redirectUrl);
    }

    /**
     * Get withdraw statistics
     */
    private function getWithdrawStats()
    {
        $db = \App\Core\Database::getInstance();
        
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'completed' => 0,
            'total_amount' => 0
        ];
        
        $result = $db->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            COALESCE(SUM(amount), 0) as total_amount
            FROM seller_withdraw_requests")->single();
        
        return array_merge($stats, $result ?? []);
    }
}

