<?php
/**
 * Image Processor Class
 * 
 * Provides image upload and processing utilities.
 */

class ImageProcessor {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;

    public function __construct() {
        $this->uploadDir = UPLOAD_DIR;
        $this->allowedTypes = ALLOWED_IMAGE_TYPES ? explode(',', ALLOWED_IMAGE_TYPES) : ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->maxFileSize = MAX_UPLOAD_SIZE ?? 5242880; // 5MB
    }

    /**
     * Validate uploaded file
     */
    public function validate($file) {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload error: ' . ($file['error'] ?? 'Unknown')];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'File size exceeds maximum limit'];
        }

        // Check MIME type
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }

        // Check extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedTypes)) {
            return ['valid' => false, 'error' => 'File extension not allowed'];
        }

        return ['valid' => true];
    }

    /**
     * Upload image
     */
    public function upload($file, $directory = '') {
        $validation = $this->validate($file);
        if (!$validation['valid']) {
            return $validation;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('img_', true) . '.' . $ext;
        
        $uploadPath = $this->uploadDir . ($directory ? $directory . '/' : '');
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fullPath = $uploadPath . $filename;
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            chmod($fullPath, 0644);
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $uploadPath,
                'url' => str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', realpath($fullPath))
            ];
        }

        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }

    /**
     * Delete image
     */
    public function delete($filepath) {
        if (file_exists($filepath) && unlink($filepath)) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Failed to delete file'];
    }

    /**
     * Resize image
     */
    public function resize($sourcePath, $width, $height, $quality = 85) {
        if (!extension_loaded('gd')) {
            return ['success' => false, 'error' => 'GD extension not loaded'];
        }

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $image = imagecreatefrompng($sourcePath);
                break;
            case 'gif':
                $image = imagecreatefromgif($sourcePath);
                break;
            default:
                return ['success' => false, 'error' => 'Unsupported image format'];
        }

        $newImage = imagescale($image, $width, $height);
        $resizedPath = str_replace('.' . $ext, '_resized.' . $ext, $sourcePath);

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($newImage, $resizedPath, $quality);
                break;
            case 'png':
                imagepng($newImage, $resizedPath);
                break;
            case 'gif':
                imagegif($newImage, $resizedPath);
                break;
        }

        imagedestroy($image);
        imagedestroy($newImage);

        return ['success' => true, 'path' => $resizedPath];
    }

    /**
     * Get image info
     */
    public function getImageInfo($filepath) {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        $info = getimagesize($filepath);
        if ($info === false) {
            return ['success' => false, 'error' => 'Unable to retrieve image information'];
        }

        return [
            'success' => true,
            'width' => $info[0],
            'height' => $info[1],
            'type' => $info[2],
            'mime' => $info['mime'],
            'size' => filesize($filepath)
        ];
    }
}

?>
