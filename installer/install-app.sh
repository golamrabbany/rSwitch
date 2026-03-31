#!/bin/bash
#
# rSwitch App Server Installer
# Installs: Laravel + Nginx + PHP + Node.js + Redis (sessions/cache)
# Requires: Remote DB Server + Remote Engine Server already running
#
# Supports: Ubuntu 22.04+ LTS, Debian 12+, CentOS 9+, AlmaLinux 9+
#
set -e

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; CYAN='\033[0;36m'; NC='\033[0m'

INSTALLER_VERSION="2.0.0"
PHP_VERSION="8.3"
NODE_VERSION="20"
INSTALL_DIR="/var/www/rswitch"
DB_NAME="rswitch"
DB_USER="rswitch"
DB_PASS=""
DB_HOST=""
ENGINE_HOST=""
AMI_SECRET=""
DOMAIN=""
SSL_TYPE="letsencrypt"
ADMIN_EMAIL="admin@localhost"
ADMIN_PASSWORD=""
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

print_banner() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════════╗"
    echo "║                                                                  ║"
    echo "║   ██████╗ ███████╗██╗    ██╗██╗████████╗ ██████╗██╗  ██╗        ║"
    echo "║   ██╔══██╗██╔════╝██║    ██║██║╚══██╔══╝██╔════╝██║  ██║        ║"
    echo "║   ██████╔╝███████╗██║ █╗ ██║██║   ██║   ██║     ███████║        ║"
    echo "║   ██╔══██╗╚════██║██║███╗██║██║   ██║   ██║     ██╔══██║        ║"
    echo "║   ██║  ██║███████║╚███╔███╔╝██║   ██║   ╚██████╗██║  ██║        ║"
    echo "║   ╚═╝  ╚═╝╚══════╝ ╚══╝╚══╝ ╚═╝   ╚═╝    ╚═════╝╚═╝  ╚═╝        ║"
    echo "║                                                                  ║"
    echo "║          APP SERVER Installer v${INSTALLER_VERSION}                           ║"
    echo "║      Laravel + Nginx + PHP + Redis                              ║"
    echo "║                                                                  ║"
    echo "╚══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

log_info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error()   { echo -e "${RED}[ERROR]${NC} $1"; }
log_step()    { echo ""; echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; echo -e "${CYAN}  $1${NC}"; echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; echo ""; }

check_root() { [[ $EUID -ne 0 ]] && log_error "This script must be run as root" && exit 1; }

check_os() {
    [[ -f /etc/os-release ]] && . /etc/os-release || { log_error "Cannot determine OS."; exit 1; }
    OS=$ID; OS_VERSION=$VERSION_ID; OS_MAJOR_VERSION=$(echo "$OS_VERSION" | cut -d'.' -f1)
    case "$OS" in
        ubuntu) [[ "$OS_MAJOR_VERSION" -lt 22 ]] && log_error "Ubuntu 22.04+ required" && exit 1 ;;
        debian) [[ "$OS_MAJOR_VERSION" -lt 12 ]] && log_error "Debian 12+ required" && exit 1 ;;
        centos|almalinux) [[ "$OS_MAJOR_VERSION" -lt 9 ]] && log_error "CentOS/AlmaLinux 9+ required" && exit 1 ;;
        *) log_error "Unsupported OS: $OS"; exit 1 ;;
    esac
    log_success "Detected OS: $OS $OS_VERSION"
}

generate_password() { openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24; }

# =============================================================================
# Configuration — App Server specific
# =============================================================================

