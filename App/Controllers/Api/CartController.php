<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Cart;

class CartController extends Controller
{
    private $cartModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new Cart();
    }

    /**
     * Get cart count for current user
     */
    public function count()
    {
        header('Content-Type: application/json');
        
        try {
            if (!Session::has('user_id')) {
                echo json_encode(['success' => true, 'count' => 0]);
                return;
            }

            $userId = Session::get('user_id');
            $count = $this->cartModel->getCartCount($userId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
        } catch (Exception $e) {
            error_log('Cart count error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load cart count',
                'count' => 0
            ]);
        }
    }
}


















