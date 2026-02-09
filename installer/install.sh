#!/bin/bash
#
# rSwitch Installer
# VoIP Billing & Routing Platform
#
# Supports: Ubuntu 22.04/24.04 LTS, Debian 11/12
#
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Version
INSTALLER_VERSION="1.1.0"
ASTERISK_VERSION="21.4.0"
PHP_VERSION="8.3"
NODE_VERSION="20"

# Default configuration
INSTALL_DIR="/var/www/rswitch"
DB_NAME="rswitch"
DB_USER="rswitch"
DB_PASS=""
DOMAIN=""
ADMIN_EMAIL="admin@localhost"
ADMIN_PASSWORD=""

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# =============================================================================
# Helper Functions
# =============================================================================

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
    echo "║            VoIP Billing & Routing Platform                       ║"
    echo "║                  Installer v${INSTALLER_VERSION}                              ║"
    echo "║                                                                  ║"
    echo "╚══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root"
        exit 1
    fi
}

check_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$ID
        OS_VERSION=$VERSION_ID
    else
        log_error "Cannot determine OS. /etc/os-release not found."
        exit 1
    fi

    case "$OS" in
        ubuntu)
            if [[ "$OS_VERSION" != "22.04" && "$OS_VERSION" != "24.04" ]]; then
                log_warning "Ubuntu $OS_VERSION is not officially supported. Recommended: 22.04 or 24.04 LTS"
                read -p "Continue anyway? (y/N): " -n 1 -r
                echo
                [[ ! $REPLY =~ ^[Yy]$ ]] && exit 1
            fi
            ;;
        debian)
            if [[ "$OS_VERSION" != "11" && "$OS_VERSION" != "12" ]]; then
                log_warning "Debian $OS_VERSION is not officially supported. Recommended: 11 or 12"
                read -p "Continue anyway? (y/N): " -n 1 -r
                echo
                [[ ! $REPLY =~ ^[Yy]$ ]] && exit 1
            fi
            ;;
        *)
            log_error "Unsupported OS: $OS"
            log_info "Supported: Ubuntu 22.04/24.04 LTS, Debian 11/12"
            exit 1
            ;;
    esac

    log_success "Detected OS: $OS $OS_VERSION"
}

generate_password() {
    openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 24
}

# =============================================================================
# Installation Steps
# =============================================================================

gather_configuration() {
    log_step "Configuration"

    # Domain
    read -p "Enter domain name (e.g., voip.example.com): " DOMAIN
    while [[ -z "$DOMAIN" ]]; do
        log_warning "Domain is required"
        read -p "Enter domain name: " DOMAIN
    done

    # Database password
    DB_PASS=$(generate_password)
    log_info "Generated database password"

    # Admin email
    read -p "Admin email [$ADMIN_EMAIL]: " input_email
    ADMIN_EMAIL=${input_email:-$ADMIN_EMAIL}

    # Admin password
    read -s -p "Admin password (leave empty to generate): " input_pass
    echo
    if [[ -z "$input_pass" ]]; then
        ADMIN_PASSWORD=$(generate_password)
        log_info "Generated admin password"
    else
        ADMIN_PASSWORD=$input_pass
    fi

    # Installation directory
    read -p "Installation directory [$INSTALL_DIR]: " input_dir
    INSTALL_DIR=${input_dir:-$INSTALL_DIR}

    # Summary
    echo ""
    log_info "Installation Summary:"
    echo "  Domain:           $DOMAIN"
    echo "  Install Dir:      $INSTALL_DIR"
    echo "  Database:         $DB_NAME"
    echo "  Database User:    $DB_USER"
    echo "  Admin Email:      $ADMIN_EMAIL"
    echo "  PHP Version:      $PHP_VERSION"
    echo "  Node Version:     $NODE_VERSION"
    echo "  Asterisk:         $ASTERISK_VERSION"
    echo ""

    read -p "Proceed with installation? (Y/n): " -n 1 -r
    echo
    [[ $REPLY =~ ^[Nn]$ ]] && exit 0
}

