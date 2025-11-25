<?php

namespace App\Controllers\Seller;

use Exception;

class Reviews extends BaseSellerController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \App\Core\Database::getInstance();
    }

    public function index()
    {
        // Get all reviews for products owned by this seller
        $reviews = $this->db->query(
            "SELECT r.*, 
                    p.product_name, p.seller_id,
                    pi.image_url as product_image,
                    u.first_name, u.last_name, u.email, u.profile_image
             FROM reviews r
             INNER JOIN products p ON r.product_id = p.id
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             LEFT JOIN users u ON r.user_id = u.id
             WHERE p.seller_id = ?
             ORDER BY r.created_at DESC",
            [$this->sellerId]
        )->all();
        
        // Calculate stats
        $stats = [
            'total' => count($reviews),
            'pending' => 0,
            'approved' => 0,
            'average_rating' => 0
        ];
        
        $totalRating = 0;
        foreach ($reviews as $review) {
            $totalRating += $review['rating'];
        }
        
        if (count($reviews) > 0) {
            $stats['average_rating'] = round($totalRating / count($reviews), 2);
        }
        
        $this->view('seller/reviews/index', [
            'title' => 'Product Reviews',
            'reviews' => $reviews,
            'stats' => $stats,
            'statusFilter' => ''
        ]);
    }

    public function reply($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/reviews');
            return;
        }
        
        try {
            $reply = trim($_POST['reply'] ?? '');
            
            if (empty($reply)) {
                $this->setFlash('error', 'Reply message is required');
                $this->redirect('seller/reviews');
                return;
            }
            
            // Verify review belongs to seller's product
            $review = $this->db->query(
                "SELECT r.* FROM reviews r
                 INNER JOIN products p ON r.product_id = p.id
                 WHERE r.id = ? AND p.seller_id = ?",
                [$id, $this->sellerId]
            )->single();
            
            if (!$review) {
                $this->setFlash('error', 'Review not found');
                $this->redirect('seller/reviews');
                return;
            }
            
            // Note: The reviews table doesn't have seller_reply column
            // We'll need to add it or use a separate table for seller replies
            // For now, we'll log the reply (you may want to create a seller_review_replies table)
            error_log("Seller reply to review {$id}: {$reply}");
            
            $this->setFlash('success', 'Reply logged successfully. Note: Review reply feature needs database update.');
        } catch (Exception $e) {
            error_log('Reply to review error: ' . $e->getMessage());
            $this->setFlash('error', 'Error adding reply');
        }
        
        $this->redirect('seller/reviews');
    }
}

