<?php
/**
 * Run All Migrations Script
 * Execute this file to run all pending migrations
 * 
 * Usage: php Database/migration/run_all_migrations.php
 * Or via browser: http://your-domain/Database/migration/run_all_migrations.php
 */

// Load database configuration from config.php
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
    <title>Database Migrations</title>
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
    echo "  DATABASE MIGRATION RUNNER\n";
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
    
    // Check for banners table migration
    echo "<span class='info'>[2/3] Checking banners table...</span>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'banners'");
    $bannersExists = $stmt->fetch();
    
    if ($bannersExists) {
        echo "<span class='warning'>⚠ Banners table already exists</span>\n";
        $stmt = $pdo->query("DESCRIBE banners");
        $structure = $stmt->fetchAll();
        echo "  Columns: " . count($structure) . "\n";
    } else {
        echo "<span class='info'>  Creating banners table...</span>\n";
        
        $sql = "CREATE TABLE `banners` (
            `id` int NOT NULL AUTO_INCREMENT,
            `image_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
            `clicks` int NOT NULL DEFAULT '0',
            `views` int NOT NULL DEFAULT '0',
            `status` enum('active','inactive') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
            `link_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $pdo->exec($sql);
        echo "<span class='success'>✓ Banners table created successfully</span>\n";
        
        // Verify
        $stmt = $pdo->query("DESCRIBE banners");
        $structure = $stmt->fetchAll();
        echo "  Columns created: " . count($structure) . "\n";
    }
    
    // Check for sellers table
    echo "<span class='info'>[3/5] Checking sellers table...</span>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'sellers'");
    $sellersExists = $stmt->fetch();

    if ($sellersExists) {
        echo "<span class='warning'>⚠ Sellers table already exists</span>\n";
    } else {
        echo "<span class='info'>  Creating sellers table...</span>\n";
        $sellersSql = file_get_contents(__DIR__ . '/create_sellers_table.sql');
        $sellersSql = preg_replace('/--.*$/m', '', $sellersSql);
        $sellersSql = preg_replace('/\/\*.*?\*\//s', '', $sellersSql);
        
        try {
            $pdo->exec($sellersSql);
            echo "<span class='success'>✓ Sellers table created successfully</span>\n";
        } catch (PDOException $e) {
            echo "<span class='warning'>⚠ Sellers: " . htmlspecialchars($e->getMessage()) . "</span>\n";
        }
    }

    // Check for seller_id in coupons
    echo "<span class='info'>[4/5] Checking seller_id in coupons table...</span>\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM coupons LIKE 'seller_id'");
        $sellerIdExists = $stmt->fetch();
        
        if (!$sellerIdExists) {
            echo "<span class='info'>  Adding seller_id to coupons table...</span>\n";
            // Use direct ALTER TABLE instead of prepared statement
            try {
                $pdo->exec("ALTER TABLE `coupons` ADD COLUMN `seller_id` int DEFAULT NULL AFTER `status`");
                $pdo->exec("ALTER TABLE `coupons` ADD KEY `idx_seller_id` (`seller_id`)");
                echo "<span class='success'>✓ seller_id added to coupons table</span>\n";
            } catch (PDOException $e2) {
                // Check if column already exists (race condition)
                if (strpos($e2->getMessage(), 'Duplicate column') !== false) {
                    echo "<span class='warning'>⚠ seller_id already exists in coupons table</span>\n";
                } else {
                    throw $e2;
                }
            }
        } else {
            echo "<span class='warning'>⚠ seller_id already exists in coupons table</span>\n";
        }
    } catch (PDOException $e) {
        echo "<span class='warning'>⚠ Coupons seller_id: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    }

    echo "\n";
    echo "<span class='info'>[5/5] Running other migrations...</span>\n";
    
    // Run other migration files if they exist
    $migrationDir = __DIR__;
    // Check for order_cancel_log table
    echo "<span class='info'>[3/4] Checking order_cancel_log table...</span>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'order_cancel_log'");
    $cancelLogExists = $stmt->fetch();

    if ($cancelLogExists) {
        echo "<span class='warning'>⚠ Order cancel log table already exists</span>\n";
    } else {
        echo "<span class='info'>  Creating order_cancel_log table...</span>\n";
        $cancelLogSql = file_get_contents(__DIR__ . '/create_order_cancel_log_table.sql');
        $cancelLogSql = preg_replace('/--.*$/m', '', $cancelLogSql);
        $cancelLogSql = preg_replace('/\/\*.*?\*\//s', '', $cancelLogSql);
        
        try {
            $pdo->exec($cancelLogSql);
            echo "<span class='success'>✓ Order cancel log table created successfully</span>\n";
        } catch (PDOException $e) {
            echo "<span class='warning'>⚠ Order cancel log: " . htmlspecialchars($e->getMessage()) . "</span>\n";
        }
    }

    echo "\n";
    echo "<span class='info'>[4/4] Running other migrations...</span>\n";

    $migrationFiles = [
        'add_subtype_column.sql',
        'add_is_digital_and_colors_columns.sql',
        'add_auth0_id_column.sql',
        'add_color_size_to_cart.sql',
        'add_color_size_to_order_items.sql',
        'add_scheduled_end_date.sql',
        'add_logo_url_to_sellers.sql',
        'add_seller_documents_and_approval.sql',
        'create_seller_tables.sql',
        'add_pending_status_to_products.sql',
        'add_seller_id_to_order_items.sql',
        'add_seller_id_to_order_cancel_log.sql',
        'create_ads_tables.sql',
        'create_stock_movement_log.sql',
        'add_seller_profile_fields.sql',
        'create_products_views_table.sql',
        'create_products_social_table.sql'
    ];
    
    $executed = 0;
    foreach ($migrationFiles as $file) {
        $filePath = $migrationDir . '/' . $file;
        if (file_exists($filePath)) {
            $sql = file_get_contents($filePath);
            // Remove comments and empty lines for cleaner execution
            $sql = preg_replace('/--.*$/m', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            if (!empty(trim($sql))) {
                try {
                    $pdo->exec($sql);
                    echo "<span class='success'>✓ Executed: {$file}</span>\n";
                    $executed++;
                } catch (PDOException $e) {
                    // Ignore "already exists" errors
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        echo "<span class='warning'>⚠ {$file}: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                    }
                }
            }
        }
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "<span class='success'>✅ MIGRATION COMPLETED SUCCESSFULLY!</span>\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n<span class='info'>Summary:</span>\n";
    echo "  - Banners table: " . ($bannersExists ? "Already exists" : "Created") . "\n";
    echo "  - Other migrations executed: {$executed}\n";
    echo "\n<span class='info'>You can now use the banner system in the admin panel.</span>\n";
    
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
