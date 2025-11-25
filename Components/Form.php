<?php
/**
 * Premium Form Component - Enhanced shadcn/ui Design
 * Usage: include 'Components/Form.php';
 */

class Form {
    public static function render($fields, $submitText = 'Submit', $method = 'POST', $action = '', $attributes = []) {
        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= " $key=\"$value\"";
        }
        
        $html = "<form class=\"form\" method=\"$method\" action=\"$action\"$attrString>";
        
        foreach ($fields as $field) {
            $html .= "<div class=\"form-group\">";
            
            switch ($field['type']) {
                case 'input':
                    if (isset($field['label'])) {
                        $html .= "<label class=\"form-label\">{$field['label']}</label>";
                    }
                    $html .= Input::render(
                        $field['input_type'] ?? 'text',
                        $field['placeholder'] ?? '',
                        $field['variant'] ?? 'default',
                        $field['size'] ?? 'default',
                        $field['attributes'] ?? []
                    );
                    if (isset($field['error'])) {
                        $html .= "<p class=\"form-error\">{$field['error']}</p>";
                    }
                    break;
                    
                case 'textarea':
                    if (isset($field['label'])) {
                        $html .= "<label class=\"form-label\">{$field['label']}</label>";
                    }
                    $html .= "<textarea class=\"textarea\" placeholder=\"{$field['placeholder']}\" rows=\"{$field['rows']}\">{$field['value']}</textarea>";
                    break;
                    
                case 'select':
                    if (isset($field['label'])) {
                        $html .= "<label class=\"form-label\">{$field['label']}</label>";
                    }
                    $html .= "<select class=\"select\">";
                    foreach ($field['options'] as $value => $text) {
                        $selected = ($field['selected'] ?? '') === $value ? 'selected' : '';
                        $html .= "<option value=\"$value\" $selected>$text</option>";
                    }
                    $html .= "</select>";
                    break;
                    
                case 'checkbox':
                    $html .= "<div class=\"checkbox-group\">";
                    $html .= "<input type=\"checkbox\" class=\"checkbox\" id=\"{$field['id']}\" name=\"{$field['name']}\" value=\"{$field['value']}\">";
                    $html .= "<label class=\"checkbox-label\" for=\"{$field['id']}\">{$field['label']}</label>";
                    $html .= "</div>";
                    break;
            }
            
            $html .= "</div>";
        }
        
        $html .= "<div class=\"form-actions\">";
        $html .= Button::primary($submitText, 'default', ['type' => 'submit']);
        $html .= "</div>";
        
        $html .= "</form>";
        
        return $html;
    }
    
    public static function login($action = '', $attributes = []) {
        $fields = [
            [
                'type' => 'input',
                'input_type' => 'email',
                'label' => 'Email Address',
                'placeholder' => 'Enter your email',
                'attributes' => ['name' => 'email', 'required' => 'required']
            ],
            [
                'type' => 'input',
                'input_type' => 'password',
                'label' => 'Password',
                'placeholder' => 'Enter your password',
                'attributes' => ['name' => 'password', 'required' => 'required']
            ],
            [
                'type' => 'checkbox',
                'id' => 'remember',
                'name' => 'remember',
                'value' => '1',
                'label' => 'Remember me'
            ]
        ];
        
        return self::render($fields, 'Sign In', 'POST', $action, $attributes);
    }
    
    public static function contact($action = '', $attributes = []) {
        $fields = [
            [
                'type' => 'input',
                'input_type' => 'text',
                'label' => 'Name',
                'placeholder' => 'Your name',
                'attributes' => ['name' => 'name', 'required' => 'required']
            ],
            [
                'type' => 'input',
                'input_type' => 'email',
                'label' => 'Email',
                'placeholder' => 'your@email.com',
                'attributes' => ['name' => 'email', 'required' => 'required']
            ],
            [
                'type' => 'textarea',
                'label' => 'Message',
                'placeholder' => 'Your message...',
                'rows' => '4',
                'value' => ''
            ]
        ];
        
        return self::render($fields, 'Send Message', 'POST', $action, $attributes);
    }
}
?>