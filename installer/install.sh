#!/bin/bash
#
# rSwitch Installer
# VoIP Billing & Routing Platform
#
# Supports: Ubuntu 22.04+ LTS, Debian 12+, CentOS 9+, AlmaLinux 9+
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
INSTALLER_VERSION="1.4.0"
ASTERISK_VERSION="20.11.1"
PHP_VERSION="8.3"
NODE_VERSION="20"

# Default configuration
INSTALL_DIR="/var/www/rswitch"
DB_NAME="rswitch"
DB_USER="rswitch"
DB_PASS=""
DOMAIN=""
SSL_TYPE="letsencrypt"  # letsencrypt, namecheap, or skip
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

    # Extract major version number
    OS_MAJOR_VERSION=$(echo "$OS_VERSION" | cut -d'.' -f1)

    case "$OS" in
        ubuntu)
            if [[ "$OS_MAJOR_VERSION" -lt 22 ]]; then
                log_error "Ubuntu $OS_VERSION is not supported. Minimum required: Ubuntu 22.04 LTS"
                exit 1
            fi
            ;;
        debian)
            if [[ "$OS_MAJOR_VERSION" -lt 12 ]]; then
                log_error "Debian $OS_VERSION is not supported. Minimum required: Debian 12"
                exit 1
            fi
            ;;
        centos)
            if [[ "$OS_MAJOR_VERSION" -lt 9 ]]; then
                log_error "CentOS $OS_VERSION is not supported. Minimum required: CentOS 9"
                exit 1
            fi
            ;;
        almalinux)
            if [[ "$OS_MAJOR_VERSION" -lt 9 ]]; then
                log_error "AlmaLinux $OS_VERSION is not supported. Minimum required: AlmaLinux 9"
                exit 1
            fi
            ;;
        *)
            log_error "Unsupported OS: $OS"
            log_info "Supported: Ubuntu 22.04+, Debian 12+, CentOS 9+, AlmaLinux 9+"
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

    # SSL Configuration
    echo ""
    log_info "SSL Certificate Options:"
    echo "  1) Let's Encrypt (free, auto-renewal) - Recommended"
    echo "  2) Commercial SSL (Namecheap, DigiCert, etc.)"
    echo "  3) Skip SSL (HTTP only, not recommended)"
    echo ""
    read -p "Select SSL option [1]: " ssl_choice
    case "${ssl_choice:-1}" in
        1)
            SSL_TYPE="letsencrypt"
            ;;
        2)
            SSL_TYPE="commercial"
            ;;
        3)
            SSL_TYPE="skip"
            log_warning "SSL will not be configured. Your site will be HTTP only."
            ;;
        *)
            SSL_TYPE="letsencrypt"
            ;;
    esac

    # Summary
    echo ""
    log_info "Installation Summary:"
    echo "  Domain:           $DOMAIN"
    echo "  Install Dir:      $INSTALL_DIR"
    echo "  Database:         $DB_NAME"
    echo "  Database User:    $DB_USER"
    echo "  Admin Email:      $ADMIN_EMAIL"
    echo "  SSL Type:         $SSL_TYPE"
    echo "  PHP Version:      $PHP_VERSION"
    echo "  Node Version:     $NODE_VERSION"
    echo "  Asterisk:         $ASTERISK_VERSION"
    echo ""

    read -p "Proceed with installation? (Y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Nn]$ ]]; then exit 0; fi
}

install_system_dependencies() {
    log_step "Installing System Dependencies"

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        log_info "Updating package lists..."
        dnf update -y -q

        log_info "Installing EPEL repository..."
        dnf install -y -q epel-release

        log_info "Installing essential packages..."
        dnf install -y -q \
            ca-certificates \
            curl \
            wget \
            gnupg2 \
            git \
            unzip \
            zip \
            acl \
            supervisor \
            cronie \
            logrotate \
            firewalld \
            fail2ban \
            htop \
            vim \
            nano \
            python3 \
            python3-pip \
            policycoreutils-python-utils \
            ffmpeg
    else
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
            nano \
            python3-venv \
            python3-pip \
            ffmpeg
    fi

    log_success "System dependencies installed"
}

install_php() {
    log_step "Installing PHP $PHP_VERSION"

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        # RHEL-based: use Remi repository
        log_info "Installing Remi repository..."
        dnf install -y -q https://rpms.remirepo.net/enterprise/remi-release-9.rpm
        dnf module reset php -y -q
        dnf module enable php:remi-${PHP_VERSION} -y -q

        log_info "Installing PHP packages..."
        dnf install -y -q \
            php-fpm \
            php-cli \
            php-common \
            php-mysqlnd \
            php-pgsql \
            php-pdo \
            php-redis \
            php-xml \
            php-curl \
            php-gd \
            php-imagick \
            php-mbstring \
            php-zip \
            php-bcmath \
            php-intl \
            php-soap \
            php-ldap \
            php-imap \
            php-opcache

        # Configure PHP-FPM for RHEL
        log_info "Configuring PHP-FPM..."
        PHP_FPM_CONF="/etc/php-fpm.d/www.conf"
        sed -i "s/^user = .*/user = nginx/" $PHP_FPM_CONF
        sed -i "s/^group = .*/group = nginx/" $PHP_FPM_CONF
        sed -i "s/^listen.owner = .*/listen.owner = nginx/" $PHP_FPM_CONF
        sed -i "s/^listen.group = .*/listen.group = nginx/" $PHP_FPM_CONF
        sed -i "s|^listen = .*|listen = /run/php-fpm/www.sock|" $PHP_FPM_CONF
        sed -i "s/^;clear_env = .*/clear_env = no/" $PHP_FPM_CONF

        # PHP config
        PHP_INI="/etc/php.ini"
        sed -i "s/^memory_limit = .*/memory_limit = 256M/" $PHP_INI
        sed -i "s/^max_execution_time = .*/max_execution_time = 300/" $PHP_INI
        sed -i "s/^upload_max_filesize = .*/upload_max_filesize = 100M/" $PHP_INI
        sed -i "s/^post_max_size = .*/post_max_size = 100M/" $PHP_INI

        systemctl restart php-fpm
        systemctl enable php-fpm
    else
        # Debian/Ubuntu: use ondrej/sury repository
        if [[ "$OS" == "ubuntu" ]]; then
            add-apt-repository -y ppa:ondrej/php
        else
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
    fi

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

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        curl -fsSL https://rpm.nodesource.com/setup_${NODE_VERSION}.x | bash -
        dnf install -y -q nodejs
    else
        curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
        apt-get install -y -qq nodejs
    fi

    # Install npm globally
    npm install -g npm@latest

    log_success "Node.js installed: $(node --version)"
    log_success "npm installed: $(npm --version)"
}

