<?php
namespace App\Helpers;

/**
 * File Helper
 * Provides file handling functionality
 */
class FileHelper
{
    /**
     * Upload a file
     *
     * @param array $file
     * @param string $directory
     * @param array $allowedTypes
     * @param int $maxSize
     * @return string|false
     */
    public static function upload($file, $directory, $allowedTypes = [], $maxSize = 5242880)
    {
        // Check if file was uploaded
        if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Check file type if specified
        if (!empty($allowedTypes)) {
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                return false;
            }
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . basename($file['name']);
        $destination = $directory . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }
        
        return false;
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    public static function delete($path)
    {
        if (file_exists($path)) {
            return unlink($path);
        }
        
        return false;
    }

    /**
     * Get file extension
     *
     * @param string $filename
     * @return string
     */
    public static function getExtension($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * Get file size in human-readable format
     *
     * @param string $path
     * @return string
     */
    public static function getSize($path)
    {
        if (!file_exists($path)) {
            return '0 B';
        }
        
        $size = filesize($path);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @return bool
     */
    public static function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Create a directory
     *
     * @param string $path
     * @param int $permissions
     * @param bool $recursive
     * @return bool
     */
    public static function createDirectory($path, $permissions = 0777, $recursive = true)
    {
        if (!file_exists($path)) {
            return mkdir($path, $permissions, $recursive);
        }
        
        return true;
    }

    /**
     * Get all files in a directory
     *
     * @param string $directory
     * @param string $extension
     * @return array
     */
    public static function getFiles($directory, $extension = '')
    {
        if (!file_exists($directory)) {
            return [];
        }
        
        $files = scandir($directory);
        $result = [];
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $path = $directory . '/' . $file;
            
            if (is_file($path)) {
                if (empty($extension) || self::getExtension($file) === $extension) {
                    $result[] = $file;
                }
            }
        }
        
        return $result;
    }
}
