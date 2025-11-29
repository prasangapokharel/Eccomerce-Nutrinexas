<?php

namespace App\Helpers;

use App\Core\Database;
use App\Core\Cache;

class QueryOptimizer
{
    private static $cache;
    private static $queryCount = 0;
    private static $totalQueryTime = 0;
    private static $slowQueries = [];
    
    public static function init()
    {
        if (!self::$cache) {
            self::$cache = new Cache();
        }
    }
    
    public static function cachedQuery($sql, $params = [], $ttl = 1800, $useCache = true)
    {
        self::init();
        
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        if ($useCache) {
            $cached = self::$cache->get($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        $db = Database::getInstance();
        $result = $db->query($sql, $params)->all();
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        self::$queryCount++;
        self::$totalQueryTime += $executionTime;
        
        if ($executionTime > 100) {
            self::$slowQueries[] = [
                'query' => substr($sql, 0, 200),
                'time' => round($executionTime, 2)
            ];
        }
        
        if ($useCache && $result !== false) {
            self::$cache->set($cacheKey, $result, $ttl);
        }
        
        return $result;
    }
    
    public static function cachedSingle($sql, $params = [], $ttl = 1800, $useCache = true)
    {
        self::init();
        
        $cacheKey = 'query_single_' . md5($sql . serialize($params));
        
        if ($useCache) {
            $cached = self::$cache->get($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $startTime = microtime(true);
        $db = Database::getInstance();
        $result = $db->query($sql, $params)->single();
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        self::$queryCount++;
        self::$totalQueryTime += $executionTime;
        
        if ($executionTime > 100) {
            self::$slowQueries[] = [
                'query' => substr($sql, 0, 200),
                'time' => round($executionTime, 2)
            ];
        }
        
        if ($useCache && $result !== false) {
            self::$cache->set($cacheKey, $result, $ttl);
        }
        
        return $result;
    }
    
    public static function clearQueryCache($pattern = null)
    {
        self::init();
        
        if ($pattern) {
            return self::$cache->deletePattern('query_' . $pattern);
        }
        
        return self::$cache->deletePattern('query_*');
    }
    
    public static function getStats()
    {
        return [
            'query_count' => self::$queryCount,
            'total_time' => round(self::$totalQueryTime, 2),
            'avg_time' => self::$queryCount > 0 ? round(self::$totalQueryTime / self::$queryCount, 2) : 0,
            'slow_queries' => self::$slowQueries
        ];
    }
    
    public static function resetStats()
    {
        self::$queryCount = 0;
        self::$totalQueryTime = 0;
        self::$slowQueries = [];
    }
}




