<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Database class
 * Handles database connections and queries
 */
class Database
{
    private static $instance = null;
    private $pdo;
    private $stmt;

    private function __construct()
    {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            // Persistent connections disabled for better stability
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO', wait_timeout=28800, interactive_timeout=28800",
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_COMPRESS => true,
        ];

        try {
            if (!defined('DEBUG') || !DEBUG) {
                error_log("Database: Connecting to " . DB_HOST . "/" . DB_NAME);
            }
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            if (!defined('DEBUG') || !DEBUG) {
                error_log("Database: Connection successful");
            }
            
            $this->pdo->query("SELECT 1");
        } catch (PDOException $e) {
            $errorMsg = "Database: Connection failed: " . $e->getMessage();
            error_log($errorMsg);
            
            if (defined('DEBUG') && DEBUG) {
                echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px; border-radius: 4px;'>";
                echo "<h3 style='color: #d32f2f; margin: 0 0 10px 0;'>Database Connection Error</h3>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
                echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
                echo "<p><strong>User:</strong> " . DB_USER . "</p>";
                echo "<p><strong>DSN:</strong> " . $dsn . "</p>";
                echo "</div>";
            }
            
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Prepare statement with query
     *
     * @param string $sql The SQL query
     * @param array $params Optional parameters to bind
     * @return $this
     */
    public function query($sql, $params = [])
    {
        $this->stmt = $this->pdo->prepare($sql);
        if (!empty($params)) {
            $this->bind($params);
        }
        return $this;
    }

    /**
     * Bind values to prepared statement
     * Supports both array of parameters and single parameter binding
     *
     * @param mixed $param Parameter key or array of parameters
     * @param mixed $value Parameter value (if $param is not array)
     * @param int $type PDO parameter type
     * @return $this
     */
    public function bind($param, $value = null, $type = null)
    {
        // If $param is an array, bind all parameters
        if (is_array($param)) {
            foreach ($param as $key => $val) {
                // Skip if value is an array (should be handled separately or converted to JSON)
                if (is_array($val)) {
                    $val = json_encode($val);
                }
                $paramType = $this->determineParamType($val);
                $this->stmt->bindValue(
                    is_numeric($key) ? $key + 1 : $key,
                    $val,
                    $paramType
                );
            }
        } else {
            // Single parameter binding
            if ($type === null) {
                $type = $this->determineParamType($value);
            }
            // Handle array values in single parameter binding
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $this->stmt->bindValue($param, $value, $type);
        }
        return $this;
    }

    /**
     * Execute the prepared statement
     *
     * @param array $params Optional parameters to bind
     * @return bool
     */
    public function execute($params = [])
    {
        if (!empty($params)) {
            $this->bind($params);
        }
        
        try {
            $startTime = microtime(true);
            $result = $this->stmt->execute();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log slow queries (> 100ms) in development
            if (defined('DEBUG') && DEBUG && $executionTime > 100) {
                error_log("Database: Slow query detected ({$executionTime}ms): " . substr($this->stmt->queryString, 0, 200));
            }
            
            if (!$result) {
                $errorInfo = $this->stmt->errorInfo();
                error_log("Database: Statement error: " . json_encode($errorInfo));
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("Database: Execute exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Determine PDO parameter type based on value
     */
    private function determineParamType($value)
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }
        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }

    /**
     * Get a single record
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all records
     */
    public function all()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alias for all() method - for backward compatibility
     */
    public function resultSet()
    {
        return $this->all();
    }

    /**
     * Get row count
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if currently in a transaction
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Alias for rollBack() method - for backward compatibility
     */
    /**
     * Get the PDO instance
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Execute a raw query and return statement
     * Useful for operations that don't need parameter binding
     */
    public function rawQuery($sql)
    {
        return $this->pdo->query($sql);
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}