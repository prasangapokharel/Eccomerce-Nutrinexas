<?php

namespace App\Controllers\Seller;

use App\Models\Coupon;
use Exception;

class Marketing extends BaseSellerController
{
    private $couponModel;

    public function __construct()
    {
        parent::__construct();
        $this->couponModel = new Coupon();
    }

    /**
     * Marketing tools
     */
    public function index()
    {
        $coupons = $this->getSellerCoupons();

        $this->view('seller/marketing/index', [
            'title' => 'Marketing Tools',
            'coupons' => $coupons
        ]);
    }

    /**
     * Create coupon
     */
    public function createCoupon()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreateCoupon();
            return;
        }

        $this->view('seller/marketing/create-coupon', [
            'title' => 'Create Coupon'
        ]);
    }

    /**
     * Handle coupon creation
     */
    private function handleCreateCoupon()
    {
        try {
            $data = [
                'code' => strtoupper(trim($_POST['code'] ?? '')),
                'discount_type' => $_POST['discount_type'] ?? 'percentage',
                'discount_value' => (float)($_POST['discount_value'] ?? 0),
                'min_order_amount' => (float)($_POST['min_purchase'] ?? 0),
                'max_discount_amount' => !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null,
                'usage_limit_global' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
                'expires_at' => !empty($_POST['valid_until']) ? $_POST['valid_until'] . ' 23:59:59' : null,
                'is_active' => ($_POST['status'] ?? 'active') === 'active' ? 1 : 0,
                'status' => 'private',
                'seller_id' => $this->sellerId
            ];

            $result = $this->couponModel->createCoupon($data);
            
            if ($result) {
                $this->setFlash('success', 'Coupon created successfully');
                $this->redirect('seller/marketing');
            } else {
                $this->setFlash('error', 'Failed to create coupon');
                $this->redirect('seller/marketing/create-coupon');
            }
        } catch (Exception $e) {
            error_log('Create coupon error: ' . $e->getMessage());
            $this->setFlash('error', 'Error creating coupon: ' . $e->getMessage());
            $this->redirect('seller/marketing/create-coupon');
        }
    }

    /**
     * Get seller coupons
     */
    private function getSellerCoupons()
    {
        $db = \App\Core\Database::getInstance();
        return $db->query(
            "SELECT * FROM coupons WHERE seller_id = ? ORDER BY created_at DESC",
            [$this->sellerId]
        )->all();
    }
}

