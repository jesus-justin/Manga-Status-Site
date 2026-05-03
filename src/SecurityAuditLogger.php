<?php
/**
 * Security Audit Logger
 *
 * Comprehensive security event logging for compliance, forensics,
 * and threat detection. Logs all security-relevant events.
 *
 * @version 2.0.0
 */

class SecurityAuditLogger {
    private $logger;
    private $clientIp;

    public function __construct() {
        $this->logger = new Logger('security_audit');
        $this->clientIp = $this->getClientIp();
    }

    /**
     * Log authentication attempt
     */
    public function logAuthenticationAttempt($username, $success, $reason = null) {
        $level = $success ? 'info' : 'warning';
        $status = $success ? 'SUCCESS' : 'FAILED';

        $this->logger->log($level, "[AUTH] Authentication attempt - $status", [
            'event_type' => 'authentication_attempt',
            'username' => $username,
            'success' => $success,
            'reason' => $reason,
            'ip_address' => $this->clientIp,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id()
        ]);
    }

    /**
     * Log login success
     */
    public function logLogin($userId, $username, $remember = false) {
        $this->logger->info("[LOGIN] User logged in", [
            'event_type' => 'login_success',
            'user_id' => $userId,
            'username' => $username,
            'remember_me' => $remember,
            'ip_address' => $this->clientIp,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log login failure
     */
    public function logLoginFailure($username, $reason = 'Invalid credentials') {
        $this->logger->warning("[LOGIN_FAILED] Login attempt failed", [
            'event_type' => 'login_failure',
            'username' => $username,
            'reason' => $reason,
            'ip_address' => $this->clientIp,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log logout
     */
    public function logLogout($userId, $username) {
        $this->logger->info("[LOGOUT] User logged out", [
            'event_type' => 'logout',
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log failed login attempt (rate limit)
     */
    public function logFailedAttempt($username, $attemptNumber, $remaining) {
        $this->logger->warning("[LOGIN_ATTEMPTS] Failed login attempt", [
            'event_type' => 'failed_attempt',
            'username' => $username,
            'attempt_number' => $attemptNumber,
            'remaining_attempts' => $remaining,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log account locked due to failed attempts
     */
    public function logAccountLocked($username) {
        $this->logger->error("[ACCOUNT_LOCKED] Account locked due to failed attempts", [
            'event_type' => 'account_locked',
            'username' => $username,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log password change
     */
    public function logPasswordChange($userId, $username, $success = true) {
        $level = $success ? 'info' : 'warning';
        $this->logger->log($level, "[PASSWORD_CHANGE] Password changed", [
            'event_type' => 'password_change',
            'user_id' => $userId,
            'username' => $username,
            'success' => $success,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log password reset request
     */
    public function logPasswordResetRequest($username, $email) {
        $this->logger->info("[PASSWORD_RESET_REQUEST] Password reset requested", [
            'event_type' => 'password_reset_request',
            'username' => $username,
            'email' => $this->maskEmail($email),
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log successful data modification
     */
    public function logDataModification($userId, $action, $entity, $entityId, $changes = []) {
        $this->logger->info("[DATA_MODIFICATION] $action on $entity", [
            'event_type' => 'data_modification',
            'action' => $action,
            'user_id' => $userId,
            'entity' => $entity,
            'entity_id' => $entityId,
            'changes' => $changes,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log permission denied
     */
    public function logPermissionDenied($userId, $resource, $action, $reason = '') {
        $this->logger->warning("[PERMISSION_DENIED] Access denied", [
            'event_type' => 'permission_denied',
            'user_id' => $userId,
            'resource' => $resource,
            'action' => $action,
            'reason' => $reason,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log CSRF token validation failure
     */
    public function logCsrfValidationFailure() {
        $this->logger->error("[CSRF_FAILED] CSRF token validation failed", [
            'event_type' => 'csrf_validation_failure',
            'ip_address' => $this->clientIp,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            'path' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log suspicious activity detection
     */
    public function logSuspiciousActivity($activityType, $details = []) {
        $this->logger->warning("[SUSPICIOUS_ACTIVITY] Potential security threat", array_merge([
            'event_type' => 'suspicious_activity',
            'activity_type' => $activityType,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ], $details));
    }

    /**
     * Log SQL injection attempt
     */
    public function logSqlInjectionAttempt($input, $field) {
        $this->logger->error("[SQL_INJECTION_ATTEMPT] Possible SQL injection detected", [
            'event_type' => 'sql_injection_attempt',
            'field' => $field,
            'input' => substr($input, 0, 100),
            'ip_address' => $this->clientIp,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log XSS attempt
     */
    public function logXssAttempt($input, $field) {
        $this->logger->error("[XSS_ATTEMPT] Possible XSS attempt detected", [
            'event_type' => 'xss_attempt',
            'field' => $field,
            'input' => substr($input, 0, 100),
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log API key violation
     */
    public function logApiKeyViolation($reason, $details = []) {
        $this->logger->warning("[API_KEY_VIOLATION] Invalid API key usage", array_merge([
            'event_type' => 'api_key_violation',
            'reason' => $reason,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ], $details));
    }

    /**
     * Log rate limit violation
     */
    public function logRateLimitViolation($endpoint, $limit, $window) {
        $this->logger->warning("[RATE_LIMIT_VIOLATION] Rate limit exceeded", [
            'event_type' => 'rate_limit_violation',
            'endpoint' => $endpoint,
            'limit' => $limit,
            'window_seconds' => $window,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log admin action
     */
    public function logAdminAction($userId, $action, $target, $details = []) {
        $this->logger->info("[ADMIN_ACTION] Admin action performed", array_merge([
            'event_type' => 'admin_action',
            'admin_user_id' => $userId,
            'action' => $action,
            'target' => $target,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ], $details));
    }

    /**
     * Log security configuration change
     */
    public function logSecurityConfigChange($setting, $oldValue, $newValue, $changedBy) {
        $this->logger->warning("[SECURITY_CONFIG_CHANGE] Security configuration changed", [
            'event_type' => 'security_config_change',
            'setting' => $setting,
            'old_value' => $this->maskSensitive($oldValue),
            'new_value' => $this->maskSensitive($newValue),
            'changed_by' => $changedBy,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log file upload
     */
    public function logFileUpload($userId, $filename, $filesize, $mimeType) {
        $this->logger->info("[FILE_UPLOAD] File uploaded", [
            'event_type' => 'file_upload',
            'user_id' => $userId,
            'filename' => $filename,
            'filesize' => $filesize,
            'mime_type' => $mimeType,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log failed file upload
     */
    public function logFailedFileUpload($userId, $filename, $reason) {
        $this->logger->warning("[FILE_UPLOAD_FAILED] File upload failed", [
            'event_type' => 'file_upload_failed',
            'user_id' => $userId,
            'filename' => $filename,
            'reason' => $reason,
            'ip_address' => $this->clientIp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
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

        return 'unknown';
    }

    /**
     * Mask sensitive information
     */
    private function maskSensitive($value) {
        if (strlen($value) < 4) {
            return '***';
        }
        return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
    }

    /**
     * Mask email address
     */
    private function maskEmail($email) {
        if (strpos($email, '@') === false) {
            return '***';
        }

        list($local, $domain) = explode('@', $email);
        $localMasked = substr($local, 0, 2) . str_repeat('*', max(1, strlen($local) - 4)) . 
                      (strlen($local) > 4 ? substr($local, -2) : '');

        return $localMasked . '@' . $domain;
    }

    /**
     * Generate security report
     */
    public function generateSecurityReport($days = 7) {
        // This would generate statistics from the security audit logs
        // For now, return placeholder
        return [
            'period_days' => $days,
            'generated_at' => date('Y-m-d H:i:s'),
            'report_type' => 'security_audit_summary'
        ];
    }
}
?>
