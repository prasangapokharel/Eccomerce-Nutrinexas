<?php
/**
 * Premium Card Component - Enhanced shadcn/ui Design
 * Usage: include 'Components/Card.php';
 */

class Card {
    public static function render($content, $header = null, $footer = null, $variant = 'default', $size = 'default', $attributes = []) {
        $baseClasses = "card";
        
        // Variant classes
        $variantClasses = [
            'default' => 'card-default',
            'primary' => 'card-primary',
            'secondary' => 'card-secondary', 
            'success' => 'card-success',
            'warning' => 'card-warning',
            'destructive' => 'card-destructive',
            'outline' => 'card-outline',
            'ghost' => 'card-ghost'
        ];
        
        // Size classes
        $sizeClasses = [
            'sm' => 'card-sm',
            'default' => 'card-default-size',
            'lg' => 'card-lg'
        ];
        
        $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
        
        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " $key=\"$value\"";
        }
        
        $html = "<div class=\"$classes\"$attrString>";
        
        // Header
        if ($header) {
            $html .= '<div class="card-header">';
            $html .= '<h3 class="card-title">' . $header . '</h3>';
            $html .= '</div>';
        }
        
        // Content
        $html .= '<div class="card-content">';
        $html .= $content;
        $html .= '</div>';
        
        // Footer
        if ($footer) {
            $html .= '<div class="card-footer">';
            $html .= $footer;
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public static function simple($content, $variant = 'default', $size = 'default', $attributes = []) {
        return self::render($content, null, null, $variant, $size, $attributes);
    }
    
    public static function withHeader($content, $header, $variant = 'default', $size = 'default', $attributes = []) {
        return self::render($content, $header, null, $variant, $size, $attributes);
    }
    
    public static function withFooter($content, $footer, $variant = 'default', $size = 'default', $attributes = []) {
        return self::render($content, null, $footer, $variant, $size, $attributes);
    }
    
    public static function premium($content, $header = null, $footer = null, $attributes = []) {
        return self::render($content, $header, $footer, 'primary', 'lg', $attributes);
    }
}
?>


