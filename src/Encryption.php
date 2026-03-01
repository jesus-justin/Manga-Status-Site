<?php
/**
 * Encryption Utility
 * 
 * Provides data encryption and decryption
 */

class Encryption {
    private $key;
    private $algorithm = 'AES-256-CBC';

    public function __construct($key = null) {
        if ($key === null) {
            $key = hash('sha256', defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default-key');
        }
        $this->key = $key;
    }

    /**
     * Encrypt data
     */
    public function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->algorithm));
        $encrypted = openssl_encrypt($data, $this->algorithm, $this->key, 0, $iv);
        
        // Combine IV and encrypted data
        $combined = base64_encode($iv . $encrypted);
        return $combined;
    }

    /**
     * Decrypt data
     */
    public function decrypt($data) {
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($this->algorithm);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        $decrypted = openssl_decrypt($encrypted, $this->algorithm, $this->key, 0, $iv);
        return $decrypted;
    }

    /**
     * Hash data (one-way)
     */
    public function hash($data, $algo = 'sha256') {
        return hash($algo, $data);
    }

    /**
     * Verify hash
     */
    public function verifyHash($data, $hash, $algo = 'sha256') {
        return hash_equals(hash($algo, $data), $hash);
    }

    /**
     * Generate random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generate random string
     */
    public static function generateRandomString($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $randomString;
    }
}

?>
