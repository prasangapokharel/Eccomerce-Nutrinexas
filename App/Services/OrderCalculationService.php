<?php

namespace App\Services;

/**
 * Order Calculation Service
 * Centralized order calculation logic to prevent duplication
 */
class OrderCalculationService
{
    /**
     * Calculate order totals
     * 
     * @param float $subtotal
     * @param float $discountAmount
     * @param float $deliveryFee
     * @param float $taxRate Tax rate as percentage (e.g., 13 for 13%)
     * @return array ['subtotal', 'discount', 'tax', 'delivery', 'total']
     */
    public static function calculateTotals(
        float $subtotal,
        float $discountAmount = 0,
        float $deliveryFee = 0,
        float $taxRate = 13
    ): array {
        // Calculate subtotal after discount
        $subtotalAfterDiscount = max(0, $subtotal - $discountAmount);
        
        // Calculate tax on subtotal after discount
        $taxAmount = ($subtotalAfterDiscount * $taxRate) / 100;
        
        // Calculate final total
        $total = $subtotalAfterDiscount + $taxAmount + $deliveryFee;
        
        return [
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'tax' => $taxAmount,
            'tax_rate' => $taxRate,
            'delivery' => $deliveryFee,
            'total' => $total
        ];
    }

    /**
     * Calculate cart total from items
     * Handles both direct price/quantity and product lookup
     * 
     * @param array $cartItems Array of items with 'price' and 'quantity' or 'product_id'
     * @param \App\Models\Product|null $productModel Optional product model for lookup
     * @return float
     */
    public static function calculateCartTotal(array $cartItems, $productModel = null): float
    {
        $total = 0;
        foreach ($cartItems as $item) {
            // If price is directly available
            if (isset($item['price'])) {
                $price = (float)$item['price'];
                $quantity = (int)($item['quantity'] ?? 0);
                $total += $price * $quantity;
            } 
            // If product_id is available and product model provided
            elseif (isset($item['product_id']) && $productModel) {
                $product = $productModel->find($item['product_id']);
                if ($product) {
                    $priceData = \App\Helpers\SaleHelper::calculateProductPrice($product);
                    $currentPrice = $priceData['final_price'];
                    $quantity = (int)($item['quantity'] ?? 0);
                    $total += $currentPrice * $quantity;
                }
            }
        }
        return $total;
    }

    /**
     * Apply coupon discount
     * 
     * @param float $subtotal
     * @param array $coupon Coupon data with 'discount_type' and 'discount_value'
     * @return float Discount amount
     */
    public static function applyCouponDiscount(float $subtotal, array $coupon): float
    {
        $discountType = $coupon['discount_type'] ?? 'percentage';
        $discountValue = (float)($coupon['discount_value'] ?? 0);
        
        if ($discountType === 'percentage') {
            $discount = ($subtotal * $discountValue) / 100;
        } else {
            $discount = min($discountValue, $subtotal); // Fixed amount, can't exceed subtotal
        }
        
        return max(0, $discount);
    }

    /**
     * Calculate delivery fee
     * 
     * @param string $city
     * @param float $subtotal
     * @param float $freeDeliveryThreshold
     * @return float
     */
    public static function calculateDeliveryFee(
        string $city,
        float $subtotal = 0,
        float $freeDeliveryThreshold = 0
    ): float {
        // If subtotal exceeds threshold, delivery is free
        if ($freeDeliveryThreshold > 0 && $subtotal >= $freeDeliveryThreshold) {
            return 0;
        }
        
        // Get delivery charge for city
        $deliveryModel = new \App\Models\DeliveryCharge();
        $charge = $deliveryModel->getChargeByLocation($city);
        
        return (float)($charge['fee'] ?? 150); // Default 150
    }
}

