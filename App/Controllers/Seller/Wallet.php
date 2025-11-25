<?php

namespace App\Controllers\Seller;

use Exception;

class Wallet extends BaseSellerController
{
    private $walletModel;

    public function __construct()
    {
        parent::__construct();
        $this->walletModel = new \App\Models\SellerWallet();
    }

    public function index()
    {
        $wallet = $this->walletModel->getWalletBySellerId($this->sellerId);
        $transactions = $this->walletModel->getTransactions($this->sellerId, 50);
        
        $this->view('seller/wallet/index', [
            'title' => 'My Wallet',
            'wallet' => $wallet,
            'transactions' => $transactions
        ]);
    }

    public function transactions()
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 25;
        $offset = ($page - 1) * $limit;
        
        $transactions = $this->walletModel->getTransactions($this->sellerId, $limit, $offset);
        $total = count($this->walletModel->getTransactions($this->sellerId, 1000));
        $totalPages = ceil($total / $limit);
        
        $this->view('seller/wallet/transactions', [
            'title' => 'Wallet Transactions',
            'transactions' => $transactions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }
}

