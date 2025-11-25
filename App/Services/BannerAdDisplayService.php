<?php

namespace App\Services;

use App\Config\BannerSlotConfig;
use App\Core\Database;

class BannerAdDisplayService
{
    private const PLACEMENT_TO_SLOTS = [
        'home_top' => ['slot_home_top'],
        'home_mid' => ['slot_home_mid'],
        'home_deals' => ['slot_home_offer_box'],
        'category_top' => ['slot_category_top'],
        'category_mid' => ['slot_category_mid'],
        'search_top' => ['slot_search_top'],
        'search_mid' => ['slot_search_mid'],
        'search_bottom' => ['slot_search_bottom'],
        'product_sidebar' => ['slot_product_sidebar'],
        'footer' => ['slot_footer_banner'],
        'cart_checkout' => ['slot_cart_checkout'],
        'blog_featured' => ['slot_blog_featured'],
        'seller_dashboard' => ['slot_seller_dashboard'],
    ];

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getBannerBySlot(string $slotKey): ?array
    {
        $slot = BannerSlotConfig::getSlot($slotKey);
        if (!$slot) {
            return null;
        }

        $bannerType = $this->db->query(
            "SELECT id FROM ads_types WHERE name = 'banner_external' LIMIT 1"
        )->single();

        if (!$bannerType) {
            return null;
        }

        $this->syncBannerStatuses($bannerType['id']);

        // Get all eligible banners for this slot (tier-based, no bid ordering)
        $ads = $this->db->query(
            "SELECT a.*, s.company_name
             FROM ads a
             LEFT JOIN ads_payments ap ON a.id = ap.ads_id
             LEFT JOIN sellers s ON a.seller_id = s.id
             WHERE a.ads_type_id = ?
               AND a.slot_key = ?
               AND a.tier = ?
               AND a.status = 'active'
               AND (a.auto_paused = 0 OR a.auto_paused IS NULL)
               AND DATE(CURDATE()) BETWEEN DATE(a.start_date) AND DATE(a.end_date)
               AND (ap.payment_status = 'paid' OR ap.id IS NULL)
               AND a.banner_image IS NOT NULL
               AND a.banner_image <> ''
               AND a.banner_link IS NOT NULL
               AND a.banner_link <> ''
             ORDER BY RAND()
             LIMIT 10",
            [$bannerType['id'], $slotKey, $slot['tier']]
        )->all();

        if (empty($ads)) {
            return null;
        }

        // Pure rotation: randomly select from all eligible banners in the slot
        return $ads[array_rand($ads)];
    }

    /**
     * Ensure banner ads respect start/end dates by updating statuses
     */
    private function syncBannerStatuses($bannerTypeId): void
    {
        $today = date('Y-m-d');

        // Expire banners whose end_date has passed
        $this->db->query(
            "UPDATE ads 
             SET status = 'expired', updated_at = NOW()
             WHERE ads_type_id = ?
             AND status IN ('active', 'inactive')
             AND end_date < ?",
            [$bannerTypeId, $today]
        )->execute();

        // Do not auto-reactivate manually paused banners.
    }

    /**
     * Resolve a banner for requested tier + placement. Falls back to any slot
     * in the same tier to avoid blank inventory during tests.
     */
    public function getBannerForPlacement(string $tier, string $placement): ?array
    {
        $tier = strtolower($tier);
        $placementKey = strtolower(trim($placement));
        $candidates = self::PLACEMENT_TO_SLOTS[$placementKey] ?? [];

        // Allow direct slot keys (slot_home_top etc.)
        if (empty($candidates) && BannerSlotConfig::getSlot($placementKey)) {
            $candidates = [$placementKey];
        }

        foreach ($candidates as $slotKey) {
            $slotMeta = BannerSlotConfig::getSlot($slotKey);
            if (!$slotMeta || $slotMeta['tier'] !== $tier) {
                continue;
            }

            $banner = $this->getBannerBySlot($slotKey);
            if ($banner) {
                return $banner;
            }
        }

        // Fallback: try any slot that belongs to the tier
        foreach (BannerSlotConfig::getSlots() as $slotKey => $slotMeta) {
            if ($slotMeta['tier'] !== $tier) {
                continue;
            }

            $banner = $this->getBannerBySlot($slotKey);
            if ($banner) {
                return $banner;
            }
        }

        return null;
    }

    /**
     * Get banner for homepage (Tier 1 - Premium)
     */
    public function getHomepageBanner()
    {
        return $this->getBannerBySlot('slot_home_top');
    }

    /**
     * Get banner for category page (Tier 1 - Above Product Grid)
     */
    public function getCategoryBanner()
    {
        return $this->getBannerBySlot('slot_category_top');
    }

    /**
     * Get banner for search results (Tier 2 - Search Results Top)
     */
    public function getSearchBanner()
    {
        return $this->getBannerBySlot('slot_search_top');
    }

    /**
     * Get banner for between product rows (Tier 2 - Between Product Rows)
     */
    public function getBetweenProductsBanner()
    {
        return $this->getBannerBySlot('slot_home_mid');
    }

    /**
     * Get banner for home page deals/offers (Tier 3)
     */
    public function getHomeDealsBanner()
    {
        return $this->getBannerBySlot('slot_home_offer_box');
    }

    /**
     * Get banner below homepage slider (Tier 2)
     */
    public function getHomeSliderBanner()
    {
        return $this->getBannerBySlot('slot_home_slider_banner');
    }

    /**
     * Get banner below home categories section (Tier 2)
     */
    public function getHomeCategoriesBanner()
    {
        return $this->getBannerBySlot('slot_home_categories_banner');
    }

    /**
     * Get banner for product detail page (Tier 3 - Related Items)
     */
    public function getProductDetailBanner()
    {
        return $this->getBannerBySlot('slot_home_offer_box');
    }

    /**
     * Get banner for product sidebar (Tier 3)
     */
    public function getProductSidebarBanner()
    {
        return $this->getBannerBySlot('slot_product_sidebar');
    }

    /**
     * Get banner for footer (Tier 4 - Footer)
     */
    public function getFooterBanner()
    {
        return $this->getBannerBySlot('slot_footer_banner');
    }

    /**
     * Get banner for search mid placement (Tier 2)
     */
    public function getSearchMidBanner()
    {
        return $this->getBannerBySlot('slot_search_mid');
    }

    /**
     * Get banner for cart page (Tier 4 - Cart Side)
     */
    public function getCartBanner()
    {
        return $this->getBannerBySlot('slot_search_bottom');
    }

    /**
     * Get banner for checkout offer (Tier 3)
     */
    public function getCartCheckoutBanner()
    {
        return $this->getBannerBySlot('slot_cart_checkout');
    }

    /**
     * Get banner for seller dashboard promotions (Tier 2)
     */
    public function getSellerDashboardBanner()
    {
        return $this->getBannerBySlot('slot_seller_dashboard');
    }

    /**
     * Get featured blog banner (Tier 2)
     */
    public function getBlogBanner()
    {
        return $this->getBannerBySlot('slot_blog_featured');
    }

    /**
     * Get product grid banner (Tier 3 - Between product rows)
     */
    public function getProductGridBanner()
    {
        return $this->getBannerBySlot('slot_product_grid');
    }

    /**
     * Get global footer banner (Tier 3 - Site bottom)
     */
    public function getGlobalFooterBanner()
    {
        return $this->getBannerBySlot('slot_global_footer');
    }
}

