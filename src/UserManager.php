<?php
/**
 * User Manager
 * Manages user accounts for WiFi authentication system
 */

class UserManager {
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create new user account
     */
    public function createUser($username, $password, $email = null) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password, email, is_active, created_at)
            VALUES (?, ?, ?, 1, NOW())
        ");
        
        try {
            $stmt->execute([$username, $hashedPassword, $email]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Username already exists");
            }
            throw $e;
        }
    }
    
    /**
     * Authenticate user
     */
    public function authenticateUser($username, $password) {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE username = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            $this->updateLastLogin($user['id']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, is_active, created_at, last_login
            FROM users WHERE id = ? LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, is_active, created_at, last_login
            FROM users WHERE username = ? LIMIT 1
        ");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("
            UPDATE users SET last_login = NOW() WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Deactivate user account
     */
    public function deactivateUser($userId) {
        $stmt = $this->db->prepare("
            UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Activate user account
     */
    public function activateUser($userId) {
        $stmt = $this->db->prepare("
            UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Get all users
     */
    public function getAllUsers($limit = 100, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, is_active, created_at, last_login
            FROM users
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get active users count
     */
    public function getActiveUsersCount() {
        $stmt = $this->db->query("
            SELECT COUNT(*) as count FROM users WHERE is_active = 1
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM users WHERE username = ?
        ");
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
