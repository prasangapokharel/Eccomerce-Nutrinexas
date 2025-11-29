<?php
/**
 * Run Product Views and Social Tables Migration
 * Execute this file to create products_views and products_social tables
 * 
 * Usage: php Database/migration/run_product_tables_migration.php
 * Or via browser: http://your-domain/Database/migration/run_product_tables_migration.php
 */

// Load database configuration
require_once __DIR__ . '/../../App/Config/config.php';

// Database credentials from config
$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;
$charset = 'utf8mb4';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Tables Migration</title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 20px; background: #1a1a1a; color: #0f0; line-height: 1.6; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .info { color: #0ff; }
        .section { margin: 10px 0; padding: 10px; background: #2a2a2a; border-left: 3px solid #0f0; }
    </style>
</head>
<body>
<pre>
<?php
try {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  PRODUCT VIEWS & SOCIAL TABLES MIGRATION\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    // Connect to database
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    echo "<span class='info'>[1/3] Connecting to database...</span>\n";
    echo "  Host: {$host}\n";
    echo "  Database: {$dbname}\n";
    echo "  User: {$username}\n";
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "<span class='success'>✓ Database connection established</span>\n\n";
    
    // Check for products_views table
    echo "<span class='info'>[2/3] Checking products_views table...</span>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'products_views'");
    $viewsTableExists = $stmt->fetch();
    
    if ($viewsTableExists) {
        echo "<span class='warning'>⚠ products_views table already exists</span>\n";
        $stmt = $pdo->query("DESCRIBE products_views");
        $structure = $stmt->fetchAll();
        echo "  Columns: " . count($structure) . "\n";
        foreach ($structure as $col) {
            echo "    - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "<span class='info'>  Creating products_views table...</span>\n";
        $viewsSql = file_get_contents(__DIR__ . '/create_products_views_table.sql');
        // Remove comments and clean SQL
        $viewsSql = preg_replace('/--.*$/m', '', $viewsSql);
        $viewsSql = preg_replace('/\/\*.*?\*\//s', '', $viewsSql);
        
        try {
            $pdo->exec($viewsSql);
            echo "<span class='success'>✓ products_views table created successfully</span>\n";
            
            // Verify
            $stmt = $pdo->query("DESCRIBE products_views");
            $structure = $stmt->fetchAll();
            echo "  Columns created: " . count($structure) . "\n";
            foreach ($structure as $col) {
                echo "    - {$col['Field']} ({$col['Type']})\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "<span class='error'>✗ Error creating products_views: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                throw $e;
            } else {
                echo "<span class='warning'>⚠ products_views table already exists</span>\n";
            }
        }
    }
    
    // Check for products_social table
    echo "\n<span class='info'>[3/3] Checking products_social table...</span>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'products_social'");
    $socialTableExists = $stmt->fetch();
    
    if ($socialTableExists) {
        echo "<span class='warning'>⚠ products_social table already exists</span>\n";
        $stmt = $pdo->query("DESCRIBE products_social");
        $structure = $stmt->fetchAll();
        echo "  Columns: " . count($structure) . "\n";
        foreach ($structure as $col) {
            echo "    - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "<span class='info'>  Creating products_social table...</span>\n";
        $socialSql = file_get_contents(__DIR__ . '/create_products_social_table.sql');
        // Remove comments and clean SQL
        $socialSql = preg_replace('/--.*$/m', '', $socialSql);
        $socialSql = preg_replace('/\/\*.*?\*\//s', '', $socialSql);
        
        try {
            $pdo->exec($socialSql);
            echo "<span class='success'>✓ products_social table created successfully</span>\n";
            
            // Verify
            $stmt = $pdo->query("DESCRIBE products_social");
            $structure = $stmt->fetchAll();
            echo "  Columns created: " . count($structure) . "\n";
            foreach ($structure as $col) {
                echo "    - {$col['Field']} ({$col['Type']})\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "<span class='error'>✗ Error creating products_social: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                throw $e;
            } else {
                echo "<span class='warning'>⚠ products_social table already exists</span>\n";
            }
        }
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "<span class='success'>✅ MIGRATION COMPLETED SUCCESSFULLY!</span>\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n<span class='info'>Summary:</span>\n";
    echo "  - products_views table: " . ($viewsTableExists ? "Already exists" : "Created") . "\n";
    echo "  - products_social table: " . ($socialTableExists ? "Already exists" : "Created") . "\n";
    echo "\n<span class='info'>Product view tracking and like/unlike system is now ready!</span>\n";
    
} catch (PDOException $e) {
    echo "\n<span class='error'>✗ Database Error:</span>\n";
    echo "<span class='error'>  " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\n<span class='info'>Check your database configuration in App/Config/config.php</span>\n";
} catch (Exception $e) {
    echo "\n<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
}
?>
</pre>
</body>
</html>




