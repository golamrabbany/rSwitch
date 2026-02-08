# rSwitch Installer

Automated installation scripts for rSwitch VoIP Billing & Routing Platform.

## System Requirements

### Supported Operating Systems
- Ubuntu 22.04 LTS
- Ubuntu 24.04 LTS
- Debian 11 (Bullseye)
- Debian 12 (Bookworm)

### Minimum Hardware
- **CPU:** 2 cores
- **RAM:** 4 GB
- **Storage:** 20 GB SSD
- **Network:** 1 Gbps

### Recommended Hardware (Production)
- **CPU:** 4+ cores
- **RAM:** 8+ GB
- **Storage:** 100+ GB SSD
- **Network:** 1 Gbps with low latency

## Quick Start

### 1. Download and Extract

```bash
# Clone or download rSwitch to your server
cd /tmp
git clone https://github.com/your-repo/rswitch.git
cd rswitch/installer
```

### 2. Run Installer

```bash
chmod +x install.sh
sudo ./install.sh
```

### 3. Follow Prompts

The installer will ask for:
- **Domain name:** Your FQDN (e.g., `voip.example.com`)
- **Admin email:** Email for the admin account
- **Admin password:** Leave empty to auto-generate
- **Installation directory:** Default `/var/www/rswitch`

### 4. Post-Installation

After installation completes:

```bash
# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com

# Start Asterisk
sudo systemctl start asterisk

# Verify all services
sudo ./troubleshoot.sh
```

## What Gets Installed

| Component | Version | Description |
|-----------|---------|-------------|
| PHP | 8.3 | With FPM and extensions |
| Composer | Latest | PHP dependency manager |
| Node.js | 20.x | JavaScript runtime |
| MySQL | 8.0 | Database server |
| Redis | Latest | Cache and queue |
| Nginx | Latest | Web server |
| Asterisk | 21.4.0 | VoIP PBX |
| Supervisor | Latest | Process manager |
| Certbot | Latest | SSL certificate |
| Fail2Ban | Latest | Security |
| UFW | Latest | Firewall |

## Directory Structure

```
/var/www/rswitch/          # Application directory
├── app/                   # Laravel application
├── public/                # Web root
├── storage/               # Logs, cache, uploads
│   └── logs/
│       ├── laravel.log    # Application log
│       ├── worker.log     # Queue worker log
│       └── agi.log        # AGI server log
└── .env                   # Configuration

/etc/asterisk/             # Asterisk configuration
├── pjsip.conf             # PJSIP settings
├── pjsip_trunks.conf      # Trunk configurations (auto-generated)
├── extensions.conf        # Dialplan
├── extconfig.conf         # Realtime mapping
├── manager.conf           # AMI configuration
└── res_odbc.conf          # ODBC settings

/etc/nginx/sites-available/rswitch   # Nginx config
/etc/supervisor/conf.d/rswitch.conf  # Supervisor config
/root/rswitch-credentials.txt        # Installation credentials
```

## Scripts

### install.sh
Main installation script. Installs all components and configures the system.

```bash
sudo ./install.sh
```

### update.sh
Updates the application to the latest version. Creates a backup first.

```bash
sudo ./update.sh
```

### uninstall.sh
Removes rSwitch. Offers options to keep/remove database and Asterisk.

```bash
sudo ./uninstall.sh
```

### troubleshoot.sh
Diagnostic tool that checks all services and common issues.

```bash
sudo ./troubleshoot.sh
```

## Configuration Templates

The `templates/` directory contains reference configurations:

```
templates/
├── nginx.conf.template           # Nginx site configuration
├── supervisor.conf.template      # Supervisor processes
├── fail2ban-asterisk.conf.template
├── logrotate.conf.template
└── asterisk/
    ├── pjsip.conf.template       # PJSIP settings
    ├── extensions.conf.template   # Dialplan
    ├── manager.conf.template      # AMI settings
    └── modules.conf.template      # Module loading
```

