<?php

namespace App\Controllers\Seller;

use App\Controllers\Seller\BaseSellerController;
use App\Models\Ad;
use App\Models\AdType;
use App\Models\AdCost;
use App\Models\AdPayment;
use App\Models\Product;

class Ads extends BaseSellerController
{
    private $adModel;
    private $adTypeModel;
    private $adCostModel;
    private $adPaymentModel;
    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->adModel = new Ad();
        $this->adTypeModel = new AdType();
        $this->adCostModel = new AdCost();
        $this->adPaymentModel = new AdPayment();
        $this->productModel = new Product();
    }

    /**
     * List all ads for seller
     */
    public function index()
    {
        $ads = $this->adModel->getBySellerId($this->sellerId);
        
        $this->view('seller/ads/index', [
            'ads' => $ads,
            'page' => 'ads'
        ]);
    }

    /**
     * Show create ad form
     */
    public function create()
    {
        // Only allow product_internal for sellers
        $productInternalType = $this->adTypeModel->findByName('product_internal');
        $adTypes = $productInternalType ? [$productInternalType] : [];
        $products = $this->productModel->getProductsBySellerId($this->sellerId, 100, 0);
        
        // Get min_cpc_rate from settings
        $db = \App\Core\Database::getInstance();
        $minCpcRate = 2.00;
        try {
            $setting = $db->query(
                "SELECT setting_value FROM ad_settings WHERE setting_key = 'min_cpc_rate'"
            )->single();
            if ($setting) {
                $minCpcRate = (float)$setting['setting_value'];
            }
        } catch (\Exception $e) {
            error_log("Error loading min_cpc_rate: " . $e->getMessage());
        }
        
        $this->view('seller/ads/create', [
            'adTypes' => $adTypes,
            'products' => $products,
            'minCpcRate' => $minCpcRate,
            'page' => 'ads'
        ]);
    }


    /**
     * Store new ad
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/ads');
            return;
        }

        // Get min_cpc_rate from settings
        $db = \App\Core\Database::getInstance();
        $minCpcRate = 2.00;
        try {
            $setting = $db->query(
                "SELECT setting_value FROM ad_settings WHERE setting_key = 'min_cpc_rate'"
            )->single();
            if ($setting) {
                $minCpcRate = (float)$setting['setting_value'];
            }
        } catch (\Exception $e) {
            error_log("Error loading min_cpc_rate: " . $e->getMessage());
        }

        // Get form data
        $startDate = $_POST['start_date'] ?? null;
        $durationDays = (int)($_POST['duration_days'] ?? 0);
        $totalClicks = (int)($_POST['total_clicks'] ?? 0);
        $endDate = $_POST['end_date'] ?? null;

        // Validate
        if (!$startDate || $durationDays <= 0 || $totalClicks <= 0) {
            $this->setFlash('error', 'Please fill all required fields');
            $this->redirect('seller/ads/create');
            return;
        }

        // Calculate end_date if not provided
        if (!$endDate && $startDate && $durationDays) {
            $start = new \DateTime($startDate);
            $start->modify('+' . $durationDays . ' days');
            $endDate = $start->format('Y-m-d');
        }

        // Calculate required balance
        $requiredBalance = $minCpcRate * $totalClicks;

        $data = [
            'seller_id' => $this->sellerId,
            'ads_type_id' => $_POST['ads_type_id'] ?? null,
            'product_id' => $_POST['product_id'] ?? null,
            'banner_image' => $_POST['banner_image'] ?? null,
            'banner_link' => $_POST['banner_link'] ?? null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_days' => $durationDays,
            'ads_cost_id' => null, // No longer required for clicks-based system
            'billing_type' => 'per_click',
            'daily_budget' => 0,
            'per_click_rate' => $minCpcRate,
            'per_impression_rate' => 0,
            'total_clicks' => $totalClicks,
            'remaining_clicks' => $totalClicks,
            'current_daily_spend' => 0,
            'current_day_spent' => 0,
            'last_spend_reset_date' => date('Y-m-d'),
            'auto_paused' => 0,
            'status' => 'inactive',
            'approval_status' => 'pending', // New ads require admin approval
            'notes' => $_POST['notes'] ?? null
        ];

        // Validate ad type - sellers can only create product_internal ads
        $adType = $this->adTypeModel->find($data['ads_type_id']);
        if (!$adType || $adType['name'] !== 'product_internal') {
            $this->setFlash('error', 'Sellers can only create product ads');
            $this->redirect('seller/ads/create');
            return;
        }
        
        // Validate product is required
        if (!$data['product_id']) {
            $this->setFlash('error', 'Product is required');
            $this->redirect('seller/ads/create');
            return;
        }
        
        // Verify product belongs to seller
        $product = $this->productModel->getProductById($data['product_id']);
        if (!$product || ($product['seller_id'] ?? 0) != $this->sellerId) {
            $this->setFlash('error', 'Invalid product selected');
            $this->redirect('seller/ads/create');
            return;
        }

        // Check wallet balance (no locking - just check if has enough for at least one click)
        $walletModel = new \App\Models\SellerWallet();
        $wallet = $walletModel->getWalletBySellerId($this->sellerId);
        $walletBalance = (float)($wallet['balance'] ?? 0);

        // Only check if wallet has at least enough for one click
        if ($walletBalance < $minCpcRate) {
            $this->setFlash('error', "Insufficient wallet balance. Minimum required: Rs " . number_format($minCpcRate, 2) . " for at least one click. Current balance: Rs " . number_format($walletBalance, 2));
            $this->redirect('seller/ads/create');
            return;
        }

        // Create ad
        $adId = $this->adModel->create($data);

        if ($adId) {
            $this->setFlash('success', 'Ad created successfully and submitted for admin approval. Required balance: Rs ' . number_format($requiredBalance, 2) . '. You can start/stop the ad after admin approval.');
            $this->redirect('seller/ads');
        } else {
            $this->setFlash('error', 'Failed to create ad');
            $this->redirect('seller/ads/create');
        }
    }

    /**
     * Show ad details
     */
    public function show($id)
    {
        $ad = $this->adModel->findWithDetails($id);
        
        if (!$ad || $ad['seller_id'] != $this->sellerId) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('seller/ads');
            return;
        }

        $statistics = $this->adModel->getStatistics($id);
        $payment = $this->adPaymentModel->getByAdId($id);

        $this->view('seller/ads/show', [
            'ad' => $ad,
            'statistics' => $statistics,
            'payment' => $payment,
            'page' => 'ads'
        ]);
    }

    /**
     * Update ad status
     */
    public function updateStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/ads');
            return;
        }

        $ad = $this->adModel->find($id);
        
        if (!$ad || $ad['seller_id'] != $this->sellerId) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('seller/ads');
            return;
        }

        // Check if ad is approved - only approved ads can be started/stopped
        $approvalStatus = $ad['approval_status'] ?? 'pending';
        if ($approvalStatus !== 'approved') {
            $this->setFlash('error', 'Ad must be approved by admin before you can start/stop it');
            $this->redirect('seller/ads/show/' . $id);
            return;
        }

        $status = $_POST['status'] ?? null;
        
        if (!in_array($status, ['active', 'inactive'])) {
            $this->setFlash('error', 'Invalid status');
            $this->redirect('seller/ads');
            return;
        }

        // For real-time billing, use AdActivationService
        if ($status === 'active') {
            $activationService = new \App\Services\AdActivationService();
            $result = $activationService->activateAd($id);
            
            if (!$result['success']) {
                $this->setFlash('error', $result['message']);
                $this->redirect('seller/ads/show/' . $id);
                return;
            }
            
            $this->setFlash('success', $result['message']);
            $this->redirect('seller/ads/show/' . $id);
            return;
        }

        // Simply update status - no balance locking/unlocking needed
        $this->adModel->updateStatus($id, $status);
        $this->setFlash('success', 'Ad status updated successfully');
        $this->redirect('seller/ads/show/' . $id);
    }

    /**
     * Resume auto-paused ad
     */
    public function resume($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/ads');
            return;
        }

        $ad = $this->adModel->find($id);
        
        if (!$ad || $ad['seller_id'] != $this->sellerId) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('seller/ads');
            return;
        }

        $billingService = new \App\Services\RealTimeAdBillingService();
        $result = $billingService->resumeAd($id);
        
        if ($result['success']) {
            $this->setFlash('success', 'Ad resumed successfully');
        } else {
            $this->setFlash('error', $result['message']);
        }
        
        $this->redirect('seller/ads/show/' . $id);
    }

    /**
     * Delete ad
     */
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/ads');
            return;
        }

        $ad = $this->adModel->find($id);
        
        if (!$ad || $ad['seller_id'] != $this->sellerId) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('seller/ads');
            return;
        }

        $this->adModel->delete($id);
        $this->setFlash('success', 'Ad deleted successfully');
        $this->redirect('seller/ads');
    }
}
