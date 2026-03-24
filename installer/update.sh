#!/bin/bash
#
# rSwitch Updater
# Updates the application to the latest version
#
set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

INSTALL_DIR="/var/www/rswitch"

log_info() { echo -e "${CYAN}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root"
        exit 1
    fi
}

print_banner() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════════╗"
    echo "║                      rSwitch Updater                             ║"
    echo "╚══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

check_installation() {
    if [[ ! -d "$INSTALL_DIR" ]]; then
        log_error "rSwitch not found at $INSTALL_DIR"
        exit 1
    fi

    if [[ ! -f "$INSTALL_DIR/.env" ]]; then
        log_error "Environment file not found"
        exit 1
    fi
}

backup_application() {
    log_info "Creating backup..."

    BACKUP_DIR="/root/rswitch-backups"
    BACKUP_FILE="$BACKUP_DIR/rswitch-$(date +%Y%m%d_%H%M%S).tar.gz"

    mkdir -p $BACKUP_DIR

    # Backup application files (excluding vendor and node_modules)
    tar -czf $BACKUP_FILE \
        --exclude="$INSTALL_DIR/vendor" \
        --exclude="$INSTALL_DIR/node_modules" \
        --exclude="$INSTALL_DIR/storage/logs/*" \
        -C $(dirname $INSTALL_DIR) \
        $(basename $INSTALL_DIR)

    # Backup database
    if [[ -f /root/rswitch-credentials.txt ]]; then
        DB_PASS=$(grep "^Password:" /root/rswitch-credentials.txt | head -1 | awk '{print $2}')
        mysqldump -u rswitch -p"$DB_PASS" rswitch > "$BACKUP_DIR/rswitch-db-$(date +%Y%m%d_%H%M%S).sql"
    fi

    log_success "Backup created: $BACKUP_FILE"
}

enable_maintenance() {
    log_info "Enabling maintenance mode..."
    cd $INSTALL_DIR
    sudo -u www-data php artisan down --retry=60 --refresh=15
}

disable_maintenance() {
    log_info "Disabling maintenance mode..."
    cd $INSTALL_DIR
    sudo -u www-data php artisan up
}

