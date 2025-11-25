<?php
/**
 * ImageKit Configuration
 * Cloud image storage service configuration
 */

return [
    'public_key' => 'public_1jNeswPM/KBFLvseZCmkPjzUQ98=',
    'private_key' => 'private_QYeo+CnqoXK8a980wjSZfwXj2xo=',
    'url_endpoint' => 'https://ik.imagekit.io/nutrinexas/',
    'upload_path' => 'nutrinexus/reviews/', // Folder structure in ImageKit
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],
    'allowed_video_types' => ['mp4', 'webm', 'ogg'],
    'image_quality' => 75,
    'max_image_dimensions' => [
        'width' => 800,
        'height' => 600
    ]
];
