<?php

namespace App\Controllers\Seller;

use App\Core\Controller;
use App\Core\Session;
use Exception;

class BaseSellerController extends Controller
{
    protected $sellerId;
    protected $sellerData;

    public function __construct()
    {
        parent::__construct();
        $this->checkSellerAuth();
    }

    /**
     * Check if seller is authenticated
     */
    protected function checkSellerAuth()
    {
        $sellerId = Session::get('seller_id');
        
        if (!$sellerId) {
            $this->setFlash('error', 'Please login to access seller dashboard');
            $this->redirect('seller/login');
            exit;
        }

        $this->sellerId = $sellerId;
        
        // Load seller data
        $sellerModel = new \App\Models\Seller();
        $this->sellerData = $sellerModel->find($sellerId);
        
        if (!$this->sellerData || $this->sellerData['status'] !== 'active') {
            Session::remove('seller_id');
            $this->setFlash('error', 'Your seller account is not active');
            $this->redirect('seller/login');
            exit;
        }
    }

    /**
     * Require seller authentication
     */
    protected function requireSeller()
    {
        if (!$this->sellerId) {
            $this->redirect('seller/login');
            exit;
        }
    }
}


