<?php
/**
 * Wifidog Authentication Server Configuration
 * 
 * Copy this file to config.php and update with your actual values.
 * config.php is gitignored for security.
 * 
 * Security Warning: Never commit real credentials to version control!
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'wifidog_auth');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

// For PostgreSQL, use:
// define('DB_TYPE', 'pgsql');
// define('DB_PORT', '5432');

// Application Settings
define('APP_ENV', 'development'); // development, staging, production
define('APP_DEBUG', true); // Set to false in production
define('APP_TIMEZONE', 'UTC');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // Session timeout in seconds (1 hour)
define('SESSION_TOKEN_LENGTH', 64); // Length of session tokens
define('SESSION_CLEANUP_INTERVAL', 86400); // Cleanup old sessions every 24 hours

// Wifidog Protocol Settings
define('WIFIDOG_PING_INTERVAL', 60); // Expected ping interval from gateways (seconds)
define('WIFIDOG_GATEWAY_TIMEOUT', 300); // Mark gateway offline after this many seconds
define('WIFIDOG_AUTH_SUCCESS', 1); // Auth response for success
define('WIFIDOG_AUTH_DENY', 0); // Auth response for denial

// Security Settings
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_HASH_COST', 12); // bcrypt cost (10-12 recommended)
define('MAX_LOGIN_ATTEMPTS', 5); // Maximum failed login attempts before lockout
define('LOCKOUT_DURATION', 900); // Lockout duration in seconds (15 minutes)

// Rate Limiting
define('RATE_LIMIT_AUTH', 60); // Max auth requests per minute per IP
define('RATE_LIMIT_LOGIN', 10); // Max login attempts per minute per IP

// Bandwidth Tracking
define('BANDWIDTH_UPDATE_INTERVAL', 300); // Update bandwidth stats every 5 minutes
define('BANDWIDTH_AGGREGATION_HOURS', 1); // Aggregate stats hourly

// Logging Configuration
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'info'); // debug, info, warning, error
define('LOG_FILE_PATH', __DIR__ . '/logs/wifidog.log');
define('LOG_AUTH_ATTEMPTS', true); // Log all authentication attempts
define('LOG_BANDWIDTH_USAGE', true); // Log bandwidth updates

// Email Configuration (for notifications)
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'your-email-password');
define('SMTP_FROM', 'noreply@yourwifidog.com');

// Portal Configuration
define('PORTAL_URL', 'https://yourwifidog.com/portal');
define('PORTAL_REDIRECT_SUCCESS', 'https://yourwifidog.com/welcome');
define('PORTAL_REDIRECT_FAIL', 'https://yourwifidog.com/login?error=1');

// API Configuration
define('API_ENABLED', true);
define('API_AUTH_REQUIRED', false); // Set true to require API key
define('API_KEY', 'your-api-key-here'); // Generate secure random key

// Maintenance Mode
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'System is under maintenance. Please try again later.');

// Advanced Settings
define('ALLOW_MULTIPLE_SESSIONS', false); // Allow same user on multiple devices
define('STRICT_MAC_VALIDATION', true); // Enforce MAC address consistency
define('AUTO_LOGOUT_INACTIVE', true); // Auto-logout after inactivity
define('INACTIVE_TIMEOUT', 1800); // Inactivity timeout (30 minutes)

// Database Connection Options
$db_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Timezone Setup
date_default_timezone_set(APP_TIMEZONE);

// Error Reporting (adjust for production)
if (APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

/**
 * Configuration Validation
 * Checks if critical settings are configured
 */
function validateConfig() {
    $errors = [];
    
    if (DB_USER === 'your_db_username') {
        $errors[] = 'Database username not configured';
    }
    
    if (DB_PASS === 'your_db_password') {
        $errors[] = 'Database password not configured';
    }
    
    if (API_ENABLED && API_KEY === 'your-api-key-here') {
        $errors[] = 'API key not configured';
    }
    
    if (count($errors) > 0) {
        if (APP_ENV === 'development') {
            echo "<h3>Configuration Errors:</h3><ul>";
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
        }
        return false;
    }
    
    return true;
}

// Auto-validate in development
if (APP_ENV === 'development') {
    validateConfig();
}
