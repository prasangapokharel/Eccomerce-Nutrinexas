<?php
/**
 * Premium Input Component - Enhanced shadcn/ui Design
 * Usage: include 'Components/Input.php';
 */

class Input {
    public static function render($type = 'text', $placeholder = '', $variant = 'default', $size = 'default', $attributes = []) {
        $baseClasses = "input";
        
        // Variant classes
        $variantClasses = [
            'default' => 'input-default',
            'success' => 'input-success',
            'error' => 'input-error',
            'ghost' => 'input-ghost'
        ];
        
        // Size classes
        $sizeClasses = [
            'sm' => 'input-sm',
            'default' => 'input-default-size',
            'lg' => 'input-lg'
        ];
        
        $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
        
        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " $key=\"$value\"";
        }
        
        return "<input type=\"$type\" placeholder=\"$placeholder\" class=\"$classes\"$attrString>";
    }
    
    public static function withLabel($label, $type = 'text', $placeholder = '', $variant = 'default', $size = 'default', $attributes = []) {
        $input = self::render($type, $placeholder, $variant, $size, $attributes);
        return "<div class=\"input-group\"><label class=\"input-label\">$label</label>$input</div>";
    }
    
    public static function withError($label, $error, $type = 'text', $placeholder = '', $attributes = []) {
        $input = self::render($type, $placeholder, 'error', 'default', $attributes);
        return "<div class=\"input-group\"><label class=\"input-label\">$label</label>$input<p class=\"input-error\">$error</p></div>";
    }
    
    public static function search($placeholder = 'Search...', $size = 'default', $attributes = []) {
        return self::render('search', $placeholder, 'default', $size, $attributes);
    }
}
?>