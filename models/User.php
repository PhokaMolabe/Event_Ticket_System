<?php

class User {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = Config::getInstance();
    }
    
    public function create($data) {
        $validation = $this->validateUserData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $uuid = Ramsey\Uuid\Uuid::uuid4()->toString();
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $userData = [
            'uuid' => $uuid,
            'email' => strtolower(trim($data['email'])),
            'password_hash' => $passwordHash,
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'phone' => $data['phone'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'status' => 'active'
        ];
        
        try {
            $userId = $this->db->insert('users', $userData);
            
            // Assign default role
            $this->assignRole($userId, 2); // Assuming role 2 is 'user'
            
            // Log activity
            $this->logActivity($userId, 'user_created', 'user', $userId);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'uuid' => $uuid
            ];
        } catch (Exception $e) {
            error_log("User creation failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'User creation failed'];
        }
    }
    
    public function authenticate($email, $password) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? AND status = 'active'",
            [strtolower(trim($email))]
        );
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Update last login
        $this->db->update('users', 
            ['last_login_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$user['id']]
        );
        
        // Log activity
        $this->logActivity($user['id'], 'user_login', 'user', $user['id']);
        
        return [
            'success' => true,
            'user' => $this->sanitizeUserData($user)
        ];
    }
    
    public function getById($id) {
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) {
            return null;
        }
        return $this->sanitizeUserData($user);
    }
    
    public function getByUuid($uuid) {
        $user = $this->db->fetch("SELECT * FROM users WHERE uuid = ?", [$uuid]);
        if (!$user) {
            return null;
        }
        return $this->sanitizeUserData($user);
    }
    
    public function getByEmail($email) {
        $user = $this->db->fetch("SELECT * FROM users WHERE email = ?", [strtolower(trim($email))]);
        if (!$user) {
            return null;
        }
        return $this->sanitizeUserData($user);
    }
    
    public function update($id, $data) {
        $allowedFields = ['first_name', 'last_name', 'phone', 'date_of_birth', 'avatar_url'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = trim($data[$field]);
            }
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'error' => 'No valid fields to update'];
        }
        
        try {
            $this->db->update('users', $updateData, 'id = ?', [$id]);
            
            // Log activity
            $this->logActivity($id, 'user_updated', 'user', $id);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Update failed'];
        }
    }
    
    public function updatePassword($id, $currentPassword, $newPassword) {
        $user = $this->getById($id);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }
        
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }
        
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            $this->db->update('users', 
                ['password_hash' => $passwordHash], 
                'id = ?', 
                [$id]
            );
            
            // Log activity
            $this->logActivity($id, 'password_updated', 'user', $id);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Password update failed'];
        }
    }
    
    public function assignRole($userId, $roleId, $assignedBy = null) {
        try {
            $this->db->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_by' => $assignedBy
            ]);
            
            return ['success' => true];
        } catch (Exception $e) {
            // Might be duplicate assignment
            return ['success' => false, 'error' => 'Role assignment failed'];
        }
    }
    
    public function getUserRoles($userId) {
        $sql = "
            SELECT r.* 
            FROM roles r 
            JOIN user_roles ur ON r.id = ur.role_id 
            WHERE ur.user_id = ? 
            AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
        ";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    public function hasPermission($userId, $permission) {
        $sql = "
            SELECT COUNT(*) as count
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ? 
            AND p.name = ?
            AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
        ";
        
        $result = $this->db->fetch($sql, [$userId, $permission]);
        return $result['count'] > 0;
    }
    
    public function verifyEmail($userId) {
        try {
            $this->db->update('users', 
                ['email_verified_at' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$userId]
            );
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Email verification failed'];
        }
    }
    
    public function deactivate($id) {
        try {
            $this->db->update('users', 
                ['status' => 'inactive'], 
                'id = ?', 
                [$id]
            );
            
            // Log activity
            $this->logActivity($id, 'user_deactivated', 'user', $id);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Deactivation failed'];
        }
    }
    
    public function search($query, $limit = 20, $offset = 0) {
        $sql = "
            SELECT id, uuid, email, first_name, last_name, status, created_at
            FROM users 
            WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)
            AND status != 'deleted'
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $searchTerm = "%{$query}%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
    }
    
    private function validateUserData($data) {
        $errors = [];
        
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($this->getByEmail($data['email'])) {
            $errors[] = 'Email already exists';
        }
        
        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        } elseif (strlen($data['first_name']) < 2) {
            $errors[] = 'First name must be at least 2 characters';
        }
        
        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        } elseif (strlen($data['last_name']) < 2) {
            $errors[] = 'Last name must be at least 2 characters';
        }
        
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function sanitizeUserData($user) {
        unset($user['password_hash']);
        return $user;
    }
    
    private function logActivity($userId, $action, $resourceType = null, $resourceId = null, $metadata = null) {
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => $metadata ? json_encode($metadata) : null
        ];
        
        $this->db->insert('user_activity_logs', $logData);
    }
}
