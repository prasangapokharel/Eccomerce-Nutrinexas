<?php
namespace App\Commands;

use App\Core\Command;

/**
 * Base Generator Command class
 * Provides common functionality for all generators
 */
abstract class GeneratorCommand extends Command
{
    protected $name;
    protected $description;
    protected $signature;
    protected $options = [];
    protected $arguments = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->parseSignature();
    }

    /**
     * Parse the command signature
     */
    protected function parseSignature()
    {
        if (!$this->signature) {
            return;
        }

        $parts = explode(' ', $this->signature);
        $this->name = array_shift($parts);

        foreach ($parts as $part) {
            if (strpos($part, '{') === 0) {
                // Argument
                $name = trim($part, '{}?');
                $this->arguments[$name] = [
                    'required' => !str_contains($part, '?'),
                    'description' => ''
                ];
            } elseif (strpos($part, '--') === 0) {
                // Option
                $name = substr($part, 2);
                $this->options[$name] = [
                    'description' => '',
                    'default' => null
                ];
            }
        }
    }

    /**
     * Execute the command
     */
    public function execute(array $args = []): int
    {
        try {
            $this->handle($args);
            return 0;
        } catch (\Exception $e) {
            $this->output("Error: " . $e->getMessage(), 'error');
            return 1;
        }
    }

    /**
     * Handle the command execution
     */
    abstract protected function handle(array $args): void;

    /**
     * Get argument value
     */
    protected function argument(string $name, $default = null)
    {
        $index = array_search($name, array_keys($this->arguments));
        return $args[$index] ?? $default;
    }

    /**
     * Get option value
     */
    protected function option(string $name, $default = null)
    {
        foreach ($this->args as $arg) {
            if (strpos($arg, "--$name=") === 0) {
                return substr($arg, strlen("--$name="));
            } elseif ($arg === "--$name") {
                return true;
            }
        }
        return $default;
    }

    /**
     * Parse command line arguments
     */
    protected function parseArgs(array $args): array
    {
        $parsed = [
            'arguments' => [],
            'options' => []
        ];

        foreach ($args as $arg) {
            if (strpos($arg, '--') === 0) {
                if (strpos($arg, '=') !== false) {
                    [$key, $value] = explode('=', substr($arg, 2), 2);
                    $parsed['options'][$key] = $value;
                } else {
                    $parsed['options'][substr($arg, 2)] = true;
                }
            } else {
                $parsed['arguments'][] = $arg;
            }
        }

        return $parsed;
    }

    /**
     * Create directory if it doesn't exist
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            $this->output("Created directory: $path", 'success');
        }
    }

    /**
     * Write file content
     */
    protected function writeFile(string $path, string $content): void
    {
        $this->ensureDirectoryExists(dirname($path));
        
        if (file_put_contents($path, $content) === false) {
            throw new \Exception("Failed to write file: $path");
        }
        
        $this->output("Created file: $path", 'success');
    }

    /**
     * Get template content
     */
    protected function getTemplate(string $template, array $replacements = []): string
    {
        $templatePath = __DIR__ . "/templates/$template";
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found: $template");
        }
        
        $content = file_get_contents($templatePath);
        
        foreach ($replacements as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }
        
        return $content;
    }

    /**
     * Convert string to PascalCase
     */
    protected function toPascalCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
    }

    /**
     * Convert string to camelCase
     */
    protected function toCamelCase(string $string): string
    {
        return lcfirst($this->toPascalCase($string));
    }

    /**
     * Convert string to snake_case
     */
    protected function toSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * Convert string to kebab-case
     */
    protected function toKebabCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
    }

    /**
     * Get plural form of a word
     */
    protected function pluralize(string $word): string
    {
        $rules = [
            '/([^aeiou])y$/' => '\1ies',
            '/([sxz]|[cs]h)$/' => '\1es',
            '/([^aeiou])o$/' => '\1oes',
            '/([^aeiou])f$/' => '\1ves',
            '/([^aeiou])fe$/' => '\1ves',
            '/s$/' => 's'
        ];

        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }

        return $word . 's';
    }

    /**
     * Display help information
     */
    public function help(): void
    {
        echo "Usage: php artisan {$this->name} [options]\n\n";
        
        if ($this->description) {
            echo "Description:\n  {$this->description}\n\n";
        }
        
        if (!empty($this->arguments)) {
            echo "Arguments:\n";
            foreach ($this->arguments as $name => $config) {
                $required = $config['required'] ? 'required' : 'optional';
                echo "  $name ($required)\n";
            }
            echo "\n";
        }
        
        if (!empty($this->options)) {
            echo "Options:\n";
            foreach ($this->options as $name => $config) {
                echo "  --$name\n";
            }
        }
    }
}
