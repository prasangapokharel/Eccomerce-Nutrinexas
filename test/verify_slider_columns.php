<?php
require_once __DIR__ . '/../App/Config/config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

$db = Database::getInstance();
$cols = $db->query('SHOW COLUMNS FROM sliders')->all();

echo "Sliders table columns:\n";
foreach($cols as $c) {
    echo "  - {$c['Field']} ({$c['Type']}) " . ($c['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