gather_configuration() {
    log_step "App Server Configuration"

    read -p "Domain name (e.g., voip.example.com): " DOMAIN
    while [[ -z "$DOMAIN" ]]; do log_warning "Domain is required"; read -p "Domain name: " DOMAIN; done

    echo ""
    echo -e "${YELLOW}Remote Server Configuration${NC}"
    echo "  These servers must be set up BEFORE running this installer."
    echo ""

    # DB Server
    read -p "Database Server IP: " DB_HOST
    while [[ -z "$DB_HOST" ]]; do log_warning "DB Server IP is required"; read -p "Database Server IP: " DB_HOST; done

    read -p "Database name [$DB_NAME]: " input_db
    DB_NAME=${input_db:-$DB_NAME}

    read -p "Database username [$DB_USER]: " input_dbuser
    DB_USER=${input_dbuser:-$DB_USER}

    read -s -p "Database password: " DB_PASS
    echo
    while [[ -z "$DB_PASS" ]]; do log_warning "DB password is required"; read -s -p "Database password: " DB_PASS; echo; done

    # Engine Server
    read -p "Engine Server IP (Asterisk + Python billing): " ENGINE_HOST
    while [[ -z "$ENGINE_HOST" ]]; do log_warning "Engine Server IP is required"; read -p "Engine Server IP: " ENGINE_HOST; done

    read -s -p "AMI Secret (from Engine server): " AMI_SECRET
    echo
    while [[ -z "$AMI_SECRET" ]]; do log_warning "AMI secret is required"; read -s -p "AMI Secret: " AMI_SECRET; echo; done

    # Admin
    read -p "Admin email [$ADMIN_EMAIL]: " input_email
    ADMIN_EMAIL=${input_email:-$ADMIN_EMAIL}

    read -s -p "Admin password (leave empty to generate): " input_pass
    echo
    if [[ -z "$input_pass" ]]; then ADMIN_PASSWORD=$(generate_password); log_info "Generated admin password"; else ADMIN_PASSWORD=$input_pass; fi

    read -p "Installation directory [$INSTALL_DIR]: " input_dir
    INSTALL_DIR=${input_dir:-$INSTALL_DIR}

    # SSL
    echo ""
    log_info "SSL Certificate Options:"
    echo "  1) Let's Encrypt (free, auto-renewal)"
    echo "  2) Commercial SSL"
    echo "  3) Skip SSL (HTTP only)"
    read -p "Select SSL option [1]: " ssl_choice
    case "${ssl_choice:-1}" in 1) SSL_TYPE="letsencrypt" ;; 2) SSL_TYPE="commercial" ;; 3) SSL_TYPE="skip" ;; *) SSL_TYPE="letsencrypt" ;; esac

    # Test DB connection
    echo ""
    log_info "Testing database connection..."
    if command -v mysql &>/dev/null; then
        mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" -e "SELECT 1" &>/dev/null && log_success "Database connection OK" || log_warning "Cannot connect to database. Ensure DB server is ready."
    else
        log_info "mysql client not installed yet — will test after installation"
    fi

    # Summary
    echo ""
    log_info "Installation Summary:"
    echo "  Domain:         $DOMAIN"
    echo "  DB Server:      $DB_HOST"
    echo "  Engine Server:  $ENGINE_HOST"
    echo "  Install Dir:    $INSTALL_DIR"
    echo "  Admin Email:    $ADMIN_EMAIL"
    echo "  SSL Type:       $SSL_TYPE"
    echo ""

    read -p "Proceed with installation? (Y/n): " -n 1 -r; echo
    [[ $REPLY =~ ^[Nn]$ ]] && exit 0
}

# =============================================================================
# Installation Functions (App Server only — no Asterisk, no MySQL, no Python)
# =============================================================================

install_system_dependencies() {
    log_step "Installing System Dependencies"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf update -y -q
        dnf install -y -q epel-release
        dnf install -y -q ca-certificates curl wget gnupg2 git unzip zip acl supervisor cronie logrotate firewalld htop vim nano mysql
    else
        export DEBIAN_FRONTEND=noninteractive
        apt-get update -qq
        apt-get install -y -qq software-properties-common apt-transport-https ca-certificates curl wget gnupg lsb-release git unzip zip acl supervisor cron logrotate ufw htop vim nano mysql-client
    fi
    log_success "System dependencies installed"
}