update_application() {
    log_info "Updating application..."
    cd $INSTALL_DIR

    # Pull latest code from git
    if [[ -d "$INSTALL_DIR/.git" ]]; then
        log_info "Pulling latest code from git..."
        git stash --quiet 2>/dev/null || true
        git pull origin master --no-edit
        git stash pop --quiet 2>/dev/null || true
        log_success "Code updated from git"
    else
        log_warning "Not a git repository — skipping git pull"
        log_info "To enable git updates, run: cd $INSTALL_DIR && git init && git remote add origin https://github.com/golamrabbany/rSwitch.git"
    fi

    # Clear caches
    log_info "Clearing caches..."
    sudo -u www-data php artisan optimize:clear

    # Update Composer dependencies
    log_info "Updating Composer dependencies..."
    sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

    # Update NPM dependencies
    log_info "Updating NPM dependencies..."
    sudo -u www-data npm ci

    # Build assets
    log_info "Building frontend assets..."
    sudo -u www-data npm run build

    # Run migrations
    log_info "Running database migrations..."
    sudo -u www-data php artisan migrate --force

    # Cache configuration
    log_info "Caching configuration..."
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache

    # Update Asterisk dialplan
    log_info "Updating Asterisk dialplan..."
    if [[ -f "$INSTALL_DIR/installer/templates/asterisk/extensions.conf.template" ]]; then
        cp "$INSTALL_DIR/installer/templates/asterisk/extensions.conf.template" /etc/asterisk/extensions.conf
        chown asterisk:asterisk /etc/asterisk/extensions.conf
        asterisk -rx "dialplan reload" 2>/dev/null || true
        log_success "Asterisk dialplan updated"
    fi

    # Create recording directory if it doesn't exist
    mkdir -p /var/spool/asterisk/recording
    chown asterisk:asterisk /var/spool/asterisk/recording

    # Update IVR sound files
    if [[ -d "$INSTALL_DIR/IVR" ]]; then
        AST_SOUNDS="/usr/share/asterisk/sounds/en"
        [[ ! -d "$AST_SOUNDS" ]] && AST_SOUNDS="/var/lib/asterisk/sounds/en"
        mkdir -p "${AST_SOUNDS}/IVR"
        cp -f "$INSTALL_DIR"/IVR/*.gsm "${AST_SOUNDS}/IVR/" 2>/dev/null
        chown -R asterisk:asterisk "${AST_SOUNDS}/IVR"
        log_success "IVR sound files updated"
    fi

    # Ensure sorcery.conf has PJSIP realtime mappings
    log_info "Checking Asterisk sorcery configuration..."
    if ! grep -q "endpoint=realtime,ps_endpoints" /etc/asterisk/sorcery.conf 2>/dev/null; then
        cat > /etc/asterisk/sorcery.conf << 'SORCERYEOF'
[res_pjsip]
endpoint=realtime,ps_endpoints
auth=realtime,ps_auths
aor=realtime,ps_aors

[res_pjsip_endpoint_identifier_ip]
identify=realtime,ps_endpoint_id_ips
SORCERYEOF
        chown asterisk:asterisk /etc/asterisk/sorcery.conf
        log_success "Sorcery configuration updated"
    fi

    # Remove dangerous rasterisk killer cron/scripts (if present from manual installs)
    log_info "Cleaning up dangerous Asterisk cron jobs..."
    if crontab -l 2>/dev/null | grep -q 'pkill.*rasterisk'; then
        crontab -l 2>/dev/null | grep -v 'pkill.*rasterisk' | crontab -
        log_warning "Removed dangerous rasterisk killer from crontab (was killing Asterisk)"
    fi
    rm -f /usr/local/bin/asterisk-cleanup
    # Remove stale PID files if Asterisk is not running
    if ! pgrep -x asterisk > /dev/null 2>&1; then
        rm -f /var/run/asterisk/asterisk.pid /var/run/asterisk/asterisk.ctl
    fi

    # Ensure Asterisk is running via systemd
    log_info "Ensuring Asterisk is running..."
    systemctl enable asterisk 2>/dev/null || true
    if ! asterisk -rx 'core show uptime' > /dev/null 2>&1; then
        rm -f /var/run/asterisk/asterisk.pid /var/run/asterisk/asterisk.ctl
        systemctl restart asterisk
        log_success "Asterisk restarted"
    else
        asterisk -rx "dialplan reload" 2>/dev/null || true
    fi

    # Update Python services
    log_info "Updating Python billing + call control services..."
    if [[ -d "$INSTALL_DIR/python-services" ]]; then
        cd "$INSTALL_DIR/python-services"
        if [[ -d "venv" ]]; then
            source venv/bin/activate
            pip install -r requirements.txt --quiet 2>/dev/null
            deactivate
        else
            log_warning "Python venv not found — creating..."
            python3 -m venv venv
            source venv/bin/activate
            pip install --upgrade pip --quiet
            pip install -r requirements.txt --quiet
            deactivate
        fi
        chown -R www-data:www-data "$INSTALL_DIR/python-services"
        log_success "Python services updated"
    fi

    # Restart all supervisor services
    log_info "Restarting all services..."
    supervisorctl reread 2>/dev/null || true
    supervisorctl update 2>/dev/null || true
    supervisorctl restart rswitch-worker:* 2>/dev/null || true
    supervisorctl restart rswitch-api 2>/dev/null || true
    supervisorctl restart rswitch-celery 2>/dev/null || true
    supervisorctl restart rswitch-celery-beat 2>/dev/null || true

    log_success "Application updated"
}

main() {
    print_banner
    check_root
    check_installation

    echo ""
    log_warning "This will update rSwitch to the latest version."
    log_info "A backup will be created before updating."
    echo ""
    read -p "Continue with update? (Y/n): " -n 1 -r
    echo
    [[ $REPLY =~ ^[Nn]$ ]] && exit 0

    backup_application
    enable_maintenance
    update_application
    disable_maintenance

    echo ""
    log_success "rSwitch has been updated successfully!"
    echo ""
}

main "$@"
