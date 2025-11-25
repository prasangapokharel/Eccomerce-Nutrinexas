<?php
namespace App\Commands;

/**
 * View Generator Command
 * 
 * Usage: php artisan make:view view.name [--path=resources/views] [--ext=php] [--controller=ControllerName]
 */
class ViewGenerator extends GeneratorCommand
{
    protected $signature = 'make:view {name} {--path=resources/views} {--ext=php} {--controller=} {--fields=}';
    protected $description = 'Create a new view file';

    /**
     * Handle the command execution
     */
    protected function handle(array $args): void
    {
        $parsed = $this->parseArgs($args);
        
        if (empty($parsed['arguments'])) {
            $this->output('Error: View name is required', 'error');
            $this->help();
            return;
        }

        $viewName = $parsed['arguments'][0];
        $path = $parsed['options']['path'] ?? 'resources/views';
        $extension = $parsed['options']['ext'] ?? 'php';
        $controllerName = $parsed['options']['controller'] ?? '';
        $fields = $parsed['options']['fields'] ?? '';

        // Convert to absolute path
        $fullPath = __DIR__ . '/../../' . $path;
        
        // Parse view name (e.g., admin/users/index -> admin/users/index.php)
        $viewParts = explode('/', $viewName);
        $fileName = array_pop($viewParts) . '.' . $extension;
        $viewDir = implode('/', $viewParts);
        
        if ($viewDir) {
            $fullPath .= '/' . $viewDir;
        }
        
        $filePath = $fullPath . '/' . $fileName;

        // Check if file already exists
        if (file_exists($filePath)) {
            if (!$this->confirm("View $viewName already exists. Overwrite?")) {
                $this->output('Operation cancelled', 'warning');
                return;
            }
        }

        // Determine view type and generate appropriate content
        $viewType = $this->determineViewType($fileName);
        $content = $this->generateViewContent($viewType, $viewName, $controllerName, $fields);

        // Write file
        $this->writeFile($filePath, $content);

        $this->output("View created successfully: $filePath", 'success');
        $this->output("View type: $viewType", 'info');
        $this->output("Extension: $extension", 'info');
        
        if ($controllerName) {
            $this->output("Associated controller: $controllerName", 'info');
        }
    }

    /**
     * Determine view type based on filename
     */
    protected function determineViewType(string $fileName): string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        
        switch ($name) {
            case 'index':
                return 'index';
            case 'create':
                return 'create';
            case 'edit':
                return 'edit';
            case 'show':
                return 'show';
            default:
                return 'custom';
        }
    }

    /**
     * Generate view content based on type
     */
    protected function generateViewContent(string $viewType, string $viewName, string $controllerName, string $fields): string
    {
        $className = $controllerName ?: $this->extractClassNameFromView($viewName);
        $modelName = $this->extractModelName($className);
        $routePrefix = $this->generateRoutePrefix($viewName);

        $replacements = [
            'className' => $className,
            'modelName' => $modelName,
            'routePrefix' => $routePrefix,
            'viewPath' => $viewName
        ];

        switch ($viewType) {
            case 'index':
                $replacements['tableHeaders'] = $this->generateTableHeaders($fields);
                $replacements['tableRows'] = $this->generateTableRows($fields);
                return $this->getTemplate('view_index.php', $replacements);
                
            case 'create':
                $replacements['formFields'] = $this->generateFormFields($fields, 'create');
                return $this->getTemplate('view_create.php', $replacements);
                
            case 'edit':
                $replacements['formFields'] = $this->generateFormFields($fields, 'edit');
                return $this->getTemplate('view_edit.php', $replacements);
                
            case 'show':
                $replacements['showFields'] = $this->generateShowFields($fields);
                return $this->getTemplate('view_show.php', $replacements);
                
            default:
                return $this->generateCustomView($viewName, $className);
        }
    }

    /**
     * Extract class name from view name
     */
    protected function extractClassNameFromView(string $viewName): string
    {
        $parts = explode('/', $viewName);
        $lastPart = end($parts);
        
        // Convert to PascalCase and add Controller suffix
        return $this->toPascalCase($lastPart) . 'Controller';
    }

    /**
     * Extract model name from class name
     */
    protected function extractModelName(string $className): string
    {
        return preg_replace('/Controller$/', '', $className);
    }

    /**
     * Generate route prefix from view name
     */
    protected function generateRoutePrefix(string $viewName): string
    {
        $parts = explode('/', $viewName);
        $lastPart = array_pop($parts);
        
        // Remove the action (index, create, etc.)
        if (in_array($lastPart, ['index', 'create', 'edit', 'show'])) {
            $lastPart = $this->toKebabCase($this->extractModelName($this->toPascalCase($lastPart)));
        }
        
        $routePrefix = $this->toKebabCase($lastPart);
        
        if (!empty($parts)) {
            $namespace = strtolower(implode('/', $parts));
            $routePrefix = "$namespace/$routePrefix";
        }
        
        return $routePrefix;
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
     * Generate custom view
     */
    protected function generateCustomView(string $viewName, string $className): string
    {
        return "<?php
/**
 * $className View
 * 
 * @var array \$data
 */
?>

<div class=\"container-fluid\">
    <div class=\"row\">
        <div class=\"col-12\">
            <div class=\"card\">
                <div class=\"card-header\">
                    <h3 class=\"card-title\">$className</h3>
                </div>
                
                <div class=\"card-body\">
                    <!-- Your custom content here -->
                    <p>Welcome to the $viewName view!</p>
                </div>
            </div>
        </div>
    </div>
</div>";
    }

    /**
     * Display help information
     */
    public function help(): void
    {
        echo "Usage: php artisan make:view view.name [options]\n\n";
        echo "Arguments:\n";
        echo "  view.name               The name of the view (e.g., admin/users/index)\n\n";
        echo "Options:\n";
        echo "  --path=resources/views  The path where the view will be created (default: resources/views)\n";
        echo "  --ext=php               The file extension (default: php)\n";
        echo "  --controller=ControllerName  The associated controller name\n";
        echo "  --fields=field1,field2,field3  Comma-separated list of form fields\n\n";
        echo "Examples:\n";
        echo "  php artisan make:view admin/users/index\n";
        echo "  php artisan make:view products/create --controller=ProductController\n";
        echo "  php artisan make:view blog/edit --fields=title,content,status --ext=php\n";
        echo "  php artisan make:view custom/page --path=resources/views/custom\n";
    }
}
