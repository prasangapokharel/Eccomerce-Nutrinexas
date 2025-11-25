<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ad;
use App\Models\Product;
use App\Models\SellerWallet;

class AdValidationService
{
    private $db;
    private $adModel;
    private $productModel;
    private $walletModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->adModel = new Ad();
        $this->productModel = new Product();
        $this->walletModel = new SellerWallet();
    }

    /**
     * Validate ad before activation
     * 
     * @param int $adId
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateBeforeActivation($adId)
    {
        $errors = [];
        $ad = $this->adModel->find($adId);
        
        if (!$ad) {
            return ['valid' => false, 'errors' => ['Ad not found']];
        }

        // Get admin settings
        $adminSettings = $this->getAdminSettings();

        // 1. Validate total clicks
        $totalClicks = (int)($ad['total_clicks'] ?? 0);
        if ($totalClicks <= 0) {
            $errors[] = "Total clicks must be greater than 0";
            $errors[] = "Total clicks must be at least 1";
        }

        // 2. Validate wallet has minimum balance for at least one click
        $wallet = $this->walletModel->getWalletBySellerId($ad['seller_id']);
        $walletBalance = (float)($wallet['balance'] ?? 0);
        $minCpcRate = (float)($adminSettings['min_cpc_rate'] ?? 2.00);
        
        // Check if wallet has at least enough for one click
        if ($walletBalance < $minCpcRate) {
            $errors[] = "Insufficient wallet balance. Minimum required: Rs " . number_format($minCpcRate, 2) . " for at least one click. Current balance: Rs " . number_format($walletBalance, 2);
        }

        // 3. Validate start_date and end_date
        $today = date('Y-m-d');
        $startDate = $ad['start_date'] ?? null;
        $endDate = $ad['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            $errors[] = "Start date and end date are required";
        } else {
            if (strtotime($startDate) > strtotime($endDate)) {
                $errors[] = "Start date cannot be after end date";
            }
            if (strtotime($endDate) < strtotime($today)) {
                $errors[] = "End date cannot be in the past";
            }
        }

        // 4. Validate product exists and approved
        if ($ad['product_id']) {
            $product = $this->productModel->getProductById($ad['product_id']);
            if (!$product) {
                $errors[] = "Product not found";
            } elseif ($product['status'] !== 'active' && $product['status'] !== 'approved') {
                $errors[] = "Product is not approved or active";
            }
        }

        // 5. Validate ad type is allowed
        $adTypeId = $ad['ads_type_id'] ?? null;
        if (!$adTypeId) {
            $errors[] = "Ad type is required";
        }

        // 6. Check blocklisted products/categories (if admin has set them)
        if ($ad['product_id']) {
            $blockListedProducts = $adminSettings['block_listed_products'] ?? '';
            if (!empty($blockListedProducts)) {
                $blockedIds = array_map('trim', explode(',', $blockListedProducts));
                if (in_array($ad['product_id'], $blockedIds)) {
                    $errors[] = "This product is blocklisted";
                }
            }

            if ($product && isset($product['category'])) {
                $blockListedCategories = $adminSettings['block_listed_categories'] ?? '';
                if (!empty($blockListedCategories)) {
                    $blockedCats = array_map('trim', explode(',', $blockListedCategories));
                    if (in_array($product['category'], $blockedCats)) {
                        $errors[] = "This product category is blocklisted";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get admin settings for ads
     * 
     * @return array
     */
    private function getAdminSettings()
    {
        $settings = [];
        
        try {
            $dbSettings = $this->db->query(
                "SELECT setting_key, setting_value FROM ad_admin_settings"
            )->all();
            
            foreach ($dbSettings as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
        } catch (\Exception $e) {
            error_log("AdValidationService: Error loading admin settings: " . $e->getMessage());
        }
        
        return $settings;
    }
}
