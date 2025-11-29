<?php
/**
 * Shared pricing helper closure.
 * Ensures every view formats pricing/discount data consistently.
 * Now includes site-wide sale support.
 */

if (!isset($pricingHelper) || !is_callable($pricingHelper)) {
    $pricingHelper = static function (array $product): array {
        $priceData = \App\Helpers\SaleHelper::calculateProductPrice($product);
        return [
            'original' => $priceData['original_price'],
            'current' => $priceData['final_price'],
            'discountPercent' => $priceData['discount_percent'],
            'hasSale' => $priceData['has_sale']
        ];
    };
}

