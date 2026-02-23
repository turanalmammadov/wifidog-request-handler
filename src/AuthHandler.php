<?php
/**
 * Wifidog Authentication Handler
 * Processes Wifidog protocol requests (ping, auth, login, portal)
 */

class AuthHandler {
    
    private $db;
    private $config;
    
    public function __construct($database, $config) {
        $this->db = $database;
        $this->config = $config;
    }
    
    /**
     * Handle ping request from Wifidog gateway
     * Returns "Pong" to confirm auth server is alive
     */
    public function handlePing($params) {
        $gatewayId = $params['gw_id'] ?? null;
        $sysUptime = $params['sys_uptime'] ?? null;
        
        if ($gatewayId) {
            $this->updateGatewayStatus($gatewayId, $sysUptime);
        }
        
        return "Pong";
    }
    
    /**
     * Handle authentication request from Wifidog gateway
     * Validates token and returns Auth: 1 (allow) or Auth: 0 (deny)
     */
    public function handleAuth($params) {
        $token = $params['token'] ?? null;
        $stage = $params['stage'] ?? 'login';
        $ip = $params['ip'] ?? null;
        $mac = $params['mac'] ?? null;
        
        if (!$token) {
            $this->logAuth(null, null, 'auth', 'deny', 'Missing token');
            return "Auth: 0";
        }
        
        $session = $this->getSessionByToken($token);
        
        if (!$session || !$session['is_active']) {
            $this->logAuth(null, null, 'auth', 'deny', 'Invalid or inactive session');
            return "Auth: 0";
        }
        
        // Update session activity
        $this->updateSessionActivity($session['id'], $ip, $mac);
        
        $this->logAuth($session['user_id'], $session['id'], 'auth', 'allow', 'Successful auth');
        return "Auth: 1";
    }
    
    /**
     * Generate secure session token
     */
    public function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Create new session for authenticated user
     */
    public function createSession($userId, $mac, $ip, $gatewayId) {
        $token = $this->generateToken();
        
        $stmt = $this->db->prepare("
            INSERT INTO sessions (token, user_id, mac_address, ip_address, gateway_id, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([$token, $userId, $mac, $ip, $gatewayId]);
        
        return $token;
    }
    
    /**
     * Get session by token
     */
    private function getSessionByToken($token) {
        $stmt = $this->db->prepare("
            SELECT * FROM sessions WHERE token = ? AND is_active = 1 LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update session last activity
     */
    private function updateSessionActivity($sessionId, $ip, $mac) {
        $stmt = $this->db->prepare("
            UPDATE sessions 
            SET last_activity = NOW(), ip_address = ?, mac_address = ?
            WHERE id = ?
        ");
        $stmt->execute([$ip, $mac, $sessionId]);
    }
    
    /**
     * Update gateway online status
     */
    private function updateGatewayStatus($gatewayId, $sysUptime) {
        $stmt = $this->db->prepare("
            INSERT INTO gateways (gateway_id, last_ping, is_online)
            VALUES (?, NOW(), 1)
            ON DUPLICATE KEY UPDATE last_ping = NOW(), is_online = 1
        ");
        $stmt->execute([$gatewayId]);
    }
    
    /**
     * Log authentication attempt
     */
    private function logAuth($userId, $sessionId, $action, $result, $message) {
        $stmt = $this->db->prepare("
            INSERT INTO auth_logs (user_id, session_id, action, result, message, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $sessionId, $action, $result, $message]);
    }
    
    /**
     * Terminate session (logout)
     */
    public function terminateSession($token) {
        $stmt = $this->db->prepare("
            UPDATE sessions 
            SET is_active = 0, session_end = NOW()
            WHERE token = ?
        ");
        $stmt->execute([$token]);
    }
    
    /**
     * Get active sessions count
     */
    public function getActiveSessionsCount() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM sessions WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
}
