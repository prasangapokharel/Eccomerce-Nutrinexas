<?php
namespace App\Commands;

use App\Core\Command;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * Cache management command
 */
class CacheCommand extends Command
{
    private $symfonyCache;
    private $tagCache;
    
    public function __construct()
    {
        parent::__construct();
        
        // Initialize Symfony Cache components
        try {
            // Create a filesystem adapter with a namespace and custom directory
            $this->symfonyCache = new FilesystemAdapter(
                'app.cache', // namespace
                0,           // default lifetime (0 = unlimited)
                defined('CACHE_DIR') ? CACHE_DIR : __DIR__ . '/../../storage/cache' // directory
            );
            
            // Create a tag-aware adapter to allow cache invalidation by tags
            $this->tagCache = new TagAwareAdapter($this->symfonyCache);
        } catch (\Exception $e) {
            // Log error but continue
            echo "Failed to initialize Symfony Cache: " . $e->getMessage() . PHP_EOL;
            $this->symfonyCache = null;
            $this->tagCache = null;
        }
    }
    
    /**
     * Clear all caches
     */
    public function clear()
    {
        echo "Clearing all caches..." . PHP_EOL;
        
        // Clear regular cache
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : __DIR__ . '/../../storage/cache';
        $this->clearDirectory($cacheDir);
        
        // Clear Symfony cache if available
        if ($this->tagCache) {
            try {
                // Clear all caches with the 'page' tag
                $this->tagCache->invalidateTags(['page']);
                echo "Symfony cache cleared successfully." . PHP_EOL;
            } catch (\Exception $e) {
                echo "Failed to clear Symfony cache: " . $e->getMessage() . PHP_EOL;
            }
        }
        
        echo "All caches cleared successfully." . PHP_EOL;
    }
    
    /**
     * Clear a specific cache tag
     * 
     * @param string $tag
     */
    public function clearTag($tag)
    {
        if (!$tag) {
            echo "Please specify a tag to clear." . PHP_EOL;
            return;
        }
        
        echo "Clearing cache for tag: $tag..." . PHP_EOL;
        
        // Clear Symfony cache if available
        if ($this->tagCache) {
            try {
                // Clear all caches with the specified tag
                $this->tagCache->invalidateTags([$tag]);
                echo "Symfony cache for tag '$tag' cleared successfully." . PHP_EOL;
            } catch (\Exception $e) {
                echo "Failed to clear Symfony cache for tag '$tag': " . $e->getMessage() . PHP_EOL;
            }
        } else {
            echo "Symfony cache not available." . PHP_EOL;
        }
    }
    
    /**
     * Warm up the cache
     */
    public function warmup()
    {
        echo "Warming up cache..." . PHP_EOL;
        
        // Create a new instance of HomeController
        $homeController = new \App\Controllers\HomeController();
        
        // Call the warmupCache method
        $homeController->warmupCache();
        
        echo "Cache warmed up successfully." . PHP_EOL;
    }
    
    /**
     * Clear a directory recursively
     * 
     * @param string $dir
     */
    private function clearDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->clearDirectory($path);
                rmdir($path);
            } else {
                unlink($path);
            }
        }
        
        echo "Directory cleared: $dir" . PHP_EOL;
    }
    
    /**
     * Show cache statistics
     */
    public function stats()
    {
        echo "Cache Statistics:" . PHP_EOL;
        
        // Get cache directory size
        $cacheDir = defined('CACHE_DIR') ? CACHE_DIR : __DIR__ . '/../../storage/cache';
        $size = $this->getDirectorySize($cacheDir);
        
        echo "Cache Directory: $cacheDir" . PHP_EOL;
        echo "Total Size: " . $this->formatSize($size) . PHP_EOL;
        
        // Count files
        $fileCount = $this->countFiles($cacheDir);
        echo "Total Files: $fileCount" . PHP_EOL;
        
        // Show Symfony cache info if available
        if ($this->symfonyCache) {
            echo PHP_EOL . "Symfony Cache Information:" . PHP_EOL;
            echo "Namespace: app.cache" . PHP_EOL;
            
            // Try to get some items
            try {
                $homePageItem = $this->symfonyCache->getItem('home_page_data');
                if ($homePageItem->isHit()) {
                    echo "Home Page Cache: Hit (Expires: " . date('Y-m-d H:i:s', $homePageItem->getMetadata()['expiry']) . ")" . PHP_EOL;
                } else {
                    echo "Home Page Cache: Miss" . PHP_EOL;
                }
            } catch (\Exception $e) {
                echo "Error checking cache items: " . $e->getMessage() . PHP_EOL;
            }
        }
    }
    
    /**
     * Get directory size recursively
     * 
     * @param string $dir
     * @return int
     */
    private function getDirectorySize($dir)
    {
        $size = 0;
        
        if (!is_dir($dir)) {
            return 0;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $size += $this->getDirectorySize($path);
            } else {
                $size += filesize($path);
            }
        }
        
        return $size;
    }
    
    /**
     * Count files in directory recursively
     * 
     * @param string $dir
     * @return int
     */
    private function countFiles($dir)
    {
        $count = 0;
        
        if (!is_dir($dir)) {
            return 0;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $count += $this->countFiles($path);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Format size in human readable format
     * 
     * @param int $size
     * @return string
     */
    private function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
}