install_system_dependencies() {
    log_step "Installing System Dependencies"

    export DEBIAN_FRONTEND=noninteractive

    log_info "Updating package lists..."
    apt-get update -qq

    log_info "Installing essential packages..."
    apt-get install -y -qq \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        curl \
        wget \
        gnupg \
        lsb-release \
        git \
        unzip \
        zip \
        acl \
        supervisor \
        cron \
        logrotate \
        ufw \
        fail2ban \
        htop \
        vim \
        nano

    log_success "System dependencies installed"
}

install_php() {
    log_step "Installing PHP $PHP_VERSION"

    # Add PHP repository
    if [[ "$OS" == "ubuntu" ]]; then
        add-apt-repository -y ppa:ondrej/php
    else
        # Debian - use sury.org
        curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/php-archive-keyring.gpg
        echo "deb [signed-by=/usr/share/keyrings/php-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    fi

    apt-get update -qq

    log_info "Installing PHP packages..."
    apt-get install -y -qq \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-memcached \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-imagick \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-ldap \
        php${PHP_VERSION}-imap \
        php${PHP_VERSION}-opcache

    # Configure PHP-FPM
    log_info "Configuring PHP-FPM..."
    PHP_FPM_CONF="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    sed -i "s/^user = .*/user = www-data/" $PHP_FPM_CONF
    sed -i "s/^group = .*/group = www-data/" $PHP_FPM_CONF
    sed -i "s/^listen.owner = .*/listen.owner = www-data/" $PHP_FPM_CONF
    sed -i "s/^listen.group = .*/listen.group = www-data/" $PHP_FPM_CONF
    sed -i "s/^;clear_env = .*/clear_env = no/" $PHP_FPM_CONF

    # PHP CLI config
    PHP_INI="/etc/php/${PHP_VERSION}/cli/php.ini"
    sed -i "s/^memory_limit = .*/memory_limit = 512M/" $PHP_INI
    sed -i "s/^max_execution_time = .*/max_execution_time = 300/" $PHP_INI
    sed -i "s/^upload_max_filesize = .*/upload_max_filesize = 100M/" $PHP_INI
    sed -i "s/^post_max_size = .*/post_max_size = 100M/" $PHP_INI

    # PHP-FPM config
    PHP_FPM_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
    sed -i "s/^memory_limit = .*/memory_limit = 256M/" $PHP_FPM_INI
    sed -i "s/^max_execution_time = .*/max_execution_time = 300/" $PHP_FPM_INI
    sed -i "s/^upload_max_filesize = .*/upload_max_filesize = 100M/" $PHP_FPM_INI
    sed -i "s/^post_max_size = .*/post_max_size = 100M/" $PHP_FPM_INI

    systemctl restart php${PHP_VERSION}-fpm
    systemctl enable php${PHP_VERSION}-fpm

    log_success "PHP $PHP_VERSION installed and configured"
}

install_composer() {
    log_step "Installing Composer"

    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    chmod +x /usr/local/bin/composer

    log_success "Composer installed: $(composer --version)"
}

install_nodejs() {
    log_step "Installing Node.js $NODE_VERSION"

    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
    apt-get install -y -qq nodejs

    # Install npm globally
    npm install -g npm@latest

    log_success "Node.js installed: $(node --version)"
    log_success "npm installed: $(npm --version)"
}

install_mysql() {
    log_step "Installing MySQL 8.0"

    apt-get install -y -qq mysql-server mysql-client

    # Start MySQL
    systemctl start mysql
    systemctl enable mysql

    # Secure MySQL installation
    log_info "Securing MySQL installation..."
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_PASS}_root';"
    mysql -u root -p"${DB_PASS}_root" -e "DELETE FROM mysql.user WHERE User='';"
    mysql -u root -p"${DB_PASS}_root" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -u root -p"${DB_PASS}_root" -e "DROP DATABASE IF EXISTS test;"
    mysql -u root -p"${DB_PASS}_root" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -u root -p"${DB_PASS}_root" -e "FLUSH PRIVILEGES;"

    # Create application database and user
    log_info "Creating database and user..."
    mysql -u root -p"${DB_PASS}_root" -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u root -p"${DB_PASS}_root" -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -u root -p"${DB_PASS}_root" -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql -u root -p"${DB_PASS}_root" -e "FLUSH PRIVILEGES;"

    log_success "MySQL installed and configured"
}