install_mysql() {
    log_step "Installing MySQL 8.0"

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q mysql-server mysql
        systemctl start mysqld
        systemctl enable mysqld
    else
        export DEBIAN_FRONTEND=noninteractive
        apt-get install -y -qq mysql-server mysql-client
        systemctl start mysql
        systemctl enable mysql
    fi

    # Determine how to talk to MySQL as root.
    # Priority: passwordless socket → debian.cnf (Ubuntu/Debian) → set new password.
    MYSQL_ROOT_AUTH=""
    if mysql -u root -e "SELECT 1" &>/dev/null; then
        log_info "Securing MySQL installation..."
        mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_PASS}_root';"
        MYSQL_ROOT_AUTH="-u root -p${DB_PASS}_root"
    elif [[ -f /etc/mysql/debian.cnf ]] && mysql --defaults-file=/etc/mysql/debian.cnf -e "SELECT 1" &>/dev/null; then
        log_info "Using existing MySQL via /etc/mysql/debian.cnf (root password preserved)."
        MYSQL_ROOT_AUTH="--defaults-file=/etc/mysql/debian.cnf"
    else
        log_warning "MySQL root credentials not auto-detected."
        log_warning "Skipping database/user creation — you must create database '${DB_NAME}' and user '${DB_USER}'@'localhost' manually."
        log_warning "Then re-run installer or update .env DB_PASSWORD accordingly."
        return 0
    fi

    # Clean up default users and databases (best-effort)
    mysql ${MYSQL_ROOT_AUTH} -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null || true
    mysql ${MYSQL_ROOT_AUTH} -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" 2>/dev/null || true
    mysql ${MYSQL_ROOT_AUTH} -e "DROP DATABASE IF EXISTS test;" 2>/dev/null || true
    mysql ${MYSQL_ROOT_AUTH} -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';" 2>/dev/null || true
    mysql ${MYSQL_ROOT_AUTH} -e "FLUSH PRIVILEGES;" 2>/dev/null || true

    # Create application database and user
    log_info "Creating database and user..."
    mysql ${MYSQL_ROOT_AUTH} -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql ${MYSQL_ROOT_AUTH} -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql ${MYSQL_ROOT_AUTH} -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
    mysql ${MYSQL_ROOT_AUTH} -e "FLUSH PRIVILEGES;"

    # MySQL tuning for high-volume billing
    log_info "Applying MySQL performance tuning..."
    TOTAL_RAM_MB=$(free -m | awk '/^Mem:/{print $2}')
    BUFFER_POOL_MB=$((TOTAL_RAM_MB * 65 / 100))
    [[ $BUFFER_POOL_MB -gt 32768 ]] && BUFFER_POOL_MB=32768
    BUFFER_POOL_SIZE="${BUFFER_POOL_MB}M"

    # /etc/mysql/conf.d/ is the universal include directory on Ubuntu/Debian
    # (read by both MySQL and MariaDB layouts of /etc/mysql/my.cnf).
    # /etc/mysql/mysql.conf.d/ is NOT read on systems where my.cnf is the
    # MariaDB-shaped sample (Ubuntu 22.04+).
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        MYSQL_CONF_DIR="/etc/my.cnf.d"
    else
        MYSQL_CONF_DIR="/etc/mysql/conf.d"
    fi

    sed "s/__BUFFER_POOL_SIZE__/${BUFFER_POOL_SIZE}/" \
        "${SCRIPT_DIR}/templates/mysql-tuning.cnf.template" \
        > "${MYSQL_CONF_DIR}/rswitch-tuning.cnf"

    # systemd LimitNOFILE caps mysqld's open_files_limit regardless of what
    # the .cnf says. Without this drop-in, MySQL silently shrinks
    # table_open_cache and runs out of FDs under high connection load.
    if [[ -d /etc/systemd/system && -f /usr/lib/systemd/system/mysql.service ]]; then
        mkdir -p /etc/systemd/system/mysql.service.d
        cat > /etc/systemd/system/mysql.service.d/limits.conf << 'LIMEOF'
[Service]
LimitNOFILE=65535
LimitNPROC=65535
LIMEOF
        systemctl daemon-reload
    fi

    systemctl restart mysql 2>/dev/null || systemctl restart mysqld 2>/dev/null
    log_info "innodb_buffer_pool_size set to ${BUFFER_POOL_SIZE} (65% of ${TOTAL_RAM_MB}MB RAM)"

    # Create CDR archive directory
    mkdir -p /var/backups/rswitch/cdr
    chown www-data:www-data /var/backups/rswitch/cdr 2>/dev/null || chown nginx:nginx /var/backups/rswitch/cdr 2>/dev/null

    # Disk space warning
    DISK_GB=$(df -BG / | awk 'NR==2{print $2}' | tr -d 'G')
    if [[ "$DISK_GB" -lt 400 ]]; then
        log_warning "Disk space: ${DISK_GB}GB detected. Recommended 400GB+ for high-volume (10M calls/day)."
    fi

    log_success "MySQL installed and configured"
}

install_redis() {
    log_step "Installing Redis"

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q redis
        REDIS_CONF="/etc/redis/redis.conf"
    else
        apt-get install -y -qq redis-server
        REDIS_CONF="/etc/redis/redis.conf"
    fi

    # Configure Redis
    sed -i "s/^supervised .*/supervised systemd/" $REDIS_CONF
    sed -i "s/^# maxmemory .*/maxmemory 256mb/" $REDIS_CONF
    sed -i "s/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/" $REDIS_CONF

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        systemctl restart redis
        systemctl enable redis
    else
        systemctl restart redis-server
        systemctl enable redis-server
    fi

    log_success "Redis installed and configured"
}

install_nginx() {
    log_step "Installing Nginx"

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q nginx
        # SELinux: allow nginx to connect to network
        setsebool -P httpd_can_network_connect 1 2>/dev/null || true
    else
        apt-get install -y -qq nginx
        # Remove default site
        rm -f /etc/nginx/sites-enabled/default
    fi

    systemctl start nginx
    systemctl enable nginx

    log_success "Nginx installed"
}

install_asterisk() {
    log_step "Installing Asterisk $ASTERISK_VERSION"

    # Install build dependencies
    log_info "Installing Asterisk build dependencies..."
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf groupinstall -y -q "Development Tools"
        dnf install -y -q \
            ncurses-devel \
            jansson-devel \
            libxml2-devel \
            sqlite-devel \
            libuuid-devel \
            openssl-devel \
            libedit-devel \
            libsrtp-devel \
            spandsp-devel \
            libcurl-devel \
            newt-devel \
            libogg-devel \
            libvorbis-devel \
            speex-devel \
            unbound-devel \
            unixODBC \
            unixODBC-devel \
            mariadb-connector-odbc \
            mariadb-devel \
            freetds-devel \
            libpq-devel \
            opus-devel
    else
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
            libvpb-dev \
            subversion
    fi

    # Download Asterisk
    log_info "Downloading Asterisk $ASTERISK_VERSION..."
    cd /usr/src
    if [[ ! -f asterisk-${ASTERISK_VERSION}.tar.gz ]]; then
        wget -q https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${ASTERISK_VERSION}.tar.gz
    fi
    if [[ ! -d asterisk-${ASTERISK_VERSION} ]]; then
        tar -xzf asterisk-${ASTERISK_VERSION}.tar.gz
    fi
    cd asterisk-${ASTERISK_VERSION}

    # Install MP3 support
    log_info "Installing MP3 support..."
    contrib/scripts/get_mp3_source.sh || true

    # Install prerequisites
    log_info "Installing Asterisk prerequisites..."
    export DEBIAN_FRONTEND=noninteractive
    yes | contrib/scripts/install_prereq install || true

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
        --enable MOH-OPSOUND-WAV \
        --enable MOH-OPSOUND-ULAW \
        --enable MOH-OPSOUND-ALAW \
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

    # Allow www-data (PHP) to query Asterisk CLI for registration status
    echo 'www-data ALL=(ALL) NOPASSWD: /usr/sbin/asterisk' > /etc/sudoers.d/asterisk-www-data
    chmod 440 /etc/sudoers.d/asterisk-www-data
    log_info "Configured sudoers for www-data to access Asterisk CLI"

    # Ensure no dangerous rasterisk killer cron jobs exist
    if crontab -l 2>/dev/null | grep -q 'pkill.*rasterisk'; then
        crontab -l 2>/dev/null | grep -v 'pkill.*rasterisk' | crontab -
        log_warning "Removed dangerous rasterisk killer from crontab"
    fi
    rm -f /usr/local/bin/asterisk-cleanup

    # Enable Asterisk to start on boot
    systemctl enable asterisk 2>/dev/null || true

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
max_connections => 100
connect_timeout => 10
logging => no
EOF

    log_success "ODBC configured"
}

