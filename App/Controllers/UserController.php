<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Models\Address;
use App\Models\Order;
use App\Models\ReferralEarning;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\Setting;
use App\Core\Session;

class UserController extends Controller
{
    protected $db;
    private $userModel;
    private $addressModel;
    private $orderModel;
    private $referralEarningModel;
    private $withdrawalModel;
    private $transactionModel;
    private $notificationModel;
    private $settingModel;

    public function __construct()
    {
        parent::__construct();
        
        // Initialize database connection
        try {
            $this->db = Database::getInstance();
        } catch (\Exception $e) {
            error_log('Database connection failed in UserController: ' . $e->getMessage());
            $this->db = null;
        }
        
        $this->userModel = new User();
        $this->addressModel = new Address();
        $this->orderModel = new Order();
        $this->referralEarningModel = new ReferralEarning();
        $this->withdrawalModel = new Withdrawal();
        $this->transactionModel = new Transaction();
        $this->notificationModel = new Notification();
        $this->settingModel = new Setting();
        
        // Check if user is logged in
        $this->requireLogin();
    }

    /**
     * Display user account dashboard
     */
    public function account()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->redirect('auth/logout');
        }

        // Get user statistics
        $stats = $this->getUserStats($userId);
        
        // Get dynamic settings
        $settings = $this->getUserSettings();

        // Ensure user has a referral code
        if (empty($user['referral_code'])) {
            $referralCode = $this->generateUserReferralCode($userId);
            $user['referral_code'] = $referralCode;
        }

        $this->view('user/account', array_merge([
            'user' => $user,
        ], $stats, $settings));
    }

    /**
     * Display user invite page
     */
    public function invite()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->redirect('auth/logout');
        }

        // Gate by sponsor_status
        $sponsorStatus = $user['sponsor_status'] ?? 'inactive';
        $isSponsorActive = ($sponsorStatus === 'active');

        // Get user statistics
        $stats = $this->getUserStats($userId);
        $referrals = $this->getReferralsWithDetails($userId);
        $referralStats = $this->getReferralStats($userId);
        
        // Get dynamic settings
        $settings = $this->getUserSettings();

        // Ensure user has a referral code
        if (empty($user['referral_code'])) {
            $referralCode = $this->generateUserReferralCode($userId);
            $user['referral_code'] = $referralCode;
        }

        $this->view('user/invite', array_merge([
            'user' => $user,
            'referrals' => $referrals,
            'stats' => $referralStats,
            'isSponsorActive' => $isSponsorActive,
        ], $stats, $settings));
    }

    /**
     * Display user balance page
     */
    public function balance()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->redirect('auth/logout');
        }

        // Get user statistics
        $stats = $this->getUserStats($userId);
        $recentActivities = $this->getRecentActivities($userId);
        
        // Get dynamic settings
        $settings = $this->getUserSettings();

        $this->view('user/balance', array_merge([
            'user' => $user,
            'recentActivities' => $recentActivities,
        ], $stats, $settings));
    }

    /**
     * Display user profile
     */
    public function profile()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->redirect('auth/logout');
        }
        
        $this->view('user/profile', [
            'user' => $user,
            'title' => 'My Profile'
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('user/profile');
        }
        
        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('user/profile');
            return;
        }
        
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->redirect('auth/logout');
        }
        
        // Process form data
        $data = $this->processProfileData();
        $errors = $this->validateProfileData($data, $user);
        
        if (!empty($errors)) {
            $this->view('user/profile', [
                'user' => $user,
                'errors' => $errors,
                'title' => 'My Profile'
            ]);
            return;
        }
        
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && is_uploaded_file($_FILES['profile_image']['tmp_name'])) {
            $profileImage = $this->handleProfileImageUpload($_FILES['profile_image']);
            if ($profileImage) {
                $data['profile_image'] = $profileImage;
            }
        }
        
        // Update user data
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->userModel->update($userId, $data)) {
            $this->setFlash('success', 'Profile updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update profile');
        }
        
        $this->redirect('user/profile');
    }

    /**
     * Display user addresses
     */
    public function addresses()
    {
        $userId = Session::get('user_id');
        $addresses = $this->addressModel->getByUserId($userId);
        
        $this->view('user/addresses', [
            'addresses' => $addresses,
            'title' => 'My Addresses'
        ]);
    }

    /**
     * Add or edit address
     */
    public function address($id = null)
    {
        $userId = Session::get('user_id');
        $address = null;
        
        if ($id) {
            $address = $this->addressModel->find($id);
            
            // Check if address belongs to user
            if (!$address || $address['user_id'] != $userId) {
                $this->redirect('user/addresses');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF validation
            if (!$this->validateCSRF()) {
                $this->setFlash('error', 'Invalid security token. Please try again.');
                $this->redirect('user/addresses');
                return;
            }
            
            $data = $this->processAddressData($userId);
            $errors = $this->validateAddressData($data);
            
            if (!empty($errors)) {
                $this->view('user/address', [
                    'address' => $address,
                    'data' => $data,
                    'errors' => $errors,
                    'title' => $id ? 'Edit Address' : 'Add Address'
                ]);
                return;
            }
            
            // If setting as default, unset other default addresses
            if ($data['is_default']) {
                $this->addressModel->unsetDefaultAddresses($userId);
            }
            
            // Update or create address
            if ($id) {
                $result = $this->addressModel->update($id, $data);
                $message = 'Address updated successfully';
            } else {
                $result = $this->addressModel->create($data);
                $message = 'Address added successfully';
            }
            
            if ($result) {
                $this->setFlash('success', $message);
            } else {
                $this->setFlash('error', 'Failed to save address');
            }
            
            $this->redirect('user/addresses');
        } else {
            $this->view('user/address', [
                'address' => $address,
                'title' => $id ? 'Edit Address' : 'Add Address'
            ]);
        }
    }

    /**
     * Delete address
     */
    public function deleteAddress($id = null)
    {
        if (!$id) {
            $this->redirect('user/addresses');
        }
        
        $userId = Session::get('user_id');
        $address = $this->addressModel->find($id);
        
        // Check if address belongs to user
        if (!$address || $address['user_id'] != $userId) {
            $this->redirect('user/addresses');
        }
        
        $result = $this->addressModel->delete($id);
        
        if ($result) {
            $this->setFlash('success', 'Address deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete address');
        }
        
        $this->redirect('user/addresses');
    }

    /**
     * Set address as default
     */
    public function setDefaultAddress($id = null)
    {
        if (!$id) {
            $this->redirect('user/addresses');
        }
        
        $userId = Session::get('user_id');
        $address = $this->addressModel->find($id);
        
        // Check if address belongs to user
        if (!$address || $address['user_id'] != $userId) {
            $this->redirect('user/addresses');
        }
        
        // Unset all default addresses for this user
        $this->addressModel->unsetDefaultAddresses($userId);
        
        // Set this address as default
        $result = $this->addressModel->update($id, ['is_default' => 1]);
        
        if ($result) {
            $this->setFlash('success', 'Default address updated');
        } else {
            $this->setFlash('error', 'Failed to update default address');
        }
        
        $this->redirect('user/addresses');
    }

    /**
     * Display withdraw page
     */
    public function withdraw()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);
        
        // Get balance information
        $balance = $this->getBalanceInfo($userId);
        
        // Get withdrawal history
        $withdrawals = $this->withdrawalModel->getByUserId($userId);
        
        $this->view('user/withdraw', [
            'balance' => $balance,
            'withdrawals' => $withdrawals,
            'title' => 'Withdraw Funds'
        ]);
    }

    /**
     * Process withdrawal request
     */
    public function requestWithdrawal()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('user/withdraw');
        }
        
        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('user/withdraw');
            return;
        }
        
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);
        
        $data = $this->processWithdrawalData();
        $errors = $this->validateWithdrawalData($data, $userId);
        
        if (!empty($errors)) {
            $balance = $this->getBalanceInfo($userId);
            $withdrawals = $this->withdrawalModel->getByUserId($userId);
            
            $this->view('user/withdraw', [
                'balance' => $balance,
                'withdrawals' => $withdrawals,
                'errors' => $errors,
                'title' => 'Withdraw Funds'
            ]);
            return;
        }
        
        // Create withdrawal request
        $withdrawalData = [
            'user_id' => $userId,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'payment_details' => json_encode($data['payment_details']),
            'status' => 'pending'
        ];
        
        // Use the referral service to process withdrawal
        try {
            $referralService = new \App\Services\ReferralEarningService();
            $withdrawalId = $referralService->processWithdrawal($userId, $data['amount']);
            
            if ($withdrawalId) {
                // Update withdrawal record with payment details
                $this->withdrawalModel->update($withdrawalId, [
                    'payment_method' => $data['payment_method'],
                    'payment_details' => json_encode($data['payment_details']),
                    'status' => 'pending'
                ]);
            
                // Record transaction
                $this->transactionModel->recordWithdrawal($userId, $data['amount'], $withdrawalId);
                
                // Create notification
                $this->createWithdrawalNotification($userId, $data['amount'], $withdrawalId);
                
                $this->setFlash('success', 'Withdrawal request submitted successfully');
                $this->redirect('user/balance');
            } else {
                $this->setFlash('error', 'Failed to submit withdrawal request');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error processing withdrawal: ' . $e->getMessage());
            $this->redirect('user/withdraw');
        }
    }
    
    /**
     * Display notifications page
     */
    public function notifications()
    {
        $userId = Session::get('user_id');
        $notifications = $this->notificationModel->getByUserId($userId, 50);
        
        // Mark all as read
        $this->notificationModel->markAllAsRead($userId);
        
        $this->view('user/notifications', [
            'notifications' => $notifications,
            'title' => 'My Notifications'
        ]);
    }
    
    /**
     * Display transactions page
     */
    public function transactions()
    {
        $userId = Session::get('user_id');
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $transactions = $this->transactionModel->getByUserId($userId, $limit, $offset);
        $totalTransactions = $this->transactionModel->getCountByUserId($userId);
        $totalPages = ceil($totalTransactions / $limit);
        
        $this->view('user/transactions', [
            'transactions' => $transactions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'title' => 'Transaction History'
        ]);
    }
    
    /**
     * Display payment methods page
     */
    public function payments()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->redirect('auth/logout');
        }

        $this->view('user/payments', [
            'user' => $user,
            'title' => 'Payment Methods'
        ]);
    }

    /**
     * Display settings page
     */
    public function settings()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->redirect('auth/logout');
        }

        $this->view('user/settings', [
            'user' => $user,
            'title' => 'Settings'
        ]);
    }

    /**
     * Display API keys management page
     */
    public function apiKeys()
    {
        $userId = Session::get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->redirect('auth/logout');
        }
        
        $this->view('user/profile', [
            'user' => $user,
            'title' => 'API Key Management'
        ]);
    }

    /**
     * Serve profile image
     */
    public function serveProfileImage($filename)
    {
        $imagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'profileimage' . DIRECTORY_SEPARATOR . $filename;
        
        if (file_exists($imagePath) && is_file($imagePath)) {
            $mimeType = mime_content_type($imagePath);
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($imagePath));
            header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
            readfile($imagePath);
        } else {
            // Serve default avatar if image not found
            $defaultPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'default-avatar.png';
            if (file_exists($defaultPath)) {
                header('Content-Type: image/png');
                header('Cache-Control: public, max-age=31536000');
                readfile($defaultPath);
            } else {
                http_response_code(404);
                echo 'Image not found';
            }
        }
        exit;
    }

    // Private helper methods

    /**
     * Get user statistics
     */
    private function getUserStats($userId)
    {
        if (!$this->db) {
            return $this->getDefaultStats();
        }
        
        try {
            $wishlistCount = $this->getWishlistCount($userId);
            $ordersCount = $this->getOrdersCount($userId);
            $totalSpent = $this->getTotalSpent($userId);
            $earnings = $this->getTotalEarnings($userId);
            $referralCount = $this->getReferralCount($userId);
            $availableBalance = $this->getAvailableBalance($userId);
            $monthlyEarnings = $this->getMonthlyEarnings($userId);
            $pendingEarnings = $this->getPendingEarnings($userId);
            $withdrawnAmount = $this->getWithdrawnAmount($userId);

            return [
                'wishlistCount' => $wishlistCount,
                'ordersCount' => $ordersCount,
                'totalSpent' => $totalSpent,
                'earnings' => $earnings,
                'referralCount' => $referralCount,
                'availableBalance' => $availableBalance,
                'monthlyEarnings' => $monthlyEarnings,
                'pendingEarnings' => $pendingEarnings,
                'withdrawnAmount' => $withdrawnAmount,
            ];
        } catch (\Exception $e) {
            return $this->getDefaultStats();
        }
    }

    /**
     * Get default statistics
     */
    private function getDefaultStats()
    {
        return [
            'wishlistCount' => 0,
            'ordersCount' => 0,
            'totalSpent' => 0,
            'earnings' => 0,
            'referralCount' => 0,
            'availableBalance' => 0,
            'monthlyEarnings' => 0,
            'pendingEarnings' => 0,
            'withdrawnAmount' => 0,
        ];
    }

    /**
     * Get user settings
     */
    private function getUserSettings()
    {
        return [
            'commissionRate' => $this->settingModel->get('commission_rate', 5),
            'minWithdrawal' => $this->settingModel->get('min_withdrawal', 100),
            'autoApprove' => $this->settingModel->get('auto_approve', 'true'),
        ];
    }

    /**
     * Process profile form data
     */
    private function processProfileData()
    {
        return [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];
    }

    /**
     * Validate profile data
     */
    private function validateProfileData($data, $user)
    {
        $errors = [];
        
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }
        
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } elseif ($data['email'] !== $user['email']) {
            // Check if email is already taken
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] !== $user['id']) {
                $errors['email'] = 'Email is already taken';
            }
        }
        
        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required';
        }
        
        // Password validation
        $newPassword = $_POST['new_password'] ?? '';
        if (!empty($newPassword)) {
            $currentPassword = $_POST['current_password'] ?? '';
            if (empty($currentPassword)) {
                $errors['current_password'] = 'Current password is required to change password';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $errors['current_password'] = 'Current password is incorrect';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'New password must be at least 6 characters';
            } elseif ($newPassword !== ($_POST['confirm_password'] ?? '')) {
                $errors['confirm_password'] = 'New passwords do not match';
            } else {
                $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }
        
        return $errors;
    }

    /**
     * Process address form data
     */
    private function processAddressData($userId)
    {
        $data = [
            'user_id' => $userId,
            'recipient_name' => trim($_POST['recipient_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address_line1' => trim($_POST['address_line1'] ?? ''),
            'address_line2' => trim($_POST['address_line2'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => trim($_POST['state'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''), // Required in DB, can be empty string
            'country' => trim($_POST['country'] ?? 'Nepal'), // Default to Nepal if empty
            'is_default' => isset($_POST['is_default']) ? 1 : 0
        ];
        
        return $data;
    }

    /**
     * Validate address data
     */
    private function validateAddressData($data)
    {
        $errors = [];
        
        $requiredFields = [
            'recipient_name' => 'Recipient name is required',
            'phone' => 'Phone number is required',
            'address_line1' => 'Address line 1 is required',
            'city' => 'City is required',
            'state' => 'State is required',
            'country' => 'Country is required',
        ];
        
        foreach ($requiredFields as $field => $message) {
            if (empty($data[$field])) {
                $errors[$field] = $message;
            }
        }
        
        return $errors;
    }

    /**
     * Process withdrawal form data
     */
    private function processWithdrawalData()
    {
        $data = [
            'amount' => isset($_POST['amount']) ? (float)$_POST['amount'] : 0,
            'payment_method' => $_POST['payment_method'] ?? '',
            'payment_details' => []
        ];
        
        // Process payment method specific details
        switch ($data['payment_method']) {
            case 'bank_transfer':
                $data['payment_details'] = [
                    'account_name' => $_POST['account_name'] ?? '',
                    'account_number' => $_POST['account_number'] ?? '',
                    'bank_name' => $_POST['bank_name'] ?? '',
                    'ifsc_code' => $_POST['ifsc_code'] ?? '',
                ];
                break;
                
            case 'upi':
                $data['payment_details'] = [
                    'upi_id' => $_POST['upi_id'] ?? '',
                ];
                break;
                
            case 'paytm':
                $data['payment_details'] = [
                    'paytm_number' => $_POST['paytm_number'] ?? '',
                ];
                break;
        }
        
        return $data;
    }

    /**
     * Validate withdrawal data
     */
    private function validateWithdrawalData($data, $userId)
    {
        $errors = [];
        
        if ($data['amount'] <= 0) {
            $errors['amount'] = 'Amount must be greater than zero';
        }
        
        if ($data['amount'] < 100) {
            $errors['amount'] = 'Minimum withdrawal amount is ₹100';
        }
        
        // Get available balance using the service
        $referralService = new \App\Services\ReferralEarningService();
        $availableBalance = $referralService->getAvailableBalance($userId);
        
        if ($data['amount'] > $availableBalance) {
            $errors['amount'] = 'Withdrawal amount cannot exceed your available balance. Available: ₹' . number_format($availableBalance, 2);
        }
        
        if (empty($data['payment_method'])) {
            $errors['payment_method'] = 'Payment method is required';
        }
        
        // Validate payment method specific fields
        switch ($data['payment_method']) {
            case 'bank_transfer':
                if (empty($data['payment_details']['account_name'])) {
                    $errors['account_name'] = 'Account name is required';
                }
                if (empty($data['payment_details']['account_number'])) {
                    $errors['account_number'] = 'Account number is required';
                }
                if (empty($data['payment_details']['bank_name'])) {
                    $errors['bank_name'] = 'Bank name is required';
                }
                if (empty($data['payment_details']['ifsc_code'])) {
                    $errors['ifsc_code'] = 'IFSC code is required';
                }
                break;
                
            case 'upi':
                if (empty($data['payment_details']['upi_id'])) {
                    $errors['upi_id'] = 'UPI ID is required';
                }
                break;
                
            case 'paytm':
                if (empty($data['payment_details']['paytm_number'])) {
                    $errors['paytm_number'] = 'Paytm number is required';
                }
                break;
        }
        
        return $errors;
    }

    /**
     * Get balance information
     */
    private function getBalanceInfo($userId)
    {
        $availableBalance = $this->getAvailableBalance($userId);
        $pendingWithdrawals = $this->getPendingEarnings($userId);
        $totalWithdrawn = $this->getWithdrawnAmount($userId);
        
        return [
            'available_balance' => $availableBalance,
            'pending_withdrawals' => $pendingWithdrawals,
            'total_withdrawn' => $totalWithdrawn
        ];
    }

    /**
     * Create withdrawal notification
     */
    private function createWithdrawalNotification($userId, $amount, $withdrawalId)
    {
        $notificationData = [
            'user_id' => $userId,
            'title' => 'Withdrawal Request Submitted',
            'message' => 'Your withdrawal request for ₹' . number_format($amount, 2) . ' has been submitted and is being processed.',
            'type' => 'withdrawal_request',
            'reference_id' => $withdrawalId,
            'is_read' => 0
        ];
        $this->notificationModel->createNotification($notificationData);
    }

    // Database query methods

    private function getWishlistCount($userId)
    {
        if (!$this->db) return 0;
        
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?", [$userId])->single();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getOrdersCount($userId)
    {
        if (!$this->db) return 0;
        
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$userId])->single();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTotalSpent($userId)
    {
        if (!$this->db) return 0;
        
        try {
            $result = $this->db->query("SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND status != 'cancelled'", [$userId])->single();
            return $result['total'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getTotalEarnings($userId)
    {
        if (!$this->db) return 0;
        
        try {
            // Only count paid earnings, exclude cancelled ones
            // This ensures earnings never go negative
            $result = $this->db->query(
                "SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as total 
                 FROM referral_earnings 
                 WHERE user_id = ? AND status != 'cancelled'", 
                [$userId]
            )->single();
            $total = (float)($result['total'] ?? 0);
            // Ensure earnings never go negative (double protection)
            return max(0, $total);
        } catch (\Exception $e) {
            error_log('Error calculating total earnings: ' . $e->getMessage());
            return 0;
        }
    }

    private function getReferralCount($userId)
    {
        if (!$this->db) return 0;
        
        try {
            $result = $this->db->query("SELECT COUNT(*) as count FROM users WHERE referred_by = ?", [$userId])->single();
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAvailableBalance($userId)
    {
        if (!$this->db) return 0;
        
        try {
            $earningsResult = $this->db->query("SELECT SUM(amount) as balance FROM referral_earnings WHERE user_id = ? AND status = 'paid'", [$userId])->single();
            $earnings = $earningsResult['balance'] ?? 0;

            $withdrawnResult = $this->db->query("SELECT SUM(amount) as withdrawn FROM withdrawals WHERE user_id = ? AND status = 'approved'", [$userId])->single();
            $withdrawn = $withdrawnResult['withdrawn'] ?? 0;

            return max(0, $earnings - $withdrawn);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getMonthlyEarnings($userId)
    {
        if (!$this->db) return 0;
        
        try {
            $result = $this->db->query("SELECT SUM(amount) as total FROM referral_earnings WHERE user_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())", [$userId])->single();
            return $result['total'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getPendingEarnings($userId)
    {
        if (!$this->db) return 0;
        
        try {
            $result = $this->db->query("SELECT SUM(amount) as total FROM referral_earnings WHERE user_id = ? AND status = 'pending'", [$userId])->single();
            return $result['total'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getWithdrawnAmount($userId)
    {
        if (!$this->db) return 0;
        
        try {
            $result = $this->db->query("SELECT SUM(amount) as total FROM withdrawals WHERE user_id = ? AND status = 'approved'", [$userId])->single();
            return $result['total'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getRecentActivities($userId)
    {
        if (!$this->db) return [];
        
        try {
            $activities = [];

            // Get recent orders
            $orders = $this->db->query("SELECT id, total_amount as amount, 'Order placed' as description, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3", [$userId])->all();

            foreach ($orders as $order) {
                $activities[] = [
                    'type' => 'order',
                    'amount' => -$order['amount'],
                    'description' => $order['description'] . ' - Order #' . $order['id'],
                    'created_at' => $order['created_at']
                ];
            }

            // Get recent earnings
            $earnings = $this->db->query("SELECT amount, 'Referral commission' as description, created_at FROM referral_earnings WHERE user_id = ? ORDER BY created_at DESC LIMIT 3", [$userId])->all();

            foreach ($earnings as $earning) {
                $activities[] = [
                    'type' => 'earning',
                    'amount' => $earning['amount'],
                    'description' => $earning['description'],
                    'created_at' => $earning['created_at']
                ];
            }

            // Sort by date
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return array_slice($activities, 0, 5);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function generateUserReferralCode($userId)
    {
        if (!$this->db) {
            return 'NUTRI' . rand(100, 999);
        }
        
        try {
            // Generate a unique referral code
            $code = 'NUTRI' . rand(100, 999);
            
            // Check if code already exists
            $existing = $this->db->query("SELECT id FROM users WHERE referral_code = ?", [$code])->single();
            
            // If exists, generate a new one
            if ($existing) {
                $code = 'NUTRI' . rand(1000, 9999);
            }
            
            // Update user with referral code
            $this->db->query("UPDATE users SET referral_code = ? WHERE id = ?", [$code, $userId])->execute();
            
            return $code;
        } catch (\Exception $e) {
            return 'NUTRI' . rand(100, 999);
        }
    }

    private function getReferralsWithDetails($userId)
    {
        if (!$this->db) {
            return [];
        }
        
        try {
            $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.profile_image, u.created_at,
                           COUNT(o.id) as order_count,
                           COALESCE(SUM(o.total_amount), 0) as total_spent
                    FROM users u
                    LEFT JOIN orders o ON u.id = o.user_id
                    WHERE u.referred_by = ?
                    GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.profile_image, u.created_at
                    ORDER BY u.created_at DESC";
            
            return $this->db->query($sql)->bind([$userId])->all();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getReferralStats($userId)
    {
        if (!$this->db) {
            return ['referred_orders' => 0, 'total_referred_spent' => 0];
        }
        
        try {
            $sql = "SELECT COUNT(o.id) as referred_orders,
                           COALESCE(SUM(o.total_amount), 0) as total_referred_spent
                    FROM users u
                    LEFT JOIN orders o ON u.id = o.user_id
                    WHERE u.referred_by = ?";
            
            $result = $this->db->query($sql)->bind([$userId])->single();
            return [
                'referred_orders' => $result['referred_orders'] ?? 0,
                'total_referred_spent' => $result['total_referred_spent'] ?? 0
            ];
        } catch (\Exception $e) {
            return ['referred_orders' => 0, 'total_referred_spent' => 0];
        }
    }

    /**
     * Handle profile image upload
     */
    private function handleProfileImageUpload($file)
    {
        try {
            $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'profileimage';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($extension, $allowedTypes)) {
                return false;
            }

            // Check if GD library is available for compression
            if (function_exists('imagecreatetruecolor')) {
            // Generate unique filename
            $uniqueName = uniqid('profile_') . '_' . time() . '.jpg';
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
            
            // Compress and optimize image
            if ($this->compressProfileImage($file['tmp_name'], $targetPath)) {
                    return $uniqueName;
                }
            }
            
            // Fallback: Move file without compression if GD not available
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
            $uniqueName = uniqid('profile_') . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return $uniqueName;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Profile image upload failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compress and optimize profile image
     */
    private function compressProfileImage($source, $destination)
    {
        try {
            if (!function_exists('getimagesize')) {
                error_log('GD library not installed. Image processing disabled.');
                return false;
            }
            
            $info = \getimagesize($source);
            if (!$info) {
                return false;
            }

            $width = $info[0];
            $height = $info[1];
            $mime = $info['mime'] ?? '';

            // Profile images should be square and reasonable size
            $maxSize = 300;
            
            if ($width > $maxSize || $height > $maxSize) {
                $ratio = min($maxSize / $width, $maxSize / $height);
                $newWidth = round($width * $ratio);
                $newHeight = round($height * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            // Create image resource
            $image = $this->createImageResource($source, $mime);
            if (!$image) {
                return false;
            }

            // Check if GD library is available
            if (!function_exists('imagecreatetruecolor')) {
                error_log('GD library not available for image processing');
                return false;
            }

            // Create optimized image
            $optimizedImage = \imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency for PNG
            if ($mime === 'image/png') {
                \imagealphablending($optimizedImage, false);
                \imagesavealpha($optimizedImage, true);
                $transparent = \imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
                \imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resize image
            \imagecopyresampled($optimizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save optimized image
            \imagejpeg($optimizedImage, $destination, 85);

            // Clean up
            \imagedestroy($image);
            \imagedestroy($optimizedImage);

            return true;
        } catch (\Exception $e) {
            error_log("Profile image compression failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create image resource from file
     */
    private function createImageResource($filePath, $mime)
    {
        if (!function_exists('imagecreatefromjpeg')) {
            error_log('GD library not installed. Image processing disabled.');
            return false;
        }
        
        switch ($mime) {
            case 'image/jpeg':
                return \imagecreatefromjpeg($filePath);
            case 'image/png':
                return \imagecreatefrompng($filePath);
            case 'image/gif':
                return \imagecreatefromgif($filePath);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return \imagecreatefromwebp($filePath);
                }
                return false;
            default:
                return false;
        }
    }
}