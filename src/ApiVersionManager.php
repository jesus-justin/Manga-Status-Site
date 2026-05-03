<?php
/**
 * API Version Manager
 *
 * Manages API versioning and backward compatibility layer.
 * Allows multiple API versions to coexist with migration paths.
 *
 * @version 2.0.0
 */

class ApiVersionManager {
    /**
     * Current API version
     */
    const CURRENT_VERSION = '2.0.0';

    /**
     * Supported API versions
     */
    private static $supportedVersions = ['v1', 'v2'];

    /**
     * Version features (what's available in each version)
     */
    private static $versionFeatures = [
        'v1' => [
            'endpoints' => [
                'GET /api/v1/user/profile',
                'GET /api/v1/user/collection',
                'GET /api/v1/manga/:id',
                'GET /api/v1/manga/search/:query',
                'POST /api/v1/progress/update',
                'DELETE /api/v1/progress/delete/:manga_id'
            ],
            'authentication' => 'session',
            'rate_limit' => 100,
            'deprecation' => 'Deprecated since 2.0.0',
            'sunset_date' => '2027-12-31'
        ],
        'v2' => [
            'endpoints' => [
                'GET /api/v2/user/profile',
                'GET /api/v2/user/collection',
                'GET /api/v2/user/stats',
                'GET /api/v2/manga/:id',
                'GET /api/v2/manga/search/:query',
                'POST /api/v2/progress/update',
                'DELETE /api/v2/progress/delete/:manga_id',
                'GET /api/v2/health',
                'POST /api/v2/auth/refresh'
            ],
            'authentication' => 'session',
            'rate_limit' => 200,
            'deprecation' => null,
            'features' => [
                'improved_filtering',
                'pagination',
                'sorting',
                'response_compression',
                'security_headers',
                'request_logging'
            ]
        ]
    ];

    /**
     * Extract version from request URL or header
     */
    public static function extractVersion($requestUri) {
        // Try to extract from URL path: /api/v2/...
        if (preg_match('/\/api\/(v\d+)\//', $requestUri, $matches)) {
            return $matches[1];
        }

        // Try header: X-API-Version: v2
        if (isset($_SERVER['HTTP_X_API_VERSION'])) {
            return $_SERVER['HTTP_X_API_VERSION'];
        }

        // Default to latest version
        return 'v2';
    }

    /**
     * Check if version is supported
     */
    public static function isSupported($version) {
        return in_array($version, self::$supportedVersions);
    }

    /**
     * Get version features
     */
    public static function getFeatures($version) {
        return self::$versionFeatures[$version] ?? [];
    }

    /**
     * Check if endpoint exists in version
     */
    public static function endpointExists($version, $method, $path) {
        $features = self::getFeatures($version);
        $endpoint = "$method /api/$version$path";
        return in_array($endpoint, $features['endpoints'] ?? []);
    }

    /**
     * Get deprecation info for version
     */
    public static function getDeprecationInfo($version) {
        $features = self::getFeatures($version);
        return [
            'deprecated' => $features['deprecation'] !== null,
            'message' => $features['deprecation'],
            'sunset_date' => $features['sunset_date'] ?? null
        ];
    }

    /**
     * Get rate limit for version
     */
    public static function getRateLimit($version) {
        $features = self::getFeatures($version);
        return $features['rate_limit'] ?? 100;
    }

    /**
     * Get all supported versions info
     */
    public static function getSupportedVersions() {
        $versions = [];
        foreach (self::$supportedVersions as $version) {
            $deprecation = self::getDeprecationInfo($version);
            $versions[$version] = [
                'current' => $version === 'v2',
                'endpoints' => count(self::getFeatures($version)['endpoints'] ?? []),
                'deprecated' => $deprecation['deprecated'],
                'deprecation_message' => $deprecation['message'],
                'sunset_date' => $deprecation['sunset_date'],
                'rate_limit' => self::getRateLimit($version),
                'features' => self::getFeatures($version)['features'] ?? []
            ];
        }
        return $versions;
    }

    /**
     * Convert v1 response to v2 format if needed
     */
    public static function convertResponse($version, $data) {
        if ($version === 'v1') {
            // V1 uses different response format
            // Add compatibility layer here
            return self::convertV1ToV2($data);
        }
        return $data;
    }

    /**
     * Convert v1 response structure to v2
     */
    private static function convertV1ToV2($data) {
        // Transform v1 structure to v2 if needed
        // This is a compatibility layer
        return [
            'success' => isset($data['status']) && $data['status'] === 'success',
            'data' => $data['data'] ?? $data,
            'status' => 200
        ];
    }

    /**
     * Send version deprecation header for deprecated versions
     */
    public static function sendDeprecationHeader($version) {
        $deprecation = self::getDeprecationInfo($version);
        if ($deprecation['deprecated']) {
            header('Deprecation: true');
            header('Sunset: ' . date('r', strtotime($deprecation['sunset_date'])));
            header('Warning: 299 - "API version ' . $version . ' is deprecated"');
        }
    }
}