configure_asterisk() {
    log_step "Configuring Asterisk for rSwitch"

    # PJSIP configuration
    cat > /etc/asterisk/pjsip.conf << 'EOF'
[global]
type=global
max_initial_qualify_time=4
keep_alive_interval=30
user_agent=rSwitch 2.01

[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060
cos=5
tos=0xB8

[transport-tcp]
type=transport
protocol=tcp
bind=0.0.0.0:5060

; [transport-tls]
; type=transport
; protocol=tls
; bind=0.0.0.0:5061
; cert_file=/etc/asterisk/keys/asterisk.crt
; priv_key_file=/etc/asterisk/keys/asterisk.key
; method=tlsv1_2

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

    # Sorcery configuration (PJSIP realtime mapping)
    cat > /etc/asterisk/sorcery.conf << 'EOF'
[res_pjsip]
endpoint=realtime,ps_endpoints
auth=realtime,ps_auths
aor=realtime,ps_aors
contact=realtime,ps_contacts

[res_pjsip_endpoint_identifier_ip]
identify=realtime,ps_endpoint_id_ips
EOF

    # RTP configuration (optimized for 1000+ calls)
    cat > /etc/asterisk/rtp.conf << 'EOF'
[general]
rtpstart=10000
rtpend=30000
strictrtp=yes
icesupport=no
stunaddr=
EOF

    # Extensions configuration (Python FastAGI call control on port 4573)
    if [[ -f "${INSTALL_DIR}/docker/asterisk/conf/extensions.conf" ]]; then
        cp "${INSTALL_DIR}/docker/asterisk/conf/extensions.conf" /etc/asterisk/extensions.conf
    else
        cat > /etc/asterisk/extensions.conf << 'EOF'
[general]
static=yes
writeprotect=yes
clearglobalvars=yes

[globals]
AGI_HOST=127.0.0.1
AGI_PORT=4573

[from-internal]
exten => _X.,1,NoOp(Outbound call from ${CALLERID(all)} to ${EXTEN})
 same => n,Set(CHANNEL(language)=en)
 same => n,Set(CHANNEL(hangup_handler_push)=hangup-handler,s,1)
 same => n,AGI(agi://${AGI_HOST}:${AGI_PORT}/route_outbound)
 same => n,GotoIf($["${AGISTATUS}" != "SUCCESS"]?error)
 same => n,GotoIf($["${ROUTE_ACTION}" = "REJECT"]?reject)
 same => n,GotoIf($["${ROUTE_ACTION}" = "DIAL_INTERNAL"]?internal)
 same => n,Set(CALLERID(name)=${ROUTE_CLI_NAME})
 same => n,Set(CALLERID(num)=${ROUTE_CLI_NUM})
 same => n,ExecIf($["${RECORD_CALL}" = "1"]?MixMonitor(/var/spool/asterisk/recording/${CDR_UUID}.wav,b))
 same => n,Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gT)
 same => n,GotoIf($["${DIALSTATUS}" = "ANSWER"]?done)
 same => n,GotoIf($["${ROUTE_FAILOVER}" = ""]?done)
 same => n,Dial(${ROUTE_FAILOVER},${ROUTE_DIAL_TIMEOUT},gT)
 same => n,Goto(done)
 same => n(internal),Set(CALLERID(name)=${ROUTE_CLI_NAME})
 same => n,Set(CALLERID(num)=${ROUTE_CLI_NUM})
 same => n,Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gT)
 same => n,Goto(done)
 same => n(reject),Answer()
 same => n,Playback(ss-noservice)
 same => n,Hangup()
 same => n(error),Answer()
 same => n,Playback(an-error-has-occurred)
 same => n,Hangup()
 same => n(done),Hangup()

[from-trunk]
exten => _X.,1,NoOp(Inbound call from ${CALLERID(all)} to DID ${EXTEN})
 same => n,Set(CHANNEL(language)=en)
 same => n,Set(TRUNK_ENDPOINT=${CHANNEL(pjsip,endpoint)})
 same => n,Set(CHANNEL(hangup_handler_push)=hangup-handler,s,1)
 same => n,AGI(agi://${AGI_HOST}:${AGI_PORT}/route_inbound)
 same => n,GotoIf($["${AGISTATUS}" != "SUCCESS"]?error)
 same => n,GotoIf($["${ROUTE_ACTION}" = "REJECT"]?reject)
 same => n,Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gT)
 same => n,Goto(done)
 same => n(reject),Answer()
 same => n,Playback(ss-noservice)
 same => n,Hangup()
 same => n(error),Answer()
 same => n,Playback(an-error-has-occurred)
 same => n,Hangup()
 same => n(done),Hangup()

[hangup-handler]
exten => s,1,NoOp(Hangup handler — CDR UUID: ${CDR_UUID})
 same => n,GotoIf($["${CDR_UUID}" = ""]?done)
 same => n,Set(CALL_DURATION=${CDR(duration)})
 same => n,Set(CALL_BILLSEC=${CDR(billsec)})
 same => n,AGI(agi://${AGI_HOST}:${AGI_PORT}/call_end)
 same => n(done),Return()

[echo-test]
exten => 600,1,Answer()
 same => n,Playback(demo-echotest)
 same => n,Echo()
 same => n,Playback(demo-echodone)
 same => n,Hangup()
EOF
    fi

    # Modules — hardened: only load what rSwitch needs
    cat > /etc/asterisk/modules.conf << 'EOF'
[modules]
autoload = yes
preload = res_odbc.so
preload = res_config_odbc.so

; Unused channel drivers
noload = chan_alsa.so
noload = chan_console.so
noload = chan_skinny.so
noload = chan_unistim.so
noload = chan_phone.so
noload = chan_mgcp.so
noload = chan_oss.so
noload = chan_sip.so
noload = chan_iax2.so
noload = chan_dahdi.so
noload = chan_audiosocket.so
noload = chan_motif.so
noload = chan_rtp.so

; Unused apps
noload = app_agent_pool.so
noload = app_alarmreceiver.so
noload = app_audiosocket.so
noload = app_dictate.so
noload = app_disa.so
noload = app_dtmfstore.so
noload = app_externalivr.so
noload = app_followme.so
noload = app_jack.so
noload = app_mf.so
noload = app_minivm.so
noload = app_morsecode.so
noload = app_mp3.so
noload = app_privacy.so
noload = app_sf.so
noload = app_signal.so
noload = app_sms.so
noload = app_stream_echo.so
noload = app_test.so
noload = app_waitforcond.so
noload = app_waitforring.so
noload = app_waitforsilence.so
noload = app_zapateller.so
noload = app_festival.so
noload = app_amd.so
noload = app_getcpeid.so
noload = app_adsiprog.so

; ARI REST API (not used, security risk)
noload = res_ari.so
noload = res_ari_applications.so
noload = res_ari_asterisk.so
noload = res_ari_bridges.so
noload = res_ari_channels.so
noload = res_ari_device_states.so
noload = res_ari_endpoints.so
noload = res_ari_events.so
noload = res_ari_model.so
noload = res_ari_playbacks.so
noload = res_ari_recordings.so
noload = res_ari_sounds.so

; Unused codecs
noload = codec_codec2.so
noload = codec_lpc10.so
noload = codec_ilbc.so
noload = codec_speex.so
noload = codec_g726.so

; Unused CDR/CEL backends
noload = cdr_adaptive_odbc.so
noload = cdr_pgsql.so
noload = cdr_tds.so
noload = cdr_sqlite3_custom.so
noload = cdr_radius.so
noload = cdr_odbc.so
noload = cel_pgsql.so
noload = cel_tds.so
noload = cel_sqlite3_custom.so
noload = cel_radius.so
noload = cel_odbc.so

; Unused resource modules
noload = res_pjsip_phoneprov.so
noload = res_config_pgsql.so
noload = res_config_ldap.so
noload = res_config_sqlite3.so
noload = res_config_curl.so
noload = res_hep.so
noload = res_hep_pjsip.so
noload = res_hep_rtcp.so
noload = res_calendar.so
noload = res_fax.so
noload = res_fax_spandsp.so
noload = res_adsi.so
noload = res_smdi.so
noload = res_snmp.so
noload = res_xmpp.so
noload = res_phoneprov.so
noload = res_audiosocket.so
noload = res_ael_share.so
noload = res_prometheus.so
noload = res_statsd.so
noload = res_rtp_multicast.so
noload = res_geolocation.so
noload = res_stun_monitor.so
noload = res_tonedetect.so
noload = res_corosync.so
noload = res_calendar_caldav.so
noload = res_calendar_icalendar.so
noload = res_calendar_exchange.so
noload = res_calendar_ews.so
noload = res_pjsip_geolocation.so
noload = res_pjsip_phoneprov_provider.so
noload = res_aeap.so

; Unused PBX
noload = pbx_lua.so
noload = pbx_ael.so
noload = pbx_dundi.so
noload = pbx_realtime.so

; Unused formats
noload = format_g719.so
noload = format_g723.so
noload = format_g726.so
noload = format_g729.so
noload = format_h263.so
noload = format_h264.so
noload = format_ilbc.so
noload = format_ogg_speex.so
noload = format_ogg_vorbis.so
noload = format_siren14.so
noload = format_siren7.so
noload = format_vox.so
EOF

    # Manager configuration (AMI)
    AMI_SECRET=$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)
    cat > /etc/asterisk/manager.conf << EOF
[general]
enabled = yes
port = 5038
bindaddr = 127.0.0.1
webenabled = no

[rswitch]
secret = ${AMI_SECRET}
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.255
read = command,system,call,cdr
write = command,system,call
EOF

    # Logger configuration (with security log for Fail2Ban)
    cat > /etc/asterisk/logger.conf << 'EOF'
[general]
dateformat=%F %T.%3q

[logfiles]
console => notice,warning,error
messages => notice,warning,error
full => notice,warning,error,debug,verbose
security => security
EOF

    # Asterisk core configuration
    # Detect module path for this OS
    if [[ -d "/usr/lib/x86_64-linux-gnu/asterisk/modules" ]]; then
        AST_MOD_DIR="/usr/lib/x86_64-linux-gnu/asterisk/modules"
    elif [[ -d "/usr/lib64/asterisk/modules" ]]; then
        AST_MOD_DIR="/usr/lib64/asterisk/modules"
    else
        AST_MOD_DIR="/usr/lib/asterisk/modules"
    fi

    cat > /etc/asterisk/asterisk.conf << EOF
[directories]
astetcdir => /etc/asterisk
astmoddir => ${AST_MOD_DIR}
astvarlibdir => /var/lib/asterisk
astdbdir => /var/lib/asterisk
astkeydir => /var/lib/asterisk
astdatadir => /usr/share/asterisk
astagidir => /usr/share/asterisk/agi-bin
astspooldir => /var/spool/asterisk
astrundir => /var/run/asterisk
astlogdir => /var/log/asterisk

[options]
runuser = asterisk
rungroup = asterisk
verbose = 1
debug = 0
highpriority = yes
maxcalls = 3000
maxload = 0.9
cache_record_files = yes
transmit_silence = yes
hideconnect = yes
live_dangerously = no
timestamp = yes
EOF

    # Create voicebroadcast + voice-files directories
    mkdir -p /var/spool/asterisk/voicebroadcast
    mkdir -p /var/spool/asterisk/outgoing
    mkdir -p /var/spool/asterisk/recording
    chown asterisk:asterisk /var/spool/asterisk/voicebroadcast
    chmod 775 /var/spool/asterisk/voicebroadcast
    chown asterisk:asterisk /var/spool/asterisk/recording
    # outgoing: www-data writes .call files, asterisk reads them
    chown asterisk:asterisk /var/spool/asterisk/outgoing
    chmod 775 /var/spool/asterisk/outgoing
    usermod -aG asterisk www-data 2>/dev/null || true
    mkdir -p "$INSTALL_DIR/storage/app/private/voice-files"
    chown www-data:www-data "$INSTALL_DIR/storage/app/private/voice-files"

    # File descriptor limits — high for 3000+ concurrent calls
    cat > /etc/security/limits.d/asterisk.conf << 'LIMEOF'
asterisk soft nofile 131072
asterisk hard nofile 131072
LIMEOF

    mkdir -p /etc/systemd/system/asterisk.service.d
    cat > /etc/systemd/system/asterisk.service.d/limits.conf << 'LIMEOF'
[Service]
LimitNOFILE=131072
LimitCORE=infinity
Nice=-10
LIMEOF
    systemctl daemon-reload

    # Kernel tuning for high-volume SIP/RTP
    cat > /etc/sysctl.d/99-rswitch-asterisk.conf << 'SYSEOF'
# rSwitch Asterisk Performance Tuning
# Network buffers for SIP/RTP traffic
net.core.rmem_max = 26214400
net.core.wmem_max = 26214400
net.core.rmem_default = 1048576
net.core.wmem_default = 1048576
net.core.somaxconn = 4096
net.core.netdev_max_backlog = 10000

# TCP tuning — syn backlog raised for 50-70 cps signaling bursts
net.ipv4.tcp_max_syn_backlog = 8192
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15

# UDP buffer
net.ipv4.udp_mem = 65536 131072 262144
net.ipv4.udp_rmem_min = 8192
net.ipv4.udp_wmem_min = 8192

# Connection tracking (for NAT/firewall)
net.netfilter.nf_conntrack_max = 262144
net.netfilter.nf_conntrack_udp_timeout = 30
net.netfilter.nf_conntrack_udp_timeout_stream = 60

# File handles — raised for 1000+ concurrent calls
fs.file-max = 524288
SYSEOF
    sysctl -p /etc/sysctl.d/99-rswitch-asterisk.conf 2>/dev/null || true
    log_success "Kernel tuning applied"

    # Create SSL directory
    mkdir -p /etc/asterisk/keys
    chown asterisk:asterisk /etc/asterisk/keys

    # Set ownership
    chown -R asterisk:asterisk /etc/asterisk

    # Deploy IVR sound files
    log_info "Deploying IVR sound files..."
    AST_SOUNDS="/usr/share/asterisk/sounds/en"
    if [[ ! -d "$AST_SOUNDS" ]]; then
        AST_SOUNDS="/var/lib/asterisk/sounds/en"
    fi
    mkdir -p "${AST_SOUNDS}/IVR"
    if [[ -f "${INSTALL_DIR}/IVR/wrong_number.gsm" ]]; then
        cp "${INSTALL_DIR}/IVR/wrong_number.gsm" "${AST_SOUNDS}/IVR/wrong_number.gsm"
    fi
    chown -R asterisk:asterisk "${AST_SOUNDS}/IVR"

    # Enable Asterisk service
    systemctl enable asterisk

    log_success "Asterisk configured for rSwitch"
}

