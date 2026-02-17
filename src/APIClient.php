<?php
/**
 * API Client Class
 * 
 * Provides HTTP client for external API calls.
 */

class APIClient {
    private $timeout;
    private $userAgent;

    public function __construct($timeout = 10) {
        $this->timeout = $timeout;
        $this->userAgent = APP_NAME . '/' . APP_VERSION ?? '1.0';
    }

    /**
     * Make HTTP GET request
     */
    public function get($url, $headers = []) {
        return $this->request('GET', $url, null, $headers);
    }

    /**
     * Make HTTP POST request
     */
    public function post($url, $data = [], $headers = []) {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * Make HTTP PUT request
     */
    public function put($url, $data = [], $headers = []) {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * Make HTTP DELETE request
     */
    public function delete($url, $headers = []) {
        return $this->request('DELETE', $url, null, $headers);
    }

    /**
     * Make HTTP request
     */
    private function request($method, $url, $data = null, $headers = []) {
        if (!extension_loaded('curl')) {
            return ['success' => false, 'error' => 'cURL extension not loaded'];
        }

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        // Set HTTP method
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        // Set headers
        $headerArray = ['User-Agent: ' . $this->userAgent];
        foreach ($headers as $key => $value) {
            $headerArray[] = "$key: $value";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        // Try to parse as JSON
        $json = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'data' => $json ?? $response,
            'raw' => $response
        ];
    }

    /**
     * Fetch JSON from URL
     */
    public function fetchJSON($url) {
        $result = $this->get($url, ['Accept: application/json']);
        if (!$result['success']) {
            return $result;
        }
        return [
            'success' => true,
            'data' => is_array($result['data']) ? $result['data'] : json_decode($result['raw'], true)
        ];
    }

    /**
     * Download file
     */
    public function download($url, $savePath) {
        if (!extension_loaded('curl')) {
            return ['success' => false, 'error' => 'cURL extension not loaded'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            return ['success' => false, 'error' => $error ?: 'HTTP ' . $httpCode];
        }

        if (file_put_contents($savePath, $data) === false) {
            return ['success' => false, 'error' => 'Failed to write file'];
        }

        return ['success' => true, 'path' => $savePath, 'size' => strlen($data)];
    }
}

?>
