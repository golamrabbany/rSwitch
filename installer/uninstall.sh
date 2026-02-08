#!/bin/bash
#
# rSwitch Uninstaller
# Removes rSwitch and optionally all components
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
    echo -e "${RED}"
    echo "╔══════════════════════════════════════════════════════════════════╗"
    echo "║                    rSwitch Uninstaller                           ║"
    echo "╚══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

confirm_uninstall() {
    echo ""
    log_warning "This will remove the rSwitch application."
    echo ""
    echo "Options:"
    echo "  1) Remove application only (keep database and Asterisk)"
    echo "  2) Remove application and database (keep Asterisk)"
    echo "  3) Remove everything (application, database, Asterisk)"
    echo "  4) Cancel"
    echo ""
    read -p "Select option [1-4]: " option

    case $option in
        1) REMOVE_DB=false; REMOVE_ASTERISK=false ;;
        2) REMOVE_DB=true; REMOVE_ASTERISK=false ;;
        3) REMOVE_DB=true; REMOVE_ASTERISK=true ;;
        *) echo "Cancelled."; exit 0 ;;
    esac

    read -p "Are you sure? This cannot be undone. (yes/NO): " confirm
    [[ "$confirm" != "yes" ]] && exit 0
}

remove_application() {
    log_info "Removing rSwitch application..."

    # Stop supervisor processes
    if command -v supervisorctl &> /dev/null; then
        supervisorctl stop all 2>/dev/null || true
        rm -f /etc/supervisor/conf.d/rswitch.conf
        supervisorctl reread 2>/dev/null || true
        supervisorctl update 2>/dev/null || true
    fi

    # Remove nginx site
    rm -f /etc/nginx/sites-enabled/rswitch
    rm -f /etc/nginx/sites-available/rswitch
    systemctl reload nginx 2>/dev/null || true

    # Remove application directory
    if [[ -d "$INSTALL_DIR" ]]; then
        rm -rf "$INSTALL_DIR"
        log_success "Application directory removed"
    fi

    # Remove credentials file
    rm -f /root/rswitch-credentials.txt

    log_success "Application removed"
}

remove_database() {
    log_info "Removing database..."

    if command -v mysql &> /dev/null; then
        # Try to get root password from credentials file
        if [[ -f /root/rswitch-credentials.txt ]]; then
            DB_ROOT_PASS=$(grep "Root Password:" /root/rswitch-credentials.txt | awk '{print $3}')
        else
            read -s -p "Enter MySQL root password: " DB_ROOT_PASS
            echo
        fi

        mysql -u root -p"$DB_ROOT_PASS" -e "DROP DATABASE IF EXISTS rswitch;" 2>/dev/null || true
        mysql -u root -p"$DB_ROOT_PASS" -e "DROP USER IF EXISTS 'rswitch'@'localhost';" 2>/dev/null || true
        log_success "Database removed"
    else
        log_warning "MySQL not found, skipping database removal"
    fi
}

remove_asterisk() {
    log_info "Removing Asterisk..."

    # Stop Asterisk
    systemctl stop asterisk 2>/dev/null || true
    systemctl disable asterisk 2>/dev/null || true

    # Remove Asterisk configs
    rm -f /etc/asterisk/pjsip_trunks.conf
    rm -f /etc/asterisk/extconfig.conf
    rm -f /etc/asterisk/res_odbc.conf

    # Remove ODBC config
    rm -f /etc/odbc.ini

    # Remove fail2ban Asterisk jail
    rm -f /etc/fail2ban/jail.d/asterisk.conf
    rm -f /etc/fail2ban/filter.d/asterisk.conf
    systemctl restart fail2ban 2>/dev/null || true

    log_success "Asterisk configuration removed"
    log_warning "Asterisk binaries not removed. Run 'apt remove asterisk' if needed."
}

main() {
    print_banner
    check_root
    confirm_uninstall

    remove_application

    if [[ "$REMOVE_DB" == "true" ]]; then
        remove_database
    fi

    if [[ "$REMOVE_ASTERISK" == "true" ]]; then
        remove_asterisk
    fi

    echo ""
    log_success "rSwitch has been uninstalled."
    echo ""
}

main "$@"
