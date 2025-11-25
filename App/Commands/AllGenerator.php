<?php
namespace App\Commands;

/**
 * All Generator Command - Generate complete MVC set
 * 
 * Usage: php artisan make:all ModelName [--controller-path=app/Controllers] [--view-path=resources/views] [--model-path=app/Models]
 */
class AllGenerator extends GeneratorCommand
{
    protected $signature = 'make:all {name} {--controller-path=app/Controllers} {--view-path=resources/views} {--model-path=app/Models} {--table=} {--fillable=} {--fields=}';
    protected $description = 'Create a complete MVC set (Model, Controller, and Views)';

    /**
     * Handle the command execution
     */
    protected function handle(array $args): void
    {
        $parsed = $this->parseArgs($args);
        
        if (empty($parsed['arguments'])) {
            $this->output('Error: Model name is required', 'error');
            $this->help();
            return;
        }

        $modelName = $parsed['arguments'][0];
        $controllerPath = $parsed['options']['controller-path'] ?? 'app/Controllers';
        $viewPath = $parsed['options']['view-path'] ?? 'resources/views';
        $modelPath = $parsed['options']['model-path'] ?? 'app/Models';
        $tableName = $parsed['options']['table'] ?? $this->toSnakeCase($this->pluralize($modelName));
        $fillableFields = $parsed['options']['fillable'] ?? '';
        $formFields = $parsed['options']['fields'] ?? '';

        $this->output("Generating complete MVC set for: $modelName", 'info');
        $this->output("==========================================", 'info');

        // Generate Model
        $this->generateModel($modelName, $modelPath, $tableName, $fillableFields);
        
        // Generate Controller
        $controllerName = $modelName . 'Controller';
        $this->generateController($controllerName, $controllerPath, $modelName, $viewPath);
        
        // Generate Views
        $this->generateViews($modelName, $viewPath, $controllerName, $formFields);

        $this->output("==========================================", 'info');
        $this->output("MVC set generated successfully!", 'success');
        $this->output("Model: $modelName", 'info');
        $this->output("Controller: $controllerName", 'info');
        $this->output("Views: index, create, edit, show", 'info');
    }

    /**
     * Generate Model
     */
    protected function generateModel(string $modelName, string $path, string $tableName, string $fillableFields): void
    {
        $this->output("Generating Model: $modelName", 'info');
        
        $fullPath = __DIR__ . '/../../' . $path;
        $fileName = $modelName . '.php';
        $filePath = $fullPath . '/' . $fileName;

        // Check if file already exists
        if (file_exists($filePath)) {
            if (!$this->confirm("Model $modelName already exists. Overwrite?")) {
                $this->output('Skipping model generation', 'warning');
                return;
            }
        }

        // Prepare fillable fields
        $fillableArray = [];
        if ($fillableFields) {
            $fields = explode(',', $fillableFields);
            foreach ($fields as $field) {
                $fillableArray[] = "'" . trim($field) . "'";
            }
        } else {
            // Default fields
            $fillableArray = ["'name'", "'description'", "'status'"];
        }

        $fillableString = implode(",\n        ", $fillableArray);

        // Generate model content
        $content = $this->getTemplate('model.php', [
            'className' => $modelName,
            'tableName' => $tableName,
            'fillableFields' => $fillableString
        ]);

        // Write file
        $this->writeFile($filePath, $content);
        $this->output("✓ Model created: $filePath", 'success');
    }

    /**
     * Generate Controller
     */
    protected function generateController(string $controllerName, string $path, string $modelName, string $viewPath): void
    {
        $this->output("Generating Controller: $controllerName", 'info');
        
        $fullPath = __DIR__ . '/../../' . $path;
        $fileName = $controllerName . '.php';
        $filePath = $fullPath . '/' . $fileName;

        // Check if file already exists
        if (file_exists($filePath)) {
            if (!$this->confirm("Controller $controllerName already exists. Overwrite?")) {
                $this->output('Skipping controller generation', 'warning');
                return;
            }
        }

        // Generate route prefix
        $routePrefix = $this->toKebabCase($modelName);

        // Generate validation rules
        $validationRules = $this->generateValidationRules($modelName);

        // Generate controller content
        $content = $this->getTemplate('controller.php', [
            'className' => $controllerName,
            'modelName' => $modelName,
            'viewPath' => $this->toKebabCase($modelName),
            'routePrefix' => $routePrefix,
            'validationRules' => $validationRules
        ]);

        // Write file
        $this->writeFile($filePath, $content);
        $this->output("✓ Controller created: $filePath", 'success');
    }

