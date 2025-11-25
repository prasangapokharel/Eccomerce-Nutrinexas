<?php
/**
 * Premium Badge Component - Enhanced shadcn/ui Design
 * Usage: include 'Components/Badge.php';
 */
class Badge {
    public static function render($text, $variant = 'default', $size = 'default', $attributes = []) {
        $baseClasses = "badge";
        
        // Variant classes
        $variantClasses = [
            'default' => 'badge-default',
            'primary' => 'badge-primary',
            'secondary' => 'badge-secondary',
            'success' => 'badge-success',
            'warning' => 'badge-warning',
            'destructive' => 'badge-destructive',
            'outline' => 'badge-outline'
        ];
        
        // Size classes
        $sizeClasses = [
            'sm' => 'badge-sm',
            'default' => 'badge-default-size',
            'lg' => 'badge-lg'
        ];
        
        $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
        
        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " $key=\"$value\"";
        }
        
        return "<span class=\"$classes\"$attrString>$text</span>";
    }
    
    public static function primary($text, $size = 'default', $attributes = []) {
        return self::render($text, 'primary', $size, $attributes);
    }
    
    public static function success($text, $size = 'default', $attributes = []) {
        return self::render($text, 'success', $size, $attributes);
    }
    
    public static function warning($text, $size = 'default', $attributes = []) {
        return self::render($text, 'warning', $size, $attributes);
    }
    
    public static function destructive($text, $size = 'default', $attributes = []) {
        return self::render($text, 'destructive', $size, $attributes);
    }
    
    public static function outline($text, $size = 'default', $attributes = []) {
        return self::render($text, 'outline', $size, $attributes);
    }
}
?>