install_application() {
    log_step "Installing rSwitch Application"

    # Determine web user based on OS
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        WEB_USER="nginx"
    else
        WEB_USER="www-data"
    fi

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
    chown -R ${WEB_USER}:${WEB_USER} $INSTALL_DIR

    # Install Composer dependencies
    log_info "Installing Composer dependencies..."
    cd $INSTALL_DIR
    sudo -u ${WEB_USER} composer install --no-dev --optimize-autoloader --no-interaction
    sudo -u ${WEB_USER} composer dump-autoload

    # Create .env file
    log_info "Creating environment configuration..."
    cp .env.example .env

    # Update .env (handle both commented and uncommented lines)
    sed -i "s|^APP_NAME=.*|APP_NAME=rSwitch|" .env
    sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
    sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
    sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
    sed -i "s|^# DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
    sed -i "s|^# DB_HOST=.*|DB_HOST=127.0.0.1|" .env
    sed -i "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" .env
    sed -i "s|^# DB_PORT=.*|DB_PORT=3306|" .env
    sed -i "s|^DB_PORT=.*|DB_PORT=3306|" .env
    sed -i "s|^# DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
    sed -i "s|^# DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
    sed -i "s|^# DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
    sed -i "s|^CACHE_STORE=.*|CACHE_STORE=redis|" .env
    sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=redis|" .env
    sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" .env
    sed -i "s|^REDIS_HOST=.*|REDIS_HOST=127.0.0.1|" .env

    # Ensure .env is owned by web user before artisan commands
    chown ${WEB_USER}:${WEB_USER} .env

    # Add AGI and AMI configuration
    # Read AMI secret from manager.conf (generated during configure_asterisk)
    AMI_SECRET_FOR_ENV=$(grep '^secret' /etc/asterisk/manager.conf 2>/dev/null | head -1 | awk -F'= ' '{print $2}' | tr -d ' ')

    cat >> .env << EOF

# Asterisk AGI Server (Python FastAGI)
AGI_HOST=127.0.0.1
AGI_PORT=4573

# Asterisk AMI
AMI_HOST=127.0.0.1
AMI_PORT=5038
AMI_USER=rswitch
AMI_SECRET=${AMI_SECRET_FOR_ENV}

BROADCAST_VOICE_PATH=/var/spool/asterisk/voicebroadcast

# Python Billing API (bare metal = localhost:8001)
PYTHON_API_URL=http://127.0.0.1:8001
EOF

    # Generate application key
    sudo -u ${WEB_USER} php artisan key:generate --force

    # Run migrations
    log_info "Running database migrations..."
    sudo -u ${WEB_USER} php artisan migrate --force

    # Fix ps_contacts table for Asterisk 20+ compatibility
    log_info "Updating ps_contacts table for Asterisk compatibility..."
    mysql -u${DB_USER} -p"${DB_PASS}" ${DB_NAME} -e "
        ALTER TABLE ps_contacts
            ADD COLUMN IF NOT EXISTS via_addr VARCHAR(40) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS via_port INT DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS call_id VARCHAR(255) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS endpoint VARCHAR(40) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS prune_on_boot VARCHAR(5) DEFAULT 'no',
            ADD COLUMN IF NOT EXISTS authenticate_qualify VARCHAR(5) DEFAULT 'no',
            ADD COLUMN IF NOT EXISTS qualify_timeout FLOAT DEFAULT 3.0;
    " 2>/dev/null || {
        # MySQL < 8.0.16 doesn't support IF NOT EXISTS for ADD COLUMN
        for col in "via_addr VARCHAR(40)" "via_port INT" "call_id VARCHAR(255)" "endpoint VARCHAR(40)" "prune_on_boot VARCHAR(5) DEFAULT 'no'" "authenticate_qualify VARCHAR(5) DEFAULT 'no'" "qualify_timeout FLOAT DEFAULT 3.0"; do
            COL_NAME=$(echo "$col" | awk '{print $1}')
            mysql -u${DB_USER} -p"${DB_PASS}" ${DB_NAME} -e "ALTER TABLE ps_contacts ADD COLUMN $col;" 2>/dev/null || true
        done
    }

    # Seed database
    log_info "Seeding database..."
    sudo -u ${WEB_USER} php artisan db:seed --force

    # Install NPM dependencies and build assets
    log_info "Building frontend assets..."
    mkdir -p /var/www/.npm && chown -R ${WEB_USER}:${WEB_USER} /var/www/.npm
    sudo -u ${WEB_USER} npm ci
    sudo -u ${WEB_USER} npm run build

    # Create storage link
    sudo -u ${WEB_USER} php artisan storage:link

    # Cache configuration
    log_info "Caching configuration..."
    sudo -u ${WEB_USER} php artisan config:cache
    sudo -u ${WEB_USER} php artisan route:cache
    sudo -u ${WEB_USER} php artisan view:cache

    # Set permissions
    chmod -R 775 storage bootstrap/cache
    chown -R ${WEB_USER}:${WEB_USER} storage bootstrap/cache

    log_success "rSwitch application installed"
}

