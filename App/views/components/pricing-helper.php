<?php
/**
 * Shared pricing helper closure.
 * Ensures every view formats pricing/discount data consistently.
 */

if (!isset($pricingHelper) || !is_callable($pricingHelper)) {
    $pricingHelper = static function (array $product): array {
        $originalPrice = max(0, (float)($product['price'] ?? 0));
        $salePrice = null;
        $hasSale = false;
        $now = date('Y-m-d H:i:s');

        if (
            !empty($product['is_on_sale']) &&
            !empty($product['sale_discount_percent']) &&
            !empty($product['sale_start_date']) &&
            !empty($product['sale_end_date']) &&
            $product['sale_start_date'] <= $now &&
            $product['sale_end_date'] >= $now
        ) {
            $discountPercent = (float)$product['sale_discount_percent'];
            if ($discountPercent > 0) {
                $salePrice = $originalPrice - (($originalPrice * $discountPercent) / 100);
                $hasSale = true;
            }
        }

        if (!empty($product['sale_price'])) {
            $candidate = (float)$product['sale_price'];
            if ($candidate > 0 && $candidate < $originalPrice) {
                if (!$hasSale || $candidate < (float)$salePrice) {
                    $salePrice = $candidate;
                    $hasSale = true;
                }
            }
        }

        $currentPrice = $hasSale && $salePrice !== null ? $salePrice : $originalPrice;
        $divider = $originalPrice > 0 ? $originalPrice : 1;
        $discountPercent = $hasSale ? round((($originalPrice - $currentPrice) / $divider) * 100) : 0;

        return [
            'original' => $originalPrice,
            'current' => $currentPrice,
            'discountPercent' => max(0, $discountPercent),
            'hasSale' => $hasSale
        ];
    };
}

