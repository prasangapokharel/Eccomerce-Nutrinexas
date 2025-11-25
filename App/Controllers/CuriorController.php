<?php
/**
 * Legacy CuriorController - Redirects to new module-based structure
 * This file is kept for backward compatibility
 */

namespace App\Controllers;

use App\Core\Controller;

class CuriorController extends Controller
{
    /**
     * Redirect to new Auth controller
     */
    public function login()
    {
        $this->redirect('curior/login');
    }

    /**
     * Redirect to new Dashboard controller
     */
    public function dashboard()
    {
        $this->redirect('curior/dashboard');
    }

    /**
     * Redirect to new Order controller
     */
    public function viewOrder($id = null)
    {
        if ($id) {
            $this->redirect('curior/order/view/' . $id);
        } else {
            $this->redirect('curior/dashboard');
        }
    }

    /**
     * Redirect to new Order controller
     */
    public function getOrders()
    {
        $this->redirect('curior/orders');
    }

    /**
     * Redirect to new Order controller
     */
    public function updateOrderStatus()
    {
        $this->redirect('curior/dashboard');
    }

    /**
     * Redirect to new Auth controller
     */
    public function logout()
    {
        $this->redirect('curior/logout');
    }
}
