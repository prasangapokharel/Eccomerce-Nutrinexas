<?php

namespace App\Helpers;

use App\Models\SiteWideSale;

class SaleHelper
{
    private static $activeSaleCache = null;

    /**
     * Get active site-wide sale (cached)
     */
    public static function getActiveSale()
    {
        if (self::$activeSaleCache === null) {
            $saleModel = new SiteWideSale();
            self::$activeSaleCache = $saleModel->getActiveSale();
        }
        return self::$activeSaleCache;
    }

    /**
     * Calculate final price for a product considering site-wide sale
     * 
     * @param array $product Product data with price, sale_price, sale columns
     * @return array ['final_price' => float, 'original_price' => float, 'discount_percent' => float, 'has_sale' => bool]
     */
    public static function calculateProductPrice($product)
    {
        $originalPrice = max(0, (float)($product['price'] ?? 0));
        $salePrice = !empty($product['sale_price']) ? (float)$product['sale_price'] : null;
        $saleFlag = $product['sale'] ?? 'off';
        $finalPrice = $originalPrice;
        $hasSale = false;
        $discountPercent = 0;

        // Check if site-wide sale is active and product has sale='on'
        $activeSale = self::getActiveSale();
        if ($activeSale && $saleFlag === 'on') {
            $salePercent = (float)($activeSale['sale_percent'] ?? $activeSale['discount_percent'] ?? 0);
            if ($salePercent > 0 && $salePercent < 100) {
                // Use sale_price if exists, else calculate from sale_percent
                if ($salePrice !== null && $salePrice > 0 && $salePrice < $originalPrice) {
                    $finalPrice = $salePrice;
                    $discountPercent = round((($originalPrice - $finalPrice) / $originalPrice) * 100);
                } else {
                    $finalPrice = $originalPrice - (($originalPrice * $salePercent) / 100);
                    $discountPercent = $salePercent;
                }
                $hasSale = true;
            }
        } elseif ($salePrice !== null && $salePrice > 0 && $salePrice < $originalPrice) {
            // Regular sale_price (not site-wide)
            $finalPrice = $salePrice;
            $hasSale = true;
            $discountPercent = round((($originalPrice - $finalPrice) / $originalPrice) * 100);
        }

        return [
            'final_price' => max(0, $finalPrice),
            'original_price' => $originalPrice,
            'discount_percent' => $discountPercent,
            'has_sale' => $hasSale,
            'current' => max(0, $finalPrice),
            'original' => $originalPrice
        ];
    }
}

