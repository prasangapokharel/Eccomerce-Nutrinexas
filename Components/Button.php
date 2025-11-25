<?php
/**
 * Premium Button Component - Enhanced shadcn/ui Design
 * Usage: include 'Components/Button.php';
 */

class Button {
    public static function render($text, $variant = 'default', $size = 'default', $attributes = []) {
        $baseClasses = "btn";
        
        // Variant classes
        $variantClasses = [
            'default' => 'btn-default',
            'primary' => 'btn-primary',
            'secondary' => 'btn-secondary',
            'destructive' => 'btn-destructive',
            'outline' => 'btn-outline',
            'ghost' => 'btn-ghost',
            'link' => 'btn-link',
            'accent' => 'btn-accent'
        ];
        
        // Size classes
        $sizeClasses = [
            'sm' => 'btn-sm',
            'default' => 'btn-default-size',
            'lg' => 'btn-lg',
            'icon' => 'btn-icon'
        ];
        
        $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
        
        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " $key=\"$value\"";
        }
        
        return "<button class=\"$classes\"$attrString>$text</button>";
    }
    
    public static function primary($text, $size = 'default', $attributes = []) {
        return self::render($text, 'primary', $size, $attributes);
    }
    
    public static function secondary($text, $size = 'default', $attributes = []) {
        return self::render($text, 'secondary', $size, $attributes);
    }
    
    public static function destructive($text, $size = 'default', $attributes = []) {
        return self::render($text, 'destructive', $size, $attributes);
    }
    
    public static function outline($text, $size = 'default', $attributes = []) {
        return self::render($text, 'outline', $size, $attributes);
    }
    
    public static function ghost($text, $size = 'default', $attributes = []) {
        return self::render($text, 'ghost', $size, $attributes);
    }
    
    public static function link($text, $size = 'default', $attributes = []) {
        return self::render($text, 'link', $size, $attributes);
    }
}
?>
