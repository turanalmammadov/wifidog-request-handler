# Deployment Guide

Production deployment guide for Wifidog Authentication Server.

## Prerequisites

- PHP 7.4+ with extensions: pdo, pdo_mysql, mbstring, openssl
- Apache 2.4+ or Nginx 1.18+
- MySQL 5.7+ or PostgreSQL 11+
- SSL certificate for production
- Domain name pointed to server

## Production Setup

### 1. Server Configuration

**Apache (.htaccess included):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

**Nginx:**
```nginx
server {
    listen 443 ssl http2;
    server_name auth.yourdomain.com;
    root /var/www/wifidog;
    index index.php;

    ssl_certificate /path/to/certificate.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 2. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE wifidog_auth CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user with proper permissions
CREATE USER 'wifidog'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON wifidog_auth.* TO 'wifidog'@'localhost';
FLUSH PRIVILEGES;

# Import schema
mysql -u wifidog -p wifidog_auth < database/schema.sql
```

### 3. Application Configuration

```bash
# Copy and configure
cp config.example.php config.php
nano config.php

# Set database credentials
# Set production environment
# Configure session timeouts
# Enable logging
```

**Critical Settings:**
```php
define('APP_ENV', 'production');
define('APP_DEBUG', false);
define('DB_HOST', 'localhost');
define('DB_NAME', 'wifidog_auth');
define('DB_USER', 'wifidog');
define('DB_PASS', 'strong_password');
```

### 4. Security Hardening

**File Permissions:**
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/wifidog

# Secure config
chmod 640 config.php
chmod 640 database/schema.sql

# Protect sensitive directories
chmod 750 database/
chmod 750 src/
```

**PHP Security:**
```php
// In php.ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

### 5. SSL/TLS Setup

**Let's Encrypt (Free):**
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d auth.yourdomain.com
```

**Manual Certificate:**
```bash
# Place certificates
cp certificate.pem /etc/ssl/certs/wifidog-cert.pem
cp private.key /etc/ssl/private/wifidog-key.pem

# Secure permissions
chmod 644 /etc/ssl/certs/wifidog-cert.pem
chmod 600 /etc/ssl/private/wifidog-key.pem
```

## Router Configuration

Edit `/etc/wifidog.conf` on your Wifidog router:

```conf
AuthServer {
    Hostname auth.yourdomain.com
    SSLAvailable yes
    SSLPort 443
    HTTPPort 80
    Path /
}

# Gateway settings
GatewayID your-gateway-unique-id
GatewayInterface br-lan
```

Restart Wifidog:
```bash
/etc/init.d/wifidog restart
```

## Monitoring

### Health Checks

```bash
# Check service status
curl https://auth.yourdomain.com/health

# Check database
mysql -u wifidog -p wifidog_auth -e "SELECT COUNT(*) FROM users;"
```

### Log Monitoring

```bash
# Application logs
tail -f logs/wifidog.log

# PHP errors
tail -f /var/log/php_errors.log

# Apache errors
tail -f /var/log/apache2/error.log

# Database logs
tail -f /var/log/mysql/error.log
```

## Backup Strategy

### Database Backup

```bash
# Daily backup script
#!/bin/bash
DATE=$(date +%Y%m%d)
mysqldump -u wifidog -p'password' wifidog_auth > backup_$DATE.sql
gzip backup_$DATE.sql

# Keep last 7 days
find . -name "backup_*.sql.gz" -mtime +7 -delete
```

**Cron:**
```cron
0 2 * * * /path/to/backup.sh
```

### File Backup

```bash
# Backup config and custom code
tar -czf wifidog_backup.tar.gz \
    config.php \
    src/ \
    database/
```

## Performance Optimization

### Database

```sql
-- Add indexes
ALTER TABLE sessions ADD INDEX idx_last_activity (last_activity);
ALTER TABLE auth_logs ADD INDEX idx_created_at (created_at);

-- Optimize tables
OPTIMIZE TABLE sessions;
OPTIMIZE TABLE auth_logs;
```

### PHP

```ini
; In php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### Cleanup

```bash
# Cron job to cleanup old sessions
0 0 * * * php /var/www/wifidog/scripts/cleanup.php
```

## Troubleshooting

### Router Can't Connect

1. Check firewall (ports 80, 443 open)
2. Verify DNS resolution
3. Check SSL certificate validity
4. Test with `curl` from router

### Authentication Fails

1. Check database connectivity
2. Verify user credentials
3. Check session table
4. Review auth_logs for errors

### High Load

1. Enable opcache
2. Add database indexes
3. Implement Redis caching
4. Use CDN for static assets

## Scaling

### Horizontal Scaling

**Load Balancer:**
```nginx
upstream wifidog_backend {
    server 192.168.1.10:80;
    server 192.168.1.11:80;
    server 192.168.1.12:80;
}
```

**Shared Database:**
- Use external MySQL/PostgreSQL server
- Configure read replicas
- Implement connection pooling

**Session Storage:**
- Use Redis for session data
- Enable session replication
- Implement sticky sessions on load balancer

### Vertical Scaling

- Increase PHP memory_limit
- Add more database connections
- Optimize queries with indexes
- Enable query caching

## Maintenance

### Regular Tasks

- Daily: Monitor logs
- Weekly: Review active sessions
- Monthly: Database optimization
- Quarterly: Security audit

### Updates

```bash
# Backup before update
./backup.sh

# Pull latest code
git pull origin master

# Run migrations if any
php database/migrate.php

# Clear cache
php -r "opcache_reset();"
```

## Security Checklist

- [ ] Strong database passwords
- [ ] SSL/TLS enabled
- [ ] File permissions secured
- [ ] PHP display_errors off
- [ ] Regular backups configured
- [ ] Log monitoring setup
- [ ] Rate limiting enabled
- [ ] Input validation in place
- [ ] SQL injection prevention
- [ ] CORS properly configured

## Support

For deployment issues:
- Check logs first
- Review configuration
- Test components individually
- Open GitHub issue with details

---

**Production Checklist Complete? Deploy with confidence! 🚀**