install_python_services() {
    log_step "Installing Python Billing + Call Control Services"

    # Determine web user
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        WEB_USER="nginx"
    else
        WEB_USER="www-data"
    fi

    cd "${INSTALL_DIR}/python-services"

    # Create virtual environment
    log_info "Creating Python virtual environment..."
    python3 -m venv venv
    source venv/bin/activate
    pip install --upgrade pip --quiet
    pip install -r requirements.txt --quiet
    deactivate

    # Create Python .env
    log_info "Configuring Python services..."

    # Generate a separate Python DB user password
    PYTHON_DB_PASS=$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 16)

    # Create Python DB user
    mysql -e "
        CREATE USER IF NOT EXISTS 'python_svc'@'localhost' IDENTIFIED BY '${PYTHON_DB_PASS}';
        GRANT SELECT ON ${DB_NAME}.* TO 'python_svc'@'localhost';
        GRANT SELECT, INSERT, UPDATE ON ${DB_NAME}.call_records TO 'python_svc'@'localhost';
        GRANT SELECT, INSERT, UPDATE ON ${DB_NAME}.transactions TO 'python_svc'@'localhost';
        GRANT SELECT, INSERT, UPDATE ON ${DB_NAME}.invoices TO 'python_svc'@'localhost';
        GRANT SELECT, UPDATE ON ${DB_NAME}.users TO 'python_svc'@'localhost';
        FLUSH PRIVILEGES;
    " 2>/dev/null || log_warn "Python DB user may already exist"

    # Read AMI secret from Asterisk manager.conf
    AMI_SECRET_VALUE=$(grep '^secret' /etc/asterisk/manager.conf 2>/dev/null | head -1 | awk -F'= ' '{print $2}' | tr -d ' ')
    if [[ -z "$AMI_SECRET_VALUE" ]]; then
        AMI_SECRET_VALUE="${DB_PASS}"
    fi

    cat > .env << EOF
DATABASE_URL=mysql+pymysql://python_svc:${PYTHON_DB_PASS}@127.0.0.1:3306/${DB_NAME}
ASYNC_DATABASE_URL=mysql+aiomysql://python_svc:${PYTHON_DB_PASS}@127.0.0.1:3306/${DB_NAME}
REDIS_URL=redis://127.0.0.1:6379/0
ASTERISK_AMI_HOST=127.0.0.1
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USER=rswitch
ASTERISK_AMI_SECRET=${AMI_SECRET_VALUE}
DEBUG=false
LOG_LEVEL=info
EOF

    # Fix ownership
    chown -R ${WEB_USER}:${WEB_USER} "${INSTALL_DIR}/python-services"

    # Save Python credentials
    echo "PYTHON_DB_USER=python_svc" >> /root/rswitch-credentials.txt
    echo "PYTHON_DB_PASS=${PYTHON_DB_PASS}" >> /root/rswitch-credentials.txt

    log_success "Python billing + call control services installed"
}

