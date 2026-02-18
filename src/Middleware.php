<?php
/**
 * Middleware System
 * 
 * Provides middleware pipeline for request processing
 */

class Middleware {
    private $middlewares = [];
    private $auth;
    private $rateLimiter;

    public function __construct($auth = null, $rateLimiter = null) {
        $this->auth = $auth;
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Add middleware to pipeline
     */
    public function add($name, $callback) {
        $this->middlewares[$name] = $callback;
        return $this;
    }

    /**
     * Run middleware pipeline
     */
    public function run($middlewares, $request = null) {
        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && isset($this->middlewares[$middleware])) {
                $result = call_user_func($this->middlewares[$middleware], $request);
                if ($result === false) {
                    return false;
                }
            } elseif (is_callable($middleware)) {
                $result = call_user_func($middleware, $request);
                if ($result === false) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Authentication middleware
     */
    public function authMiddleware() {
        return function($request) {
            if (!$this->auth || !$this->auth->isLoggedIn()) {
                http_response_code(401);
                if ($this->isAjaxRequest()) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                } else {
                    header('Location: login_fixed.php');
                }
                exit;
            }
            return true;
        };
    }

    /**
     * CSRF middleware
     */
    public function csrfMiddleware() {
        return function($request) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$this->auth || !$this->auth->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                    http_response_code(403);
                    if ($this->isAjaxRequest()) {
                        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                    } else {
                        die('Invalid CSRF token');
                    }
                    exit;
                }
            }
            return true;
        };
    }

    /**
     * Rate limiting middleware
     */
    public function rateLimitMiddleware($maxAttempts = 60, $decayMinutes = 1) {
        return function($request) use ($maxAttempts, $decayMinutes) {
            if (!$this->rateLimiter) {
                return true;
            }

            $key = $this->getRateLimitKey();
            
            if (!$this->rateLimiter->attempt($key, $maxAttempts, $decayMinutes)) {
                $retryAfter = $this->rateLimiter->availableIn($key);
                http_response_code(429);
                header("Retry-After: $retryAfter");
                
                if ($this->isAjaxRequest()) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Too many requests',
                        'retry_after' => $retryAfter
                    ]);
                } else {
                    die('Too many requests. Please try again later.');
                }
                exit;
            }
            return true;
        };
    }

    /**
     * Admin middleware
     */
    public function adminMiddleware() {
        return function($request) {
            if (!$this->auth || !$this->auth->isLoggedIn()) {
                http_response_code(401);
                header('Location: login_fixed.php');
                exit;
            }

            // Check if user is admin (user_id = 1)
            if ($_SESSION['user_id'] != 1) {
                http_response_code(403);
                die('Access denied. Admin only.');
            }
            return true;
        };
    }

    /**
     * JSON middleware
     */
    public function jsonMiddleware() {
        return function($request) {
            header('Content-Type: application/json');
            return true;
        };
    }

    /**
     * CORS middleware
     */
    public function corsMiddleware($allowedOrigins = ['*']) {
        return function($request) use ($allowedOrigins) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
            
            if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
                header("Access-Control-Allow-Credentials: true");
            }

            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit;
            }
            return true;
        };
    }

    /**
     * Logging middleware
     */
    public function loggingMiddleware($logger = null) {
        return function($request) use ($logger) {
            if ($logger) {
                $logger->info($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'], [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
            }
            return true;
        };
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get rate limit key
     */
    private function getRateLimitKey() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $ip . ':' . $uri;
    }

    /**
     * Create middleware group
     */
    public function group($name, array $middlewares) {
        $this->middlewares[$name] = function($request) use ($middlewares) {
            return $this->run($middlewares, $request);
        };
        return $this;
    }
}

?>
