<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ad;
use App\Models\Product;

class SponsoredAdsService
{
    private $db;
    private $adModel;
    private $productModel;
    private $billingService;
    private array $adTypeNameCache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->adModel = new Ad();
        $this->productModel = new Product();
        $this->billingService = new \App\Services\RealTimeAdBillingService();
    }

    /**
     * Get sponsored products for search results
     * Returns products with ads that match the search keyword
     * 
     * @param string $keyword Search keyword
     * @param int $limit Number of sponsored products to get
     * @return array Array of products with ad info
     */
    /**
     * Calculate Product Score (Flipkart Formula)
     * Product Score = (Rating × 0.6) + (Sales × 0.3) + (CTR × 0.1)
     */
    private function calculateProductScore($rating, $sales, $ctr)
    {
        $ratingScore = ($rating ?? 0) * 0.6;
        $salesScore = min(($sales ?? 0) / 100, 10) * 0.3; // Normalize sales (max 1000 = 10 points)
        $ctrScore = min(($ctr ?? 0) * 10, 10) * 0.1; // Normalize CTR (max 1.0 = 10 points)
        return $ratingScore + $salesScore + $ctrScore;
    }

    /**
     * Calculate Ad Rank (Flipkart Formula)
     * Ad Rank = Bid Amount + (Product Score × Weight)
     * Weight = 0.1 to 0.5 (using 0.3 as default)
     */
    private function calculateAdRank($bidAmount, $productScore, $weight = 0.3)
    {
        return $bidAmount + ($productScore * $weight);
    }

    public function getSponsoredProductsForSearch($keyword, $limit = 10)
    {
        $searchPattern = "%{$keyword}%";
        
        // Get active product ads with bid amount (cost), product data, and stats
        // Note: For real-time billing, we don't check payment_status = 'paid' anymore
        // Instead, we check wallet balance in real-time
        $sponsoredProducts = $this->db->query(
            "SELECT p.*, a.id as ad_id, a.seller_id as ad_seller_id, a.reach, a.click, a.created_at as ad_created_at,
                    a.billing_type, a.daily_budget, a.per_click_rate,
                    COALESCE(ac.cost_amount, 0) as bid_amount,
                    COALESCE(pi.image_url, p.image) as image_url,
                    (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) as review_count,
                    (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id) as avg_rating,
                    (SELECT COUNT(*) FROM order_items oi 
                     INNER JOIN orders o ON oi.order_id = o.id 
                     WHERE oi.product_id = p.id 
                     AND o.status = 'delivered' 
                     AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as monthly_sales
             FROM ads a
             INNER JOIN products p ON a.product_id = p.id
             INNER JOIN ads_types at ON a.ads_type_id = at.id
             LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE a.status = 'active'
             AND a.auto_paused = 0
             AND at.name = 'product_internal'
             AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
             AND CURDATE() BETWEEN a.start_date AND a.end_date
             AND p.status = 'active'
             AND (p.product_name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)
             ORDER BY a.created_at DESC
             LIMIT ?",
            [$searchPattern, $searchPattern, $searchPattern, $limit * 3] // Get more for filtering by wallet
        )->all();

        // Filter ads that can be shown (have sufficient wallet balance)
        $sponsoredProducts = array_filter($sponsoredProducts, function($product) {
            $canShow = $this->billingService->canShowAd($product['ad_id']);
            return $canShow['can_show'];
        });

        // Calculate ad rank for each product and sort
        foreach ($sponsoredProducts as &$product) {
            $rating = (float)($product['avg_rating'] ?? 0);
            $sales = (int)($product['monthly_sales'] ?? 0);
            $ctr = 0;
            if ($product['reach'] > 0) {
                $ctr = ($product['click'] ?? 0) / $product['reach'];
            }
            
            // Calculate product score
            $productScore = $this->calculateProductScore($rating, $sales, $ctr);
            
            // For real-time billing, use daily_budget or per_click_rate as bid amount
            $bidAmount = 0;
            if ($product['billing_type'] === 'daily_budget') {
                $bidAmount = (float)($product['daily_budget'] ?? 0);
            } elseif ($product['billing_type'] === 'per_click') {
                $bidAmount = (float)($product['per_click_rate'] ?? 0) * 10; // Multiply by 10 for ranking
            } else {
                $bidAmount = (float)($product['bid_amount'] ?? 0);
            }
            
            // Calculate ad rank (bid amount + product score × weight)
            $adRank = $this->calculateAdRank($bidAmount, $productScore, 0.3);
            
            $product['product_score'] = $productScore;
            $product['ad_rank'] = $adRank;
            $product['review_count'] = (int)($product['review_count'] ?? 0);
            $product['avg_rating'] = $rating;
            
            // Ensure image_url is set
            if (empty($product['image_url'])) {
                $product['image_url'] = $product['image'] ?? '/assets/images/products/default.jpg';
            }
        }
        unset($product);

        // Sort by ad rank (highest first) - Flipkart style
        // Tie-breaking: if same ad_rank, use newest ad (created_at DESC) or higher product_score
        usort($sponsoredProducts, function($a, $b) {
            $rankDiff = $b['ad_rank'] <=> $a['ad_rank'];
            if ($rankDiff !== 0) {
                return $rankDiff;
            }
            // Tie: prefer higher product score
            $scoreDiff = $b['product_score'] <=> $a['product_score'];
            if ($scoreDiff !== 0) {
                return $scoreDiff;
            }
            // If still tied, prefer newer ad (ad_created_at DESC)
            $createdA = strtotime($a['ad_created_at'] ?? $a['created_at'] ?? '1970-01-01');
            $createdB = strtotime($b['ad_created_at'] ?? $b['created_at'] ?? '1970-01-01');
            return $createdB <=> $createdA;
        });

        // Return top ranked products
        return array_slice($sponsoredProducts, 0, $limit);
    }

    /**
     * Get sponsored products for category page
     * 
     * @param string $category Category name
     * @param string|null $subtype Optional subtype
     * @param int $limit Number of sponsored products to get
     * @return array Array of products with ad info
     */
    public function getSponsoredProductsForCategory($category, $subtype = null, $limit = 10)
    {
        $params = [$category];
        $categoryFilter = "c.name COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci";
        
        if ($subtype) {
            $categoryFilter .= " AND st.name COLLATE utf8mb4_unicode_ci = ? COLLATE utf8mb4_unicode_ci";
            $params[] = $subtype;
        }
        
        $params[] = $limit;

        // Get active product ads with matching category products
        // For real-time billing, check wallet balance instead of payment_status
        $sponsoredProducts = $this->db->query(
            "SELECT DISTINCT p.*, a.id as ad_id, a.seller_id as ad_seller_id, a.created_at as ad_created_at, a.reach as ad_reach
             FROM ads a
             INNER JOIN products p ON a.product_id = p.id
             INNER JOIN ads_types at ON a.ads_type_id = at.id
             LEFT JOIN categories c ON p.category COLLATE utf8mb4_unicode_ci = c.name COLLATE utf8mb4_unicode_ci
             LEFT JOIN categories st ON p.subtype COLLATE utf8mb4_unicode_ci = st.name COLLATE utf8mb4_unicode_ci
             WHERE a.status = 'active'
             AND a.auto_paused = 0
             AND at.name = 'product_internal'
             AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
             AND CURDATE() BETWEEN a.start_date AND a.end_date
             AND p.status = 'active'
             AND {$categoryFilter}
             ORDER BY a.created_at DESC, a.reach DESC
             LIMIT ?",
            $params
        )->all();

        // Filter ads that can be shown (have sufficient wallet balance)
        $sponsoredProducts = array_filter($sponsoredProducts, function($product) {
            $canShow = $this->billingService->canShowAd($product['ad_id']);
            return $canShow['can_show'];
        });

        return $sponsoredProducts;
    }

    /**
     * Insert sponsored products into search results (Flipkart Style)
     * Positions: 1st, 3rd, 6th, then every 10th position
     * 
     * @param array $products Original products array
     * @param string $keyword Search keyword
     * @return array Products array with sponsored ads inserted
     */
    public function insertSponsoredInSearchResults($products, $keyword)
    {
        if (empty($products)) {
            return $products;
        }

        // Get sponsored products sorted by ad rank (highest first)
        $sponsoredProducts = $this->getSponsoredProductsForSearch($keyword, 20);
        
        if (empty($sponsoredProducts)) {
            return $products;
        }

        // Remove sponsored products that are already in results
        $existingProductIds = array_column($products, 'id');
        $sponsoredProducts = array_filter($sponsoredProducts, function($sp) use ($existingProductIds) {
            return !in_array($sp['id'], $existingProductIds);
        });

        if (empty($sponsoredProducts)) {
            return $products;
        }

        // Reset array keys
        $sponsoredProducts = array_values($sponsoredProducts);
        
        // Flipkart positioning: 1st (index 0), 3rd (index 2), 6th (index 5), then every 10th
        $fixedPositions = [0, 2, 5]; // 1st, 3rd, 6th
        $interval = 10; // Every 10th position after 6th
        
        // Calculate all positions where ads should be inserted
        $adPositions = $fixedPositions;
        $maxPosition = count($products) + count($sponsoredProducts);
        for ($pos = 15; $pos < $maxPosition; $pos += $interval) {
            $adPositions[] = $pos;
        }
        
        $result = [];
        $sponsoredIndex = 0;
        $insertionOffset = 0; // Track how many ads we've inserted
        
        // Insert ads at calculated positions
        foreach ($products as $index => $product) {
            $currentPosition = $index + $insertionOffset;
            
            // Check if we should insert a sponsored ad at this position
            if (in_array($currentPosition, $adPositions) && $sponsoredIndex < count($sponsoredProducts)) {
                $sponsoredAd = $sponsoredProducts[$sponsoredIndex];
                $sponsoredAd['is_sponsored'] = true;
                $sponsoredAd['ad_id'] = $sponsoredAd['ad_id'] ?? null;
                
                // Ensure image_url is set
                if (empty($sponsoredAd['image_url'])) {
                    $sponsoredAd['image_url'] = $sponsoredAd['image'] ?? '/assets/images/products/default.jpg';
                }
                
                $result[] = $sponsoredAd;
                $sponsoredIndex++;
                $insertionOffset++;
            }
            
            // Add the regular product
            $result[] = $product;
        }

        return $result;
    }

    /**
     * Insert sponsored products into category results
     * Positions: Top (0) and then every 8-12 products
     * 
     * @param array $products Original products array
     * @param string $category Category name
     * @param string|null $subtype Optional subtype
     * @return array Products array with sponsored ads inserted
     */
    public function insertSponsoredInCategoryResults($products, $category, $subtype = null)
    {
        if (empty($products)) {
            return $products;
        }

        // Get sponsored products
        $sponsoredProducts = $this->getSponsoredProductsForCategory($category, $subtype, 15);
        
        if (empty($sponsoredProducts)) {
            return $products;
        }

        // Remove sponsored products that are already in results
        $existingProductIds = array_column($products, 'id');
        $sponsoredProducts = array_filter($sponsoredProducts, function($sp) use ($existingProductIds) {
            return !in_array($sp['id'], $existingProductIds);
        });

        if (empty($sponsoredProducts)) {
            return $products;
        }

        // Reset array keys
        $sponsoredProducts = array_values($sponsoredProducts);
        
        // Calculate insertion positions: top (0) and then every 8-12 products
        $positions = [0]; // Always at top
        $interval = 10; // Insert every 10 products (between 8-12)
        $maxPosition = count($products) + count($sponsoredProducts);
        
        for ($pos = $interval; $pos < $maxPosition; $pos += $interval) {
            $positions[] = $pos;
        }

        $result = [];
        $sponsoredIndex = 0;
        $sponsoredCount = count($sponsoredProducts);
        $insertionOffset = 0; // Track how many ads we've inserted

        foreach ($products as $index => $product) {
            $currentPosition = $index + $insertionOffset;
            
            // Check if we should insert a sponsored ad at this position
            if (in_array($currentPosition, $positions) && $sponsoredIndex < $sponsoredCount) {
                // Mark as sponsored
                $sponsoredProduct = $sponsoredProducts[$sponsoredIndex];
                $sponsoredProduct['is_sponsored'] = true;
                $sponsoredProduct['ad_id'] = $sponsoredProduct['ad_id'] ?? null;
                
                $result[] = $sponsoredProduct;
                $sponsoredIndex++;
                $insertionOffset++;
            }
            
            // Add the regular product
            $result[] = $product;
        }

        return $result;
    }

    /**
     * Log ad view/impression and charge for it
     */
    public function logAdView($adId, $ipAddress = null)
    {
        if (!$adId) {
            return;
        }

        $ad = $this->adModel->find($adId);
        if (!$ad) {
            return;
        }

        $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        // Banner ads: just log reach, no billing or auto pause
        if ($this->isBannerAdType($ad['ads_type_id'] ?? null)) {
            $this->adModel->logReach($adId, $ipAddress);
            return;
        }

        // Product ads: proceed with billing
        $chargeResult = $this->billingService->chargeImpression($adId);
        
        if (!$chargeResult['success']) {
            // Ad cannot be shown (insufficient balance or budget exhausted)
            error_log("SponsoredAdsService: Cannot show ad #{$adId} - {$chargeResult['message']}");
            return;
        }

        $this->adModel->logReach($adId, $ipAddress);
        
        if ($chargeResult['charged'] > 0) {
            error_log("SponsoredAdsService: Charged Rs {$chargeResult['charged']} for ad #{$adId} impression");
        }
    }

    /**
     * Log ad click and charge for it
     */
    public function logAdClick($adId, $ipAddress = null)
    {
        if (!$adId) {
            return;
        }

        $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        
        // Check for fraud first (before charging)
        $fraudCheck = $this->adModel->logClick($adId, $ipAddress);
        
        // If duplicate or high fraud score, don't charge
        if ($fraudCheck['is_duplicate'] || ($fraudCheck['is_fraud'] && $fraudCheck['fraud_score'] >= 50)) {
            error_log("SponsoredAdsService: Click blocked (fraud/duplicate) for ad #{$adId} - no charge");
            return;
        }

        $ad = $this->adModel->find($adId);
        if (!$ad) {
            return;
        }

        // Banner ads: no billing / auto pause on clicks
        if ($this->isBannerAdType($ad['ads_type_id'] ?? null)) {
            return;
        }
        
        // Charge for click (if per_click billing) - only if not fraud
        // Pass IP address for fraud detection
        $chargeResult = $this->billingService->chargeClick($adId, $ipAddress);
        
        if (!$chargeResult['success']) {
            error_log("SponsoredAdsService: Cannot charge for ad #{$adId} click - {$chargeResult['message']}");
        } else if ($chargeResult['charged'] > 0) {
            error_log("SponsoredAdsService: Charged Rs {$chargeResult['charged']} for ad #{$adId} click");
        }
    }

    /**
     * Determine if ad belongs to banner_external type
     */
    private function isBannerAdType($adTypeId): bool
    {
        if (!$adTypeId) {
            return false;
        }

        $typeName = $this->getAdTypeName($adTypeId);
        return $typeName === 'banner_external';
    }

    private function getAdTypeName($adTypeId): ?string
    {
        if (isset($this->adTypeNameCache[$adTypeId])) {
            return $this->adTypeNameCache[$adTypeId];
        }

        $row = $this->db->query(
            "SELECT name FROM ads_types WHERE id = ? LIMIT 1",
            [$adTypeId]
        )->single();

        $this->adTypeNameCache[$adTypeId] = $row['name'] ?? null;
        return $this->adTypeNameCache[$adTypeId];
    }
}

