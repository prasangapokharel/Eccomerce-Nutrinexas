<?php
namespace App\Controllers\Review;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;

class ReviewController extends Controller
{
    private $reviewModel;
    private $productModel;
    private $userModel; // Properly declared property

    public function __construct()
    {
        parent::__construct();
        $this->reviewModel = new Review();
        $this->productModel = new Product();
        $this->userModel = new User();
    }

    /**
     * Serve review image
     *
     * @param string $filename
     */
    public function serveReviewImage($filename)
    {
        $imagePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'review' . DIRECTORY_SEPARATOR . $filename;
        
        if (file_exists($imagePath) && is_file($imagePath)) {
            $mimeType = mime_content_type($imagePath);
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($imagePath));
            header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
            readfile($imagePath);
        } else {
            http_response_code(404);
            echo 'Image not found';
        }
        exit;
    }

    public function submit()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request');
            $this->redirect('products');
            return;
        }

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $reviewText = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->setFlash('error', 'Invalid product');
            $this->redirect('products');
            return;
        }

        $userId = Session::has('user_id') ? (int)Session::get('user_id') : null;

        $errors = [];
        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Rating must be between 1 and 5';
        }
        if (strlen($reviewText) < 3) {
            $errors['review'] = 'Review is too short';
        }

        // Prevent duplicate reviews for logged-in users; allow multiple for guests (no user_id)
        if ($userId && $this->reviewModel->hasUserReviewed($userId, $productId)) {
            $errors['general'] = 'You have already reviewed this product';
        }

        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', array_values($errors)));
            $this->redirect('products/view/' . ($product['slug'] ?? $productId));
            return;
        }

        $data = [
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $rating,
            'review' => $reviewText,
        ];

        try {
            $createdId = $this->reviewModel->create($data);
            if ($createdId) {
                $this->setFlash('success', 'Review submitted successfully');
            } else {
                $this->setFlash('error', 'Failed to submit review');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('products/view/' . ($product['slug'] ?? $productId));
    }

    /**
     * Handle AJAX review submission
     */
    public function submitAjax()
    {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }

            // Get form data
            $productId = (int)($_POST['product_id'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);
            $reviewText = trim($_POST['review_text'] ?? $_POST['comment'] ?? '');
            
            // Validate required fields
            if (empty($productId) || empty($rating) || empty($reviewText)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                return;
            }

            // Validate rating
            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
                return;
            }

            // Get user ID (null for guest, actual ID for logged-in)
            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

            // Check if user already reviewed this product (only for logged-in users)
            if ($userId) {
                $existingReview = $this->reviewModel->findByCriteria(['product_id' => $productId, 'user_id' => $userId]);
                if ($existingReview) {
                    echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
                    return;
                }
            }

            // Handle image upload if provided
            $imagePath = null;
            if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reviews';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
                        return;
                    }
                }
                
                $file = $_FILES['review_image'];
                $fileName = 'img_' . uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file['type'], $allowedTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid image format. Only JPG, PNG, GIF, and WebP are allowed']);
                    return;
                }
                
                // Validate file size (max 300KB)
                if ($file['size'] > 300 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'Image size too large. Maximum size is 300KB']);
                    return;
                }
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $imagePath = $fileName; // Save just the filename, not the full path
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                    return;
                }
            }
            
            // Create review data
            $reviewData = [
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $rating,
                'review' => $reviewText,
                'image_path' => $imagePath
            ];
            


            // Create the review
            $reviewId = $this->reviewModel->create($reviewData);
            
            if (!$reviewId) {
                echo json_encode(['success' => false, 'message' => 'Failed to create review']);
                return;
            }

            // Get the newly created review for display
            $newReview = $this->reviewModel->getById($reviewId);
            
            if (!$newReview) {
                echo json_encode(['success' => false, 'message' => 'Review created but failed to retrieve for display']);
                return;
            }

            // Get user information for display
            $userName = 'Guest User';
            if ($userId) {
                $user = $this->userModel->find($userId);
                if ($user) {
                    $userName = $user['first_name'] . ' ' . $user['last_name'];
                }
            }

            // Prepare review data for display
            $displayData = [
                'id' => $newReview['id'],
                'rating' => $newReview['rating'],
                'review' => $newReview['review'],
                'image_path' => $newReview['image_path'],
                'created_at' => $newReview['created_at'],
                'user_name' => $userName,
                'user_id' => $userId,
                'first_name' => $userName,
                'last_name' => ''
            ];

            // Render the review HTML using the partial view
            ob_start();
            $rev = $displayData; // Set the $rev variable for the partial
            include dirname(__DIR__) . '/views/products/partials/_review_item.php';
            $reviewHtml = ob_get_clean();

            echo json_encode([
                'success' => true, 
                'message' => 'Review submitted successfully!',
                'reviewHtml' => $reviewHtml,
                'reviewData' => $displayData
            ]);

        } catch (\Exception $e) {
            error_log("Review submission error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while submitting your review']);
        }
    }
    
    /**
     * Delete a review (AJAX)
     */
    public function delete()
    {
        // Set JSON header
        header('Content-Type: application/json');
        
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                exit;
            }
            
            // Check if user is logged in
            $userId = Session::get('user_id');
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'You must be logged in to delete a review']);
                exit;
            }
            
            // Get review ID
            $reviewId = (int)($_POST['review_id'] ?? 0);
            if (empty($reviewId)) {
                echo json_encode(['success' => false, 'message' => 'Review ID is required']);
                exit;
            }
            
            // Get the review to verify ownership (simple query)
            $review = $this->reviewModel->findById($reviewId);
            
            if (!$review) {
                echo json_encode(['success' => false, 'message' => 'Review not found']);
                exit;
            }
            
            // Verify that the user owns this review
            if ((int)$review['user_id'] !== (int)$userId) {
                echo json_encode(['success' => false, 'message' => 'You can only delete your own reviews']);
                exit;
            }
            
            // Delete the review
            $result = $this->reviewModel->delete($reviewId);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete review']);
                exit;
            }
            
        } catch (\Exception $e) {
            error_log("Review deletion error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
            exit;
        }
    }
}
