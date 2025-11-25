<?php

namespace App\Helpers;

/**
 * High-Performance Cache System
 * Optimized for turbo-fast loading
 */
class PerformanceCache
{
    private static $cacheDir;
    private static $tempDir;
    private static $imageCacheDir;
    
    public static function init()
    {
        self::$cacheDir = ROOT_DIR . '/App/storage/cache/';
        self::$tempDir = ROOT_DIR . '/App/storage/temporarydatabase/';
        self::$imageCacheDir = ROOT_DIR . '/App/storage/cache/images/';
        
        // Create directories if they don't exist
        self::createDirectories();
    }
    
    private static function createDirectories()
    {
        $dirs = [
            self::$cacheDir,
            self::$tempDir,
            self::$imageCacheDir,
            self::$cacheDir . 'static/',
            self::$cacheDir . 'database/',
            self::$cacheDir . 'views/',
            self::$tempDir . 'sessions/',
            self::$tempDir . 'temp/'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Cache static content with compression
     */
    public static function cacheStaticContent($key, $content, $ttl = 3600)
    {
        $cacheFile = self::$cacheDir . 'static/' . md5($key) . '.cache';
        $data = [
            'content' => $content,
            'timestamp' => time(),
            'ttl' => $ttl,
            'compressed' => true
        ];
        
        // Compress content for storage
        $compressed = gzcompress(serialize($data), 9);
        file_put_contents($cacheFile, $compressed);
        
        // Set file permissions
        chmod($cacheFile, 0644);
    }
    
    /**
     * Get cached static content
     */
    public static function getStaticContent($key)
    {
        $cacheFile = self::$cacheDir . 'static/' . md5($key) . '.cache';
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $compressed = file_get_contents($cacheFile);
        $data = unserialize(gzuncompress($compressed));
        
        if (time() - $data['timestamp'] > $data['ttl']) {
            unlink($cacheFile);
            return false;
        }
        
        return $data['content'];
    }
    
    /**
     * Cache database query results
     */
    public static function cacheDatabaseQuery($query, $params, $result, $ttl = 1800)
    {
        $key = md5($query . serialize($params));
        $cacheFile = self::$cacheDir . 'database/' . $key . '.cache';
        
        $data = [
            'query' => $query,
            'params' => $params,
            'result' => $result,
            'timestamp' => time(),
            'ttl' => $ttl
        ];
        
        file_put_contents($cacheFile, serialize($data));
        chmod($cacheFile, 0644);
    }
    
    /**
     * Get cached database query
     */
    public static function getCachedDatabaseQuery($query, $params)
    {
        $key = md5($query . serialize($params));
        $cacheFile = self::$cacheDir . 'database/' . $key . '.cache';
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        
        if (time() - $data['timestamp'] > $data['ttl']) {
            unlink($cacheFile);
            return false;
        }
        
        return $data['result'];
    }
    
    /**
     * Cache and optimize images
     */
    public static function cacheImage($imagePath, $width = null, $height = null, $quality = 85)
    {
        $originalPath = ROOT_DIR . '/public/' . ltrim($imagePath, '/');
        
        if (!file_exists($originalPath)) {
            return false;
        }
        
        $cacheKey = md5($imagePath . $width . $height . $quality);
        $cachedImage = self::$imageCacheDir . $cacheKey . '.webp';
        
        if (file_exists($cachedImage) && (time() - filemtime($cachedImage)) < 86400) {
            return '/App/storage/cache/images/' . $cacheKey . '.webp';
        }
        
        // Create optimized image
        $imageInfo = getimagesize($originalPath);
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($originalPath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($originalPath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($originalPath);
                break;
            default:
                return $imagePath; // Return original if unsupported
        }
        
        if (!$source) {
            return $imagePath;
        }
        
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        
        // Calculate new dimensions
        if ($width && $height) {
            $newWidth = $width;
            $newHeight = $height;
        } elseif ($width) {
            $newWidth = $width;
            $newHeight = ($originalHeight * $width) / $originalWidth;
        } elseif ($height) {
            $newHeight = $height;
            $newWidth = ($originalWidth * $height) / $originalHeight;
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }
        
        // Create optimized image
        $optimized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($mimeType === 'image/png') {
            imagealphablending($optimized, false);
            imagesavealpha($optimized, true);
            $transparent = imagecolorallocatealpha($optimized, 255, 255, 255, 127);
            imagefilledrectangle($optimized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($optimized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Save as WebP for better compression
        imagewebp($optimized, $cachedImage, $quality);
        
        imagedestroy($source);
        imagedestroy($optimized);
        
        return '/App/storage/cache/images/' . $cacheKey . '.webp';
    }
    
    /**
     * Cache view templates
     */
    public static function cacheView($viewPath, $data, $compiledView, $ttl = 3600)
    {
        $key = md5($viewPath . serialize($data));
        $cacheFile = self::$cacheDir . 'views/' . $key . '.cache';
        
        $cacheData = [
            'view' => $compiledView,
            'timestamp' => time(),
            'ttl' => $ttl
        ];
        
        file_put_contents($cacheFile, gzcompress(serialize($cacheData), 9));
        chmod($cacheFile, 0644);
    }
    
    /**
     * Get cached view
     */
    public static function getCachedView($viewPath, $data)
    {
        $key = md5($viewPath . serialize($data));
        $cacheFile = self::$cacheDir . 'views/' . $key . '.cache';
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $compressed = file_get_contents($cacheFile);
        $cacheData = unserialize(gzuncompress($compressed));
        
        if (time() - $cacheData['timestamp'] > $cacheData['ttl']) {
            unlink($cacheFile);
            return false;
        }
        
        return $cacheData['view'];
    }
    
    /**
     * Clear all cache
     */
    public static function clearAllCache()
    {
        $dirs = [
            self::$cacheDir . 'static/',
            self::$cacheDir . 'database/',
            self::$cacheDir . 'views/',
            self::$imageCacheDir
        ];
        
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public static function getCacheStats()
    {
        $stats = [
            'static_files' => 0,
            'database_files' => 0,
            'view_files' => 0,
            'image_files' => 0,
            'total_size' => 0
        ];
        
        $dirs = [
            'static' => self::$cacheDir . 'static/',
            'database' => self::$cacheDir . 'database/',
            'views' => self::$cacheDir . 'views/',
            'images' => self::$imageCacheDir
        ];
        
        foreach ($dirs as $type => $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*');
                $count = count($files);
                $size = 0;
                
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $size += filesize($file);
                    }
                }
                
                $stats[$type . '_files'] = $count;
                $stats['total_size'] += $size;
            }
        }
        
        return $stats;
    }
    
    /**
     * Optimize image path for performance
     */
    public static function getOptimizedImagePath($imagePath, $width = null, $height = null)
    {
        // Check if it's already a full URL
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }
        
        // Try to cache and optimize the image
        $cachedPath = self::cacheImage($imagePath, $width, $height);
        
        if ($cachedPath) {
            return \App\Core\View::url($cachedPath);
        }
        
        // Fallback to original path
        return \App\Core\View::asset($imagePath);
    }
}