configure_nginx_site() {
    log_step "Configuring Nginx for rSwitch"

    # Determine PHP-FPM socket path and nginx config directory
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        PHP_FPM_SOCK="/run/php-fpm/www.sock"
        NGINX_CONF_DIR="/etc/nginx/conf.d"
        NGINX_CONF_FILE="${NGINX_CONF_DIR}/rswitch.conf"
    else
        PHP_FPM_SOCK="/var/run/php/php${PHP_VERSION}-fpm.sock"
        NGINX_CONF_DIR="/etc/nginx/sites-available"
        NGINX_CONF_FILE="${NGINX_CONF_DIR}/rswitch"
    fi

    cat > ${NGINX_CONF_FILE} << EOF
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
        fastcgi_pass unix:${PHP_FPM_SOCK};
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

    # Enable site (Debian/Ubuntu only - RHEL uses conf.d which auto-loads)
    if [[ "$OS" != "centos" && "$OS" != "almalinux" ]]; then
        ln -sf /etc/nginx/sites-available/rswitch /etc/nginx/sites-enabled/
    fi

    # Test nginx configuration
    nginx -t

    # Reload nginx
    systemctl reload nginx

    log_success "Nginx configured"
}

configure_supervisor() {
    log_step "Configuring Supervisor for Queue Workers"

    # Determine web user based on OS
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        WEB_USER="nginx"
        SUPERVISOR_CONF_DIR="/etc/supervisord.d"
        SUPERVISORD_CONF="/etc/supervisord.conf"
    else
        WEB_USER="www-data"
        SUPERVISOR_CONF_DIR="/etc/supervisor/conf.d"
        SUPERVISORD_CONF="/etc/supervisor/supervisord.conf"
    fi

    mkdir -p $SUPERVISOR_CONF_DIR

    # Raise supervisord's own FD/proc limits so all children inherit them.
    # supervisord defaults to soft NOFILE=1024; with 1000+ concurrent calls
    # each holding multiple sockets, that exhausts within minutes.
    # Per-program minfds= is silently ignored — must set at [supervisord] level.
    if [[ -f "$SUPERVISORD_CONF" ]] && ! grep -qE "^minfds=" "$SUPERVISORD_CONF"; then
        sed -i "/^\[supervisord\]/a minfds=65536\nminprocs=4096" "$SUPERVISORD_CONF"
    fi

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

[program:rswitch-webhook-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work redis --queue=webhooks --sleep=5 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${WEB_USER}
numprocs=1
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/webhook-worker.log

[program:rswitch-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while true; do php ${INSTALL_DIR}/artisan schedule:run >> /dev/null 2>&1; sleep 60; done"
autostart=true
autorestart=true
user=${WEB_USER}
numprocs=1
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/scheduler.log

; --- Python Services (Billing + Call Control + Live Monitoring) ---

[program:rswitch-api]
command=${INSTALL_DIR}/python-services/venv/bin/uvicorn main:app --host 127.0.0.1 --port 8001 --workers 1
directory=${INSTALL_DIR}/python-services
environment=PYTHONPATH="${INSTALL_DIR}/python-services"
user=${WEB_USER}
autostart=true
autorestart=true
stderr_logfile=/var/log/rswitch-python-api.err.log
stdout_logfile=/var/log/rswitch-python-api.out.log
stopwaitsecs=10

[program:rswitch-celery]
command=${INSTALL_DIR}/python-services/venv/bin/celery -A celery_app worker -l info -Q billing,monitoring,broadcast -c 12
directory=${INSTALL_DIR}/python-services
environment=PYTHONPATH="${INSTALL_DIR}/python-services"
user=${WEB_USER}
autostart=true
autorestart=true
stderr_logfile=/var/log/rswitch-celery.err.log
stdout_logfile=/var/log/rswitch-celery.out.log
stopwaitsecs=30

[program:rswitch-celery-beat]
command=${INSTALL_DIR}/python-services/venv/bin/celery -A celery_app beat -l info
directory=${INSTALL_DIR}/python-services
environment=PYTHONPATH="${INSTALL_DIR}/python-services"
user=${WEB_USER}
autostart=true
autorestart=true
stderr_logfile=/var/log/rswitch-celery-beat.err.log
stdout_logfile=/var/log/rswitch-celery-beat.out.log
EOF

    # Reload supervisor
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        systemctl restart supervisord
    else
        supervisorctl reread
        supervisorctl update
        supervisorctl start all
    fi

    log_success "Supervisor configured"
}

configure_firewall() {
    log_step "Configuring Firewall"

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        # Enable firewalld
        systemctl start firewalld
        systemctl enable firewalld

        # Allow SSH
        firewall-cmd --permanent --add-service=ssh

        # Allow HTTP/HTTPS
        firewall-cmd --permanent --add-service=http
        firewall-cmd --permanent --add-service=https

        # Allow SIP
        firewall-cmd --permanent --add-port=5060/udp
        firewall-cmd --permanent --add-port=5060/tcp
        firewall-cmd --permanent --add-port=5061/tcp

        # Allow RTP (range matches rtp.conf.template rtpend=40000)
        firewall-cmd --permanent --add-port=10000-40000/udp

        # Reload firewall
        firewall-cmd --reload
    else
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

        # Allow RTP (range matches rtp.conf.template rtpend=40000)
        ufw allow 10000:40000/udp

        # Reload firewall
        ufw reload
    fi

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

configure_ssl() {
    log_step "Configuring SSL Certificate"

    case "$SSL_TYPE" in
        letsencrypt)
            configure_ssl_letsencrypt
            ;;
        commercial)
            configure_ssl_commercial
            ;;
        skip)
            log_warning "SSL configuration skipped. Site will be HTTP only."
            log_warning "You can manually configure SSL later."
            ;;
    esac
}

configure_ssl_letsencrypt() {
    log_info "Setting up Let's Encrypt SSL..."

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q certbot python3-certbot-nginx
    else
        apt-get install -y -qq certbot python3-certbot-nginx
    fi

    log_info "Obtaining SSL certificate..."
    certbot --nginx -d ${DOMAIN} --non-interactive --agree-tos --email ${ADMIN_EMAIL} --redirect || {
        log_warning "Automatic SSL setup failed. You can manually run:"
        echo "  certbot --nginx -d ${DOMAIN}"
        return
    }

    # Set up auto-renewal cron
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        echo "0 3 * * * root certbot renew --quiet" > /etc/cron.d/certbot-renew
    fi

    log_success "Let's Encrypt SSL configured with auto-renewal"
}

