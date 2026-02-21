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

    # Restart queue workers
    log_info "Restarting queue workers..."
    supervisorctl restart rswitch-worker:*
    supervisorctl restart rswitch-webhook-worker:*

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
