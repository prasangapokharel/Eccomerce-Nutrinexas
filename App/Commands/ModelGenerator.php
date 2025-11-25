<?php
namespace App\Commands;

/**
 * Model Generator Command
 * 
 * Usage: php artisan make:model ModelName [--path=app/Models] [--table=table_name] [--fillable=field1,field2,field3]
 */
class ModelGenerator extends GeneratorCommand
{
    protected $signature = 'make:model {name} {--path=app/Models} {--table=} {--fillable=}';
    protected $description = 'Create a new model class';

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
        $path = $parsed['options']['path'] ?? 'app/Models';
        $tableName = $parsed['options']['table'] ?? $this->toSnakeCase($this->pluralize($modelName));
        $fillableFields = $parsed['options']['fillable'] ?? '';

        // Convert to absolute path
        $fullPath = __DIR__ . '/../../' . $path;
        $fileName = $modelName . '.php';
        $filePath = $fullPath . '/' . $fileName;

        // Check if file already exists
        if (file_exists($filePath)) {
            if (!$this->confirm("Model $modelName already exists. Overwrite?")) {
                $this->output('Operation cancelled', 'warning');
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

        $this->output("Model created successfully: $filePath", 'success');
        $this->output("Table name: $tableName", 'info');
        $this->output("Fillable fields: " . implode(', ', array_map(function($f) { return trim($f, "'"); }, $fillableArray)), 'info');
    }

    /**
     * Display help information
     */
    public function help(): void
    {
        echo "Usage: php artisan make:model ModelName [options]\n\n";
        echo "Arguments:\n";
        echo "  ModelName              The name of the model class\n\n";
        echo "Options:\n";
        echo "  --path=app/Models      The path where the model will be created (default: app/Models)\n";
        echo "  --table=table_name     The database table name (default: pluralized snake_case of model name)\n";
        echo "  --fillable=field1,field2,field3  Comma-separated list of fillable fields\n\n";
        echo "Examples:\n";
        echo "  php artisan make:model User\n";
        echo "  php artisan make:model BlogPost --path=app/Models --table=blog_posts\n";
        echo "  php artisan make:model Product --fillable=name,price,description,status\n";
    }
}
