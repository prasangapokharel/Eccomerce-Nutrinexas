<?php

namespace App\Controllers\Withdraw;

use App\Core\Controller;
use App\Models\Withdrawal;
use App\Models\User;

class WithdrawController extends Controller
{
    private $withdrawalModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->withdrawalModel = new Withdrawal();
        $this->userModel = new User();
        
        // Check if user is admin
        $this->requireAdmin();
    }

    /**
     * View withdrawal details
     */
    public function details($id = null)
    {
        if (!$id) {
            $this->redirect('admin/withdrawals');
        }

        $withdrawal = $this->withdrawalModel->getWithUserDetails($id);
        
        if (!$withdrawal) {
            $this->setFlash('error', 'Withdrawal not found');
            $this->redirect('admin/withdrawals');
        }

        // Get user withdrawal statistics
        $userStats = $this->withdrawalModel->getUserStats($withdrawal['user_id']);
        
        // Get recent withdrawals for this user
        $recentWithdrawals = $this->withdrawalModel->getRecentByUserId($withdrawal['user_id'], 5);
        
        // Parse payment details if it's JSON
        $paymentDetails = [];
        if (!empty($withdrawal['payment_details'])) {
            $decoded = json_decode($withdrawal['payment_details'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $paymentDetails = $decoded;
            }
        }

        $this->view('admin/withdrawals/view', [
            'withdrawal' => $withdrawal,
            'userStats' => $userStats,
            'recentWithdrawals' => $recentWithdrawals,
            'paymentDetails' => $paymentDetails,
            'title' => 'Withdrawal Details - #' . $withdrawal['id']
        ]);
    }

    /**
     * Get user withdrawals
     */
    public function userWithdrawals($userId = null)
    {
        if (!$userId) {
            $this->redirect('admin/withdrawals');
        }

        $withdrawals = $this->withdrawalModel->getByUserId($userId);
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->setFlash('error', 'User not found');
            $this->redirect('admin/withdrawals');
        }

        $this->view('admin/withdrawals/user', [
            'withdrawals' => $withdrawals,
            'user' => $user,
            'title' => 'User Withdrawals - ' . $user['username']
        ]);
    }
}
