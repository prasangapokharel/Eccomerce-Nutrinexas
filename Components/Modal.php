<?php
/**
 * Premium Modal Component - Enhanced shadcn/ui Design
 * Usage: include 'Components/Modal.php';
 */

class Modal {
    public static function render($content, $title = '', $size = 'default', $attributes = []) {
        $baseClasses = "modal";
        
        // Size classes
        $sizeClasses = [
            'sm' => 'modal-sm',
            'default' => 'modal-default',
            'lg' => 'modal-lg',
            'xl' => 'modal-xl'
        ];
        
        $classes = $baseClasses . ' ' . $sizeClasses[$size];
        
        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " $key=\"$value\"";
        }
        
        $html = "<div class=\"modal-overlay\"$attrString>";
        $html .= "<div class=\"$classes\">";
        
        if ($title) {
            $html .= "<div class=\"modal-header\">";
            $html .= "<h3 class=\"modal-title\">$title</h3>";
            $html .= "<button class=\"modal-close\">×</button>";
            $html .= "</div>";
        }
        
        $html .= "<div class=\"modal-content\">$content</div>";
        $html .= "</div></div>";
        
        return $html;
    }
    
    public static function confirm($title, $message, $confirmText = 'Confirm', $cancelText = 'Cancel', $attributes = []) {
        $content = "<p class=\"modal-message\">$message</p>";
        $content .= "<div class=\"modal-actions\">";
        $content .= "<button class=\"btn btn-destructive\">$confirmText</button>";
        $content .= "<button class=\"btn btn-outline\">$cancelText</button>";
        $content .= "</div>";
        
        return self::render($content, $title, 'default', $attributes);
    }
    
    public static function form($title, $formContent, $submitText = 'Submit', $attributes = []) {
        $content = "<form class=\"modal-form\">$formContent";
        $content .= "<div class=\"modal-actions\">";
        $content .= "<button type=\"submit\" class=\"btn btn-primary\">$submitText</button>";
        $content .= "<button type=\"button\" class=\"btn btn-outline\">Cancel</button>";
        $content .= "</div></form>";
        
        return self::render($content, $title, 'default', $attributes);
    }
}
?>