configure_ssl_commercial() {
    log_info "Setting up Commercial SSL Certificate..."

    # Create SSL directory
    SSL_DIR="/etc/nginx/ssl/${DOMAIN}"
    mkdir -p ${SSL_DIR}

    # Generate private key
    log_info "Generating private key..."
    openssl genrsa -out ${SSL_DIR}/${DOMAIN}.key 2048
    chmod 600 ${SSL_DIR}/${DOMAIN}.key

    # Generate CSR (Certificate Signing Request)
    log_info "Generating Certificate Signing Request (CSR)..."
    openssl req -new -key ${SSL_DIR}/${DOMAIN}.key \
        -out ${SSL_DIR}/${DOMAIN}.csr \
        -subj "/C=US/ST=State/L=City/O=Organization/OU=IT/CN=${DOMAIN}"

    # Create a placeholder for the certificate
    touch ${SSL_DIR}/${DOMAIN}.crt
    touch ${SSL_DIR}/${DOMAIN}.ca-bundle

    # Display CSR for user
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}  COMMERCIAL SSL CERTIFICATE SETUP${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "${YELLOW}Your Certificate Signing Request (CSR) has been generated.${NC}"
    echo ""
    echo "CSR Location: ${SSL_DIR}/${DOMAIN}.csr"
    echo "Key Location: ${SSL_DIR}/${DOMAIN}.key"
    echo ""
    echo -e "${YELLOW}Next Steps:${NC}"
    echo "  1. Copy the CSR content below"
    echo "  2. Go to your SSL provider (Namecheap, DigiCert, etc.)"
    echo "  3. Paste the CSR when purchasing/activating your certificate"
    echo "  4. Complete domain validation (email, DNS, or file-based)"
    echo "  5. Download your certificate files"
    echo "  6. Install the certificate:"
    echo ""
    echo -e "${CYAN}Installation Commands:${NC}"
    echo "  # Copy your certificate to:"
    echo "  ${SSL_DIR}/${DOMAIN}.crt"
    echo ""
    echo "  # Copy your CA bundle to:"
    echo "  ${SSL_DIR}/${DOMAIN}.ca-bundle"
    echo ""
    echo "  # Then run:"
    echo "  cat ${SSL_DIR}/${DOMAIN}.crt ${SSL_DIR}/${DOMAIN}.ca-bundle > ${SSL_DIR}/${DOMAIN}.chained.crt"
    echo "  systemctl reload nginx"
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}YOUR CSR (Certificate Signing Request):${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo ""
    cat ${SSL_DIR}/${DOMAIN}.csr
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
    echo ""

    # Save CSR to credentials file for later reference
    CSR_CONTENT=$(cat ${SSL_DIR}/${DOMAIN}.csr)

    # Update nginx config for SSL (prepared but using self-signed until commercial cert installed)
    configure_nginx_ssl_commercial

    log_success "CSR generated. Install your commercial SSL certificate to complete setup."
}

configure_nginx_ssl_commercial() {
    # Determine nginx config file
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        PHP_FPM_SOCK="/run/php-fpm/www.sock"
        NGINX_CONF_FILE="/etc/nginx/conf.d/rswitch.conf"
    else
        PHP_FPM_SOCK="/var/run/php/php${PHP_VERSION}-fpm.sock"
        NGINX_CONF_FILE="/etc/nginx/sites-available/rswitch"
    fi

    SSL_DIR="/etc/nginx/ssl/${DOMAIN}"

    # Generate temporary self-signed cert until commercial cert is installed
    log_info "Generating temporary self-signed certificate..."
    openssl req -x509 -nodes -days 365 \
        -key ${SSL_DIR}/${DOMAIN}.key \
        -out ${SSL_DIR}/${DOMAIN}.chained.crt \
        -subj "/C=US/ST=State/L=City/O=Organization/OU=IT/CN=${DOMAIN}"

    cat > ${NGINX_CONF_FILE} << EOF
# HTTP - Redirect to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 https://\$server_name\$request_uri;
}

# HTTPS
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN};
    root ${INSTALL_DIR}/public;

    # SSL Configuration
    ssl_certificate ${SSL_DIR}/${DOMAIN}.chained.crt;
    ssl_certificate_key ${SSL_DIR}/${DOMAIN}.key;

    # SSL Security Settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_stapling on;
    ssl_stapling_verify on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

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

    location ~ \.php\$ {
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)\$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
EOF

    # Test and reload nginx
    nginx -t && systemctl reload nginx
}

create_admin_user() {
    log_step "Creating Admin User"

    cd $INSTALL_DIR

    # Determine web user
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        WEB_USER="nginx"
    else
        WEB_USER="www-data"
    fi

    # Ensure psysh config dir is writable
    mkdir -p /var/www/.config/psysh && chown -R ${WEB_USER}:${WEB_USER} /var/www/.config

    # Create admin user via MySQL (avoids tinker permission issues)
    HASHED_PASS=$(php -r "echo password_hash('${ADMIN_PASSWORD}', PASSWORD_BCRYPT);")

    MYSQL_ROOT_PASS="${DB_PASS}_root"
    mysql -u root -p"${MYSQL_ROOT_PASS}" ${DB_NAME} -e "
        INSERT INTO users (name, email, password, role, status, email_verified_at, billing_type, balance, created_at, updated_at)
        VALUES ('Super Admin', '${ADMIN_EMAIL}', '${HASHED_PASS}', 'super_admin', 'active', NOW(), 'postpaid', 0, NOW(), NOW())
        ON DUPLICATE KEY UPDATE name=name;
    " 2>/dev/null

    # Set hierarchy path for the admin user
    mysql -u root -p"${MYSQL_ROOT_PASS}" ${DB_NAME} -e "
        UPDATE users SET hierarchy_path = CONCAT('/', id, '/') WHERE hierarchy_path IS NULL OR hierarchy_path = '';
    " 2>/dev/null

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
Type: ${SSL_TYPE}
EOF

    # Add SSL-specific instructions
    case "$SSL_TYPE" in
        letsencrypt)
            cat >> $CREDS_FILE << EOF
Status: Configured with auto-renewal
Renew: certbot renew
EOF
            ;;
        commercial)
            cat >> $CREDS_FILE << EOF
CSR File:     /etc/nginx/ssl/${DOMAIN}/${DOMAIN}.csr
Key File:     /etc/nginx/ssl/${DOMAIN}/${DOMAIN}.key
Cert File:    /etc/nginx/ssl/${DOMAIN}/${DOMAIN}.chained.crt

To install your commercial certificate:
1. Purchase/activate certificate with your SSL provider
2. Download certificate files (.crt and CA bundle)
3. Copy certificate:
   cat your_cert.crt ca_bundle.crt > /etc/nginx/ssl/${DOMAIN}/${DOMAIN}.chained.crt
4. Reload nginx:
   systemctl reload nginx
EOF
            ;;
        skip)
            cat >> $CREDS_FILE << EOF
Status: Not configured (HTTP only)
To add SSL later:
  apt install certbot python3-certbot-nginx
  certbot --nginx -d ${DOMAIN}
EOF
            ;;
    esac

    cat >> $CREDS_FILE << EOF

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

    if [[ "$SSL_TYPE" == "skip" ]]; then
        echo -e "  ${CYAN}Web Interface:${NC}   http://${DOMAIN}"
    else
        echo -e "  ${CYAN}Web Interface:${NC}   https://${DOMAIN}"
    fi

    echo -e "  ${CYAN}Admin Email:${NC}     ${ADMIN_EMAIL}"
    echo -e "  ${CYAN}Admin Password:${NC}  ${ADMIN_PASSWORD}"
    echo ""
    echo -e "  ${YELLOW}Credentials saved to:${NC} /root/rswitch-credentials.txt"
    echo ""
    echo -e "  ${CYAN}Next Steps:${NC}"

    case "$SSL_TYPE" in
        letsencrypt)
            echo "    1. SSL is already configured with Let's Encrypt"
            echo "    2. Start Asterisk: systemctl start asterisk"
            echo "    3. Access web interface and configure your trunks/rates"
            ;;
        commercial)
            echo "    1. Install your commercial SSL certificate (see CSR above)"
            echo "    2. Start Asterisk: systemctl start asterisk"
            echo "    3. Access web interface and configure your trunks/rates"
            echo ""
            echo -e "  ${YELLOW}SSL Certificate Files:${NC}"
            echo "    CSR:  /etc/nginx/ssl/${DOMAIN}/${DOMAIN}.csr"
            echo "    Key:  /etc/nginx/ssl/${DOMAIN}/${DOMAIN}.key"
            echo "    Cert: /etc/nginx/ssl/${DOMAIN}/${DOMAIN}.chained.crt"
            ;;
        skip)
            echo "    1. Configure SSL: certbot --nginx -d ${DOMAIN}"
            echo "    2. Start Asterisk: systemctl start asterisk"
            echo "    3. Access web interface and configure your trunks/rates"
            ;;
    esac

    echo ""
    echo -e "  ${YELLOW}IMPORTANT:${NC} Delete /root/rswitch-credentials.txt after saving!"
    echo ""
}

