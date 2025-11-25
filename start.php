<?php
/**
 * NutriNexus Application Starter
 * 
 * This script starts the PHP built-in web server for development
 * and points it to the public directory.
 */

// Configuration
$host = '0.0.0.0'; // Listen on all interfaces (localhost + IP)
$port = 8000;
$public_dir = __DIR__ . '/public';

// Function to get current network IPv4 address
function getCurrentNetworkIP() {
    // Try different methods to get the local IPv4 address
    $ip = null;
    
    // Method 1: Windows - Use ipconfig to get IPv4 address
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $output = shell_exec('ipconfig | findstr "IPv4"');
        if (preg_match_all('/IPv4.*?(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
            // Prioritize 192.168.x.x addresses over APIPA (169.254.x.x)
            $ips = $matches[1];
            $preferredIp = null;
            $fallbackIp = null;
            
            foreach ($ips as $testIp) {
                if (strpos($testIp, '192.168.') === 0) {
                    $preferredIp = $testIp;
                    break; // Found preferred network, use it
                } elseif (strpos($testIp, '169.254.') !== 0 && $testIp !== '127.0.0.1') {
                    $fallbackIp = $testIp; // Store as fallback if not APIPA
                }
            }
            
            $ip = $preferredIp ?: $fallbackIp;
        }
    } else {
        // Method 2: Linux/Unix - Use hostname -I
        $output = shell_exec('hostname -I 2>/dev/null');
        if ($output) {
            $ips = explode(' ', trim($output));
            // Find the first non-localhost IPv4 address, prioritizing 192.168.x.x
            $preferredIp = null;
            $fallbackIp = null;
            
            foreach ($ips as $testIp) {
                if (strpos($testIp, '192.168.') === 0) {
                    $preferredIp = $testIp;
                    break; // Found preferred network, use it
                } elseif ($testIp !== '127.0.0.1' && strpos($testIp, '169.254.') !== 0) {
                    $fallbackIp = $testIp; // Store as fallback if not APIPA
                }
            }
            
            $ip = $preferredIp ?: $fallbackIp;
        }
    }
    
    // Method 3: Fallback - try to connect to a remote address to get local IP
    if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
        $connection = @fsockopen('8.8.8.8', 53, $errno, $errstr, 1);
        if ($connection) {
            $ip = stream_socket_get_name($connection, true);
            $ip = substr($ip, 0, strrpos($ip, ':'));
            fclose($connection);
        }
    }
    
    // Method 4: Use gethostbyname as last resort
    if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
        $hostname = gethostname();
        $ip = gethostbyname($hostname);
        if ($ip === $hostname) {
            $ip = null;
        }
    }
    
    // Final fallback - current network IP
    if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
        $ip = '192.168.1.79'; // Current network IP
    }
    
    return $ip;
}

// Get current network IP
$currentIP = getCurrentNetworkIP();

// Display startup message
echo "Starting NutriNexus application server...\n";
echo "Server will be available at:\n";
echo "  - http://localhost:{$port}/\n";
echo "  - http://{$currentIP}:{$port}/\n";
echo "\nPress Ctrl+C to stop the server.\n\n";

// Check if the public directory exists
if (!is_dir($public_dir)) {
    die("Error: Public directory not found at {$public_dir}\n");
}

// Create router script to handle requests
$router_file = __DIR__ . '/router.php';
file_put_contents($router_file, '<?php
// Router script for PHP built-in server
$uri = urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));

// Serve static files directly
if ($uri !== "/" && file_exists(__DIR__ . "/public" . $uri)) {
    return false;
}

// Otherwise, route everything to public/index.php
include_once __DIR__ . "/public/index.php";
');

// Register shutdown function to clean up
register_shutdown_function(function() use ($router_file) {
    if (file_exists($router_file)) {
        unlink($router_file);
    }
});

// Start the server
$command = sprintf(
    'php -S %s:%d -t %s %s',
    $host,
    $port,
    escapeshellarg($public_dir),
    escapeshellarg($router_file)
);

// Execute the command
echo "Executing: {$command}\n\n";
system($command);
?>