install_php() {
    log_step "Installing PHP $PHP_VERSION"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q https://rpms.remirepo.net/enterprise/remi-release-9.rpm
        dnf module reset php -y -q; dnf module enable php:remi-${PHP_VERSION} -y -q
        dnf install -y -q php-fpm php-cli php-common php-mysqlnd php-pdo php-redis php-xml php-curl php-gd php-imagick php-mbstring php-zip php-bcmath php-intl php-soap php-opcache
        PHP_FPM_CONF="/etc/php-fpm.d/www.conf"
        sed -i "s/^user = .*/user = nginx/" $PHP_FPM_CONF; sed -i "s/^group = .*/group = nginx/" $PHP_FPM_CONF
        sed -i "s/^listen.owner = .*/listen.owner = nginx/" $PHP_FPM_CONF; sed -i "s/^listen.group = .*/listen.group = nginx/" $PHP_FPM_CONF
        sed -i "s|^listen = .*|listen = /run/php-fpm/www.sock|" $PHP_FPM_CONF
        PHP_INI="/etc/php.ini"
        sed -i "s/^memory_limit = .*/memory_limit = 256M/" $PHP_INI; sed -i "s/^upload_max_filesize = .*/upload_max_filesize = 100M/" $PHP_INI; sed -i "s/^post_max_size = .*/post_max_size = 100M/" $PHP_INI
        systemctl restart php-fpm; systemctl enable php-fpm
    else
        [[ "$OS" == "ubuntu" ]] && add-apt-repository -y ppa:ondrej/php || { curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/php-archive-keyring.gpg; echo "deb [signed-by=/usr/share/keyrings/php-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list; }
        apt-get update -qq
        apt-get install -y -qq php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-common php${PHP_VERSION}-mysql php${PHP_VERSION}-redis php${PHP_VERSION}-xml php${PHP_VERSION}-curl php${PHP_VERSION}-gd php${PHP_VERSION}-imagick php${PHP_VERSION}-mbstring php${PHP_VERSION}-zip php${PHP_VERSION}-bcmath php${PHP_VERSION}-intl php${PHP_VERSION}-soap php${PHP_VERSION}-opcache
        PHP_FPM_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
        sed -i "s/^memory_limit = .*/memory_limit = 256M/" $PHP_FPM_INI; sed -i "s/^upload_max_filesize = .*/upload_max_filesize = 100M/" $PHP_FPM_INI; sed -i "s/^post_max_size = .*/post_max_size = 100M/" $PHP_FPM_INI
        systemctl restart php${PHP_VERSION}-fpm; systemctl enable php${PHP_VERSION}-fpm
    fi
    log_success "PHP $PHP_VERSION installed"
}

install_composer() {
    log_step "Installing Composer"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    chmod +x /usr/local/bin/composer
    log_success "Composer installed"
}

install_nodejs() {
    log_step "Installing Node.js $NODE_VERSION"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        curl -fsSL https://rpm.nodesource.com/setup_${NODE_VERSION}.x | bash -; dnf install -y -q nodejs
    else
        curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -; apt-get install -y -qq nodejs
    fi
    npm install -g npm@latest
    log_success "Node.js $(node --version) installed"
}

install_redis() {
    log_step "Installing Redis (local — sessions, cache, queue)"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q redis; REDIS_CONF="/etc/redis/redis.conf"
        systemctl restart redis; systemctl enable redis
    else
        apt-get install -y -qq redis-server; REDIS_CONF="/etc/redis/redis.conf"
        systemctl restart redis-server; systemctl enable redis-server
    fi
    sed -i "s/^supervised .*/supervised systemd/" $REDIS_CONF
    sed -i "s/^# maxmemory .*/maxmemory 256mb/" $REDIS_CONF
    log_success "Redis installed"
}

install_nginx() {
    log_step "Installing Nginx"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q nginx; setsebool -P httpd_can_network_connect 1 2>/dev/null || true
    else
        apt-get install -y -qq nginx; rm -f /etc/nginx/sites-enabled/default
    fi
    systemctl start nginx; systemctl enable nginx
    log_success "Nginx installed"
}

install_application() {
    log_step "Installing rSwitch Application"

    [[ "$OS" == "centos" || "$OS" == "almalinux" ]] && WEB_USER="nginx" || WEB_USER="www-data"

    mkdir -p $INSTALL_DIR; cd $INSTALL_DIR

    if [[ -d "$SCRIPT_DIR/../app" ]]; then
        log_info "Copying application files..."
        cp -r $SCRIPT_DIR/../* $INSTALL_DIR/
        rm -rf $INSTALL_DIR/installer
    else
        log_error "Application files not found. Run installer from the rSwitch directory."
        exit 1
    fi

    chown -R ${WEB_USER}:${WEB_USER} $INSTALL_DIR

    log_info "Installing Composer dependencies..."
    cd $INSTALL_DIR
    sudo -u ${WEB_USER} composer install --no-dev --optimize-autoloader --no-interaction
    sudo -u ${WEB_USER} composer dump-autoload

    log_info "Creating environment configuration..."
    cp .env.example .env

    # Point to remote DB and Engine servers
    sed -i "s|^APP_NAME=.*|APP_NAME=rSwitch|" .env
    sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
    sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
    sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
    sed -i "s|^# DB_HOST=.*|DB_HOST=${DB_HOST}|" .env
    sed -i "s|^DB_HOST=.*|DB_HOST=${DB_HOST}|" .env
    sed -i "s|^DB_PORT=.*|DB_PORT=3306|" .env
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
    sed -i "s|^CACHE_STORE=.*|CACHE_STORE=redis|" .env
    sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=redis|" .env
    sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" .env
    sed -i "s|^REDIS_HOST=.*|REDIS_HOST=127.0.0.1|" .env

    # Remove old .env AMI lines (from .env.example) before adding correct ones
    sed -i '/^# rSwitch: Asterisk/d' .env
    sed -i '/^AMI_USERNAME=/d' .env
    sed -i '0,/^AMI_SECRET=$/{/^AMI_SECRET=$/d}' .env
    sed -i '0,/^AMI_PORT=5038$/{/^AMI_PORT=5038$/d}' .env
    sed -i '/^AMI_HOST=asterisk/d' .env

    cat >> .env << EOF

# Remote Engine Server (Asterisk + Python billing)
AMI_HOST=${ENGINE_HOST}
AMI_PORT=5038
AMI_USER=rswitch
AMI_SECRET=${AMI_SECRET}

# Python Billing API on Engine Server
PYTHON_API_URL=http://${ENGINE_HOST}:8001

BROADCAST_VOICE_PATH=/var/spool/asterisk/voicebroadcast
EOF

    chown ${WEB_USER}:${WEB_USER} .env

    # Remove Docker Asterisk conf (not needed — trunks use DB realtime)
    rm -rf ${INSTALL_DIR}/docker/asterisk/conf 2>/dev/null

    sudo -u ${WEB_USER} php${PHP_VERSION} artisan key:generate --force

    log_info "Running database migrations..."
    sudo -u ${WEB_USER} php${PHP_VERSION} artisan migrate --force

    # Fix ps_contacts for Asterisk 20+ compatibility
    log_info "Fixing ps_contacts table..."
    for col in "via_addr VARCHAR(40)" "via_port INT" "call_id VARCHAR(255)" "endpoint VARCHAR(40)" "prune_on_boot VARCHAR(5) DEFAULT 'no'" "authenticate_qualify VARCHAR(5) DEFAULT 'no'" "qualify_timeout FLOAT DEFAULT 3.0"; do
        mysql -h${DB_HOST} -u${DB_USER} -p"${DB_PASS}" ${DB_NAME} -e "ALTER TABLE ps_contacts ADD COLUMN $col;" 2>/dev/null || true
    done

    log_info "Seeding database..."
    sudo -u ${WEB_USER} php${PHP_VERSION} artisan db:seed --force

    log_info "Building frontend assets..."
    mkdir -p /var/www/.npm && chown -R ${WEB_USER}:${WEB_USER} /var/www/.npm
    sudo -u ${WEB_USER} npm ci
    sudo -u ${WEB_USER} npm run build

    sudo -u ${WEB_USER} php${PHP_VERSION} artisan storage:link

    log_info "Caching configuration..."
    sudo -u ${WEB_USER} php${PHP_VERSION} artisan config:cache
    sudo -u ${WEB_USER} php${PHP_VERSION} artisan route:cache
    sudo -u ${WEB_USER} php${PHP_VERSION} artisan view:cache

    chmod -R 775 storage bootstrap/cache
    chown -R ${WEB_USER}:${WEB_USER} storage bootstrap/cache

    log_success "rSwitch application installed"
}

configure_nginx_site() {
    log_step "Configuring Nginx"

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        PHP_FPM_SOCK="/run/php-fpm/www.sock"; NGINX_CONF_FILE="/etc/nginx/conf.d/rswitch.conf"
    else
        PHP_FPM_SOCK="/var/run/php/php${PHP_VERSION}-fpm.sock"; NGINX_CONF_FILE="/etc/nginx/sites-available/rswitch"
    fi

    cat > ${NGINX_CONF_FILE} << EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${INSTALL_DIR}/public;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;
    charset utf-8;
    gzip on; gzip_vary on; gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    error_page 404 /index.php;
    location ~ \.php\$ {
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    location ~ /\.(?!well-known).* { deny all; }
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)\$ { expires 30d; add_header Cache-Control "public, immutable"; }
}
EOF

    [[ "$OS" != "centos" && "$OS" != "almalinux" ]] && ln -sf /etc/nginx/sites-available/rswitch /etc/nginx/sites-enabled/
    nginx -t && systemctl reload nginx
    log_success "Nginx configured"
}

configure_supervisor() {
    log_step "Configuring Supervisor (Laravel workers only)"

    [[ "$OS" == "centos" || "$OS" == "almalinux" ]] && { WEB_USER="nginx"; SUPERVISOR_CONF_DIR="/etc/supervisord.d"; } || { WEB_USER="www-data"; SUPERVISOR_CONF_DIR="/etc/supervisor/conf.d"; }
    mkdir -p $SUPERVISOR_CONF_DIR

    cat > ${SUPERVISOR_CONF_DIR}/rswitch.conf << EOF
[program:rswitch-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${WEB_USER}
numprocs=2
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/worker.log
stopwaitsecs=3600

[program:rswitch-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while true; do php ${INSTALL_DIR}/artisan schedule:run >> /dev/null 2>&1; sleep 60; done"
autostart=true
autorestart=true
user=${WEB_USER}
numprocs=1
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/scheduler.log
EOF

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then systemctl restart supervisord; else supervisorctl reread; supervisorctl update; supervisorctl start all; fi
    log_success "Supervisor configured (Laravel workers only)"
}

configure_firewall() {
    log_step "Configuring Firewall (Web only)"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        systemctl start firewalld; systemctl enable firewalld
        firewall-cmd --permanent --add-service=ssh; firewall-cmd --permanent --add-service=http; firewall-cmd --permanent --add-service=https
        firewall-cmd --reload
    else
        ufw --force enable; ufw allow ssh; ufw allow 80/tcp; ufw allow 443/tcp; ufw reload
    fi
    log_success "Firewall configured (22, 80, 443 only)"
}

configure_ssl() {
    log_step "Configuring SSL Certificate"
    case "$SSL_TYPE" in
        letsencrypt)
            if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then dnf install -y -q certbot python3-certbot-nginx; else apt-get install -y -qq certbot python3-certbot-nginx; fi
            certbot --nginx -d ${DOMAIN} --non-interactive --agree-tos --email ${ADMIN_EMAIL} --redirect || log_warning "SSL setup failed. Run: certbot --nginx -d ${DOMAIN}"
            log_success "Let's Encrypt SSL configured"
            ;;
        commercial)
            SSL_DIR="/etc/nginx/ssl/${DOMAIN}"; mkdir -p ${SSL_DIR}
            openssl genrsa -out ${SSL_DIR}/${DOMAIN}.key 2048
            openssl req -new -key ${SSL_DIR}/${DOMAIN}.key -out ${SSL_DIR}/${DOMAIN}.csr -subj "/CN=${DOMAIN}"
            openssl req -x509 -nodes -days 365 -key ${SSL_DIR}/${DOMAIN}.key -out ${SSL_DIR}/${DOMAIN}.chained.crt -subj "/CN=${DOMAIN}"
            log_info "CSR generated at: ${SSL_DIR}/${DOMAIN}.csr"
            log_success "Commercial SSL prepared (install your cert to complete)"
            ;;
        skip) log_warning "SSL skipped. Site is HTTP only." ;;
    esac
}

create_admin_user() {
    log_step "Creating Admin User"
    cd $INSTALL_DIR
    [[ "$OS" == "centos" || "$OS" == "almalinux" ]] && WEB_USER="nginx" || WEB_USER="www-data"
    mkdir -p /var/www/.config/psysh && chown -R ${WEB_USER}:${WEB_USER} /var/www/.config

    HASHED_PASS=$(php${PHP_VERSION} -r "echo password_hash('${ADMIN_PASSWORD}', PASSWORD_BCRYPT);")
    mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" ${DB_NAME} -e "
        INSERT INTO users (name, email, password, role, status, email_verified_at, billing_type, balance, created_at, updated_at)
        VALUES ('Super Admin', '${ADMIN_EMAIL}', '${HASHED_PASS}', 'super_admin', 'active', NOW(), 'postpaid', 0, NOW(), NOW())
        ON DUPLICATE KEY UPDATE name=name;
    " 2>/dev/null

    mysql -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" ${DB_NAME} -e "
        UPDATE users SET hierarchy_path = CONCAT('/', id, '/') WHERE hierarchy_path IS NULL OR hierarchy_path = '';
    " 2>/dev/null

    log_success "Admin user created"
}

save_credentials() {
    cat > /root/rswitch-app-credentials.txt << EOF
╔══════════════════════════════════════════════════════════════════╗
║              rSwitch App Server Credentials                      ║
╚══════════════════════════════════════════════════════════════════╝

Installation Date: $(date)
Server Type:       Application Server

Web Interface:     https://${DOMAIN}
Admin Email:       ${ADMIN_EMAIL}
Admin Password:    ${ADMIN_PASSWORD}

Remote Servers:
  DB Server:       ${DB_HOST}
  Engine Server:   ${ENGINE_HOST}

Database:
  Host:            ${DB_HOST}
  Database:        ${DB_NAME}
  Username:        ${DB_USER}

Application:
  Install Dir:     ${INSTALL_DIR}
  Config:          ${INSTALL_DIR}/.env
  Logs:            ${INSTALL_DIR}/storage/logs/

SSL Type:          ${SSL_TYPE}

Commands:
  View logs:       tail -f ${INSTALL_DIR}/storage/logs/laravel.log
  Restart workers: supervisorctl restart all
  Clear cache:     cd ${INSTALL_DIR} && php${PHP_VERSION} artisan optimize:clear

╔══════════════════════════════════════════════════════════════════╗
║  DELETE THIS FILE AFTER SAVING CREDENTIALS SECURELY!             ║
╚══════════════════════════════════════════════════════════════════╝
EOF
    chmod 600 /root/rswitch-app-credentials.txt
    log_success "Credentials saved to /root/rswitch-app-credentials.txt"
}

print_completion() {
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║       rSwitch App Server Installation Complete!                 ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    [[ "$SSL_TYPE" == "skip" ]] && echo -e "  ${CYAN}Web Interface:${NC}   http://${DOMAIN}" || echo -e "  ${CYAN}Web Interface:${NC}   https://${DOMAIN}"
    echo -e "  ${CYAN}Admin Email:${NC}     ${ADMIN_EMAIL}"
    echo -e "  ${CYAN}Admin Password:${NC}  ${ADMIN_PASSWORD}"
    echo -e "  ${CYAN}DB Server:${NC}       ${DB_HOST}"
    echo -e "  ${CYAN}Engine Server:${NC}   ${ENGINE_HOST}"
    echo ""
    echo -e "  ${YELLOW}Credentials:${NC} /root/rswitch-app-credentials.txt"
    echo ""
}

# =============================================================================
main() {
    print_banner; check_root; check_os; gather_configuration
    install_system_dependencies; install_php; install_composer; install_nodejs
    install_redis; install_nginx; install_application
    configure_nginx_site; configure_supervisor; configure_firewall; configure_ssl
    create_admin_user; save_credentials; print_completion
}
main "$@"
