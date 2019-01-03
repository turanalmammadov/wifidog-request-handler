# Wifidog Request Handler

[![WIFIDOG](https://i.ibb.co/XZRj7YJ/8352f1-inline.jpg)](http://dev.wifidog.org/)


## Setup
Get the code:

```
git clone https://github.com/turanalmammadov/wifidog-request-handler.git
```

Start using function:
1) Simply upload files to your auth server.
2) Change wifidog.conf in your router.
```
AuthServer {
 Hostname urlofyourscript
 SSLAvailable yes
 Path /
}
```
3) Create db for your users and sessions.
4) Handle all requests over wifidog_actions() functions.
