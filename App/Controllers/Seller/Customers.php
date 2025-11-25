<?php

namespace App\Controllers\Seller;

use Exception;

class Customers extends BaseSellerController
{
    /**
     * List customers
     */
    public function index()
    {
        $customers = $this->getCustomers();

        $this->view('seller/customers/index', [
            'title' => 'Customers',
            'customers' => $customers
        ]);
    }

    /**
     * View customer details
     */
    public function detail($id)
    {
        $customer = $this->getCustomerDetails($id);
        
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('seller/customers');
            return;
        }

        $orders = $this->getCustomerOrders($id);

        $this->view('seller/customers/detail', [
            'title' => 'Customer Details',
            'customer' => $customer,
            'orders' => $orders
        ]);
    }

    /**
     * Get customers who ordered from this seller
     */
    private function getCustomers()
    {
        $sql = "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.phone,
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_spent
                FROM users u
                INNER JOIN orders o ON u.id = o.user_id
                WHERE o.seller_id = ?
                GROUP BY u.id
                ORDER BY total_spent DESC";
        
        $db = \App\Core\Database::getInstance();
        return $db->query($sql, [$this->sellerId])->all();
    }

    /**
     * Get customer details
     */
    private function getCustomerDetails($userId)
    {
        $sql = "SELECT u.*, 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(o.total_amount) as total_spent
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id AND o.seller_id = ?
                WHERE u.id = ?
                GROUP BY u.id";
        
        $db = \App\Core\Database::getInstance();
        return $db->query($sql, [$this->sellerId, $userId])->single();
    }

    /**
     * Get customer orders
     */
    private function getCustomerOrders($userId)
    {
        $sql = "SELECT * FROM orders 
                WHERE user_id = ? AND seller_id = ?
                ORDER BY created_at DESC";
        
        $db = \App\Core\Database::getInstance();
        return $db->query($sql, [$userId, $this->sellerId])->all();
    }
}

