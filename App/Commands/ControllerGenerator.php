<?php
namespace App\Commands;

/**
 * Controller Generator Command
 * 
 * Usage: php artisan make:controller ControllerName [--path=app/Controllers] [--model=ModelName] [--resource]
 */
class ControllerGenerator extends GeneratorCommand
{
    protected $signature = 'make:controller {name} {--path=app/Controllers} {--model=} {--resource} {--view-path=}';
    protected $description = 'Create a new controller class';

    /**
     * Handle the command execution
     */
    protected function handle(array $args): void
    {
        $parsed = $this->parseArgs($args);
        
        if (empty($parsed['arguments'])) {
            $this->output('Error: Controller name is required', 'error');
            $this->help();
            return;
        }

        $controllerName = $parsed['arguments'][0];
        $path = $parsed['options']['path'] ?? 'app/Controllers';
        $modelName = $parsed['options']['model'] ?? $this->extractModelName($controllerName);
        $isResource = isset($parsed['options']['resource']);
        $viewPath = $parsed['options']['view-path'] ?? $this->generateViewPath($controllerName);

        // Convert to absolute path
        $fullPath = __DIR__ . '/../../' . $path;
        $fileName = $controllerName . '.php';
        $filePath = $fullPath . '/' . $fileName;

        // Check if file already exists
        if (file_exists($filePath)) {
            if (!$this->confirm("Controller $controllerName already exists. Overwrite?")) {
                $this->output('Operation cancelled', 'warning');
                return;
            }
        }

        // Generate route prefix from controller name
        $routePrefix = $this->generateRoutePrefix($controllerName);

        // Generate validation rules
        $validationRules = $this->generateValidationRules($modelName);

        // Generate controller content
        $content = $this->getTemplate('controller.php', [
            'className' => $controllerName,
            'modelName' => $modelName,
            'viewPath' => $viewPath,
            'routePrefix' => $routePrefix,
            'validationRules' => $validationRules
        ]);

        // Write file
        $this->writeFile($filePath, $content);

        $this->output("Controller created successfully: $filePath", 'success');
        $this->output("Model: $modelName", 'info');
        $this->output("View path: $viewPath", 'info');
        $this->output("Route prefix: $routePrefix", 'info');
        
        if ($isResource) {
            $this->output("Resource controller with CRUD operations created", 'info');
        }
    }

    /**
     * Extract model name from controller name
     */
    protected function extractModelName(string $controllerName): string
    {
        // Remove 'Controller' suffix if present
        $modelName = preg_replace('/Controller$/', '', $controllerName);
        
        // Handle namespaced controllers (e.g., Admin/UserController -> User)
        if (strpos($modelName, '/') !== false) {
            $parts = explode('/', $modelName);
            $modelName = end($parts);
        }
        
        return $modelName;
    }

    /**
     * Generate view path from controller name
     */
    protected function generateViewPath(string $controllerName): string
    {
        // Remove 'Controller' suffix
        $viewName = preg_replace('/Controller$/', '', $controllerName);
        
        // Convert to kebab-case
        $viewPath = $this->toKebabCase($viewName);
        
        // Handle namespaced controllers
        if (strpos($controllerName, '/') !== false) {
            $parts = explode('/', $controllerName);
            $namespace = strtolower($parts[0]);
            $controller = $this->toKebabCase($parts[1]);
            $viewPath = "$namespace/$controller";
        }
        
        return $viewPath;
    }

    /**
     * Generate route prefix from controller name
     */
    protected function generateRoutePrefix(string $controllerName): string
    {
        // Remove 'Controller' suffix
        $routeName = preg_replace('/Controller$/', '', $controllerName);
        
        // Convert to kebab-case
        $routePrefix = $this->toKebabCase($routeName);
        
        // Handle namespaced controllers
        if (strpos($controllerName, '/') !== false) {
            $parts = explode('/', $controllerName);
            $namespace = strtolower($parts[0]);
            $controller = $this->toKebabCase($parts[1]);
            $routePrefix = "$namespace/$controller";
        }
        
        return $routePrefix;
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
     * Display help information
     */
    public function help(): void
    {
        echo "Usage: php artisan make:controller ControllerName [options]\n\n";
        echo "Arguments:\n";
        echo "  ControllerName          The name of the controller class\n\n";
        echo "Options:\n";
        echo "  --path=app/Controllers  The path where the controller will be created (default: app/Controllers)\n";
        echo "  --model=ModelName       The associated model name (default: extracted from controller name)\n";
        echo "  --resource              Create a resource controller with CRUD operations\n";
        echo "  --view-path=path        The view path for the controller (default: auto-generated)\n\n";
        echo "Examples:\n";
        echo "  php artisan make:controller UserController\n";
        echo "  php artisan make:controller Admin/UserController --model=User\n";
        echo "  php artisan make:controller ProductController --resource --view-path=admin/products\n";
        echo "  php artisan make:controller BlogPostController --path=app/Controllers/Admin\n";
    }
}
