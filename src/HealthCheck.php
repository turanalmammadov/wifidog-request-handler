<?php
/**
 * HealthCheck - System health endpoint for Wifidog auth server
 * 
 * Returns JSON status of all critical subsystems:
 * - Database connectivity
 * - Active session count
 * - Server uptime
 * - PHP version
 */

declare(strict_types=1);

class HealthCheck {
    
    private PDO $db;
    private float $startTime;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        // Store start time in session/cache for accurate uptime
        if (!isset($_SESSION['server_start'])) {
            $_SESSION['server_start'] = time();
        }
        $this->startTime = (float)$_SESSION['server_start'];
    }
    
    /**
     * Run all health checks and return results
     * 
     * @return array Health check results
     */
    public function check(): array {
        $results = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'checks' => [],
        ];
        
        // Database check
        $dbCheck = $this->checkDatabase();
        $results['checks']['database'] = $dbCheck;
        if ($dbCheck['status'] !== 'ok') {
            $results['status'] = 'degraded';
        }
        
        // Session count check
        $results['checks']['sessions'] = $this->checkActiveSessions();
        
        // System info
        $results['system'] = [
            'php_version' => PHP_VERSION,
            'uptime_seconds' => (int)(time() - $this->startTime),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ];
        
        return $results;
    }
    
    /**
     * Check database connectivity with a lightweight query
     */
    private function checkDatabase(): array {
        try {
            $start = microtime(true);
            $stmt = $this->db->query('SELECT 1');
            $latencyMs = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status'     => 'ok',
                'latency_ms' => $latencyMs,
            ];
        } catch (PDOException $e) {
            return [
                'status'  => 'error',
                'message' => 'Database unreachable',
            ];
        }
    }
    
    /**
     * Report number of currently active sessions
     */
    private function checkActiveSessions(): array {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM sessions WHERE is_active = 1"
            );
            $count = (int)$stmt->fetchColumn();
            
            return [
                'status' => 'ok',
                'active' => $count,
            ];
        } catch (PDOException $e) {
            return [
                'status'  => 'error',
                'message' => 'Could not count sessions',
            ];
        }
    }
    
    /**
     * Output the health check as a JSON HTTP response
     * 
     * Exits with HTTP 200 when healthy, HTTP 503 when degraded.
     */
    public function respond(): void {
        $result   = $this->check();
        $httpCode = $result['status'] === 'ok' ? 200 : 503;
        
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
