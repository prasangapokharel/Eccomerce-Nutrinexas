<?php
/**
 * Command Line Interface for the application (Laravel Artisan Style)
 * 
 * Usage: php artisan [command] [arguments...]
 */

// Bootstrap the application
require_once __DIR__ . '/bootstrap.php';

// Get command from arguments
$args = array_slice($_SERVER['argv'], 1);
$commandName = $args[0] ?? 'help';
$commandArgs = array_slice($args, 1);

// Map of available commands
$commands = [
    // Cache commands
    'cache:clear' => \App\Commands\CacheCommand::class,
    'cache:warmup' => \App\Commands\CacheCommand::class,
    'cache:stats' => \App\Commands\CacheCommand::class,
    
    // Migration commands
    'review:migrate' => \App\Commands\ReviewAlterMigration::class,
    
    // Generator commands
    'make:model' => \App\Commands\ModelGenerator::class,
    'make:controller' => \App\Commands\ControllerGenerator::class,
    'make:view' => \App\Commands\ViewGenerator::class,
    'make:all' => \App\Commands\AllGenerator::class,
    
    // Help commands
    'help' => null,
    'list' => null,
];

// Display help if no command specified
if ($commandName === 'help' || $commandName === 'list' || empty($commandName)) {
    displayHelp();
    exit(0);
}

// Handle cache commands
if (strpos($commandName, 'cache:') === 0) {
    $cacheCommand = new \App\Commands\CacheCommand();
    $method = str_replace('cache:', '', $commandName);
    
    if (method_exists($cacheCommand, $method)) {
        $cacheCommand->$method();
        exit(0);
    } else {
        echo "Error: Cache command '$method' not found.\n";
        exit(1);
    }
}

// Check if command exists
if (!isset($commands[$commandName])) {
    echo "Error: Command '$commandName' not found.\n";
    echo "Run 'php artisan help' to see available commands.\n";
    exit(1);
}

// Create command instance
$commandClass = $commands[$commandName];
$command = new $commandClass();

// Execute command
try {
    $exitCode = $command->execute($commandArgs);
    exit($exitCode);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Display help information
 */
function displayHelp() {
    echo "\033[32mNutrinexus Artisan CLI\033[0m\n";
    echo "================================\n\n";
    
    echo "\033[36mAvailable Commands:\033[0m\n\n";
    
    echo "\033[33mCache Management:\033[0m\n";
    echo "  cache:clear              Clear all caches\n";
    echo "  cache:warmup             Warm up the cache\n";
    echo "  cache:stats              Show cache statistics\n\n";
    
    echo "\033[33mDatabase:\033[0m\n";
    echo "  review:migrate           Run review migration\n\n";
    
    echo "\033[33mCode Generation:\033[0m\n";
    echo "  make:model ModelName     Create a new model class\n";
    echo "  make:controller Name     Create a new controller class\n";
    echo "  make:view view.name      Create a new view file\n";
    echo "  make:all ModelName       Create complete MVC set\n\n";
    
    echo "\033[33mHelp:\033[0m\n";
    echo "  help                     Show this help message\n";
    echo "  list                     List all available commands\n\n";
    
    echo "\033[36mUsage Examples:\033[0m\n";
    echo "  php artisan make:model User\n";
    echo "  php artisan make:controller UserController --model=User\n";
    echo "  php artisan make:view admin/users/index\n";
    echo "  php artisan make:all BlogPost --fields=title,content,status\n";
    echo "  php artisan cache:clear\n\n";
    
    echo "For more information about a command, use:\n";
    echo "  php artisan [command] --help\n\n";
}
