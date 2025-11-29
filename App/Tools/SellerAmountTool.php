<?php

namespace App\Tools;

class SellerAmountTool
{
    /**
     * Calculate seller payout amount after all deductions
     * 
     * @param float $price Product price (seller subtotal)
     * @param float $delivery Delivery fee to deduct
     * @param float $tax Tax amount to deduct
     * @param float $coupon Coupon discount amount (seller bears this cost)
     * @param float $affPercent Affiliate percentage
     * @param bool $hasReferral Whether order has referral
     * @return float Seller amount after deductions
     */
    public function getSellerAmount($price, $delivery, $tax, $coupon, $affPercent, $hasReferral)
    {
        // Calculate affiliate deduction from product price
        $affiliate = 0;
        if ($hasReferral && $affPercent > 0) {
            $affiliate = ($price * $affPercent) / 100;
        }

        // Seller gets: product price - delivery fee - tax - affiliate - coupon
        // If seller created the coupon, they bear the discount cost
        // Formula: price - delivery - tax - affiliate - coupon
        $sellerAmount = $price - $delivery - $tax - $affiliate - $coupon;

        return max(0, round($sellerAmount, 2));
    }
}
