<?php
/**
 * API Router Class
 * 
 * Provides routing and middleware support for API endpoints.
 */

class Router {
    private $routes = [];
    private $middlewares = [];
    private $params = [];

    /**
     * Register GET route
     */
    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
        return $this;
    }

    /**
     * Register POST route
     */
    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
        return $this;
    }

    /**
     * Register PUT route
     */
    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
        return $this;
    }

    /**
     * Register DELETE route
     */
    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
        return $this;
    }

    /**
     * Add middleware
     */
    public function middleware($name, $callback) {
        $this->middlewares[$name] = $callback;
        return $this;
    }

    /**
     * Register route
     */
    private function addRoute($method, $path, $callback) {
        $pattern = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }
        
        $this->routes[$method][$pattern] = $callback;
    }

    /**
     * Match and dispatch request
     */
    public function dispatch($method, $uri) {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $uri);
        $uri = '/' . ltrim($uri, '/');\n\n        if (!isset($this->routes[$method])) {\n            return ['success' => false, 'status' => 405, 'error' => 'Method not allowed'];\n        }\n\n        foreach ($this->routes[$method] as $pattern => $callback) {\n            if (preg_match($pattern, $uri, $matches)) {\n                // Extract named parameters\n                foreach ($matches as $key => $value) {\n                    if (!is_numeric($key)) {\n                        $this->params[$key] = $value;\n                    }\n                }\n                \n                return call_user_func($callback, $this->params);\n            }\n        }\n\n        return ['success' => false, 'status' => 404, 'error' => 'Route not found'];\n    }\n\n    /**\n     * Get parameter\n     */\n    public function getParam($key, $default = null) {\n        return $this->params[$key] ?? $default;\n    }\n\n    /**\n     * Get all parameters\n     */\n    public function getParams() {\n        return $this->params;\n    }\n\n    /**\n     * Call middleware\n     */\n    public function callMiddleware($name, ...$args) {\n        if (isset($this->middlewares[$name])) {\n            return call_user_func($this->middlewares[$name], ...$args);\n        }\n        return true;\n    }\n}\n\n?>\n"