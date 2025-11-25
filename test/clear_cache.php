<?php
/**
 * Clear Application Cache
 */

$cacheDir = __DIR__ . '/../App/storage/cache';

if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $deleted = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $deleted++;
        }
    }
    echo "Cache cleared: {$deleted} files deleted\n";
} else {
    echo "Cache directory not found\n";
}

