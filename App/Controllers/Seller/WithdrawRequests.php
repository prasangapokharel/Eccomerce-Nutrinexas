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
        $bankAccounts = $this->db->query(
            "SELECT * FROM seller_bank_accounts WHERE seller_id = ? ORDER BY is_default DESC",
            [$this->sellerId]
        )->all();
        
        $this->view('seller/withdraw-requests/create', [
            'title' => 'Request Withdrawal',
            'wallet' => $wallet,
            'bankAccounts' => $bankAccounts
        ]);
    }

    public function detail($id)
    {
        $request = $this->db->query(
            "SELECT wr.*, ba.* 
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
            
            // Validate bank account is selected
            if (!$bankAccountId) {
                $this->setFlash('error', 'Please select a bank account');
                $this->redirect('seller/withdraw-requests/create');
                return;
            }
            
            if ($amount <= 0) {
                $this->setFlash('error', 'Invalid withdrawal amount');
                $this->redirect('seller/withdraw-requests/create');
                return;
            }
            
            $wallet = $this->walletModel->getWalletBySellerId($this->sellerId);
            
            if ($amount > $wallet['balance']) {
                $this->setFlash('error', 'Insufficient balance');
                $this->redirect('seller/withdraw-requests/create');
                return;
            }
            
            $result = $this->db->query(
                "INSERT INTO seller_withdraw_requests 
                 (seller_id, amount, payment_method, bank_account_id, account_details, status) 
                 VALUES (?, ?, ?, ?, ?, 'pending')",
                [$this->sellerId, $amount, $paymentMethod, $bankAccountId, $accountDetails]
            )->execute();
            
            if ($result) {
                $requestId = $this->db->lastInsertId();
                
                $newPending = $wallet['pending_withdrawals'] + $amount;
                $this->db->query(
                    "UPDATE seller_wallet SET pending_withdrawals = ? WHERE seller_id = ?",
                    [$newPending, $this->sellerId]
                )->execute();
                
                $this->setFlash('success', 'Withdrawal request submitted successfully');
                $this->redirect('seller/withdraw-requests/detail/' . $requestId);
            } else {
                $this->setFlash('error', 'Failed to create withdrawal request');
                $this->redirect('seller/withdraw-requests/create');
            }
        } catch (Exception $e) {
            error_log('Create withdrawal error: ' . $e->getMessage());
            $this->setFlash('error', 'Error creating withdrawal request');
            $this->redirect('seller/withdraw-requests/create');
        }
    }
}

