<?php

namespace App\Helpers;

class CouponCheck
{
    /**
     * Check if coupon is valid for product
     * Only seller coupons can be applied to seller products
     * 
     * @param array $coupon Coupon data
     * @param array $product Product data
     * @return bool
     */
    public function isValid($coupon, $product)
    {
        if (!$coupon || !$product) {
            return false;
        }

        // If coupon has seller_id, it must match product seller_id
        if (!empty($coupon['seller_id'])) {
            $couponSellerId = (int)$coupon['seller_id'];
            $productSellerId = (int)($product['seller_id'] ?? 0);
            
            // Only seller coupons can be applied to seller products
            if ($couponSellerId > 0 && $productSellerId !== $couponSellerId) {
                return false;
            }
        }

        // Check coupon status (1 = active)
        if (isset($coupon['status']) && $coupon['status'] != 1 && $coupon['is_active'] != 1) {
            return false;
        }

        // Check date validity if dates are provided
        if (!empty($coupon['expires_at'])) {
            $today = date('Y-m-d H:i:s');
            if ($today > $coupon['expires_at']) {
                return false;
            }
        } elseif (!empty($coupon['start_date']) && !empty($coupon['end_date'])) {
            $today = date('Y-m-d');
            if ($today < $coupon['start_date'] || $today > $coupon['end_date']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply coupon to product and calculate discount
     * 
     * @param array $coupon Coupon data
     * @param array $product Product data
     * @return array
     */
    public function apply($coupon, $product)
    {
        if (!$this->isValid($coupon, $product)) {
            return [
                'discount' => 0,
                'message' => 'Coupon not allowed'
            ];
        }

        $price = $product['price'] ?? 0;
        $discount = 0;

        if ($coupon['type'] == 'percent') {
            $discount = ($price * $coupon['value']) / 100;
        }

        if ($coupon['type'] == 'flat') {
            $discount = $coupon['value'];
        }

        return [
            'discount' => $discount,
            'message' => 'Coupon applied'
        ];
    }

    /**
     * Alias for backward compatibility
     * 
     * @param array $coupon Coupon data
     * @param array $product Product data
     * @return bool
     */
    public function validateCoupon($coupon, $product)
    {
        return $this->isValid($coupon, $product);
    }

    /**
     * Alias for backward compatibility
     * 
     * @param array $coupon Coupon data
     * @param array $product Product data
     * @return array
     */
    public function applyCoupon($coupon, $product)
    {
        return $this->apply($coupon, $product);
    }
}

