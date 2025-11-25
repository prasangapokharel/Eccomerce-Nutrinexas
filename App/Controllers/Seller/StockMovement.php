<?php

namespace App\Controllers\Seller;

use App\Models\StockMovementLog;
use Exception;

class StockMovement extends BaseSellerController
{
    private $stockLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->stockLogModel = new StockMovementLog();
    }

    /**
     * Stock movement log page
     */
    public function index()
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;
        $productFilter = !empty($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        $typeFilter = $_GET['type'] ?? '';

        $movements = $this->stockLogModel->getBySellerId($this->sellerId, $limit, $offset);
        
        // Apply filters
        if ($productFilter) {
            $movements = array_filter($movements, function($m) use ($productFilter) {
                return $m['product_id'] == $productFilter;
            });
        }
        
        if ($typeFilter) {
            $movements = array_filter($movements, function($m) use ($typeFilter) {
                return $m['movement_type'] == $typeFilter;
            });
        }

        // Get products for filter dropdown
        $db = \App\Core\Database::getInstance();
        $products = $db->query(
            "SELECT id, product_name FROM products WHERE seller_id = ? ORDER BY product_name",
            [$this->sellerId]
        )->all();

        $this->view('seller/stock-movement/index', [
            'title' => 'Stock Movement Log',
            'movements' => $movements,
            'products' => $products,
            'currentPage' => $page,
            'productFilter' => $productFilter,
            'typeFilter' => $typeFilter
        ]);
    }
}