## Firewall Ports

The installer configures UFW with these rules:

| Port | Protocol | Description |
|------|----------|-------------|
| 22 | TCP | SSH |
| 80 | TCP | HTTP |
| 443 | TCP | HTTPS |
| 5060 | UDP/TCP | SIP signaling |
| 5061 | TCP | SIP TLS |
| 10000-20000 | UDP | RTP media |

## Services Management

```bash
# Check all services
systemctl status php8.3-fpm nginx mysql redis-server asterisk supervisor

# Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo systemctl restart asterisk

# Supervisor processes
sudo supervisorctl status
sudo supervisorctl restart rswitch-worker:*
sudo supervisorctl restart rswitch-agi

# View logs
tail -f /var/www/rswitch/storage/logs/laravel.log
tail -f /var/log/asterisk/messages
```

## Troubleshooting

### Database Connection Failed
```bash
# Check MySQL status
systemctl status mysql

# Test connection
mysql -u rswitch -p -e "SELECT 1"

# Check .env credentials
cat /var/www/rswitch/.env | grep DB_
```

### Queue Workers Not Running
```bash
# Check supervisor status
supervisorctl status

# Restart workers
supervisorctl restart rswitch-worker:*

# View worker logs
tail -f /var/www/rswitch/storage/logs/worker.log
```

### Asterisk Not Connecting
```bash
# Check Asterisk status
asterisk -rx "core show uptime"

# Check ODBC connection
asterisk -rx "odbc show"

# Test ODBC directly
isql -v rswitch

# Check PJSIP transports
asterisk -rx "pjsip show transports"
```

### SIP Registration Failed
```bash
# Check endpoints
asterisk -rx "pjsip show endpoints"

# Check authentication
asterisk -rx "pjsip show auths"

# View SIP debug
asterisk -rx "pjsip set logger on"
tail -f /var/log/asterisk/messages
```

### AGI Not Working
```bash
# Check AGI server
supervisorctl status rswitch-agi

# Test AGI port
nc -zv 127.0.0.1 4573

# Check logs
tail -f /var/www/rswitch/storage/logs/agi.log
```

### Permission Issues
```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/rswitch/storage
sudo chmod -R 775 /var/www/rswitch/storage

# Fix bootstrap cache
sudo chown -R www-data:www-data /var/www/rswitch/bootstrap/cache
sudo chmod -R 775 /var/www/rswitch/bootstrap/cache
```

## SSL Certificate

Obtain a free SSL certificate with Certbot:

```bash
# Install certificate
sudo certbot --nginx -d your-domain.com

# Test renewal
sudo certbot renew --dry-run

# Auto-renewal is configured automatically
```

## Backup

### Manual Backup
```bash
# Backup application
tar -czf rswitch-backup-$(date +%Y%m%d).tar.gz \
    --exclude=/var/www/rswitch/vendor \
    --exclude=/var/www/rswitch/node_modules \
    /var/www/rswitch

# Backup database
mysqldump -u rswitch -p rswitch > rswitch-db-$(date +%Y%m%d).sql
```

### Automated Backup
Add to crontab:
```bash
0 2 * * * /path/to/backup-script.sh
```

## Security Recommendations

1. **Change default passwords** stored in `/root/rswitch-credentials.txt`
2. **Delete credentials file** after saving information securely
3. **Enable 2FA** for admin accounts
4. **Configure Fail2Ban** thresholds based on your needs
5. **Regularly update** the system and application
6. **Monitor logs** for suspicious activity
7. **Restrict AMI access** to localhost only
8. **Use SIP TLS** for encrypted signaling

## Support

For issues and feature requests, please visit:
- GitHub Issues: https://github.com/your-repo/rswitch/issues
- Documentation: https://docs.rswitch.io

## License

rSwitch is proprietary software. See LICENSE file for details.
