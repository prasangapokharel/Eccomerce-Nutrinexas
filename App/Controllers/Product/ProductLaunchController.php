<?php

namespace App\Controllers\Product;

use App\Core\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Core\Session;
use Exception;

class ProductLaunchController extends Controller
{
    private Product $productModel;
    private ProductImage $productImageModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
    }

    /**
     * List products that are scheduled to launch in the future
     */
    public function index()
    {
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $limit = 12;
        $offset = ($page - 1) * $limit;

        try {
            $db = $this->productModel->getDb();

            // Only upcoming launches: scheduled flag set and effective launch date is in the future
            $sql = "
                SELECT p.*
                FROM products p
                WHERE p.status = 'active'
                  AND (p.approval_status = 'approved'
                       OR p.approval_status IS NULL
                       OR p.seller_id IS NULL
                       OR p.seller_id = 0)
                  AND p.is_scheduled = 1
                  AND (
                        (p.scheduled_date IS NOT NULL AND p.scheduled_date > NOW())
                        OR (
                            p.scheduled_date IS NULL
                            AND p.scheduled_duration IS NOT NULL
                            AND DATE_ADD(p.created_at, INTERVAL p.scheduled_duration DAY) > NOW()
                        )
                  )
                ORDER BY COALESCE(
                    p.scheduled_date,
                    DATE_ADD(p.created_at, INTERVAL p.scheduled_duration DAY)
                ) ASC
                LIMIT ? OFFSET ?
            ";

            $products = $db->query($sql, [$limit, $offset])->all();

            // Total count for pagination (same filters without limit/offset)
            $countSql = "
                SELECT COUNT(*) as count
                FROM products p
                WHERE p.status = 'active'
                  AND (p.approval_status = 'approved'
                       OR p.approval_status IS NULL
                       OR p.seller_id IS NULL
                       OR p.seller_id = 0)
                  AND p.is_scheduled = 1
                  AND (
                        (p.scheduled_date IS NOT NULL AND p.scheduled_date > NOW())
                        OR (
                            p.scheduled_date IS NULL
                            AND p.scheduled_duration IS NOT NULL
                            AND DATE_ADD(p.created_at, INTERVAL p.scheduled_duration DAY) > NOW()
                        )
                  )
            ";
            $totalRow = $db->query($countSql)->single();
            $totalProducts = (int)($totalRow['count'] ?? 0);
            $totalPages = $totalProducts > 0 ? (int)ceil($totalProducts / $limit) : 1;

            if (!empty($products)) {
                $products = $this->enrichProductsForCards($products);
            }

            $this->view('products/launching', [
                'title' => 'Launching Soon',
                'products' => $products,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalProducts' => $totalProducts,
            ]);
        } catch (Exception $e) {
            error_log('Product launch listing error: ' . $e->getMessage());
            $this->view('products/launching', [
                'title' => 'Launching Soon',
                'products' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'totalProducts' => 0,
            ]);
        }
    }

    /**
     * Prepare products for card rendering (image, price, rating, schedule flags)
     *
     * @param array $products
     * @return array
     */
    private function enrichProductsForCards(array $products): array
    {
        // Primary images
        foreach ($products as &$product) {
            $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
            if ($primaryImage && !empty($primaryImage['image_url'])) {
                $product['image_url'] = $primaryImage['image_url'];
            } elseif (!empty($product['image'])) {
                $product['image_url'] = $product['image'];
            } else {
                $product['image_url'] = \App\Core\View::asset('images/product_default.jpg');
            }

            // Normalize scheduled flags / remaining days
            $product['remaining_days'] = 0;
            if (!empty($product['is_scheduled'])) {
                $now = new \DateTime();
                $scheduledDate = null;
                if (!empty($product['scheduled_date'])) {
                    $scheduledDate = new \DateTime($product['scheduled_date']);
                } elseif (!empty($product['scheduled_duration']) && !empty($product['created_at'])) {
                    $createdDate = new \DateTime($product['created_at']);
                    $scheduledDate = clone $createdDate;
                    $scheduledDate->add(new \DateInterval('P' . (int)$product['scheduled_duration'] . 'D'));
                }
                if ($scheduledDate instanceof \DateTime && $scheduledDate > $now) {
                    $product['is_scheduled'] = true;
                    $product['remaining_days'] = $now->diff($scheduledDate)->days;
                } else {
                    $product['is_scheduled'] = false;
                }
            }
        }
        unset($product);

        // Review stats in batch
        $ids = array_column($products, 'id');
        $reviewStats = $this->productModel->getReviewStatsBatch($ids);
        foreach ($products as &$product) {
            $stats = $reviewStats[$product['id']] ?? ['total_reviews' => 0, 'average_rating' => 0];
            $product['review_count'] = $stats['total_reviews'];
            $product['avg_rating'] = $stats['average_rating'];
        }
        unset($product);

        // Wishlist flag for logged-in users
        if (Session::has('user_id')) {
            $wishlistModel = new \App\Models\Wishlist();
            $userId = Session::get('user_id');
            foreach ($products as &$product) {
                $product['in_wishlist'] = $wishlistModel->isInWishlist($userId, $product['id']);
            }
            unset($product);
        }

        return $products;
    }
}


