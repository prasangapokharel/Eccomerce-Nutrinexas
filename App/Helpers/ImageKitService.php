<?php
namespace App\Helpers;

use ImageKit\ImageKit;

class ImageKitService
{
    private $imageKit;
    private $config;
    
    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/Config/imagekit.php';
        
        $this->imageKit = new ImageKit(
            $this->config['public_key'],
            $this->config['private_key'],
            $this->config['url_endpoint']
        );
    }
    
    /**
     * Upload image to ImageKit with compression and optimization
     * 
     * @param string $filePath Temporary file path
     * @param string $fileName Original filename
     * @return array|false Returns array with URL and fileId on success, false on failure
     */
    public function uploadImage($filePath, $fileName)
    {
        try {
            // Compress and optimize image before upload
            $optimizedPath = $this->compressAndOptimizeImage($filePath);
            
            if (!$optimizedPath) {
                return false;
            }
            
            // Generate unique filename
            $uniqueName = uniqid('img_') . '_' . time() . '.jpg';
            $uploadPath = $this->config['upload_path'] . $uniqueName;
            
            // Upload to ImageKit
            $result = $this->imageKit->upload([
                'file' => fopen($optimizedPath, 'r'),
                'fileName' => $uniqueName,
                'folder' => $this->config['upload_path'],
                'useUniqueFileName' => false,
                'tags' => ['nutrinexus', 'review', 'product'],
                'transformation' => [
                    'quality' => $this->config['image_quality'],
                    'format' => 'jpg'
                ]
            ]);
            
            // Clean up temporary optimized file
            if (file_exists($optimizedPath) && $optimizedPath !== $filePath) {
                unlink($optimizedPath);
            }
            
            if ($result && isset($result->result)) {
                return [
                    'url' => $result->result->url,
                    'fileId' => $result->result->fileId,
                    'fileName' => $uniqueName
                ];
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log("ImageKit upload failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload video to ImageKit
     * 
     * @param string $filePath Temporary file path
     * @param string $fileName Original filename
     * @return array|false Returns array with URL and fileId on success, false on failure
     */
    public function uploadVideo($filePath, $fileName)
    {
        try {
            // Check file size
            if (filesize($filePath) > $this->config['max_file_size']) {
                return false;
            }
            
            // Generate unique filename
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $uniqueName = uniqid('vid_') . '_' . time() . '.' . $extension;
            $uploadPath = $this->config['upload_path'] . $uniqueName;
            
            // Upload to ImageKit
            $result = $this->imageKit->upload([
                'file' => fopen($filePath, 'r'),
                'fileName' => $uniqueName,
                'folder' => $this->config['upload_path'],
                'useUniqueFileName' => false,
                'tags' => ['nutrinexus', 'review', 'video']
            ]);
            
            if ($result && isset($result->result)) {
                return [
                    'url' => $result->result->url,
                    'fileId' => $result->result->fileId,
                    'fileName' => $uniqueName
                ];
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log("ImageKit video upload failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Compress and optimize image before upload
     * 
     * @param string $sourcePath Source image path
     * @return string|false Returns optimized image path or false on failure
     */
    private function compressAndOptimizeImage($sourcePath)
    {
        try {
            $info = getimagesize($sourcePath);
            if (!$info) {
                return false;
            }
            
            $width = $info[0];
            $height = $info[1];
            $mime = $info['mime'] ?? '';
            
            // Calculate new dimensions maintaining aspect ratio
            $maxWidth = $this->config['max_image_dimensions']['width'];
            $maxHeight = $this->config['max_image_dimensions']['height'];
            
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = round($width * $ratio);
                $newHeight = round($height * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }
            
            // Create image resource based on type
            $image = $this->createImageResource($sourcePath, $mime);
            if (!$image) {
                return false;
            }
            
            // Create optimized image
            $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency for PNG
            if ($mime === 'image/png') {
                imagealphablending($optimizedImage, false);
                imagesavealpha($optimizedImage, true);
                $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
                imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Resize image
            imagecopyresampled($optimizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save optimized image
            $tempPath = tempnam(sys_get_temp_dir(), 'optimized_') . '.jpg';
            imagejpeg($optimizedImage, $tempPath, $this->config['image_quality']);
            
            // Clean up
            imagedestroy($image);
            imagedestroy($optimizedImage);
            
            return $tempPath;
            
        } catch (\Exception $e) {
            error_log("Image optimization failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create image resource from file
     * 
     * @param string $filePath Image file path
     * @param string $mime MIME type
     * @return resource|false Returns image resource or false on failure
     */
    private function createImageResource($filePath, $mime)
    {
        switch ($mime) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    return imagecreatefromwebp($filePath);
                }
                return false;
            default:
                return false;
        }
    }
    
    /**
     * Delete file from ImageKit
     * 
     * @param string $fileId ImageKit file ID
     * @return bool Returns true on success, false on failure
     */
    public function deleteFile($fileId)
    {
        try {
            $result = $this->imageKit->deleteFile($fileId);
            return $result && isset($result->result) && $result->result === 'deleted';
        } catch (\Exception $e) {
            error_log("ImageKit delete failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get ImageKit configuration
     * 
     * @return array Configuration array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
