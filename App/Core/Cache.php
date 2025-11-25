<?php

namespace App\Core;

/**
 * Simple caching system using file-based storage
 * Can be easily extended to use Redis, Memcached, etc.
 */
class Cache
{
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour

    public function __construct()
    {
        $this->cacheDir = ROOT_DIR . '/App/storage/cache/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get a cached value
     *
     * @param string $key
     * @return mixed|false
     */
    public function get($key)
    {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }

        $data = file_get_contents($filename);
        $cached = json_decode($data, true);

        if (!$cached || !isset($cached['expires']) || !isset($cached['data'])) {
            return false;
        }

        // Check if expired
        if (time() > $cached['expires']) {
            $this->delete($key);
            return false;
        }

        return $cached['data'];
    }

    /**
     * Set a cached value
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTTL;
        $filename = $this->getCacheFilename($key);
        
        $data = [
            'expires' => time() + $ttl,
            'data' => $value,
            'created' => time()
        ];

        $result = file_put_contents($filename, json_encode($data));
        return $result !== false;
    }

    /**
     * Delete a cached value
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }

    /**
     * Delete multiple cache keys by pattern
     *
     * @param string $pattern
     * @return int Number of deleted files
     */
    public function deletePattern($pattern)
    {
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        $deleted = 0;
        $files = glob($this->cacheDir . '*');
        
        foreach ($files as $file) {
            $key = basename($file, '.cache');
            if (preg_match($pattern, $key)) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }

    /**
     * Clear all cache
     *
     * @return int Number of deleted files
     */
    public function clear()
    {
        $deleted = 0;
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Check if a key exists and is not expired
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->get($key) !== false;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getStats()
    {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $expired = 0;
        $valid = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $data = file_get_contents($file);
            $cached = json_decode($data, true);
            
            if ($cached && isset($cached['expires'])) {
                if (time() > $cached['expires']) {
                    $expired++;
                } else {
                    $valid++;
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $valid,
            'expired_files' => $expired,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }

    /**
     * Clean expired cache entries
     *
     * @return int Number of cleaned files
     */
    public function clean()
    {
        $cleaned = 0;
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cached = json_decode($data, true);
            
            if ($cached && isset($cached['expires']) && time() > $cached['expires']) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }

    /**
     * Get cache filename for a key
     *
     * @param string $key
     * @return string
     */
    private function getCacheFilename($key)
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . $safeKey . '.cache';
    }

    /**
     * Increment a numeric cache value
     *
     * @param string $key
     * @param int $increment
     * @return int|false
     */
    public function increment($key, $increment = 1)
    {
        $current = $this->get($key);
        
        if ($current === false) {
            $current = 0;
        }
        
        if (!is_numeric($current)) {
            return false;
        }
        
        $newValue = $current + $increment;
        $this->set($key, $newValue);
        
        return $newValue;
    }

    /**
     * Decrement a numeric cache value
     *
     * @param string $key
     * @param int $decrement
     * @return int|false
     */
    public function decrement($key, $decrement = 1)
    {
        return $this->increment($key, -$decrement);
    }

    /**
     * Remember a value if it doesn't exist
     *
     * @param string $key
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    public function remember($key, callable $callback, $ttl = null)
    {
        $value = $this->get($key);
        
        if ($value !== false) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Get or set a value with TTL
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return mixed
     */
    public function put($key, $value, $ttl = null)
    {
        $this->set($key, $value, $ttl);
        return $value;
    }

    /**
     * Add a value only if it doesn't exist
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function add($key, $value, $ttl = null)
    {
        if ($this->has($key)) {
            return false;
        }
        
        return $this->set($key, $value, $ttl);
    }

    /**
     * Generate a cache key from base key and parameters
     *
     * @param string $baseKey
     * @param array $params
     * @return string
     */
    public function generateKey($baseKey, array $params = [])
    {
        if (empty($params)) {
            return $baseKey;
        }
        
        // Sort parameters to ensure consistent key generation
        ksort($params);
        
        // Create a hash of the parameters
        $paramHash = md5(serialize($params));
        
        return $baseKey . '_' . $paramHash;
    }
}