    /**
     * Generate Views
     */
    protected function generateViews(string $modelName, string $path, string $controllerName, string $formFields): void
    {
        $this->output("Generating Views for: $modelName", 'info');
        
        $viewDir = $this->toKebabCase($modelName);
        $fullPath = __DIR__ . '/../../' . $path . '/' . $viewDir;
        
        $views = ['index', 'create', 'edit', 'show'];
        
        foreach ($views as $view) {
            $fileName = $view . '.php';
            $filePath = $fullPath . '/' . $fileName;

            // Check if file already exists
            if (file_exists($filePath)) {
                if (!$this->confirm("View $viewName/$view already exists. Overwrite?")) {
                    $this->output("Skipping $view view generation", 'warning');
                    continue;
                }
            }

            $content = $this->generateViewContent($view, $modelName, $controllerName, $formFields);
            $this->writeFile($filePath, $content);
            $this->output("✓ View created: $filePath", 'success');
        }
    }

    /**
     * Generate view content
     */
    protected function generateViewContent(string $viewType, string $modelName, string $controllerName, string $formFields): string
    {
        $routePrefix = $this->toKebabCase($modelName);
        $viewPath = $this->toKebabCase($modelName);

        $replacements = [
            'className' => $controllerName,
            'modelName' => $modelName,
            'routePrefix' => $routePrefix,
            'viewPath' => $viewPath
        ];

        switch ($viewType) {
            case 'index':
                $replacements['tableHeaders'] = $this->generateTableHeaders($formFields);
                $replacements['tableRows'] = $this->generateTableRows($formFields);
                return $this->getTemplate('view_index.php', $replacements);
                
            case 'create':
                $replacements['formFields'] = $this->generateFormFields($formFields, 'create');
                return $this->getTemplate('view_create.php', $replacements);
                
            case 'edit':
                $replacements['formFields'] = $this->generateFormFields($formFields, 'edit');
                return $this->getTemplate('view_edit.php', $replacements);
                
            case 'show':
                $replacements['showFields'] = $this->generateShowFields($formFields);
                return $this->getTemplate('view_show.php', $replacements);
                
            default:
                return '';
        }
    }

    /**
     * Generate validation rules
     */
    protected function generateValidationRules(string $modelName): string
    {
        $rules = [
            "if (isset(\$data['name'])) \$validated['name'] = trim(\$data['name']);",
            "if (isset(\$data['email'])) \$validated['email'] = filter_var(\$data['email'], FILTER_SANITIZE_EMAIL);",
            "if (isset(\$data['description'])) \$validated['description'] = trim(\$data['description']);",
            "if (isset(\$data['status'])) \$validated['status'] = (int) \$data['status'];",
            "if (isset(\$data['created_at'])) \$validated['created_at'] = date('Y-m-d H:i:s');",
            "if (isset(\$data['updated_at'])) \$validated['updated_at'] = date('Y-m-d H:i:s');"
        ];
        
        return implode("\n        ", $rules);
    }

    /**
     * Generate table headers
     */
    protected function generateTableHeaders(string $fields): string
    {
        if (!$fields) {
            return '<th>ID</th><th>Name</th><th>Created</th><th>Actions</th>';
        }
        
        $fieldArray = explode(',', $fields);
        $headers = ['<th>ID</th>'];
        
        foreach ($fieldArray as $field) {
            $field = trim($field);
            $header = ucwords(str_replace(['_', '-'], ' ', $field));
            $headers[] = "<th>$header</th>";
        }
        
        $headers[] = '<th>Created</th>';
        $headers[] = '<th>Actions</th>';
        
        return implode("\n                                        ", $headers);
    }

