# Wifidog Request Handler

PHP-based authentication handler for Wifidog captive portal system.

[![Wifidog](https://img.shields.io/badge/Wifidog-Auth%20Server-blue)](http://dev.wifidog.org/)
[![PHP](https://img.shields.io/badge/PHP-7.0+-purple?logo=php)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## 🎯 About

This project provides a PHP-based authentication server for [Wifidog](http://dev.wifidog.org/), an open-source captive portal solution for wireless networks.

[![WIFIDOG](https://i.ibb.co/XZRj7YJ/8352f1-inline.jpg)](http://dev.wifidog.org/)

## ✨ Features

- 🔐 User authentication for WiFi access
- 📊 Session management and tracking
- 🌐 Wifidog protocol integration
- 🔄 Request routing and handling
- 💾 Database integration for users and sessions
- ⚡ Lightweight and fast

## 🚀 Quick Start

### Prerequisites

- PHP 7.0 or higher
- Apache/Nginx web server with mod_rewrite
- MySQL/PostgreSQL database
- Wifidog-installed router

### Installation

```bash
# Clone the repository
git clone https://github.com/turanalmammadov/wifidog-request-handler.git

# Navigate to project directory
cd wifidog-request-handler

# Configure your web server to point to this directory
# Example: /var/www/wifidog-auth
```

### Database Setup

1. Create a database for users and sessions:

```sql
CREATE DATABASE wifidog_auth;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(255) UNIQUE NOT NULL,
    user_id INT,
    mac_address VARCHAR(17),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

2. Create database configuration file (not tracked by git)

### Router Configuration

Configure your Wifidog router by editing `/etc/wifidog.conf`:

```conf
AuthServer {
    Hostname your-auth-server.com
    SSLAvailable yes
    Path /
}
```

### Configuration

The handler responds to Wifidog authentication protocol requests:

- `/ping` - Health check from router
- `/auth` - Authentication validation
- `/login` - User login page
- `/portal` - Post-authentication portal

## 📡 Wifidog Protocol

The authentication server handles these Wifidog requests:

### Ping Request
Router periodically pings auth server to verify connectivity.

**Request:** `GET /ping?gw_id={gateway_id}&sys_uptime={uptime}...`  
**Response:** `Pong`

### Auth Request
Router validates client session status.

**Request:** `GET /auth?token={token}&stage={stage}&...`  
**Response:** `Auth: {code}` (0=deny, 1=allow)

### Login Flow
1. Client connects to WiFi
2. Router redirects to auth server login page
3. User authenticates
4. Auth server returns token
5. Router validates token via /auth endpoint
6. Client gets internet access

## 🏗️ Architecture

```
┌─────────┐      HTTP      ┌──────────────┐      Auth      ┌─────────────┐
│ Client  │ ─────────────> │ Wifidog      │ ────────────> │ Auth Server │
│ Device  │                │ Router       │               │   (This)    │
└─────────┘ <───────────── └──────────────┘ <──────────── └─────────────┘
          Internet Access      Grant/Deny            Token/Session
                                                           ↓
                                                     ┌──────────┐
                                                     │ Database │
                                                     └──────────┘
```

## 🛠️ Development

### Project Structure

```
wifidog-request-handler/
├── src/
│   └── wifidog_actions.php  # Core handler functions
├── index.php                # Entry point
├── .htaccess               # URL rewriting rules
├── README.md               # This file
└── LICENSE                 # MIT License
```

### Handle Requests

Use the `wifidog_actions()` function to process all Wifidog protocol requests:

```php
// Your custom implementation
function wifidog_actions($action, $params) {
    switch($action) {
        case 'ping':
            return handlePing($params);
        case 'auth':
            return handleAuth($params);
        case 'login':
            return handleLogin($params);
        default:
            return handleUnknown();
    }
}
```

## 🔒 Security Considerations

- ✅ Validate all input parameters
- ✅ Use prepared statements for database queries
- ✅ Implement HTTPS (SSL) in production
- ✅ Secure session token generation
- ✅ Rate limiting for auth attempts
- ✅ SQL injection prevention
- ✅ XSS protection in login forms

## 📚 Resources

- [Wifidog Official Documentation](http://dev.wifidog.org/)
- [Wifidog Protocol Specification](http://dev.wifidog.org/wiki/doc/developer/WiFiDogProtocol_V1)
- [Captive Portal Overview](https://en.wikipedia.org/wiki/Captive_portal)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Turan Almammadov**
- GitHub: [@turanalmammadov](https://github.com/turanalmammadov)
- Website: [turanalmammadov.com](https://turanalmammadov.com/)

## ⭐ Support

If you find this project useful, please give it a star!

---

**Keywords:** WiFi authentication, captive portal, Wifidog, PHP, auth server, wireless network
