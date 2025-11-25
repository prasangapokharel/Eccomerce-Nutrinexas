<?php
namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Get the Nepali Rupee symbol
     */
    public static function getSymbol()
    {
        return 'रु';
    }
    
    /**
     * Get the old Indian Rupee symbol (for backward compatibility)
     */
    public static function getOldSymbol()
    {
        return '₹';
    }
    
    /**
     * Format price with Nepali Rupee symbol
     */
    public static function format($amount, $decimals = 2)
    {
        return self::getSymbol() . number_format($amount, $decimals);
    }
    
    /**
     * Format price range
     */
    public static function formatRange($min, $max, $decimals = 2)
    {
        if ($min == $max) {
            return self::format($min, $decimals);
        }
        return self::format($min, $decimals) . ' - ' . self::format($max, $decimals);
    }
    
    /**
     * Format discount amount
     */
    public static function formatDiscount($originalPrice, $salePrice)
    {
        $discount = $originalPrice - $salePrice;
        return self::format($discount, 2);
    }
    
    /**
     * Replace old currency symbols with new ones in text
     */
    public static function replaceSymbols($text)
    {
        return str_replace(self::getOldSymbol(), self::getSymbol(), $text);
    }
}
