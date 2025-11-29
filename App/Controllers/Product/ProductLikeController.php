<?php

namespace App\Controllers\Product;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ProductSocial;

/**
 * ProductLikeController
 * Handles product like/unlike functionality
 */
class ProductLikeController extends Controller
{
    private $productSocialModel;

    public function __construct()
    {
        parent::__construct();
        $this->productSocialModel = new ProductSocial();
    }

    /**
     * Like a product
     * 
     * @param int|null $productId
     * @return void
     */
    public function like($productId = null)
    {
        $this->requireLogin();

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

        $userId = Session::get('user_id');
        if (!$userId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Please login to like products'
            ], 401);
            return;
        }

        $result = $this->productSocialModel->likeProduct($productId, $userId);

        if ($result) {
            $likeCount = $this->productSocialModel->getLikeCount($productId);
            $isLiked = $this->productSocialModel->isLiked($productId, $userId);

            $this->jsonResponse([
                'success' => true,
                'liked' => true,
                'like_count' => $likeCount,
                'is_liked' => $isLiked,
                'message' => 'Product liked successfully'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to like product'
            ], 500);
        }
    }

    /**
     * Unlike a product
     * 
     * @param int|null $productId
     * @return void
     */
    public function unlike($productId = null)
    {
        $this->requireLogin();

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

        $userId = Session::get('user_id');
        if (!$userId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Please login to unlike products'
            ], 401);
            return;
        }

        $result = $this->productSocialModel->unlikeProduct($productId, $userId);

        if ($result) {
            $likeCount = $this->productSocialModel->getLikeCount($productId);
            $isLiked = $this->productSocialModel->isLiked($productId, $userId);

            $this->jsonResponse([
                'success' => true,
                'liked' => false,
                'like_count' => $likeCount,
                'is_liked' => $isLiked,
                'message' => 'Product unliked successfully'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to unlike product'
            ], 500);
        }
    }

    /**
     * Toggle like/unlike
     * 
     * @param int|null $productId
     * @return void
     */
    public function toggle($productId = null)
    {
        $this->requireLogin();

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

        $userId = Session::get('user_id');
        if (!$userId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Please login to like/unlike products'
            ], 401);
            return;
        }

        // Check current status
        $isCurrentlyLiked = $this->productSocialModel->isLiked($productId, $userId);
        
        // Toggle: if liked, unlike; if not liked, like
        $newLikeStatus = $isCurrentlyLiked ? 0 : 1;
        $result = $this->productSocialModel->toggleLike($productId, $userId, $newLikeStatus);

        if ($result) {
            $likeCount = $this->productSocialModel->getLikeCount($productId);
            $isLiked = $this->productSocialModel->isLiked($productId, $userId);

            $this->jsonResponse([
                'success' => true,
                'liked' => $isLiked,
                'like_count' => $likeCount,
                'is_liked' => $isLiked,
                'message' => $isLiked ? 'Product liked successfully' : 'Product unliked successfully'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to toggle like status'
            ], 500);
        }
    }

    /**
     * Get like count for a product
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

        $likeCount = $this->productSocialModel->getLikeCount($productId);
        $isLiked = false;

        // Check if user is logged in and has liked
        if (Session::has('user_id')) {
            $userId = Session::get('user_id');
            $isLiked = $this->productSocialModel->isLiked($productId, $userId);
        }

        $this->jsonResponse([
            'success' => true,
            'like_count' => $likeCount,
            'is_liked' => $isLiked
        ]);
    }
}




