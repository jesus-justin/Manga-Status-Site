<?php
/**
 * Two-Factor Authentication Helper
 * 
 * Provides 2FA/MFA utilities using TOTP
 */

class TwoFactorAuth {
    private $issuer = 'MangaLibrary';
    private $algorithm = 'sha1';
    private $windowSize = 1;
    private $codeLength = 6;

    /**
     * Generate secret key
     */
    public function generateSecret() {
        $randomBytes = random_bytes(32);
        return $this->base32Encode($randomBytes);
    }

    /**
     * Get provisioning URI for QR code
     */
    public function getQRCodeUri($email, $secret) {
        $label = $this->issuer . ' (' . $email . ')';
        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s',
            urlencode($label),
            $secret,
            urlencode($this->issuer)
        );
    }

    /**
     * Verify code
     */
    public function verifyCode($secret, $code) {
        $decodedSecret = $this->base32Decode($secret);
        $timeWindow = floor(time() / 30);
        
        for ($i = -$this->windowSize; $i <= $this->windowSize; $i++) {
            if ($this->generateCode($decodedSecret, $timeWindow + $i) === (int)$code) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate TOTP code
     */
    private function generateCode($secret, $time) {
        $timeBytes = pack('J', $time);
        $hmac = hash_hmac($this->algorithm, $timeBytes, $secret, true);
        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0f;
        $code = (ord($hmac[$offset]) & 0x7f) << 24 |
                (ord($hmac[$offset + 1]) & 0xff) << 16 |
                (ord($hmac[$offset + 2]) & 0xff) << 8 |
                (ord($hmac[$offset + 3]) & 0xff);
        
        return $code % pow(10, $this->codeLength);
    }

    /**
     * Base32 encode
     */
    private function base32Encode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $bitsCollected = 0;
        
        foreach (str_split($data) as $c) {
            $v = ($v << 8) | ord($c);
            $bitsCollected += 8;
            
            while ($bitsCollected >= 5) {
                $bitsCollected -= 5;
                $output .= $alphabet[($v >> $bitsCollected) & 31];
            }
        }
        
        if ($bitsCollected > 0) {
            $output .= $alphabet[($v << (5 - $bitsCollected)) & 31];
        }
        
        return $output;
    }

    /**
     * Base32 decode
     */
    private function base32Decode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $bitsCollected = 0;
        
        foreach (str_split($data) as $c) {
            $v = ($v << 5) | strpos($alphabet, $c);
            $bitsCollected += 5;
            
            if ($bitsCollected >= 8) {
                $bitsCollected -= 8;
                $output .= chr(($v >> $bitsCollected) & 255);
            }
        }
        
        return $output;
    }
}

?>
