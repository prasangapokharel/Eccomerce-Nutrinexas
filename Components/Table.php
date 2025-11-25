<?php
/**
 * Premium Table Component - Enhanced shadcn/ui Design
 * Usage: include 'Components/Table.php';
 */

class Table {
    public static function render($headers, $rows, $variant = 'default', $attributes = []) {
        $baseClasses = "table";
        
        // Variant classes
        $variantClasses = [
            'default' => 'table-default',
            'striped' => 'table-striped',
            'bordered' => 'table-bordered',
            'hover' => 'table-hover'
        ];
        
        $classes = $baseClasses . ' ' . $variantClasses[$variant];
        
        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " $key=\"$value\"";
        }
        
        $html = "<div class=\"table-container\">";
        $html .= "<table class=\"$classes\"$attrString>";
        
        // Headers
        $html .= "<thead class=\"table-header\">";
        $html .= "<tr>";
        foreach ($headers as $header) {
            $html .= "<th class=\"table-head-cell\">$header</th>";
        }
        $html .= "</tr>";
        $html .= "</thead>";
        
        // Rows
        $html .= "<tbody class=\"table-body\">";
        foreach ($rows as $row) {
            $html .= "<tr class=\"table-row\">";
            foreach ($row as $cell) {
                $html .= "<td class=\"table-cell\">$cell</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</tbody>";
        
        $html .= "</table>";
        $html .= "</div>";
        
        return $html;
    }
    
    public static function simple($headers, $rows, $attributes = []) {
        return self::render($headers, $rows, 'default', $attributes);
    }
    
    public static function striped($headers, $rows, $attributes = []) {
        return self::render($headers, $rows, 'striped', $attributes);
    }
    
    public static function withActions($headers, $rows, $actions = [], $attributes = []) {
        // Add Actions column to headers
        $headers[] = 'Actions';
        
        // Add action buttons to each row
        foreach ($rows as &$row) {
            $actionButtons = '';
            foreach ($actions as $action) {
                $actionButtons .= "<button class=\"btn btn-sm btn-ghost mr-2\">{$action}</button>";
            }
            $row[] = $actionButtons;
        }
        
        return self::render($headers, $rows, 'default', $attributes);
    }
}
?>