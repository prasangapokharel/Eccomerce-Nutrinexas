<?php

namespace App\Config;

/**
 * Central configuration for banner slots and tier pricing.
 *
 * Slots are fixed placements mapped to a tier. Tiers define pricing and duration.
 */
class BannerSlotConfig
{
    public const TIERS = [
        'tier1' => [
            'label' => 'Tier 1 · Premium Hero',
            'price' => 10000.00,
            'duration_days' => 7,
            'description' => 'Highest-visibility hero banners for home, category, and search tops.',
        ],
        'tier2' => [
            'label' => 'Tier 2 · Mid Fold Highlight',
            'price' => 5000.00,
            'duration_days' => 7,
            'description' => 'Mid-page and dashboard placements for strong-but-accessible visibility.',
        ],
        'tier3' => [
            'label' => 'Tier 3 · Offer & Support',
            'price' => 2500.00,
            'duration_days' => 7,
            'description' => 'Deals, footer, sidebar and checkout placements for budget sellers.',
        ],
    ];

    public const SLOTS = [
        'slot_home_top' => [
            'label' => 'Home · Top Hero Banner',
            'tier' => 'tier1',
            'priority' => 1,
            'description' => 'Top hero banner on the home page (Tier 1 · Premium sellers).',
        ],
        'slot_category_top' => [
            'label' => 'Category · Top Hero Banner',
            'tier' => 'tier1',
            'priority' => 2,
            'description' => 'Category page masthead for niche targeting.',
        ],
        'slot_search_top' => [
            'label' => 'Search · Sponsored Top Banner',
            'tier' => 'tier1',
            'priority' => 3,
            'description' => 'Search sponsored hero with highest CTR.',
        ],
        'slot_home_mid' => [
            'label' => 'Home · Mid Section Banner',
            'tier' => 'tier2',
            'priority' => 4,
            'description' => 'Between homepage categories for 2nd-level bidding.',
        ],
        'slot_home_slider_banner' => [
            'label' => 'Home · Below Slider Banner',
            'tier' => 'tier2',
            'priority' => 4.1,
            'description' => 'Banner placed directly under the hero slider for premium visibility.',
        ],
        'slot_home_categories_banner' => [
            'label' => 'Home · Below Categories Banner',
            'tier' => 'tier2',
            'priority' => 4.2,
            'description' => 'Banner placed under the “Shop by Category” grid.',
        ],
        'slot_category_mid' => [
            'label' => 'Category · Mid Banner',
            'tier' => 'tier2',
            'priority' => 5,
            'description' => 'Mid-category grid interstitial for mid-level sellers.',
        ],
        'slot_search_mid' => [
            'label' => 'Search · Mid Banner',
            'tier' => 'tier2',
            'priority' => 6,
            'description' => 'Search result mid placement.',
        ],
        'slot_home_offer_box' => [
            'label' => 'Home · Deals & Offers Banner',
            'tier' => 'tier3',
            'priority' => 7,
            'description' => 'Deals & offers section highlight for budget sellers.',
        ],
        'slot_product_sidebar' => [
            'label' => 'Product · Sidebar Banner',
            'tier' => 'tier3',
            'priority' => 8,
            'description' => 'Small ad inside product detail related section.',
        ],
        'slot_search_bottom' => [
            'label' => 'Search · Bottom Banner',
            'tier' => 'tier3',
            'priority' => 9,
            'description' => 'Search footer banner for awareness.',
        ],
        'slot_footer_banner' => [
            'label' => 'Global Footer Banner',
            'tier' => 'tier3',
            'priority' => 10,
            'description' => 'Site-wide footer branding banner.',
        ],
        'slot_cart_checkout' => [
            'label' => 'Cart · Checkout Offer Banner',
            'tier' => 'tier3',
            'priority' => 11,
            'description' => 'Checkout promo slot for bank/shipping offers.',
        ],
        'slot_blog_featured' => [
            'label' => 'Blog · Featured Banner',
            'tier' => 'tier2',
            'priority' => 12,
            'description' => 'High-value banner on blog detail pages.',
        ],
        'slot_seller_dashboard' => [
            'label' => 'Seller Dashboard · Internal Promo',
            'tier' => 'tier2',
            'priority' => 13,
            'description' => 'Internal ads promoting seller upgrades and packages.',
        ],
        'slot_product_grid' => [
            'label' => 'Product Grid · Between Rows',
            'tier' => 'tier3',
            'priority' => 14,
            'description' => 'Small rectangle ad between product grid rows (after every 10 products). High click performance.',
        ],
        'slot_global_footer' => [
            'label' => 'Global Footer · Site Bottom',
            'tier' => 'tier3',
            'priority' => 15,
            'description' => 'Very bottom of site footer banner. Low price, good for small companies. Always viewed by long scrolling users.',
        ],
    ];

    public static function getSlot(string $slotKey): ?array
    {
        return self::SLOTS[$slotKey] ?? null;
    }

    public static function getSlots(): array
    {
        return self::SLOTS;
    }

    public static function getSlotsGroupedByTier(): array
    {
        $grouped = [];
        foreach (self::SLOTS as $key => $slot) {
            $grouped[$slot['tier']][$key] = $slot;
        }
        return $grouped;
    }

    public static function getTierMeta(string $tier): ?array
    {
        return self::TIERS[$tier] ?? null;
    }

    public static function getTierPrice(string $tier): float
    {
        return (float) (self::TIERS[$tier]['price'] ?? 0);
    }

    public static function getTierDuration(string $tier): int
    {
        return (int) (self::TIERS[$tier]['duration_days'] ?? 0);
    }
}


