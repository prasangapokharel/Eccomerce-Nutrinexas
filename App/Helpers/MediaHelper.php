<?php

namespace App\Helpers;

class MediaHelper
{
    /**
     * Check if URL is a video file
     *
     * @param string $url
     * @return bool
     */
    public static function isVideo($url)
    {
        if (empty($url)) {
            return false;
        }
        
        $videoExtensions = ['mp4', 'webm', 'ogg', 'mov', 'avi'];
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        return in_array($extension, $videoExtensions);
    }

    /**
     * Get media type (image or video)
     *
     * @param string $url
     * @return string 'image' or 'video'
     */
    public static function getMediaType($url)
    {
        return self::isVideo($url) ? 'video' : 'image';
    }

    /**
     * Get MIME type from URL extension
     *
     * @param string $url
     * @return string
     */
    public static function getMimeType($url)
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        
        return $mimeTypes[$extension] ?? 'image/jpeg';
    }
}

