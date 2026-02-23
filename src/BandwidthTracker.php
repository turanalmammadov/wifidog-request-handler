<?php
/**
 * Bandwidth Tracker
 * Tracks and aggregates user bandwidth usage
 */

class BandwidthTracker {
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Update bandwidth for session
     */
    public function updateBandwidth($sessionId, $incomingBytes, $outgoingBytes) {
        $stmt = $this->db->prepare("
            UPDATE sessions 
            SET incoming_bytes = incoming_bytes + ?,
                outgoing_bytes = outgoing_bytes + ?,
                last_activity = NOW()
            WHERE id = ? AND is_active = 1
        ");
        return $stmt->execute([$incomingBytes, $outgoingBytes, $sessionId]);
    }
    
    /**
     * Aggregate hourly bandwidth stats
     */
    public function aggregateHourlyStats($userId, $gatewayId, $incomingBytes, $outgoingBytes) {
        $hourTimestamp = date('Y-m-d H:00:00');
        
        $stmt = $this->db->prepare("
            INSERT INTO bandwidth_stats 
            (user_id, gateway_id, hour_timestamp, incoming_bytes, outgoing_bytes)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                incoming_bytes = incoming_bytes + VALUES(incoming_bytes),
                outgoing_bytes = outgoing_bytes + VALUES(outgoing_bytes)
        ");
        
        return $stmt->execute([$userId, $gatewayId, $hourTimestamp, $incomingBytes, $outgoingBytes]);
    }
    
    /**
     * Get user bandwidth usage for date range
     */
    public function getUserBandwidth($userId, $startDate, $endDate) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(hour_timestamp) as date,
                SUM(incoming_bytes) as total_incoming,
                SUM(outgoing_bytes) as total_outgoing,
                SUM(incoming_bytes + outgoing_bytes) as total_bytes
            FROM bandwidth_stats
            WHERE user_id = ?
            AND hour_timestamp BETWEEN ? AND ?
            GROUP BY DATE(hour_timestamp)
            ORDER BY date DESC
        ");
        
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top bandwidth users
     */
    public function getTopUsers($limit = 10, $days = 7) {
        $stmt = $this->db->prepare("
            SELECT 
                u.username,
                u.email,
                SUM(bs.incoming_bytes) as total_incoming,
                SUM(bs.outgoing_bytes) as total_outgoing,
                SUM(bs.incoming_bytes + bs.outgoing_bytes) as total_bytes
            FROM bandwidth_stats bs
            JOIN users u ON bs.user_id = u.id
            WHERE bs.hour_timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY u.id, u.username, u.email
            ORDER BY total_bytes DESC
            LIMIT ?
        ");
        
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get bandwidth by gateway
     */
    public function getGatewayBandwidth($gatewayId, $days = 7) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(hour_timestamp) as date,
                SUM(incoming_bytes) as total_incoming,
                SUM(outgoing_bytes) as total_outgoing
            FROM bandwidth_stats
            WHERE gateway_id = ?
            AND hour_timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(hour_timestamp)
            ORDER BY date DESC
        ");
        
        $stmt->execute([$gatewayId, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Format bytes to human readable
     */
    public static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
