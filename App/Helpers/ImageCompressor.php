<?php

namespace App\Helpers;

class ImageCompressor
{
    /**
     * Compress image to maximum 300KB
     * 
     * @param string $sourcePath Source image path
     * @param string $destinationPath Destination path
     * @param int $maxSizeKB Maximum size in KB (default 300)
     * @return bool|string Returns destination path on success, false on failure
     */
    public static function compressToMaxSize($sourcePath, $destinationPath, $maxSizeKB = 300)
    {
        if (!function_exists('getimagesize') || !function_exists('imagecreatefromjpeg')) {
            error_log('GD library not available for image compression');
            return false;
        }

        $info = getimagesize($sourcePath);
        if (!$info) {
            return false;
        }

        $width = $info[0];
        $height = $info[1];
        $mime = $info['mime'] ?? '';

        $maxSizeBytes = $maxSizeKB * 1024;
        $quality = 85;
        $maxDimension = 1920;

        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $image = self::createImageResource($sourcePath, $mime);
        if (!$image) {
            return false;
        }

        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime === 'image/png') {
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
            $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
            imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($optimizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tempPath = $destinationPath . '.tmp';
        imagejpeg($optimizedImage, $tempPath, $quality);

        imagedestroy($image);
        imagedestroy($optimizedImage);

        $fileSize = filesize($tempPath);

        if ($fileSize > $maxSizeBytes) {
            $quality = 60;
            $image = self::createImageResource($tempPath, 'image/jpeg');
            if ($image) {
                $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($optimizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $newWidth, $newHeight);
                
                while ($quality >= 30 && filesize($tempPath) > $maxSizeBytes) {
                    imagejpeg($optimizedImage, $tempPath, $quality);
                    $quality -= 10;
                }
                
                imagedestroy($image);
                imagedestroy($optimizedImage);
            }
        }

        if (file_exists($tempPath)) {
            rename($tempPath, $destinationPath);
            return $destinationPath;
        }

        return false;
    }

    /**
     * Create image resource from file
     */
    private static function createImageResource($filePath, $mime)
    {
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($filePath);
                }
                return false;
            case 'image/gif':
                return imagecreatefromgif($filePath);
            default:
                return false;
        }
    }

    /**
     * Get file size in KB
     */
    public static function getFileSizeKB($filePath)
    {
        if (!file_exists($filePath)) {
            return 0;
        }
        return round(filesize($filePath) / 1024, 2);
    }
}

