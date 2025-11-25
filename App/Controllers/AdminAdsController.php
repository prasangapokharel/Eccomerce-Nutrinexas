<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Ad;
use App\Models\AdType;
use App\Models\AdCost;
use App\Models\AdPayment;

class AdminAdsController extends Controller
{
    private $adModel;
    private $adTypeModel;
    private $adCostModel;
    private $adPaymentModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->adModel = new Ad();
        $this->adTypeModel = new AdType();
        $this->adCostModel = new AdCost();
        $this->adPaymentModel = new AdPayment();
    }

    /**
     * List all ads
     */
    public function index()
    {
        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Get total count
        $totalCount = $this->adModel->getDb()->query("SELECT COUNT(*) as count FROM ads")->single()['count'];
        $totalPages = ceil($totalCount / $perPage);

        // Get ads with pagination
        $allAds = $this->adModel->getAllWithDetails();
        $ads = array_slice($allAds, $offset, $perPage);
        
        $this->view('admin/ads/index', [
            'ads' => $ads,
            'title' => 'Manage Ads',
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'perPage' => $perPage
        ]);
    }

    /**
     * Show ad details
     */
    public function show($id)
    {
        $ad = $this->adModel->findWithDetails($id);
        
        if (!$ad) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('admin/ads');
            return;
        }

        $statistics = $this->adModel->getStatistics($id);
        $payment = $this->adPaymentModel->getByAdId($id);

        $this->view('admin/ads/show', [
            'ad' => $ad,
            'statistics' => $statistics,
            'payment' => $payment,
            'title' => 'Ad Details'
        ]);
    }

    /**
     * Update ad status
     */
    public function updateStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads');
            return;
        }

        $status = $_POST['status'] ?? null;
        
        if (!in_array($status, ['active', 'inactive', 'suspended', 'expired'])) {
            $this->setFlash('error', 'Invalid status');
            $this->redirect('admin/ads');
            return;
        }

        $ad = $this->adModel->find($id);
        if (!$ad) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('admin/ads');
            return;
        }

        // If trying to activate, use AdActivationService
        if ($status === 'active') {
            $activationService = new \App\Services\AdActivationService();
            $result = $activationService->activateAd($id);
            
            if (!$result['success']) {
                $this->setFlash('error', $result['message']);
                $this->redirect('admin/ads/show/' . $id);
                return;
            }
            
            $this->setFlash('success', $result['message']);
            $this->redirect('admin/ads/show/' . $id);
            return;
        }

        // Handle rejection with reason
        $rejectionReason = null;
        if ($status === 'suspended' && isset($_POST['rejection_reason'])) {
            $rejectionReason = trim($_POST['rejection_reason']);
        }
        
        $this->adModel->updateStatus($id, $status, null, null, $rejectionReason);
        
        if ($status === 'suspended' && $rejectionReason) {
            $this->setFlash('success', 'Ad rejected and suspended. Seller has been notified.');
        } else {
            $this->setFlash('success', 'Ad status updated successfully');
        }
        
        $this->redirect('admin/ads/show/' . $id);
    }
    
    /**
     * Reject ad with reason
     */
    public function reject($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads/show/' . $id);
            return;
        }
        
        $ad = $this->adModel->find($id);
        if (!$ad) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('admin/ads');
            return;
        }
        
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        if (empty($rejectionReason)) {
            $this->setFlash('error', 'Rejection reason is required');
            $this->redirect('admin/ads/show/' . $id);
            return;
        }
        
        // Update ad status to suspended with rejection reason
        $notes = ($ad['notes'] ?? '') . "\n[REJECTED: " . date('Y-m-d H:i:s') . "] Reason: " . $rejectionReason;
        $this->adModel->updateStatus($id, 'suspended');
        
        // Update notes with rejection reason
        $this->adModel->getDb()->query(
            "UPDATE ads SET notes = ? WHERE id = ?",
            [$notes, $id]
        )->execute();
        
        $this->setFlash('success', 'Ad rejected and suspended. Seller has been notified.');
        $this->redirect('admin/ads/show/' . $id);
    }

    /**
     * Manage ad costs - only min_cpc_rate
     */
    public function costs()
    {
        $db = \App\Core\Database::getInstance();
        $minCpcRate = 2.00;
        
        // Get min_cpc_rate from ad_settings
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
        
        $this->view('admin/ads/costs', [
            'minCpcRate' => $minCpcRate,
            'title' => 'Manage Ad Cost Settings'
        ]);
    }

    /**
     * Update min CPC rate setting
     */
    public function updateSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads/costs');
            return;
        }

        $db = \App\Core\Database::getInstance();
        
        // Ensure ad_settings table exists
        try {
            $db->query(
                "CREATE TABLE IF NOT EXISTS `ad_settings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
                    `setting_value` VARCHAR(255) NOT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_setting_key` (`setting_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
            )->execute();
        } catch (\Exception $e) {
            error_log("Error creating ad_settings table: " . $e->getMessage());
            $this->setFlash('error', 'Error creating settings table: ' . $e->getMessage());
            $this->redirect('admin/ads/costs');
            return;
        }
        
        $minCpcRate = (float)($_POST['min_cpc_rate'] ?? 2);
        
        if ($minCpcRate <= 0) {
            $this->setFlash('error', 'Minimum CPC rate must be greater than 0');
            $this->redirect('admin/ads/costs');
            return;
        }
        
        $db->query(
            "INSERT INTO ad_settings (setting_key, setting_value) 
             VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = ?",
            ['min_cpc_rate', $minCpcRate, $minCpcRate]
        )->execute();
        
        $this->setFlash('success', 'Minimum CPC rate updated successfully');
        $this->redirect('admin/ads/costs');
    }

    /**
     * Create ad cost
     */
    public function createCost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads/costs');
            return;
        }

        $data = [
            'ads_type_id' => $_POST['ads_type_id'] ?? null,
            'duration_days' => $_POST['duration_days'] ?? null,
            'cost_amount' => $_POST['cost_amount'] ?? null
        ];

        if (!$data['ads_type_id'] || !$data['duration_days'] || !$data['cost_amount']) {
            $this->setFlash('error', 'Please fill all fields');
            $this->redirect('admin/ads/costs');
            return;
        }

        // Validate cost amount
        $costAmount = (float)$data['cost_amount'];
        if ($costAmount <= 0) {
            $this->setFlash('error', 'Cost amount must be greater than 0');
            $this->redirect('admin/ads/costs');
            return;
        }

        $this->adCostModel->getDb()->query(
            "INSERT INTO ads_costs (ads_type_id, duration_days, cost_amount) VALUES (?, ?, ?)",
            [$data['ads_type_id'], $data['duration_days'], $costAmount]
        )->execute();

        $this->setFlash('success', 'Ad cost plan created successfully');
        $this->redirect('admin/ads/costs');
    }

    /**
     * Edit ad cost
     */
    public function editCost($id)
    {
        $cost = $this->adCostModel->find($id);
        if (!$cost) {
            $this->setFlash('error', 'Cost plan not found');
            $this->redirect('admin/ads/costs');
            return;
        }

        $adTypes = $this->adTypeModel->getAll();
        
        $this->view('admin/ads/cost-edit', [
            'cost' => $cost,
            'adTypes' => $adTypes,
            'title' => 'Edit Ad Cost'
        ]);
    }

    /**
     * Update ad cost
     */
    public function updateCost($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads/costs');
            return;
        }

        $cost = $this->adCostModel->find($id);
        if (!$cost) {
            $this->setFlash('error', 'Cost plan not found');
            $this->redirect('admin/ads/costs');
            return;
        }

        $durationDays = (int)($_POST['duration_days'] ?? 0);
        $costAmount = (float)($_POST['cost_amount'] ?? 0);

        if ($durationDays <= 0 || $costAmount <= 0) {
            $this->setFlash('error', 'Invalid values');
            $this->redirect('admin/ads/costs/edit/' . $id);
            return;
        }

        $this->adCostModel->getDb()->query(
            "UPDATE ads_costs SET duration_days = ?, cost_amount = ? WHERE id = ?",
            [$durationDays, $costAmount, $id]
        )->execute();

        $this->setFlash('success', 'Cost plan updated successfully');
        $this->redirect('admin/ads/costs');
    }

    /**
     * Delete ad cost
     */
    public function deleteCost($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads/costs');
            return;
        }

        $cost = $this->adCostModel->find($id);
        if (!$cost) {
            $this->setFlash('error', 'Cost plan not found');
            $this->redirect('admin/ads/costs');
            return;
        }

        // Check if any ads are using this cost
        $adsUsing = $this->adModel->getDb()->query(
            "SELECT COUNT(*) as count FROM ads WHERE ads_cost_id = ?",
            [$id]
        )->single();

        if ($adsUsing['count'] > 0) {
            $this->setFlash('error', 'Cannot delete: ' . $adsUsing['count'] . ' ad(s) are using this cost plan');
            $this->redirect('admin/ads/costs');
            return;
        }

        $this->adCostModel->getDb()->query(
            "DELETE FROM ads_costs WHERE id = ?",
            [$id]
        )->execute();

        $this->setFlash('success', 'Cost plan deleted successfully');
        $this->redirect('admin/ads/costs');
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads');
            return;
        }

        $status = $_POST['payment_status'] ?? null;
        
        if (!in_array($status, ['pending', 'paid', 'failed'])) {
            $this->setFlash('error', 'Invalid payment status');
            $this->redirect('admin/ads');
            return;
        }

        $this->adPaymentModel->updateStatus($id, $status);
        $this->setFlash('success', 'Payment status updated successfully');
        $this->redirect('admin/ads');
    }

    /**
     * Manage admin control settings
     */
    public function adminSettings()
    {
        $db = \App\Core\Database::getInstance();
        $settings = [];
        
        try {
            $settingsData = $db->query(
                "SELECT setting_key, setting_value FROM ad_admin_settings"
            )->all();
            
            foreach ($settingsData as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
        } catch (\Exception $e) {
            error_log("Error loading ad_admin_settings: " . $e->getMessage());
        }

        // Default values
        $defaults = [
            'min_cpc_rate' => '1.00',
            'max_cpc_rate' => '100.00',
            'min_daily_budget' => '10.00',
            'ad_review_required' => 'yes',
            'block_listed_products' => '',
            'block_listed_categories' => ''
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }

        $this->view('admin/ads/admin-settings', [
            'settings' => $settings,
            'title' => 'Ad Admin Control Settings'
        ]);
    }

    /**
     * Update admin control settings
     */
    public function updateAdminSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads/admin-settings');
            return;
        }

        $db = \App\Core\Database::getInstance();

        $settings = [
            'min_cpc_rate' => (float)($_POST['min_cpc_rate'] ?? 1.00),
            'max_cpc_rate' => (float)($_POST['max_cpc_rate'] ?? 100.00),
            'min_daily_budget' => (float)($_POST['min_daily_budget'] ?? 10.00),
            'ad_review_required' => $_POST['ad_review_required'] ?? 'yes',
            'block_listed_products' => trim($_POST['block_listed_products'] ?? ''),
            'block_listed_categories' => trim($_POST['block_listed_categories'] ?? '')
        ];

        // Validate
        if ($settings['min_cpc_rate'] < 0 || $settings['max_cpc_rate'] < $settings['min_cpc_rate']) {
            $this->setFlash('error', 'Invalid CPC rate range');
            $this->redirect('admin/ads/admin-settings');
            return;
        }

        if ($settings['min_daily_budget'] < 0) {
            $this->setFlash('error', 'Minimum daily budget must be positive');
            $this->redirect('admin/ads/admin-settings');
            return;
        }

        if (!in_array($settings['ad_review_required'], ['yes', 'no'])) {
            $this->setFlash('error', 'Invalid review required value');
            $this->redirect('admin/ads/admin-settings');
            return;
        }

        foreach ($settings as $key => $value) {
            $db->query(
                "INSERT INTO ad_admin_settings (setting_key, setting_value) 
                 VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            )->execute();
        }

        $this->setFlash('success', 'Admin control settings updated successfully');
        $this->redirect('admin/ads/admin-settings');
    }

    /**
     * Bulk delete ads
     */
    public function bulkDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            } else {
                $this->redirect('admin/ads');
            }
            return;
        }

        // Handle both AJAX and form submissions
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? $_POST['ids'] ?? [];
        
        if (!is_array($ids) || empty($ids)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'No ads selected for deletion'], 400);
            } else {
                $this->setFlash('error', 'No ads selected for deletion');
                $this->redirect('admin/ads');
            }
            return;
        }

        // Convert to integers
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) { return $id > 0; });

        if (empty($ids)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'No valid ad IDs provided'], 400);
            } else {
                $this->setFlash('error', 'No valid ad IDs provided');
                $this->redirect('admin/ads');
            }
            return;
        }

        try {
            $db = $this->adModel->getDb();
            
            // Delete related data first
            foreach ($ids as $id) {
                try {
                    $db->query("DELETE FROM ads_click_logs WHERE ads_id = ?", [$id])->execute();
                    $db->query("DELETE FROM ads_reach_logs WHERE ads_id = ?", [$id])->execute();
                    $db->query("DELETE FROM ads_daily_spend_log WHERE ads_id = ?", [$id])->execute();
                    $db->query("DELETE FROM ads_payments WHERE ads_id = ?", [$id])->execute();
                } catch (\Exception $e) {
                    error_log('Bulk delete ad relations error: ' . $e->getMessage());
                }
            }

            // Use BulkActionService to delete ads
            $bulkService = new \App\Services\BulkActionService();
            $result = $bulkService->bulkDelete(\App\Models\Ad::class, $ids);

            if ($result['success']) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $result['message'],
                        'deleted_count' => $result['count']
                    ]);
                } else {
                    $this->setFlash('success', $result['message']);
                    $this->redirect('admin/ads');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => $result['message']], 400);
                } else {
                    $this->setFlash('error', $result['message']);
                    $this->redirect('admin/ads');
                }
            }
        } catch (\Exception $e) {
            error_log('Bulk delete ads error: ' . $e->getMessage());
            $errorMessage = 'An error occurred: ' . $e->getMessage();
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMessage], 500);
            } else {
                $this->setFlash('error', $errorMessage);
                $this->redirect('admin/ads');
            }
        }
    }

    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Approve ad
     */
    public function approve($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads');
            return;
        }

        $ad = $this->adModel->find($id);
        if (!$ad) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('admin/ads');
            return;
        }

        $this->adModel->getDb()->query(
            "UPDATE ads SET approval_status = 'approved', updated_at = NOW() WHERE id = ?",
            [$id]
        )->execute();

        $this->setFlash('success', 'Ad approved successfully. Seller can now start/stop the ad.');
        $this->redirect('admin/ads/show/' . $id);
    }

    /**
     * Reject ad
     */
    public function rejectAd($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/ads');
            return;
        }

        $ad = $this->adModel->find($id);
        if (!$ad) {
            $this->setFlash('error', 'Ad not found');
            $this->redirect('admin/ads');
            return;
        }

        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        
        $notes = ($ad['notes'] ?? '') . "\n[REJECTED: " . date('Y-m-d H:i:s') . "] Reason: " . $rejectionReason;
        
        $this->adModel->getDb()->query(
            "UPDATE ads SET approval_status = 'rejected', notes = ?, updated_at = NOW() WHERE id = ?",
            [$notes, $id]
        )->execute();

        $this->setFlash('success', 'Ad rejected. Seller has been notified.');
        $this->redirect('admin/ads/show/' . $id);
    }
}


