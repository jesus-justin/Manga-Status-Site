<?php
/**
 * Simple File-Based Cache
 * 
 * Provides basic caching functionality without external dependencies.
 */

class Cache {
    private $filepath;
    private $duration;

    public function __construct($duration = CACHE_DURATION) {
        $this->duration = $duration;
        $this->ensureCacheDirectory();
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory() {
        if (!is_dir(CACHE_PATH)) {
            mkdir(CACHE_PATH, 0755, true);
        }
    }

    /**
     * Get cache file path from key
     */
    private function getCachePath($key) {
        return CACHE_PATH . '/' . md5($key) . '.cache';
    }

    /**
     * Store value in cache
     */
    public function set($key, $value, $duration = null) {
        $duration = $duration ?? $this->duration;
        $filepath = $this->getCachePath($key);
        
        $data = [
            'value' => $value,
            'expiry' => time() + $duration
        ];
        
        file_put_contents($filepath, serialize($data));
        return true;
    }

    /**
     * Get value from cache
     */
    public function get($key, $default = null) {
        $filepath = $this->getCachePath($key);
        
        if (!file_exists($filepath)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($filepath));
        
        if ($data['expiry'] < time()) {
            unlink($filepath);
            return $default;
        }
        
        return $data['value'];
    }

    /**
     * Check if key exists and is valid
     */
    public function has($key) {
        return $this->get($key) !== null;
    }

    /**
     * Delete cache entry
     */
    public function delete($key) {
        $filepath = $this->getCachePath($key);
        if (file_exists($filepath)) {
            unlink($filepath);
            return true;
        }
        return false;
    }

    /**
     * Clear all cache
     */
    public function flush() {
        $files = glob(CACHE_PATH . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    /**
     * Clean expired entries
     */
    public function cleanup() {
        $files = glob(CACHE_PATH . '/*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = unserialize(file_get_contents($file));
            if ($data['expiry'] < time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}

?>
