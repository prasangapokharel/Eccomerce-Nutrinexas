<?php

namespace App\Controllers\Seller;

use Exception;

class WithdrawRequests extends BaseSellerController
{
    private $walletModel;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->walletModel = new \App\Models\SellerWallet();
        $this->db = \App\Core\Database::getInstance();
    }

    public function index()
    {
        $requests = $this->db->query(
            "SELECT wr.*, ba.account_holder_name, ba.bank_name 
             FROM seller_withdraw_requests wr
             LEFT JOIN seller_bank_accounts ba ON wr.bank_account_id = ba.id
             WHERE wr.seller_id = ? 
             ORDER BY wr.requested_at DESC",
            [$this->sellerId]
        )->all();
        
        $wallet = $this->walletModel->getWalletBySellerId($this->sellerId);
        $bankAccounts = $this->db->query(
            "SELECT * FROM seller_bank_accounts WHERE seller_id = ? ORDER BY is_default DESC",
            [$this->sellerId]
        )->all();
        
        $this->view('seller/withdraw-requests/index', [
            'title' => 'Withdrawal Requests',
            'requests' => $requests,
            'wallet' => $wallet,
            'bankAccounts' => $bankAccounts
        ]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $wallet = $this->walletModel->getWalletBySellerId($this->sellerId);
        if (!$wallet) {
            $this->setFlash('error', 'Wallet not found. Please contact support.');
            $this->redirect('seller/dashboard');
            return;
        }
        
        $bankAccounts = $this->db->query(
            "SELECT * FROM seller_bank_accounts WHERE seller_id = ? ORDER BY is_default DESC, created_at DESC",
            [$this->sellerId]
        )->all();
        
        // Check if seller has at least one bank account
        if (empty($bankAccounts)) {
            $this->setFlash('error', 'Please add a bank account before requesting withdrawal');
            $this->redirect('seller/bank-account');
            return;
        }
        
        $this->view('seller/withdraw-requests/create', [
            'title' => 'Request Withdrawal',
            'wallet' => $wallet,
            'bankAccounts' => $bankAccounts
        ]);
    }

    public function detail($id)
    {
        $request = $this->db->query(
            "SELECT wr.*, 
                    ba.account_holder_name, ba.bank_name, ba.account_number, 
                    ba.branch_name, ba.account_type, ba.ifsc_code
             FROM seller_withdraw_requests wr
             LEFT JOIN seller_bank_accounts ba ON wr.bank_account_id = ba.id
             WHERE wr.id = ? AND wr.seller_id = ?",
            [$id, $this->sellerId]
        )->single();
        
        if (!$request) {
            $this->setFlash('error', 'Withdrawal request not found');
            $this->redirect('seller/withdraw-requests');
            return;
        }
        
        // If request is rejected/failed, show message
        if (in_array($request['status'], ['rejected', 'failed', 'cancelled'])) {
            $wallet = $this->walletModel->getWalletBySellerId($this->sellerId);
            if ($wallet && $request['status'] === 'rejected') {
                // Refund pending amount if rejected
                $this->db->query(
                    "UPDATE seller_wallet 
                     SET pending_withdrawals = GREATEST(0, pending_withdrawals - ?),
                         updated_at = NOW()
                     WHERE seller_id = ?",
                    [$request['amount'], $this->sellerId]
                )->execute();
            }
        }
        
        $this->view('seller/withdraw-requests/detail', [
            'title' => 'Withdrawal Request Details',
            'request' => $request
        ]);
    }

    private function handleCreate()
    {
        try {
            $amount = (float)($_POST['amount'] ?? 0);
            $paymentMethod = 'bank_transfer'; // Only bank transfer supported for Nepal
            $bankAccountId = !empty($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : null;
            $accountDetails = trim($_POST['account_details'] ?? '');
            
            // Validate amount
            if ($amount <= 0) {
                $this->setFlash('error', 'Invalid withdrawal amount');
                $this->redirect('seller/withdraw-requests/create');
                return;
            }
            
            if ($amount < 100) {
                $this->setFlash('error', 'Minimum withdrawal amount is रु 100');
                $this->redirect('seller/withdraw-requests/create');
                return;
            }
            
            // Validate bank account is selected
            if (!$bankAccountId) {
                $this->setFlash('error', 'Please select a bank account');
                $this->redirect('seller/withdraw-requests/create');
                return;
            }
            
            // Verify bank account exists and belongs to seller
            $bankAccount = $this->db->query(
                "SELECT id, bank_name, account_number, account_holder_name 
                 FROM seller_bank_accounts 
                 WHERE id = ? AND seller_id = ?",
                [$bankAccountId, $this->sellerId]
            )->single();
            
            if (!$bankAccount) {
                $this->setFlash('error', 'Selected bank account not found or access denied. Please select a valid bank account.');
                $this->redirect('seller/withdraw-requests/create');
                return;
            }
            
            // Get wallet
            $wallet = $this->walletModel->getWalletBySellerId($this->sellerId);
            if (!$wallet) {
                $this->setFlash('error', 'Wallet not found. Please contact support.');
                $this->redirect('seller/dashboard');
                return;
            }
            
            // Check available balance (balance - pending withdrawals)
            $availableBalance = $wallet['balance'] - ($wallet['pending_withdrawals'] ?? 0);
            
            if ($amount > $availableBalance) {
                $this->setFlash('error', 'Insufficient balance. Available: रु ' . number_format($availableBalance, 2));
                $this->redirect('seller/withdraw-requests/create');
                return;
            }
            
            // Create withdrawal request
            $this->db->beginTransaction();
            
            try {
                $result = $this->db->query(
                    "INSERT INTO seller_withdraw_requests 
                     (seller_id, amount, payment_method, bank_account_id, account_details, status, requested_at) 
                     VALUES (?, ?, ?, ?, ?, 'pending', NOW())",
                    [$this->sellerId, $amount, $paymentMethod, $bankAccountId, $accountDetails]
                )->execute();
                
                if (!$result) {
                    throw new Exception('Failed to create withdrawal request');
                }
                
                $requestId = $this->db->lastInsertId();
                
                // Update pending withdrawals in wallet
                $newPending = ($wallet['pending_withdrawals'] ?? 0) + $amount;
                $updateResult = $this->db->query(
                    "UPDATE seller_wallet SET pending_withdrawals = ?, updated_at = NOW() WHERE seller_id = ?",
                    [$newPending, $this->sellerId]
                )->execute();
                
                if (!$updateResult) {
                    throw new Exception('Failed to update wallet pending withdrawals');
                }
                
                $this->db->commit();
                
                $this->setFlash('success', 'Withdrawal request submitted successfully');
                $this->redirect('seller/withdraw-requests/detail/' . $requestId);
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('Create withdrawal error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->setFlash('error', 'Error creating withdrawal request: ' . $e->getMessage());
            $this->redirect('seller/withdraw-requests/create');
        }
    }
}