install_redis() {
    log_step "Installing Redis"

    apt-get install -y -qq redis-server

    # Configure Redis
    sed -i "s/^supervised .*/supervised systemd/" /etc/redis/redis.conf
    sed -i "s/^# maxmemory .*/maxmemory 256mb/" /etc/redis/redis.conf
    sed -i "s/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/" /etc/redis/redis.conf

    systemctl restart redis-server
    systemctl enable redis-server

    log_success "Redis installed and configured"
}

install_nginx() {
    log_step "Installing Nginx"

    apt-get install -y -qq nginx

    # Remove default site
    rm -f /etc/nginx/sites-enabled/default

    systemctl start nginx
    systemctl enable nginx

    log_success "Nginx installed"
}

install_asterisk() {
    log_step "Installing Asterisk $ASTERISK_VERSION"

    # Install build dependencies
    log_info "Installing Asterisk build dependencies..."
    apt-get install -y -qq \
        build-essential \
        libncurses5-dev \
        libjansson-dev \
        libxml2-dev \
        libsqlite3-dev \
        uuid-dev \
        libssl-dev \
        libedit-dev \
        libsrtp2-dev \
        libspandsp-dev \
        libcurl4-openssl-dev \
        libnewt-dev \
        libogg-dev \
        libvorbis-dev \
        libspeex-dev \
        libspeexdsp-dev \
        libunbound-dev \
        unixodbc \
        unixodbc-dev \
        odbc-mariadb \
        libmariadb-dev \
        libmariadb-dev-compat \
        freetds-dev \
        libpq-dev \
        libopus-dev \
        libvpb-dev

    # Download Asterisk
    log_info "Downloading Asterisk $ASTERISK_VERSION..."
    cd /usr/src
    wget -q https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${ASTERISK_VERSION}.tar.gz
    tar -xzf asterisk-${ASTERISK_VERSION}.tar.gz
    cd asterisk-${ASTERISK_VERSION}

    # Install MP3 support
    log_info "Installing MP3 support..."
    contrib/scripts/get_mp3_source.sh

    # Install prerequisites
    log_info "Installing Asterisk prerequisites..."
    contrib/scripts/install_prereq install

    # Configure
    log_info "Configuring Asterisk..."
    ./configure --with-pjproject-bundled --with-jansson-bundled

    # Select modules
    make menuselect.makeopts
    menuselect/menuselect \
        --enable chan_pjsip \
        --enable res_pjsip \
        --enable res_pjsip_session \
        --enable res_pjsip_transport_websocket \
        --enable res_http_websocket \
        --enable res_agi \
        --enable res_odbc \
        --enable res_config_odbc \
        --enable cdr_odbc \
        --enable cdr_custom \
        --enable func_odbc \
        --enable res_realtime \
        --enable CORE-SOUNDS-EN-WAV \
        --enable CORE-SOUNDS-EN-ULAW \
        --enable CORE-SOUNDS-EN-ALAW \
        --enable MOH-ORSOUND-WAV \
        --enable MOH-ORSOUND-ULAW \
        --enable MOH-ORSOUND-ALAW \
        --enable EXTRA-SOUNDS-EN-WAV \
        --enable format_mp3 \
        menuselect.makeopts

    # Compile and install
    log_info "Compiling Asterisk (this may take a while)..."
    make -j$(nproc)
    make install
    make samples
    make config

    # Create asterisk user
    log_info "Creating asterisk user..."
    useradd -r -d /var/lib/asterisk -s /bin/false asterisk 2>/dev/null || true
    usermod -aG audio,dialout asterisk

    # Set permissions
    chown -R asterisk:asterisk /var/lib/asterisk
    chown -R asterisk:asterisk /var/log/asterisk
    chown -R asterisk:asterisk /var/spool/asterisk
    chown -R asterisk:asterisk /var/run/asterisk
    chown -R asterisk:asterisk /etc/asterisk

    # Configure asterisk.conf to run as asterisk user
    sed -i 's/^;runuser = .*/runuser = asterisk/' /etc/asterisk/asterisk.conf
    sed -i 's/^;rungroup = .*/rungroup = asterisk/' /etc/asterisk/asterisk.conf

    # Cleanup
    cd /usr/src
    rm -rf asterisk-${ASTERISK_VERSION}.tar.gz

    log_success "Asterisk $ASTERISK_VERSION installed"
}

