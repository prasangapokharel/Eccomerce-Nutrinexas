<?php

namespace App\Controllers\Seller;

use Exception;

class Reports extends BaseSellerController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \App\Core\Database::getInstance();
    }

    /**
     * Cancelled and returned items report
     */
    public function index()
    {
        $period = $_GET['period'] ?? '30';
        $type = $_GET['type'] ?? 'all';
        
        $dateRange = $this->getDateRange($period);
        
        $cancelledItems = $this->getCancelledItems($dateRange);
        $returnedItems = $this->getReturnedItems($dateRange);
        
        $summary = [
            'cancelled' => [
                'count' => count($cancelledItems),
                'total_value' => array_sum(array_column($cancelledItems, 'item_total'))
            ],
            'returned' => [
                'count' => count($returnedItems),
                'total_value' => array_sum(array_column($returnedItems, 'item_total'))
            ]
        ];

        $this->view('seller/reports/index', [
            'title' => 'Cancelled & Returned Items Report',
            'period' => $period,
            'type' => $type,
            'cancelledItems' => $cancelledItems,
            'returnedItems' => $returnedItems,
            'summary' => $summary,
            'dateRange' => $dateRange
        ]);
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        $end = date('Y-m-d 23:59:59');
        $days = (int)$period;
        $start = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get cancelled items
     */
    private function getCancelledItems($dateRange)
    {
        $sql = "SELECT 
                    oi.id as item_id,
                    oi.order_id,
                    oi.product_id,
                    oi.quantity,
                    oi.price,
                    oi.total as item_total,
                    o.invoice,
                    o.customer_name,
                    u.email as customer_email,
                    o.created_at as order_date,
                    o.status as order_status,
                    p.product_name,
                    c.reason as cancel_reason,
                    c.status as cancel_status,
                    c.created_at as cancelled_at
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                INNER JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_cancel_log c ON o.id = c.order_id
                WHERE oi.seller_id = ?
                  AND o.status = 'cancelled'
                  AND o.created_at BETWEEN ? AND ?
                ORDER BY c.created_at DESC, o.created_at DESC";
        
        return $this->db->query($sql, [
            $this->sellerId,
            $dateRange['start'],
            $dateRange['end']
        ])->all();
    }

    /**
     * Get returned items
     */
    private function getReturnedItems($dateRange)
    {
        $sql = "SELECT 
                    oi.id as item_id,
                    oi.order_id,
                    oi.product_id,
                    oi.quantity,
                    oi.price,
                    oi.total as item_total,
                    o.invoice,
                    o.customer_name,
                    u.email as customer_email,
                    o.created_at as order_date,
                    o.status as order_status,
                    p.product_name,
                    o.updated_at as returned_at
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                INNER JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE oi.seller_id = ?
                  AND o.status = 'returned'
                  AND o.updated_at BETWEEN ? AND ?
                ORDER BY o.updated_at DESC";
        
        return $this->db->query($sql, [
            $this->sellerId,
            $dateRange['start'],
            $dateRange['end']
        ])->all();
    }
}

