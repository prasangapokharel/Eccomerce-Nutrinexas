<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Setting;
use App\Core\Session;

class AdminReferController extends Controller
{
    protected $db;
    private $settingModel;

    public function __construct()
    {
        parent::__construct();
        
        // Check if user is admin
        if (!Session::get('is_admin')) {
            $this->redirect('auth/login');
        }
        
        // Initialize database connection
        try {
            $this->db = Database::getInstance();
        } catch (\Exception $e) {
            error_log('Database connection failed in AdminReferController: ' . $e->getMessage());
            $this->db = null;
        }
        
        $this->settingModel = new Setting();
    }

    /**
     * Display referral management page
     */
    public function index()
    {
        // Get current settings
        $commissionRate = $this->settingModel->get('commission_rate', 5);
        $minWithdrawal = $this->settingModel->get('min_withdrawal', 100);
        $autoApprove = $this->settingModel->get('auto_approve', 'true');

        // Get referral statistics
        $totalReferrals = $this->getTotalReferrals();
        $totalCommissionsPaid = $this->getTotalCommissionsPaid();
        $pendingWithdrawals = $this->getPendingWithdrawals();
        $recentReferrals = $this->getRecentReferrals();

        $this->view('admin/refer/index', [
            'commissionRate' => $commissionRate,
            'minWithdrawal' => $minWithdrawal,
            'autoApprove' => $autoApprove,
            'totalReferrals' => $totalReferrals,
            'totalCommissionsPaid' => $totalCommissionsPaid,
            'pendingWithdrawals' => $pendingWithdrawals,
            'recentReferrals' => $recentReferrals
        ]);
    }

    /**
     * Save commission settings
     */
    public function saveSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
            return;
        }

        try {
            // Validate and save settings
            if (isset($input['commission_rate'])) {
                $rate = floatval($input['commission_rate']);
                if ($rate < 0 || $rate > 100) {
                    $this->jsonResponse(['success' => false, 'message' => 'Commission rate must be between 0 and 100'], 400);
                    return;
                }
                $this->settingModel->set('commission_rate', $rate);
            }

            if (isset($input['min_withdrawal'])) {
                $min = floatval($input['min_withdrawal']);
                if ($min < 0) {
                    $this->jsonResponse(['success' => false, 'message' => 'Minimum withdrawal must be positive'], 400);
                    return;
                }
                $this->settingModel->set('min_withdrawal', $min);
            }

            if (isset($input['auto_approve'])) {
                $this->settingModel->set('auto_approve', $input['auto_approve'] ? 'true' : 'false');
            }

            $this->jsonResponse(['success' => true, 'message' => 'Settings saved successfully']);

        } catch (\Exception $e) {
            error_log('Error saving referral settings: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error saving settings'], 500);
        }
    }

    /**
     * Get total referrals count
     */
    private function getTotalReferrals()
    {
        if (!$this->db) {
            return 0;
        }
        
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM referral_earnings")->single();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total commissions paid
     */
    private function getTotalCommissionsPaid()
    {
        if (!$this->db) {
            return 0;
        }
        
        try {
            $result = $this->db->query("SELECT SUM(amount) as total FROM referral_earnings WHERE status = 'approved'")->single();
            return $result['total'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get pending withdrawals
     */
    private function getPendingWithdrawals()
    {
        if (!$this->db) {
            return 0;
        }
        
        try {
            $result = $this->db->query("SELECT SUM(amount) as total FROM withdrawals WHERE status = 'pending'")->single();
            return $result['total'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent referrals with details
     */
    private function getRecentReferrals()
    {
        if (!$this->db) {
            return [];
        }
        
        try {
            $referrals = $this->db->query("
                SELECT 
                    re.id,
                    re.amount as commission_amount,
                    re.status,
                    re.created_at,
                    o.total_amount as order_amount,
                    u1.name as referrer_name,
                    u1.email as referrer_email,
                    u2.name as referred_name,
                    u2.email as referred_email
                FROM referral_earnings re
                LEFT JOIN orders o ON re.order_id = o.id
                LEFT JOIN users u1 ON re.user_id = u1.id
                LEFT JOIN users u2 ON o.user_id = u2.id
                ORDER BY re.created_at DESC
                LIMIT 10
            ")->all();

            return $referrals;
        } catch (\Exception $e) {
            return [];
        }
    }
}
