configure_odbc() {
    log_step "Configuring ODBC for Asterisk Realtime"

    # Configure ODBC
    cat > /etc/odbcinst.ini << 'EOF'
[MariaDB]
Description = MariaDB Connector/ODBC
Driver = /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so
Driver64 = /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so
Setup = /usr/lib/x86_64-linux-gnu/odbc/libodbcmyS.so
UsageCount = 1
EOF

    cat > /etc/odbc.ini << EOF
[rswitch]
Description = rSwitch Database
Driver = MariaDB
Server = localhost
Database = ${DB_NAME}
User = ${DB_USER}
Password = ${DB_PASS}
Port = 3306
Socket = /var/run/mysqld/mysqld.sock
Option = 3
EOF

    # Configure Asterisk res_odbc
    cat > /etc/asterisk/res_odbc.conf << EOF
[rswitch]
enabled => yes
dsn => rswitch
username => ${DB_USER}
password => ${DB_PASS}
pre-connect => yes
sanitysql => select 1
max_connections => 5
connect_timeout => 10
EOF

    log_success "ODBC configured"
}

configure_asterisk() {
    log_step "Configuring Asterisk for rSwitch"

    # PJSIP configuration
    cat > /etc/asterisk/pjsip.conf << 'EOF'
; PJSIP Configuration for rSwitch
; Endpoints loaded from realtime database

[global]
type=global
max_forwards=70
user_agent=rSwitch PBX
default_from_user=rswitch

[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060
local_net=10.0.0.0/8
local_net=172.16.0.0/12
local_net=192.168.0.0/16

[transport-tcp]
type=transport
protocol=tcp
bind=0.0.0.0:5060

[transport-tls]
type=transport
protocol=tls
bind=0.0.0.0:5061
cert_file=/etc/asterisk/keys/asterisk.crt
priv_key_file=/etc/asterisk/keys/asterisk.key
method=tlsv1_2

#include pjsip_trunks.conf
EOF

    # Create empty trunks file
    touch /etc/asterisk/pjsip_trunks.conf
    chown asterisk:asterisk /etc/asterisk/pjsip_trunks.conf

    # Realtime configuration
    cat > /etc/asterisk/extconfig.conf << 'EOF'
[settings]
ps_endpoints => odbc,rswitch,ps_endpoints
ps_auths => odbc,rswitch,ps_auths
ps_aors => odbc,rswitch,ps_aors
ps_contacts => odbc,rswitch,ps_contacts
ps_endpoint_id_ips => odbc,rswitch,ps_endpoint_id_ips
EOF

    # Extensions configuration
    cat > /etc/asterisk/extensions.conf << 'EOF'
[general]
static=yes
writeprotect=yes
autofallthrough=yes
clearglobalvars=no
priorityjumping=no

[globals]
AGI_HOST=127.0.0.1
AGI_PORT=4573

[from-internal]
; Outbound calls from SIP accounts - handled by AGI
exten => _X.,1,NoOp(Outbound call from ${CALLERID(all)} to ${EXTEN})
 same => n,AGI(agi://${AGI_HOST}:${AGI_PORT}/outbound)
 same => n,Hangup()

exten => h,1,AGI(agi://${AGI_HOST}:${AGI_PORT}/hangup)

[from-trunk]
; Inbound calls from trunks - handled by AGI
exten => _X.,1,NoOp(Inbound call to ${EXTEN} from ${CALLERID(all)})
 same => n,AGI(agi://${AGI_HOST}:${AGI_PORT}/inbound)
 same => n,Hangup()

exten => h,1,AGI(agi://${AGI_HOST}:${AGI_PORT}/hangup)

[echo-test]
; Simple echo test for testing
exten => 600,1,NoOp(Echo Test)
 same => n,Answer()
 same => n,Wait(1)
 same => n,Playback(demo-echotest)
 same => n,Echo()
 same => n,Playback(demo-echodone)
 same => n,Hangup()
EOF

    # Modules configuration
    cat > /etc/asterisk/modules.conf << 'EOF'
[modules]
autoload=yes

; Security - disable unused channel drivers
noload => chan_alsa.so
noload => chan_console.so
noload => chan_skinny.so
noload => chan_unistim.so
noload => chan_phone.so
noload => chan_mgcp.so
noload => chan_oss.so

; Disable old SIP channel driver (use PJSIP)
noload => chan_sip.so

; Disable IAX2 if not needed
noload => chan_iax2.so

; Disable unused apps
noload => app_festival.so
noload => app_amd.so
noload => app_followme.so
noload => app_page.so
noload => app_minivm.so
noload => app_zapateller.so

; Load PJSIP
load => res_pjproject.so
load => res_pjsip.so
load => res_pjsip_session.so
load => res_pjsip_authenticator_digest.so
load => res_pjsip_endpoint_identifier_ip.so
load => res_pjsip_endpoint_identifier_user.so
load => res_pjsip_outbound_authenticator_digest.so
load => res_pjsip_registrar.so
load => res_pjsip_transport_websocket.so
load => chan_pjsip.so

; ODBC/Realtime
load => res_odbc.so
load => res_config_odbc.so
load => func_odbc.so
load => cdr_odbc.so
load => cdr_custom.so
load => res_realtime.so
EOF

    # Manager configuration (AMI)
    cat > /etc/asterisk/manager.conf << EOF
[general]
enabled = yes
port = 5038
bindaddr = 127.0.0.1
displayconnects = yes

[rswitch]
secret = ${DB_PASS}
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.255
read = all
write = all
writetimeout = 5000
eventfilter = !Event: RTCP*
eventfilter = !Event: VarSet
eventfilter = !Event: Cdr
eventfilter = !Event: NewExten
EOF

    # Create SSL directory
    mkdir -p /etc/asterisk/keys
    chown asterisk:asterisk /etc/asterisk/keys

    # Set ownership
    chown -R asterisk:asterisk /etc/asterisk

    # Enable Asterisk service
    systemctl enable asterisk

    log_success "Asterisk configured for rSwitch"
}

install_application() {
    log_step "Installing rSwitch Application"

    # Create installation directory
    mkdir -p $INSTALL_DIR
    cd $INSTALL_DIR

    # Copy application files (assuming we're running from the installer directory)
    if [[ -d "$SCRIPT_DIR/../app" ]]; then
        log_info "Copying application files..."
        cp -r $SCRIPT_DIR/../* $INSTALL_DIR/
        rm -rf $INSTALL_DIR/installer
    else
        log_error "Application files not found. Please run installer from the rSwitch directory."
        exit 1
    fi

    # Set ownership
    chown -R www-data:www-data $INSTALL_DIR

    # Install Composer dependencies
    log_info "Installing Composer dependencies..."
    cd $INSTALL_DIR
    sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
    sudo -u www-data composer dump-autoload

    # Create .env file
    log_info "Creating environment configuration..."
    cp .env.example .env

    # Update .env
    sed -i "s|APP_NAME=.*|APP_NAME=rSwitch|" .env
    sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env
    sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
    sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
    sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" .env
    sed -i "s|DB_PORT=.*|DB_PORT=3306|" .env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
    sed -i "s|CACHE_DRIVER=.*|CACHE_DRIVER=redis|" .env
    sed -i "s|SESSION_DRIVER=.*|SESSION_DRIVER=redis|" .env
    sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" .env
    sed -i "s|REDIS_HOST=.*|REDIS_HOST=127.0.0.1|" .env

    # Add AGI and AMI configuration
    cat >> .env << EOF

# Asterisk AGI Server
AGI_HOST=127.0.0.1
AGI_PORT=4573

# Asterisk AMI
AMI_HOST=127.0.0.1
AMI_PORT=5038
AMI_USER=rswitch
AMI_SECRET=${DB_PASS}
EOF

    # Generate application key
    sudo -u www-data php artisan key:generate --force

    # Run migrations
    log_info "Running database migrations..."
    sudo -u www-data php artisan migrate --force

    # Seed database
    log_info "Seeding database..."
    sudo -u www-data php artisan db:seed --force

    # Install NPM dependencies and build assets
    log_info "Building frontend assets..."
    sudo -u www-data npm ci
    sudo -u www-data npm run build

    # Create storage link
    sudo -u www-data php artisan storage:link

    # Cache configuration
    log_info "Caching configuration..."
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache

    # Set permissions
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache

    log_success "rSwitch application installed"
}

configure_nginx_site() {
    log_step "Configuring Nginx for rSwitch"

    cat > /etc/nginx/sites-available/rswitch << EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${INSTALL_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;

    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript application/json;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
EOF

    # Enable site
    ln -sf /etc/nginx/sites-available/rswitch /etc/nginx/sites-enabled/

    # Test nginx configuration
    nginx -t

    # Reload nginx
    systemctl reload nginx

    log_success "Nginx configured"
}

configure_supervisor() {
    log_step "Configuring Supervisor for Queue Workers"

    cat > /etc/supervisor/conf.d/rswitch.conf << EOF
[program:rswitch-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/worker.log
stopwaitsecs=3600

[program:rswitch-webhook-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work redis --queue=webhooks --sleep=5 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/webhook-worker.log

[program:rswitch-agi]
process_name=%(program_name)s
command=php ${INSTALL_DIR}/artisan agi:serve
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/agi.log

[program:rswitch-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while true; do php ${INSTALL_DIR}/artisan schedule:run >> /dev/null 2>&1; sleep 60; done"
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/scheduler.log
EOF

    # Reload supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl start all

    log_success "Supervisor configured"
}

configure_firewall() {
    log_step "Configuring Firewall"

    # Enable UFW
    ufw --force enable

    # Allow SSH
    ufw allow ssh

    # Allow HTTP/HTTPS
    ufw allow 80/tcp
    ufw allow 443/tcp

    # Allow SIP
    ufw allow 5060/udp
    ufw allow 5060/tcp
    ufw allow 5061/tcp

    # Allow RTP
    ufw allow 10000:20000/udp

    # Reload firewall
    ufw reload

    log_success "Firewall configured"
}

configure_fail2ban() {
    log_step "Configuring Fail2Ban"

    # Asterisk jail
    cat > /etc/fail2ban/jail.d/asterisk.conf << 'EOF'
[asterisk]
enabled = true
port = 5060,5061
protocol = udp,tcp
filter = asterisk
logpath = /var/log/asterisk/messages
maxretry = 5
bantime = 3600
findtime = 600
EOF

    # Create Asterisk filter
    cat > /etc/fail2ban/filter.d/asterisk.conf << 'EOF'
[INCLUDES]
before = common.conf

[Definition]
failregex = NOTICE.* .*: Registration from '.*' failed for '<HOST>:.*' - Wrong password
            NOTICE.* .*: Registration from '.*' failed for '<HOST>:.*' - No matching peer found
            NOTICE.* .*: Registration from '.*' failed for '<HOST>:.*' - Username/auth name mismatch
            NOTICE.* .*: Registration from '.*' failed for '<HOST>:.*' - Device does not match ACL
            NOTICE.* <HOST> failed to authenticate as '.*'$
            NOTICE.* .*: No registration for peer '.*' \(from <HOST>\)
            NOTICE.* .*: Host <HOST> failed MD5 authentication for '.*' (.*)
            NOTICE.* .*: Failed to authenticate device .*@<HOST>.*
ignoreregex =
EOF

    # Restart fail2ban
    systemctl restart fail2ban

    log_success "Fail2Ban configured"
}

install_certbot() {
    log_step "Installing Certbot for SSL"

    apt-get install -y -qq certbot python3-certbot-nginx

    log_info "To obtain SSL certificate, run:"
    echo "  certbot --nginx -d ${DOMAIN}"

    log_success "Certbot installed"
}

create_admin_user() {
    log_step "Creating Admin User"

    cd $INSTALL_DIR

    # Use tinker to create admin user
    php artisan tinker --execute="
        \$user = App\Models\User::where('email', '${ADMIN_EMAIL}')->first();
        if (!\$user) {
            \$user = App\Models\User::create([
                'name' => 'Administrator',
                'email' => '${ADMIN_EMAIL}',
                'password' => Hash::make('${ADMIN_PASSWORD}'),
                'role' => 'admin',
                'status' => 'active',
                'billing_type' => 'postpaid',
                'balance' => 0,
            ]);
            \$user->assignRole('admin');
            echo 'Admin user created successfully';
        } else {
            echo 'Admin user already exists';
        }
    "

    log_success "Admin user created"
}

save_credentials() {
    log_step "Saving Installation Credentials"

    CREDS_FILE="/root/rswitch-credentials.txt"

    cat > $CREDS_FILE << EOF
╔══════════════════════════════════════════════════════════════════╗
║                    rSwitch Installation Credentials              ║
╚══════════════════════════════════════════════════════════════════╝

Installation Date: $(date)

Web Application
───────────────
URL:            https://${DOMAIN}
Admin Email:    ${ADMIN_EMAIL}
Admin Password: ${ADMIN_PASSWORD}

Database
────────
Host:           localhost
Database:       ${DB_NAME}
Username:       ${DB_USER}
Password:       ${DB_PASS}
Root Password:  ${DB_PASS}_root

Asterisk
────────
AMI User:       rswitch
AMI Secret:     ${DB_PASS}
AMI Port:       5038

Application Paths
─────────────────
Install Dir:    ${INSTALL_DIR}
Config File:    ${INSTALL_DIR}/.env
Logs:           ${INSTALL_DIR}/storage/logs/

Services
────────
PHP-FPM:        systemctl status php${PHP_VERSION}-fpm
Nginx:          systemctl status nginx
MySQL:          systemctl status mysql
Redis:          systemctl status redis-server
Asterisk:       systemctl status asterisk
Supervisor:     systemctl status supervisor

Useful Commands
───────────────
View app logs:      tail -f ${INSTALL_DIR}/storage/logs/laravel.log
View Asterisk logs: tail -f /var/log/asterisk/messages
Restart workers:    supervisorctl restart all
Clear cache:        cd ${INSTALL_DIR} && php artisan optimize:clear

SSL Certificate
───────────────
Run: certbot --nginx -d ${DOMAIN}

╔══════════════════════════════════════════════════════════════════╗
║  IMPORTANT: Delete this file after saving credentials securely! ║
╚══════════════════════════════════════════════════════════════════╝
EOF

    chmod 600 $CREDS_FILE

    log_success "Credentials saved to $CREDS_FILE"
}

print_completion() {
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                                  ║${NC}"
    echo -e "${GREEN}║           rSwitch Installation Complete!                         ║${NC}"
    echo -e "${GREEN}║                                                                  ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${CYAN}Web Interface:${NC}   https://${DOMAIN}"
    echo -e "  ${CYAN}Admin Email:${NC}     ${ADMIN_EMAIL}"
    echo -e "  ${CYAN}Admin Password:${NC}  ${ADMIN_PASSWORD}"
    echo ""
    echo -e "  ${YELLOW}Credentials saved to:${NC} /root/rswitch-credentials.txt"
    echo ""
    echo -e "  ${CYAN}Next Steps:${NC}"
    echo "    1. Obtain SSL certificate: certbot --nginx -d ${DOMAIN}"
    echo "    2. Start Asterisk: systemctl start asterisk"
    echo "    3. Access web interface and configure your trunks/rates"
    echo ""
    echo -e "  ${YELLOW}IMPORTANT:${NC} Delete /root/rswitch-credentials.txt after saving!"
    echo ""
}

# =============================================================================
# Main Installation
# =============================================================================

main() {
    print_banner
    check_root
    check_os
    gather_configuration

    install_system_dependencies
    install_php
    install_composer
    install_nodejs
    install_mysql
    install_redis
    install_nginx
    install_asterisk
    configure_odbc
    configure_asterisk
    install_application
    configure_nginx_site
    configure_supervisor
    configure_firewall
    configure_fail2ban
    install_certbot
    create_admin_user
    save_credentials

    print_completion
}

# Run main function
main "$@"