    /**
     * Generate table rows
     */
    protected function generateTableRows(string $fields): string
    {
        if (!$fields) {
            return '<td><?= $item[\'id\'] ?></td>
                                            <td><?= htmlspecialchars($item[\'name\'] ?? \'\') ?></td>
                                            <td><?= date(\'M j, Y\', strtotime($item[\'created_at\'] ?? \'\')) ?></td>
                                            <td>
                                                <a href="/{{routePrefix}}/<?= $item[\'id\'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/{{routePrefix}}/<?= $item[\'id\'] ?>/edit" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>';
        }
        
        $fieldArray = explode(',', $fields);
        $rows = ['<td><?= $item[\'id\'] ?></td>'];
        
        foreach ($fieldArray as $field) {
            $field = trim($field);
            $rows[] = "<td><?= htmlspecialchars(\$item['$field'] ?? '') ?></td>";
        }
        
        $rows[] = '<td><?= date(\'M j, Y\', strtotime($item[\'created_at\'] ?? \'\')) ?></td>';
        $rows[] = '<td>
                                                <a href="/{{routePrefix}}/<?= $item[\'id\'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/{{routePrefix}}/<?= $item[\'id\'] ?>/edit" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>';
        
        return implode("\n                                            ", $rows);
    }

    /**
     * Generate form fields
     */
    protected function generateFormFields(string $fields, string $type): string
    {
        if (!$fields) {
            return $this->generateDefaultFormFields($type);
        }
        
        $fieldArray = explode(',', $fields);
        $formFields = [];
        
        foreach ($fieldArray as $field) {
            $field = trim($field);
            $label = ucwords(str_replace(['_', '-'], ' ', $field));
            $formFields[] = $this->generateFormField($field, $label, $type);
        }
        
        return implode("\n\n                        ", $formFields);
    }

    /**
     * Generate default form fields
     */
    protected function generateDefaultFormFields(string $type): string
    {
        $fields = [
            'name' => 'Name',
            'description' => 'Description',
            'status' => 'Status'
        ];
        
        $formFields = [];
        
        foreach ($fields as $field => $label) {
            $formFields[] = $this->generateFormField($field, $label, $type);
        }
        
        return implode("\n\n                        ", $formFields);
    }

    /**
     * Generate individual form field
     */
    protected function generateFormField(string $field, string $label, string $type): string
    {
        $value = $type === 'edit' ? "<?= htmlspecialchars(\$item['$field'] ?? '') ?>" : '';
        
        if ($field === 'description') {
            return "<div class=\"form-group\">
                            <label for=\"$field\">$label</label>
                            <textarea name=\"$field\" id=\"$field\" class=\"form-control\" rows=\"4\" required>$value</textarea>
                        </div>";
        } elseif ($field === 'status') {
            $selectedActive = $type === 'edit' ? "<?= (\$item['$field'] ?? 1) == 1 ? 'selected' : '' ?>" : 'selected';
            $selectedInactive = $type === 'edit' ? "<?= (\$item['$field'] ?? 1) == 0 ? 'selected' : '' ?>" : '';
            
            return "<div class=\"form-group\">
                            <label for=\"$field\">$label</label>
                            <select name=\"$field\" id=\"$field\" class=\"form-control\" required>
                                <option value=\"1\" $selectedActive>Active</option>
                                <option value=\"0\" $selectedInactive>Inactive</option>
                            </select>
                        </div>";
        } else {
            return "<div class=\"form-group\">
                            <label for=\"$field\">$label</label>
                            <input type=\"text\" name=\"$field\" id=\"$field\" class=\"form-control\" value=\"$value\" required>
                        </div>";
        }
    }

    /**
     * Generate show fields
     */
    protected function generateShowFields(string $fields): string
    {
        if (!$fields) {
            return $this->generateDefaultShowFields();
        }
        
        $fieldArray = explode(',', $fields);
        $showFields = [];
        
        foreach ($fieldArray as $field) {
            $field = trim($field);
            $label = ucwords(str_replace(['_', '-'], ' ', $field));
            $showFields[] = "<div class=\"col-md-6 mb-3\">
                                <strong>$label:</strong><br>
                                <span><?= htmlspecialchars(\$item['$field'] ?? 'N/A') ?></span>
                            </div>";
        }
        
        return implode("\n                        ", $showFields);
    }

    /**
     * Generate default show fields
     */
    protected function generateDefaultShowFields(): string
    {
        $fields = [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At'
        ];
        
        $showFields = [];
        
        foreach ($fields as $field => $label) {
            if ($field === 'status') {
                $showFields[] = "<div class=\"col-md-6 mb-3\">
                                    <strong>$label:</strong><br>
                                    <span class=\"badge badge-<?= (\$item['$field'] ?? 1) == 1 ? 'success' : 'danger' ?>\">
                                        <?= (\$item['$field'] ?? 1) == 1 ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>";
            } elseif (in_array($field, ['created_at', 'updated_at'])) {
                $showFields[] = "<div class=\"col-md-6 mb-3\">
                                    <strong>$label:</strong><br>
                                    <span><?= date('M j, Y H:i:s', strtotime(\$item['$field'] ?? '')) ?></span>
                                </div>";
            } else {
                $showFields[] = "<div class=\"col-md-6 mb-3\">
                                    <strong>$label:</strong><br>
                                    <span><?= htmlspecialchars(\$item['$field'] ?? 'N/A') ?></span>
                                </div>";
            }
        }
        
        return implode("\n                        ", $showFields);
    }

    /**
     * Display help information
     */
    public function help(): void
    {
        echo "Usage: php artisan make:all ModelName [options]\n\n";
        echo "Arguments:\n";
        echo "  ModelName               The name of the model class\n\n";
        echo "Options:\n";
        echo "  --controller-path=app/Controllers  The path for the controller (default: app/Controllers)\n";
        echo "  --view-path=resources/views        The path for the views (default: resources/views)\n";
        echo "  --model-path=app/Models            The path for the model (default: app/Models)\n";
        echo "  --table=table_name                 The database table name (default: pluralized snake_case)\n";
        echo "  --fillable=field1,field2,field3   Comma-separated list of fillable fields\n";
        echo "  --fields=field1,field2,field3     Comma-separated list of form fields\n\n";
        echo "Examples:\n";
        echo "  php artisan make:all User\n";
        echo "  php artisan make:all BlogPost --fields=title,content,status\n";
        echo "  php artisan make:all Product --table=products --fillable=name,price,description\n";
        echo "  php artisan make:all Category --controller-path=app/Controllers/Admin\n";
    }
}