# =============================================================================
# Monitoring stack (Prometheus + Grafana + 3 exporters, all on this host)
# =============================================================================

configure_monitoring() {
    log_step "Installing monitoring stack (Prometheus + Grafana + exporters)"

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        log_warning "Monitoring auto-install only supported on Debian/Ubuntu — skipping."
        return
    fi

    export DEBIAN_FRONTEND=noninteractive

    # 1. Exporters
    apt-get install -y -qq \
        prometheus prometheus-node-exporter \
        prometheus-mysqld-exporter prometheus-redis-exporter 2>&1 | tail -3

    # 2. mysqld_exporter MySQL user via debian-sys-maint
    if [[ -f /etc/mysql/debian.cnf ]]; then
        EXPORTER_PW=$(openssl rand -hex 16)
        mysql --defaults-file=/etc/mysql/debian.cnf <<SQL
CREATE USER IF NOT EXISTS 'mysql_exporter'@'localhost' IDENTIFIED BY '${EXPORTER_PW}' WITH MAX_USER_CONNECTIONS 3;
ALTER USER 'mysql_exporter'@'localhost' IDENTIFIED BY '${EXPORTER_PW}';
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'mysql_exporter'@'localhost';
FLUSH PRIVILEGES;
SQL
        cat > /var/lib/prometheus/.my.cnf <<CNF
[client]
user=mysql_exporter
password=${EXPORTER_PW}
host=127.0.0.1
CNF
        chown prometheus:prometheus /var/lib/prometheus/.my.cnf
        chmod 600 /var/lib/prometheus/.my.cnf
        sed -i 's|^ARGS=.*|ARGS="--config.my-cnf=/var/lib/prometheus/.my.cnf"|' /etc/default/prometheus-mysqld-exporter
    fi

    # 3. redis_exporter — read pw from /etc/redis/redis.conf if set
    REDIS_PW=$(grep -oP "(?<=^requirepass )[^ ]+" /etc/redis/redis.conf 2>/dev/null | head -1)
    if [[ -n "$REDIS_PW" ]]; then
        cat > /etc/default/prometheus-redis-exporter <<EOF
ARGS="--redis.addr=redis://127.0.0.1:6379 --redis.password=${REDIS_PW}"
EOF
    else
        cat > /etc/default/prometheus-redis-exporter <<EOF
ARGS="--redis.addr=redis://127.0.0.1:6379"
EOF
    fi
    chmod 600 /etc/default/prometheus-redis-exporter

    # 4. Prometheus scrape config (all targets local — single-host install)
    cat > /etc/prometheus/prometheus.yml <<'EOF'
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: prometheus
    static_configs:
      - targets: ['127.0.0.1:9090']

  - job_name: node
    static_configs:
      - targets: ['127.0.0.1:9100']
        labels: { host: localhost }

  - job_name: mysqld
    static_configs:
      - targets: ['127.0.0.1:9104']
        labels: { host: localhost }

  - job_name: redis
    static_configs:
      - targets: ['127.0.0.1:9121']
        labels: { host: localhost }

  - job_name: rswitch-api
    static_configs:
      - targets: ['127.0.0.1:8001']
        labels: { host: localhost }
EOF
    chown prometheus:prometheus /etc/prometheus/prometheus.yml

    systemctl enable --now prometheus prometheus-node-exporter prometheus-mysqld-exporter prometheus-redis-exporter
    systemctl restart prometheus prometheus-node-exporter prometheus-mysqld-exporter prometheus-redis-exporter

    # 5. Grafana — Grafana apt repo (not in Ubuntu base)
    if ! command -v grafana-server >/dev/null 2>&1; then
        apt-get install -y -qq apt-transport-https software-properties-common gnupg wget 2>&1 | tail -3
        mkdir -p /etc/apt/keyrings
        wget -qO- https://apt.grafana.com/gpg.key | gpg --dearmor -o /etc/apt/keyrings/grafana.gpg
        echo "deb [signed-by=/etc/apt/keyrings/grafana.gpg] https://apt.grafana.com stable main" > /etc/apt/sources.list.d/grafana.list
        apt-get update -qq 2>&1 | tail -3
        apt-get install -y -qq grafana 2>&1 | tail -3
    fi
    systemctl enable --now grafana-server
    sleep 4

    # 6. Reset admin password to a known value, save to credentials file
    GRAFANA_PW=$(openssl rand -hex 12)
    grafana cli --homepath /usr/share/grafana --config /etc/grafana/grafana.ini admin reset-admin-password "${GRAFANA_PW}" >/dev/null 2>&1 || true
    sleep 2

    # 7. Add Prometheus datasource via API
    curl -s -u "admin:${GRAFANA_PW}" -H "Content-Type: application/json" -X POST \
        http://127.0.0.1:3000/api/datasources -d '{
        "name": "Prometheus",
        "type": "prometheus",
        "url": "http://127.0.0.1:9090",
        "access": "proxy",
        "isDefault": true
    }' >/dev/null

    # 8. Import bundled dashboards
    for dash in node-exporter-full mysql-overview redis; do
        if [[ -f "${SCRIPT_DIR}/templates/grafana/${dash}.json" ]]; then
            python3 - "${SCRIPT_DIR}/templates/grafana/${dash}.json" <<PY > /tmp/grafana_import.json
import json, sys
d = json.load(open(sys.argv[1]))
d["id"] = None
d["uid"] = None
print(json.dumps({
    "dashboard": d, "overwrite": True, "folderId": 0,
    "inputs": [
        {"name": "DS_PROMETHEUS", "type": "datasource", "pluginId": "prometheus", "value": "Prometheus"},
        {"name": "DS_PROM",       "type": "datasource", "pluginId": "prometheus", "value": "Prometheus"}
    ]
}))
PY
            curl -s -u "admin:${GRAFANA_PW}" -H "Content-Type: application/json" -X POST \
                http://127.0.0.1:3000/api/dashboards/import -d @/tmp/grafana_import.json >/dev/null
            rm -f /tmp/grafana_import.json
        fi
    done

    # Save Grafana password to the credentials file (writes happen at the end via save_credentials)
    GRAFANA_ADMIN_PASSWORD="${GRAFANA_PW}"
    export GRAFANA_ADMIN_PASSWORD

    log_success "Monitoring stack ready: Grafana on http://127.0.0.1:3000 (admin / ${GRAFANA_PW})"
    log_info "Access via SSH tunnel: ssh -L 3000:127.0.0.1:3000 root@<this-server>"
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
    install_python_services
    configure_nginx_site
    configure_supervisor
    configure_firewall
    configure_fail2ban
    configure_monitoring
    configure_ssl
    create_admin_user
    save_credentials

    print_completion
}

# Run main function
main "$@"
