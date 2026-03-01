<?php
/**
 * File Storage Manager
 * 
 * Manages file uploads and storage
 */

class FileStorage {
    private $storageDir;
    private $maxFileSize;
    private $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'text/plain',
        'application/msword', 'application/vnd.ms-excel'
    ];

    public function __construct($storageDir = 'storage/', $maxFileSize = 10485760) {
        $this->storageDir = $storageDir;
        $this->maxFileSize = $maxFileSize;
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Store uploaded file
     */
    public function store($file, $subdir = '') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error: ' . $file['error']];
        }

        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'error' => 'File too large'];
        }

        if (!in_array($file['type'], $this->allowedMimes)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }

        $dir = $this->storageDir . ($subdir ? $subdir . '/' : '');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = uniqid('file_', true) . '_' . basename($file['name']);
        $path = $dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $path)) {
            chmod($path, 0644);
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $path,
                'url' => str_replace(DIRECTORY_SEPARATOR, '/', $path)
            ];
        }

        return ['success' => false, 'error' => 'Failed to store file'];
    }

    /**
     * Delete file
     */
    public function delete($filepath) {
        if (file_exists($filepath) && unlink($filepath)) {
            return ['success' => true, 'message' => 'File deleted'];
        }
        return ['success' => false, 'error' => 'Failed to delete file'];
    }

    /**
     * Get file info
     */
    public function getInfo($filepath) {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        return [
            'success' => true,
            'size' => filesize($filepath),
            'mime' => mime_content_type($filepath),
            'modified' => filemtime($filepath),
            'exists' => true
        ];
    }

    /**
     * List files in directory
     */
    public function listFiles($subdir = '') {
        $dir = $this->storageDir . ($subdir ? $subdir . '/' : '');
        
        if (!is_dir($dir)) {
            return ['success' => false, 'error' => 'Directory not found'];
        }

        $files = [];
        $items = glob($dir . '*');
        
        foreach ($items as $item) {
            if (is_file($item)) {
                $files[] = [
                    'name' => basename($item),
                    'path' => str_replace(DIRECTORY_SEPARATOR, '/', $item),
                    'size' => filesize($item),
                    'modified' => filemtime($item)
                ];
            }
        }

        return ['success' => true, 'files' => $files];
    }

    /**
     * Get storage space used
     */
    public function getStorageUsage() {
        $size = $this->getDirSize($this->storageDir);
        return ['total_size' => $size, 'formatted' => $this->formatBytes($size)];
    }

    /**
     * Get directory size recursively
     */
    private function getDirSize($dir) {
        $size = 0;
        foreach (glob($dir . '*', GLOB_MARK) as $file) {
            if (substr($file, -1) === DIRECTORY_SEPARATOR) {
                $size += $this->getDirSize($file);
            } else {
                $size += filesize($file);
            }
        }
        return $size;
    }

    /**
     * Format bytes
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

?>
