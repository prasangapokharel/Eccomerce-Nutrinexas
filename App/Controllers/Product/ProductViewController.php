<?php

namespace App\Controllers\Product;

use App\Core\Controller;
use App\Models\ProductView;

/**
 * ProductViewController
 * Handles product view tracking
 */
class ProductViewController extends Controller
{
    private $productViewModel;

    public function __construct()
    {
        parent::__construct();
        $this->productViewModel = new ProductView();
    }

    /**
     * Record a product view
     * Called via AJAX when product page is viewed
     * 
     * @param int|null $productId
     * @return void
     */
    public function record($productId = null)
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid request'
            ], 400);
            return;
        }

        if (!$productId) {
            $productId = $this->post('product_id') ?? $this->get('product_id');
        }

        if (!$productId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Product ID is required'
            ], 400);
            return;
        }

        // Record the view
        $viewId = $this->productViewModel->recordView($productId);

        if ($viewId) {
            // Get updated view count
            $viewCount = $this->productViewModel->getViewCount($productId);

            $this->jsonResponse([
                'success' => true,
                'view_id' => $viewId,
                'view_count' => $viewCount,
                'message' => 'View recorded successfully'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to record view'
            ], 500);
        }
    }

    /**
     * Get view count for a product
     * 
     * @param int|null $productId
     * @return void
     */
    public function getCount($productId = null)
    {
        if (!$productId) {
            $productId = $this->get('product_id') ?? $this->post('product_id');
        }

        if (!$productId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Product ID is required'
            ], 400);
            return;
        }

        $viewCount = $this->productViewModel->getViewCount($productId);

        $this->jsonResponse([
            'success' => true,
            'view_count' => $viewCount
        ]);
    }
}




