<?php
/**
 * Cleanup Script: Remove root cache folder if empty
 * Cache should be in App/storage/cache/ only
 */

define('ROOT', dirname(__DIR__));
$rootCacheDir = ROOT . DIRECTORY_SEPARATOR . 'cache';
$storageCacheDir = ROOT . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';

echo "=== Cache Folder Cleanup ===\n\n";

// Check root cache folder
if (is_dir($rootCacheDir)) {
    $files = array_diff(scandir($rootCacheDir), ['.', '..']);
    
    if (empty($files)) {
        // Empty folder, safe to remove
        if (rmdir($rootCacheDir)) {
            echo "✅ Removed empty cache/ folder from root\n";
        } else {
            echo "⚠️  Could not remove cache/ folder (may need permissions)\n";
        }
    } else {
        echo "⚠️  cache/ folder in root has files. Please move them to App/storage/cache/ manually.\n";
        echo "   Files found: " . count($files) . "\n";
    }
} else {
    echo "✅ No cache/ folder in root (already clean)\n";
}

// Ensure storage cache exists
if (!is_dir($storageCacheDir)) {
    if (mkdir($storageCacheDir, 0755, true)) {
        echo "✅ Created App/storage/cache/ folder\n";
    } else {
        echo "❌ Could not create App/storage/cache/ folder\n";
    }
} else {
    echo "✅ App/storage/cache/ folder exists\n";
}

echo "\n=== Cleanup Complete ===\n";

