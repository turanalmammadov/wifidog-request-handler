<?php
/**
 * Session Manager
 * Manages WiFi user sessions with database persistence
 */

class SessionManager {
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create new session
     */
    public function createSession($userId, $token, $mac, $ip, $gatewayId) {
        $stmt = $this->db->prepare("
            INSERT INTO sessions 
            (token, user_id, mac_address, ip_address, gateway_id, is_active, session_start)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        
        return $stmt->execute([$token, $userId, $mac, $ip, $gatewayId]);
    }
    
    /**
     * Get session by token
     */
    public function getSessionByToken($token) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.username, u.email 
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.token = ? AND s.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update session activity
     */
    public function updateActivity($sessionId, $incomingBytes = 0, $outgoingBytes = 0) {
        $stmt = $this->db->prepare("
            UPDATE sessions 
            SET last_activity = NOW(),
                incoming_bytes = incoming_bytes + ?,
                outgoing_bytes = outgoing_bytes + ?
            WHERE id = ? AND is_active = 1
        ");
        return $stmt->execute([$incomingBytes, $outgoingBytes, $sessionId]);
    }
    
    /**
     * Terminate session
     */
    public function terminateSession($token) {
        $stmt = $this->db->prepare("
            UPDATE sessions 
            SET is_active = 0, session_end = NOW()
            WHERE token = ? AND is_active = 1
        ");
        return $stmt->execute([$token]);
    }
    
    /**
     * Get active sessions
     */
    public function getActiveSessions($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.username 
            FROM sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.is_active = 1
            ORDER BY s.last_activity DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user's session history
     */
    public function getUserSessions($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT * FROM sessions
            WHERE user_id = ?
            ORDER BY session_start DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions($inactiveMinutes = 30) {
        $stmt = $this->db->prepare("
            UPDATE sessions 
            SET is_active = 0, session_end = NOW()
            WHERE is_active = 1 
            AND last_activity < DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        return $stmt->execute([$inactiveMinutes]);
    }
    
    /**
     * Get session statistics
     */
    public function getStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_sessions,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_sessions,
                SUM(incoming_bytes) as total_incoming,
                SUM(outgoing_bytes) as total_outgoing,
                AVG(TIMESTAMPDIFF(MINUTE, session_start, COALESCE(session_end, NOW()))) as avg_duration_minutes
            FROM sessions
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if MAC address has active session
     */
    public function hasActiveSession($macAddress) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM sessions 
            WHERE mac_address = ? AND is_active = 1
        ");
        $stmt->execute([$macAddress]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
