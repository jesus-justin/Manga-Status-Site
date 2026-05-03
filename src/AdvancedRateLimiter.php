<?php
/**
 * Advanced Rate Limiter Middleware
 *
 * Protects API endpoints against brute force attacks and DDoS
 * with configurable per-endpoint rate limits.
 *
 * @version 2.0.0
 */

class AdvancedRateLimiter {
    private $cacheDir = 'cache/rate_limit';
    private $logger;
    private $clientIp;
    private $defaultLimit = 100;     // Requests
    private $defaultWindow = 3600;   // Seconds (1 hour)

    /**
     * Endpoint-specific limits [endpoint => [limit, window]]
     */
    private $endpointLimits = [
        '/api/login' => [5, 900],           // 5 attempts per 15 minutes
        '/api/register' => [3, 3600],       // 3 registrations per hour
        '/api/password/reset' => [3, 3600], // 3 resets per hour
        '/api/user/profile' => [100, 3600],
        '/api/manga/search' => [50, 3600],
        '/api/progress/update' => [100, 3600],
    ];

    public function __construct($logger = null) {
        $this->logger = $logger ?? new Logger('rate_limiting');
        $this->clientIp = $this->getClientIp();

        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0700, true);
        }
    }

    /**
     * Check if request should be rate limited
     */
    public function isLimited($endpoint = null) {
        $endpoint = $endpoint ?? ($_SERVER['REQUEST_URI'] ?? '');

        // Get rate limit for this endpoint
        $limit = $this->defaultLimit;
        $window = $this->defaultWindow;

        if (isset($this->endpointLimits[$endpoint])) {
            [$limit, $window] = $this->endpointLimits[$endpoint];
        }

        // Get current usage
        $usage = $this->getUsage($endpoint, $limit, $window);

        // Check if limited
        if ($usage['count'] >= $limit) {
            $this->logger->warning('Rate limit exceeded', [
                'ip' => $this->clientIp,
                'endpoint' => $endpoint,
                'limit' => $limit,
                'window' => $window,
                'requests' => $usage['count'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            return true;
        }

        return false;
    }

    /**
     * Increment request counter
     */
    public function increment($endpoint = null) {
        $endpoint = $endpoint ?? ($_SERVER['REQUEST_URI'] ?? '');
        $key = $this->getKey($endpoint);

        $file = $this->cacheDir . '/' . md5($key);
        $data = $this->readData($file);

        $data['count'] = ($data['count'] ?? 0) + 1;
        $data['last_request'] = time();

        file_put_contents($file, json_encode($data));
    }

    /**
     * Get current usage statistics
     */
    public function getUsage($endpoint = null, $limit = null, $window = null) {
        $endpoint = $endpoint ?? ($_SERVER['REQUEST_URI'] ?? '');
        $limit = $limit ?? $this->defaultLimit;
        $window = $window ?? $this->defaultWindow;

        $key = $this->getKey($endpoint);
        $file = $this->cacheDir . '/' . md5($key);
        $data = $this->readData($file);

        $now = time();
        $elapsed = $now - ($data['created'] ?? $now);

        // Reset if window expired
        if ($elapsed > $window) {
            $data = ['count' => 0, 'created' => $now];
        }

        return [
            'count' => $data['count'] ?? 0,
            'limit' => $limit,
            'window' => $window,
            'elapsed' => $elapsed,
            'remaining' => max(0, $limit - ($data['count'] ?? 0)),
            'reset_in' => max(0, $window - $elapsed),
            'reset_at' => date('c', $now + max(0, $window - $elapsed))
        ];
    }

    /**
     * Get rate limit headers for response
     */
    public function getHeaders($endpoint = null) {
        $usage = $this->getUsage($endpoint);

        return [
            'X-RateLimit-Limit' => (string)$usage['limit'],
            'X-RateLimit-Remaining' => (string)$usage['remaining'],
            'X-RateLimit-Reset' => (string)ceil(time() + $usage['reset_in']),
            'X-RateLimit-Window' => (string)$usage['window']
        ];
    }

    /**
     * Send rate limit exceeded response
     */
    public function sendLimitExceededResponse($endpoint = null) {
        $usage = $this->getUsage($endpoint);

        $response = new ResponseFormatter();
        $response->error(
            'Rate limit exceeded',
            429,
            [
                'limit' => $usage['limit'],
                'window_seconds' => $usage['window'],
                'reset_in_seconds' => $usage['reset_in'],
                'retry_after' => $usage['reset_in']
            ]
        );
    }

    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * Generate cache key
     */
    private function getKey($endpoint) {
        return $this->clientIp . ':' . $endpoint;
    }

    /**
     * Read cache file data
     */
    private function readData($file) {
        if (!file_exists($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Reset rate limit for client
     */
    public function reset($endpoint = null) {
        $endpoint = $endpoint ?? ($_SERVER['REQUEST_URI'] ?? '');
        $key = $this->getKey($endpoint);
        $file = $this->cacheDir . '/' . md5($key);

        if (file_exists($file)) {
            unlink($file);
            $this->logger->info('Rate limit reset', ['ip' => $this->clientIp, 'endpoint' => $endpoint]);
        }
    }

    /**
     * Whitelist IP address (bypass rate limiting)
     */
    public function whitelist($ip) {
        $whitelistFile = $this->cacheDir . '/whitelist.json';
        $whitelist = [];

        if (file_exists($whitelistFile)) {
            $whitelist = json_decode(file_get_contents($whitelistFile), true) ?? [];
        }

        if (!in_array($ip, $whitelist)) {
            $whitelist[] = $ip;
            file_put_contents($whitelistFile, json_encode($whitelist));
            $this->logger->info("IP whitelisted: $ip");
        }
    }

    /**
     * Check if IP is whitelisted
     */
    public function isWhitelisted($ip = null) {
        $ip = $ip ?? $this->clientIp;
        $whitelistFile = $this->cacheDir . '/whitelist.json';

        if (!file_exists($whitelistFile)) {
            return false;
        }

        $whitelist = json_decode(file_get_contents($whitelistFile), true) ?? [];
        return in_array($ip, $whitelist);
    }

    /**
     * Get statistics
     */
    public function getStatistics() {
        $stats = [];
        $files = glob($this->cacheDir . '/*');

        foreach ($files as $file) {
            $basename = basename($file);
            if ($basename === 'whitelist.json') {
                continue;
            }

            $data = json_decode(file_get_contents($file), true) ?? [];
            $stats[] = [
                'key_hash' => $basename,
                'requests' => $data['count'] ?? 0,
                'last_request' => $data['last_request'] ?? 0,
                'age_seconds' => time() - ($data['created'] ?? 0)
            ];
        }

        usort($stats, fn($a, $b) => $b['requests'] <=> $a['requests']);

        return [
            'total_tracked' => count($stats),
            'top_clients' => array_slice($stats, 0, 10)
        ];
    }

    /**
     * Clean up old rate limit data
     */
    public function cleanup($maxAge = 86400) {
        $files = glob($this->cacheDir . '/*');
        $now = time();
        $cleaned = 0;

        foreach ($files as $file) {
            if (basename($file) === 'whitelist.json') {
                continue;
            }

            $mtime = filemtime($file);
            if ($now - $mtime > $maxAge) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        if ($cleaned > 0) {
            $this->logger->debug("Rate limit cleanup: $cleaned old entries removed");
        }

        return $cleaned;
    }
}

/**
 * Middleware function for use in API routes
 */
function checkRateLimit($endpoint = null, $sendResponse = true) {
    $limiter = new AdvancedRateLimiter();

    // Skip for whitelisted IPs
    if ($limiter->isWhitelisted()) {
        return true;
    }

    if ($limiter->isLimited($endpoint)) {
        if ($sendResponse) {
            $limiter->sendLimitExceededResponse($endpoint);
        }
        return false;
    }

    $limiter->increment($endpoint);
    return true;
}
?>
