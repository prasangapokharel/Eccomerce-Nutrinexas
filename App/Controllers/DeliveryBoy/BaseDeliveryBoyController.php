<?php

namespace App\Controllers\DeliveryBoy;

use App\Core\Controller;
use App\Core\Session;

class BaseDeliveryBoyController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Check if delivery boy is logged in
        if (!Session::has('delivery_boy_id')) {
            $this->setFlash('error', 'Please login to continue');
            $this->redirect('deliveryboy/login');
        }
    }
}

