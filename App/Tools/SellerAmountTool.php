<?php

namespace App\Tools;

class SellerAmountTool
{
    /**
     * Calculate seller payout amount after all deductions
     * 
     * Industry Standard Formula (Daraz, Flipkart, Amazon, Shopify):
     * Seller Payout = Subtotal - Delivery Fee - Coupon - Affiliate
     * 
     * IMPORTANT: Tax is NEVER deducted from seller payout.
     * Tax belongs to government and is added on top of product price.
     * 
     * @param float $price Product price (seller subtotal)
     * @param float $delivery Delivery fee to deduct
     * @param float $tax Tax amount (NOT deducted, kept for reference only)
     * @param float $coupon Coupon discount amount (seller bears this cost if seller's coupon)
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

        // Correct Formula: Subtotal - Delivery Fee - Coupon - Affiliate
        // Tax is NOT deducted (it's government money, not seller money)
        $sellerAmount = $price - $delivery - $coupon - $affiliate;

        return max(0, round($sellerAmount, 2));
    }
}
