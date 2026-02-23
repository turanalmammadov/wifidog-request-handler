# Wifidog Authentication Server API Reference

## Wifidog Protocol Endpoints

### GET /ping

Gateway health check endpoint.

**Parameters:**
- `gw_id` - Gateway unique identifier
- `sys_uptime` - Gateway system uptime (seconds)
- `sys_memfree` - Free memory (KB)
- `sys_load` - System load average
- `wifidog_uptime` - Wifidog process uptime

**Response:**
```
Pong
```

**Status Code:** `200`

---

### GET /auth

Token validation for client authorization.

**Parameters:**
- `stage` - Auth stage (login/logout/counters)
- `token` - Session token
- `ip` - Client IP address
- `mac` - Client MAC address
- `incoming` - Incoming bytes
- `outgoing` - Outgoing bytes
- `gw_id` - Gateway ID

**Response:**
```
Auth: 1  (allow)
Auth: 0  (deny)
```

**Status Code:** `200`

---

### GET /login

User login page (HTML).

**Parameters:**
- `gw_address` - Gateway IP
- `gw_port` - Gateway port
- `gw_id` - Gateway ID
- `mac` - Client MAC address

**Response:** HTML login form

---

### GET /portal

Post-authentication portal page.

**Parameters:**
- `gw_id` - Gateway ID

**Response:** HTML portal page

---

## Management API Endpoints

### GET /api/sessions

Get active sessions.

**Response:**
```json
{
  "success": true,
  "data": [{
    "id": 1,
    "token": "abc...",
    "username": "user1",
    "mac_address": "00:11:22:33:44:55",
    "session_start": "2026-02-23T10:00:00Z"
  }]
}
```

### GET /api/stats

Get system statistics.

**Response:**
```json
{
  "total_users": 100,
  "active_sessions": 25,
  "total_bandwidth_gb": 150.5,
  "active_gateways": 3
}
```

---

## Database Schema

See `database/schema.sql` for complete schema.

**Main Tables:**
- users
- sessions  
- gateways
- auth_logs
- bandwidth_stats

---

## PHP Classes

### AuthHandler
- handlePing()
- handleAuth()
- createSession()
- terminateSession()

### SessionManager
- createSession()
- getSessionByToken()
- updateActivity()
- cleanupExpiredSessions()

### UserManager
- createUser()
- authenticateUser()
- updatePassword()

### BandwidthTracker
- updateBandwidth()
- aggregateHourlyStats()
- getUserBandwidth()
- getTopUsers()

---

## Configuration

See `config.example.php` for all configuration options.

**Key Settings:**
- Database connection
- Session timeouts
- Wifidog protocol settings
- Security options
- Rate limiting

---

**Version:** 1.0  
**Protocol:** Wifidog V1
