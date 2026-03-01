<?php
/**
 * Permission & Role System
 * 
 * Handles user roles and permissions
 */

class Permission {
    private $conn;
    private $rolesList = [
        'admin' => ['manage_users', 'manage_content', 'view_analytics', 'system_settings'],
        'moderator' => ['manage_content', 'view_analytics'],
        'user' => ['edit_own_content', 'view_profile']
    ];

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($userId, $permission) {
        $stmt = $this->conn->prepare(
            "SELECT role FROM users WHERE id = ?"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            return false;
        }

        $role = $result['role'] ?? 'user';
        $permissions = $this->rolesList[$role] ?? [];
        
        return in_array($permission, $permissions);
    }

    /**
     * Get user role
     */
    public function getUserRole($userId) {
        $stmt = $this->conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['role'] ?? 'user';
    }

    /**
     * Get role permissions
     */
    public function getRolePermissions($role) {
        return $this->rolesList[$role] ?? [];
    }

    /**
     * Set user role
     */
    public function setUserRole($userId, $role) {
        if (!isset($this->rolesList[$role])) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $userId);
        return $stmt->execute();
    }

    /**
     * Add role
     */
    public function addRole($role, $permissions) {
        $this->rolesList[$role] = $permissions;
        return true;
    }

    /**
     * Check if is admin
     */
    public function isAdmin($userId) {
        return $this->getUserRole($userId) === 'admin';
    }

    /**
     * Check if is moderator
     */
    public function isModerator($userId) {
        return in_array($this->getUserRole($userId), ['moderator', 'admin']);
    }
}

?>
