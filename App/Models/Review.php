<?php
namespace App\Models;

use App\Core\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'product_id', 'rating', 'review', 'image_path', 'video_path'];

    /**
     * Get reviews by product ID with user information
     *
     * @param int $productId
     * @return array
     */
    public function getByProductId($productId)
    {
        $sql = "SELECT r.*, u.first_name, u.last_name, u.email, u.profile_image, u.sponsor_status,
                       p.product_name, p.slug
                FROM {$this->table} r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id
                WHERE r.product_id = ?
                ORDER BY r.created_at DESC";
        
        return $this->db->query($sql)->bind([$productId])->all();
    }

    /**
     * Get average rating for a product
     *
     * @param int $productId
     * @return float
     */
    public function getAverageRating($productId)
    {
        $sql = "SELECT AVG(rating) as avg_rating FROM {$this->table} WHERE product_id = ?";
        $result = $this->db->query($sql)->bind([$productId])->single();
        return $result ? round((float)$result['avg_rating'], 1) : 0;
    }

    /**
     * Get review count for a product
     *
     * @param int $productId
     * @return int
     */
    public function getReviewCount($productId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE product_id = ?";
        $result = $this->db->query($sql)->bind([$productId])->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get rating distribution for a product
     *
     * @param int $productId
     * @return array
     */
    public function getRatingDistribution($productId)
    {
        $sql = "SELECT rating, COUNT(*) as count 
                FROM {$this->table} 
                WHERE product_id = ? 
                GROUP BY rating 
                ORDER BY rating DESC";
        
        $results = $this->db->query($sql)->bind([$productId])->all();
        
        // Initialize all ratings to 0
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        
        // Fill in actual counts
        foreach ($results as $result) {
            $distribution[$result['rating']] = (int)$result['count'];
        }
        
        return $distribution;
    }

    /**
     * Check if user has reviewed a product
     *
     * @param int $userId
     * @param int $productId
     * @return bool
     */
    public function hasUserReviewed($userId, $productId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        $result = $this->db->query($sql)->bind([$userId, $productId])->single();
        return $result && $result['count'] > 0;
    }

    /**
     * Get user's review for a product
     *
     * @param int $userId
     * @param int $productId
     * @return array|null
     */
    public function getUserReview($userId, $productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        return $this->db->query($sql)->bind([$userId, $productId])->single();
    }

    /**
     * Create a new review
     *
     * @param array $data
     * @return int|false Returns the last inserted ID on success, false on failure
     * @throws \Exception If validation fails
     */
    public function create($data)
    {
        try {
            // Validate required fields
            if (empty($data['review']) || trim($data['review']) === '') {
                throw new \Exception("Review text is required");
            }

            if (empty($data['rating']) || !is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
                throw new \Exception("Rating must be between 1 and 5");
            }

            if (!isset($data['product_id']) || (int)$data['product_id'] <= 0) {
                throw new \Exception("Product ID is required");
            }

            // Normalize user_id for guests -> NULL to satisfy FK with ON DELETE SET NULL
            if (!isset($data['user_id']) || !is_numeric($data['user_id']) || (int)$data['user_id'] <= 0) {
                $data['user_id'] = null; // guest
            }

            // Check if user has already reviewed this product
            if ((int)$data['user_id'] > 0 && $this->hasUserReviewed($data['user_id'], $data['product_id'])) {
                throw new \Exception("You have already reviewed this product");
            }

            // Sanitize review text
            $data['review'] = trim($data['review']);
            $data['rating'] = (int)$data['rating'];
            
            // Only set timestamps if not provided (let database handle defaults)
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = $data['created_at'];
            }

            // Build dynamic SQL based on available fields
            $fields = ['user_id', 'product_id', 'rating', 'review', 'created_at', 'updated_at'];
            $values = ['?', '?', '?', '?', '?', '?'];
            $bindData = [
                $data['user_id'],
                $data['product_id'],
                $data['rating'],
                $data['review'],
                $data['created_at'],
                $data['updated_at']
            ];
            
            // Add image_path if provided
            if (!empty($data['image_path'])) {
                $fields[] = 'image_path';
                $values[] = '?';
                $bindData[] = $data['image_path'];
            }
            
            // Add video_path if provided
            if (!empty($data['video_path'])) {
                $fields[] = 'video_path';
                $values[] = '?';
                $bindData[] = $data['video_path'];
            }
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $values) . ")";
            
            $result = $this->db->query($sql)->bind($bindData)->execute();
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log("Review creation failed: " . $e->getMessage());
            throw $e; // Re-throw to be caught by the caller
        }
    }

    /**
     * Update an existing review
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws \Exception If validation fails
     */
    public function update($id, $data)
    {
        // Validate required fields
        if (empty($data['review']) || trim($data['review']) === '') {
            throw new \Exception("Review text is required");
        }

        if (empty($data['rating']) || !is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            throw new \Exception("Rating must be between 1 and 5");
        }

        // Sanitize review text
        $data['review'] = trim($data['review']);
        $data['rating'] = (int)$data['rating'];
        $data['updated_at'] = date('Y-m-d H:i:s');

        $sql = "UPDATE {$this->table} SET rating = ?, review = ?, updated_at = ? WHERE id = ?";
        
        return $this->db->query($sql)->bind([
            $data['rating'],
            $data['review'],
            $data['updated_at'],
            $id
        ])->execute();
    }

    /**
     * Delete a review
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql)->bind([$id])->execute();
    }

    /**
     * Get recent reviews (for admin dashboard)
     *
     * @param int $limit
     * @return array
     */
    public function getRecentReviews($limit = 10)
    {
        $sql = "SELECT r.*, u.first_name, u.last_name, p.product_name 
                FROM {$this->table} r
                JOIN users u ON r.user_id = u.id
                JOIN products p ON r.product_id = p.id
                ORDER BY r.created_at DESC
                LIMIT ?";
        
        return $this->db->query($sql)->bind([$limit])->all();
    }

    /**
     * Get all reviews with user and product context
     *
     * @return array
     */
    public function getAllReviews()
    {
        $sql = "SELECT r.*, u.first_name, u.last_name, u.email, u.profile_image, u.sponsor_status,
                       p.product_name, p.slug
                FROM {$this->table} r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id
                ORDER BY r.created_at DESC";
        return $this->db->query($sql)->all();
    }

    /**
     * Delete review by ID (alias to base delete)
     *
     * @param int $id
     * @return bool
     */
    public function deleteReview($id)
    {
        return $this->delete($id);
    }

    /**
     * Get a single review by ID (simple - for verification only)
     *
     * @param int $id
     * @return array|null
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql)->bind([$id])->single();
    }
    
    /**
     * Get a single review by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $sql = "SELECT r.*, u.first_name, u.last_name, u.email, u.profile_image, u.sponsor_status,
                       p.product_name, p.slug
                FROM {$this->table} r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id
                WHERE r.id = ?";
        
        return $this->db->query($sql)->bind([$id])->single();
    }

    /**
     * Get random reviews for home page display
     *
     * @param int $limit
     * @return array
     */
    public function getRandomReviews($limit = 6)
    {
        $sql = "SELECT r.*, 
                       u.first_name, u.last_name, u.profile_image, u.sponsor_status,
                       p.product_name, p.slug
                FROM {$this->table} r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id
                WHERE r.rating >= 4 
                ORDER BY RAND() 
                LIMIT ?";
        
        return $this->db->query($sql)->bind([$limit])->resultSet();
    }

    /**
     * Get featured reviews (high rating + has media)
     *
     * @param int $limit
     * @return array
     */
    public function getFeaturedReviews($limit = 4)
    {
        $sql = "SELECT r.*, 
                       u.first_name, u.last_name, u.profile_image, u.sponsor_status,
                       p.product_name, p.slug
                FROM {$this->table} r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id
                WHERE r.rating = 5 
                AND (r.image_path IS NOT NULL OR r.video_path IS NOT NULL)
                ORDER BY RAND() 
                LIMIT ?";
        
        return $this->db->query($sql)->bind([$limit])->resultSet();
    }

    /**
     * Find reviews by specific criteria
     *
     * @param array $criteria
     * @return array
     */
    public function findByCriteria($criteria)
    {
        $whereClause = [];
        $values = [];
        
        foreach ($criteria as $key => $value) {
            $whereClause[] = "r.{$key} = ?";
            $values[] = $value;
        }
        
        $sql = "SELECT r.*, u.first_name, u.last_name, u.email, u.profile_image, u.sponsor_status,
                       p.product_name, p.slug
                FROM {$this->table} r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN products p ON r.product_id = p.id
                WHERE " . implode(' AND ', $whereClause);
        
        return $this->db->query($sql)->bind($values)->single();
    }
}