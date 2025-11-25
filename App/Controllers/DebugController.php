<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use App\Helpers\PerformanceHelpers;
use App\Core\Database;

/**
 * Debug Controller for Performance Monitoring and System Diagnostics
 * 
 * Provides endpoints for:
 * - Cache management and statistics
 * - Performance monitoring
 * - Database diagnostics
 * - System health checks
 */
class DebugController extends Controller
{
    private $cache;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
        $this->db = Database::getInstance();
    }

    /**
     * Debug index page
     */
    public function index()
    {
        if (!$this->isDebugMode()) {
            $this->redirect('home');
            return;
        }

        $data = [
            'title' => 'System Debug & Performance',
            'system_metrics' => PerformanceHelpers::getSystemMetrics(),
            'cache_stats' => $this->cache->getStats(),
            'memory_usage' => PerformanceHelpers::getMemoryUsage(),
            'cache_hit_rate' => PerformanceHelpers::getCacheHitRate()
        ];

        $this->view('debug/index', $data);
    }

    /**
     * Cache management dashboard
     */
    public function cache($action = 'stats', $param = null)
    {
        if (!$this->isDebugMode()) {
            $this->redirect('home');
            return;
        }

        switch ($action) {
            case 'stats':
                $this->cacheStats();
                break;
            case 'clear':
                $this->clearCache($param);
                break;
            case 'clean':
                $this->cleanCache();
                break;
            case 'product':
                $this->viewProductCache($param);
                break;
            case 'user':
                $this->viewUserCache($param);
                break;
            case 'cart':
                $this->viewCartCache($param);
                break;
            default:
                $this->cacheStats();
        }
    }

    /**
     * Performance monitoring dashboard
     */
    public function performance($action = 'overview')
    {
        if (!$this->isDebugMode()) {
            $this->redirect('home');
            return;
        }

        switch ($action) {
            case 'queries':
                $this->databasePerformance();
                break;
            case 'cache':
                $this->cachePerformance();
                break;
            case 'memory':
                $this->memoryPerformance();
                break;
            case 'speed':
                $this->speedPerformance();
                break;
            default:
                $this->performanceOverview();
        }
    }

    /**
     * Database diagnostics
     */
    public function database($action = 'tables', $param = null)
    {
        if (!$this->isDebugMode()) {
            $this->redirect('home');
            return;
        }

        switch ($action) {
            case 'tables':
                $this->listTables();
                break;
            case 'structure':
                $this->tableStructure($param);
                break;
            case 'indexes':
                $this->tableIndexes($param);
                break;
            case 'queries':
                $this->recentQueries();
                break;
            case 'slow':
                $this->slowQueries();
                break;
            default:
                $this->listTables();
        }
    }

    /**
     * System health check
     */
    public function health()
    {
        if (!$this->isDebugMode()) {
            $this->redirect('home');
            return;
        }

        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => []
        ];

        // Check cache health
        try {
            $cacheStats = $this->cache->getStats();
            $health['checks']['cache'] = [
                'status' => 'healthy',
                'message' => 'Cache system operational',
                'data' => $cacheStats
            ];
        } catch (\Exception $e) {
            $health['checks']['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache system error: ' . $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }

        // Check database health
        try {
            $result = $this->db->query("SELECT 1")->fetch();
            $health['checks']['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection operational',
                'response_time' => 'OK'
            ];
        } catch (\Exception $e) {
            $health['checks']['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection error: ' . $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }

        // Check memory health
        $memoryUsage = PerformanceHelpers::getMemoryUsage();
        $memoryLimit = ini_get('memory_limit');
        $memoryPercent = ($memoryUsage['current'] / $this->parseMemoryLimit($memoryLimit)) * 100;

        if ($memoryPercent > 80) {
            $health['checks']['memory'] = [
                'status' => 'warning',
                'message' => 'High memory usage: ' . round($memoryPercent, 1) . '%',
                'data' => $memoryUsage
            ];
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        } else {
            $health['checks']['memory'] = [
                'status' => 'healthy',
                'message' => 'Memory usage normal: ' . round($memoryPercent, 1) . '%',
                'data' => $memoryUsage
            ];
        }

        // Check disk space
        try {
            $diskFree = disk_free_space(ROOT_DIR);
            $diskTotal = disk_total_space(ROOT_DIR);
            $diskPercent = (($diskTotal - $diskFree) / $diskTotal) * 100;

            if ($diskPercent > 90) {
                $health['checks']['disk'] = [
                    'status' => 'critical',
                    'message' => 'Low disk space: ' . round($diskPercent, 1) . '% used',
                    'data' => [
                        'free' => PerformanceHelpers::formatBytes($diskFree),
                        'total' => PerformanceHelpers::formatBytes($diskTotal),
                        'used_percent' => round($diskPercent, 1)
                    ]
                ];
                $health['status'] = 'critical';
            } else {
                $health['checks']['disk'] = [
                    'status' => 'healthy',
                    'message' => 'Disk space adequate: ' . round($diskPercent, 1) . '% used',
                    'data' => [
                        'free' => PerformanceHelpers::formatBytes($diskFree),
                        'total' => PerformanceHelpers::formatBytes($diskTotal),
                        'used_percent' => round($diskPercent, 1)
                    ]
                ];
            }
        } catch (\Exception $e) {
            $health['checks']['disk'] = [
                'status' => 'unknown',
                'message' => 'Unable to check disk space: ' . $e->getMessage()
            ];
        }

        $this->jsonResponse($health);
    }

    /**
     * Cache statistics
     */
    private function cacheStats()
    {
        $stats = $this->cache->getStats();
        $hitRate = PerformanceHelpers::getCacheHitRate();

        $data = [
            'title' => 'Cache Statistics',
            'stats' => $stats,
            'hit_rate' => $hitRate,
            'recommendations' => $this->getCacheRecommendations($stats, $hitRate)
        ];

        $this->view('debug/cache_stats', $data);
    }

    /**
     * Clear cache
     */
    private function clearCache($type = null)
    {
        $result = [];
        
        if ($type) {
            switch ($type) {
                case 'product':
                    PerformanceHelpers::clearProductCache();
                    $result['message'] = 'Product cache cleared';
                    break;
                case 'user':
                    PerformanceHelpers::clearUserCache();
                    $result['message'] = 'User cache cleared';
                    break;
                case 'cart':
                    PerformanceHelpers::clearCartCache();
                    $result['message'] = 'Cart cache cleared';
                    break;
                default:
                    $result['message'] = 'Unknown cache type';
                    $result['error'] = true;
            }
        } else {
            PerformanceHelpers::clearAllCache();
            $result['message'] = 'All cache cleared';
        }

        $result['timestamp'] = date('Y-m-d H:i:s');
        $result['cache_stats'] = $this->cache->getStats();

        $this->jsonResponse($result);
    }

    /**
     * Clean expired cache
     */
    private function cleanCache()
    {
        $cleaned = PerformanceHelpers::cleanExpiredCache();
        $stats = $this->cache->getStats();

        $result = [
            'message' => "Cleaned {$cleaned} expired cache files",
            'cleaned_count' => $cleaned,
            'timestamp' => date('Y-m-d H:i:s'),
            'cache_stats' => $stats
        ];

        $this->jsonResponse($result);
    }

    /**
     * View product cache
     */
    private function viewProductCache($productId)
    {
        if (!$productId) {
            $this->jsonResponse(['error' => 'Product ID required']);
            return;
        }

        $cacheKey = 'product_id_' . $productId;
        $cached = $this->cache->get($cacheKey);

        $data = [
            'product_id' => $productId,
            'cache_key' => $cacheKey,
            'cached' => $cached !== false,
            'data' => $cached,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->jsonResponse($data);
    }

    /**
     * View user cache
     */
    private function viewUserCache($userId)
    {
        if (!$userId) {
            $this->jsonResponse(['error' => 'User ID required']);
            return;
        }

        $cacheKey = 'user_id_' . $userId;
        $cached = $this->cache->get($cacheKey);

        $data = [
            'user_id' => $userId,
            'cache_key' => $cacheKey,
            'cached' => $cached !== false,
            'data' => $cached,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->jsonResponse($data);
    }

    /**
     * View cart cache
     */
    private function viewCartCache($userKey)
    {
        $userKey = $userKey ?: 'guest';
        $cachePattern = 'cart_' . $userKey . '_*';
        
        // Get all cart cache keys for this user
        $cacheFiles = glob(ROOT_DIR . '/App/storage/cache/' . $cachePattern . '.cache');
        $cartCache = [];

        foreach ($cacheFiles as $file) {
            $key = basename($file, '.cache');
            $data = $this->cache->get($key);
            if ($data !== false) {
                $cartCache[$key] = $data;
            }
        }

        $data = [
            'user_key' => $userKey,
            'cache_pattern' => $cachePattern,
            'cache_files' => $cacheFiles,
            'cart_cache' => $cartCache,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->jsonResponse($data);
    }

    /**
     * Performance overview
     */
    private function performanceOverview()
    {
        $metrics = PerformanceHelpers::getSystemMetrics();
        $cacheStats = $this->cache->getStats();
        $hitRate = PerformanceHelpers::getCacheHitRate();

        $data = [
            'title' => 'Performance Overview',
            'metrics' => $metrics,
            'cache_stats' => $cacheStats,
            'hit_rate' => $hitRate,
            'performance_score' => $this->calculatePerformanceScore($metrics, $cacheStats, $hitRate)
        ];

        $this->view('debug/performance_overview', $data);
    }

    /**
     * Database performance
     */
    private function databasePerformance()
    {
        // This would typically connect to a query log or performance schema
        $data = [
            'title' => 'Database Performance',
            'message' => 'Database performance monitoring requires query logging setup'
        ];

        $this->view('debug/database_performance', $data);
    }

    /**
     * Cache performance
     */
    private function cachePerformance()
    {
        $stats = $this->cache->getStats();
        $hitRate = PerformanceHelpers::getCacheHitRate();

        $data = [
            'title' => 'Cache Performance',
            'stats' => $stats,
            'hit_rate' => $hitRate,
            'recommendations' => $this->getCacheRecommendations($stats, $hitRate)
        ];

        $this->view('debug/cache_performance', $data);
    }

    /**
     * Memory performance
     */
    private function memoryPerformance()
    {
        $memoryUsage = PerformanceHelpers::getMemoryUsage();
        $memoryLimit = ini_get('memory_limit');

        $data = [
            'title' => 'Memory Performance',
            'memory_usage' => $memoryUsage,
            'memory_limit' => $memoryLimit,
            'usage_percent' => ($memoryUsage['current'] / $this->parseMemoryLimit($memoryLimit)) * 100
        ];

        $this->view('debug/memory_performance', $data);
    }

    /**
     * Speed performance
     */
    private function speedPerformance()
    {
        $data = [
            'title' => 'Speed Performance',
            'message' => 'Speed performance monitoring requires response time tracking setup'
        ];

        $this->view('debug/speed_performance', $data);
    }

    /**
     * List database tables
     */
    private function listTables()
    {
        try {
            $tables = $this->db->query("SHOW TABLES")->fetchAll();
            
            $data = [
                'title' => 'Database Tables',
                'tables' => $tables,
                'count' => count($tables)
            ];

            $this->view('debug/database_tables', $data);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Table structure
     */
    private function tableStructure($tableName)
    {
        if (!$tableName) {
            $this->jsonResponse(['error' => 'Table name required']);
            return;
        }

        try {
            $structure = $this->db->query("DESCRIBE {$tableName}")->fetchAll();
            
            $data = [
                'table_name' => $tableName,
                'structure' => $structure,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            $this->jsonResponse($data);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Table indexes
     */
    private function tableIndexes($tableName)
    {
        if (!$tableName) {
            $this->jsonResponse(['error' => 'Table name required']);
            return;
        }

        try {
            $indexes = $this->db->query("SHOW INDEX FROM {$tableName}")->fetchAll();
            
            $data = [
                'table_name' => $tableName,
                'indexes' => $indexes,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            $this->jsonResponse($data);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Recent queries
     */
    private function recentQueries()
    {
        $data = [
            'title' => 'Recent Queries',
            'message' => 'Query logging requires database configuration setup'
        ];

        $this->view('debug/recent_queries', $data);
    }

    /**
     * Slow queries
     */
    private function slowQueries()
    {
        $data = [
            'title' => 'Slow Queries',
            'message' => 'Slow query logging requires database configuration setup'
        ];

        $this->view('debug/slow_queries', $data);
    }

    /**
     * Check if debug mode is enabled
     */
    private function isDebugMode()
    {
        return defined('DEBUG') && DEBUG === true;
    }

    /**
     * Get cache recommendations
     */
    private function getCacheRecommendations($stats, $hitRate)
    {
        $recommendations = [];

        if ($hitRate < 80) {
            $recommendations[] = 'Cache hit rate is low. Consider increasing TTL or implementing cache warming.';
        }

        if (($stats['expired_files'] ?? 0) > ($stats['valid_files'] ?? 0)) {
            $recommendations[] = 'Many expired cache files. Consider adjusting TTL settings.';
        }

        if (($stats['total_size_mb'] ?? 0) > 100) {
            $recommendations[] = 'Cache size is large. Consider implementing cache eviction policies.';
        }

        return $recommendations;
    }

    /**
     * Calculate performance score
     */
    private function calculatePerformanceScore($metrics, $cacheStats, $hitRate)
    {
        $score = 100;

        // Deduct points for high memory usage
        $memoryPercent = ($metrics['memory']['current'] / $this->parseMemoryLimit(ini_get('memory_limit'))) * 100;
        if ($memoryPercent > 80) {
            $score -= 20;
        } elseif ($memoryPercent > 60) {
            $score -= 10;
        }

        // Deduct points for low cache hit rate
        if ($hitRate < 80) {
            $score -= 15;
        } elseif ($hitRate < 90) {
            $score -= 5;
        }

        // Deduct points for many expired cache files
        $expiredRatio = ($stats['expired_files'] ?? 0) / max(1, ($stats['total_files'] ?? 1));
        if ($expiredRatio > 0.5) {
            $score -= 10;
        }

        return max(0, $score);
    }

    /**
     * Parse memory limit string
     */
    private function parseMemoryLimit($limit)
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        switch ($unit) {
            case 'k':
                return $value * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'g':
                return $value * 1024 * 1024 * 1024;
            default:
                return $value;
        }
    }

    /**
     * Send JSON response
     */
    protected function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
