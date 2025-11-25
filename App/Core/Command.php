<?php
namespace App\Core;

/**
 * Base Command class for CLI commands
 */
abstract class Command
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize command
    }
    
    /**
     * Execute the command
     * 
     * @param array $args Command line arguments
     * @return int Exit code
     */
    public function execute(array $args = []): int
    {
        // Default implementation
        echo "Command executed\n";
        return 0; // Success
    }
    
    /**
     * Display help information
     */
    public function help(): void
    {
        echo "Usage: php command.php [command] [options]\n";
    }
    
    /**
     * Output a message
     * 
     * @param string $message
     * @param string $type (info, success, error, warning)
     */
    protected function output(string $message, string $type = 'info'): void
    {
        $prefix = '';
        
        switch ($type) {
            case 'success':
                $prefix = "\033[32m[SUCCESS]\033[0m ";
                break;
            case 'error':
                $prefix = "\033[31m[ERROR]\033[0m ";
                break;
            case 'warning':
                $prefix = "\033[33m[WARNING]\033[0m ";
                break;
            case 'info':
            default:
                $prefix = "\033[36m[INFO]\033[0m ";
                break;
        }
        
        echo $prefix . $message . PHP_EOL;
    }
    
    /**
     * Get user input
     * 
     * @param string $question
     * @param string|null $default
     * @return string
     */
    protected function ask(string $question, ?string $default = null): string
    {
        $defaultText = $default !== null ? " [$default]" : '';
        echo "$question$defaultText: ";
        $answer = trim(fgets(STDIN));
        
        if ($answer === '' && $default !== null) {
            return $default;
        }
        
        return $answer;
    }
    
    /**
     * Get confirmation from user
     * 
     * @param string $question
     * @param bool $default
     * @return bool
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        echo "$question [$defaultText]: ";
        $answer = strtolower(trim(fgets(STDIN)));
        
        if ($answer === '') {
            return $default;
        }
        
        return in_array($answer, ['y', 'yes', 'true', '1']);
    }
}
