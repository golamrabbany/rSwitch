#!/bin/bash
#
# rSwitch Troubleshooter
# Diagnostic tool for common issues
#
set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

INSTALL_DIR="/var/www/rswitch"

ok() { echo -e "  ${GREEN}[OK]${NC} $1"; }
warn() { echo -e "  ${YELLOW}[WARN]${NC} $1"; }
fail() { echo -e "  ${RED}[FAIL]${NC} $1"; }
info() { echo -e "  ${CYAN}[INFO]${NC} $1"; }
header() { echo -e "\n${CYAN}━━━ $1 ━━━${NC}"; }

print_banner() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════════╗"
    echo "║                   rSwitch Troubleshooter                         ║"
    echo "╚══════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

check_services() {
    header "Service Status"

    # PHP-FPM
    PHP_VERSION=$(php -v 2>/dev/null | head -1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    if systemctl is-active --quiet php${PHP_VERSION}-fpm 2>/dev/null; then
        ok "PHP-FPM ${PHP_VERSION} is running"
    else
        fail "PHP-FPM is not running"
        info "Try: systemctl start php${PHP_VERSION}-fpm"
    fi

    # Nginx
    if systemctl is-active --quiet nginx; then
        ok "Nginx is running"
    else
        fail "Nginx is not running"
        info "Try: systemctl start nginx"
    fi

    # MySQL
    if systemctl is-active --quiet mysql; then
        ok "MySQL is running"
    else
        fail "MySQL is not running"
        info "Try: systemctl start mysql"
    fi

    # Redis
    if systemctl is-active --quiet redis-server; then
        ok "Redis is running"
    else
        fail "Redis is not running"
        info "Try: systemctl start redis-server"
    fi

    # Asterisk
    if systemctl is-active --quiet asterisk; then
        ok "Asterisk is running"
    else
        warn "Asterisk is not running"
        info "Try: systemctl start asterisk"
    fi

    # Supervisor
    if systemctl is-active --quiet supervisor; then
        ok "Supervisor is running"
    else
        fail "Supervisor is not running"
        info "Try: systemctl start supervisor"
    fi
}

check_supervisor_processes() {
    header "Supervisor Processes"

    if ! command -v supervisorctl &> /dev/null; then
        fail "Supervisor not installed"
        return
    fi

    # Get supervisor status
    WORKER_STATUS=$(supervisorctl status rswitch-worker:* 2>/dev/null || echo "NOT_FOUND")
    if echo "$WORKER_STATUS" | grep -q "RUNNING"; then
        ok "Queue workers are running"
    elif echo "$WORKER_STATUS" | grep -q "NOT_FOUND"; then
        fail "Queue workers not configured"
    else
        warn "Queue workers not running"
        info "Try: supervisorctl start rswitch-worker:*"
    fi

    AGI_STATUS=$(supervisorctl status rswitch-agi 2>/dev/null || echo "NOT_FOUND")
    if echo "$AGI_STATUS" | grep -q "RUNNING"; then
        ok "AGI server is running"
    elif echo "$AGI_STATUS" | grep -q "NOT_FOUND"; then
        fail "AGI server not configured"
    else
        warn "AGI server not running"
        info "Try: supervisorctl start rswitch-agi"
    fi
}

check_database() {
    header "Database Connection"

    if [[ ! -f "$INSTALL_DIR/.env" ]]; then
        fail "Environment file not found"
        return
    fi

    # Get database credentials from .env
    DB_HOST=$(grep "^DB_HOST=" "$INSTALL_DIR/.env" | cut -d'=' -f2)
    DB_NAME=$(grep "^DB_DATABASE=" "$INSTALL_DIR/.env" | cut -d'=' -f2)
    DB_USER=$(grep "^DB_USERNAME=" "$INSTALL_DIR/.env" | cut -d'=' -f2)
    DB_PASS=$(grep "^DB_PASSWORD=" "$INSTALL_DIR/.env" | cut -d'=' -f2)

    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" &>/dev/null; then
        ok "Database connection successful"

        # Check table count
        TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME'" 2>/dev/null)
        info "Tables in database: $TABLE_COUNT"
    else
        fail "Cannot connect to database"
        info "Check database credentials in $INSTALL_DIR/.env"
    fi
}

check_redis() {
    header "Redis Connection"

    if redis-cli ping 2>/dev/null | grep -q "PONG"; then
        ok "Redis connection successful"
        REDIS_KEYS=$(redis-cli DBSIZE 2>/dev/null | grep -oP '\d+')
        info "Keys in Redis: $REDIS_KEYS"
    else
        fail "Cannot connect to Redis"
    fi
}

check_asterisk() {
    header "Asterisk Status"

    if ! command -v asterisk &> /dev/null; then
        fail "Asterisk not installed"
        return
    fi

    if ! systemctl is-active --quiet asterisk; then
        warn "Asterisk is not running"
        return
    fi

    # Check PJSIP
    PJSIP_STATUS=$(asterisk -rx "pjsip show transports" 2>/dev/null || echo "ERROR")
    if echo "$PJSIP_STATUS" | grep -q "Transport:"; then
        ok "PJSIP transports loaded"
    else
        warn "PJSIP transports may not be configured"
    fi

    # Check ODBC
    ODBC_STATUS=$(asterisk -rx "odbc show" 2>/dev/null || echo "ERROR")
    if echo "$ODBC_STATUS" | grep -q "Connected"; then
        ok "ODBC connected to database"
    else
        warn "ODBC not connected"
        info "Check /etc/asterisk/res_odbc.conf and /etc/odbc.ini"
    fi

    # Check endpoints
    ENDPOINT_COUNT=$(asterisk -rx "pjsip show endpoints" 2>/dev/null | grep -c "Endpoint:" || echo "0")
    info "Registered endpoints: $ENDPOINT_COUNT"
}

check_permissions() {
    header "File Permissions"

    if [[ ! -d "$INSTALL_DIR" ]]; then
        fail "Application directory not found: $INSTALL_DIR"
        return
    fi

    # Check storage directory
    if [[ -w "$INSTALL_DIR/storage" ]]; then
        ok "Storage directory is writable"
    else
        fail "Storage directory is not writable"
        info "Try: chown -R www-data:www-data $INSTALL_DIR/storage"
    fi

    # Check bootstrap/cache
    if [[ -w "$INSTALL_DIR/bootstrap/cache" ]]; then
        ok "Bootstrap cache is writable"
    else
        fail "Bootstrap cache is not writable"
        info "Try: chown -R www-data:www-data $INSTALL_DIR/bootstrap/cache"
    fi

    # Check .env file
    if [[ -r "$INSTALL_DIR/.env" ]]; then
        ok ".env file exists and is readable"
    else
        fail ".env file not found or not readable"
    fi
}

check_ports() {
    header "Port Status"

    # Check web ports
    if ss -tuln | grep -q ":80 "; then
        ok "Port 80 (HTTP) is listening"
    else
        warn "Port 80 (HTTP) is not listening"
    fi

    if ss -tuln | grep -q ":443 "; then
        ok "Port 443 (HTTPS) is listening"
    else
        warn "Port 443 (HTTPS) is not listening"
    fi

    # Check SIP ports
    if ss -tuln | grep -q ":5060 "; then
        ok "Port 5060 (SIP) is listening"
    else
        warn "Port 5060 (SIP) is not listening"
    fi

    # Check AGI port
    if ss -tuln | grep -q ":4573 "; then
        ok "Port 4573 (AGI) is listening"
    else
        warn "Port 4573 (AGI) is not listening"
    fi

    # Check AMI port
    if ss -tuln | grep -q ":5038 "; then
        ok "Port 5038 (AMI) is listening"
    else
        warn "Port 5038 (AMI) is not listening"
    fi
}

check_logs() {
    header "Recent Errors"

    # Laravel log
    if [[ -f "$INSTALL_DIR/storage/logs/laravel.log" ]]; then
        LARAVEL_ERRORS=$(grep -c "ERROR\|CRITICAL\|EMERGENCY" "$INSTALL_DIR/storage/logs/laravel.log" 2>/dev/null || echo "0")
        if [[ "$LARAVEL_ERRORS" -gt 0 ]]; then
            warn "Found $LARAVEL_ERRORS error(s) in Laravel log"
            info "Last error:"
            grep -E "ERROR|CRITICAL|EMERGENCY" "$INSTALL_DIR/storage/logs/laravel.log" | tail -1
        else
            ok "No errors in Laravel log"
        fi
    else
        info "Laravel log file not found"
    fi

    # Nginx error log
    if [[ -f "/var/log/nginx/error.log" ]]; then
        NGINX_ERRORS=$(tail -100 /var/log/nginx/error.log 2>/dev/null | grep -c "error\|crit\|alert" || echo "0")
        if [[ "$NGINX_ERRORS" -gt 0 ]]; then
            warn "Found $NGINX_ERRORS recent error(s) in Nginx log"
        else
            ok "No recent errors in Nginx log"
        fi
    fi
}

check_disk_space() {
    header "Disk Space"

    DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
    if [[ "$DISK_USAGE" -lt 80 ]]; then
        ok "Disk usage: ${DISK_USAGE}%"
    elif [[ "$DISK_USAGE" -lt 90 ]]; then
        warn "Disk usage: ${DISK_USAGE}% (consider cleanup)"
    else
        fail "Disk usage: ${DISK_USAGE}% (critical)"
    fi

    # Storage directory size
    if [[ -d "$INSTALL_DIR/storage" ]]; then
        STORAGE_SIZE=$(du -sh "$INSTALL_DIR/storage" 2>/dev/null | cut -f1)
        info "Storage directory size: $STORAGE_SIZE"
    fi
}

run_artisan_check() {
    header "Laravel Application Check"

    if [[ ! -d "$INSTALL_DIR" ]]; then
        fail "Application not found"
        return
    fi

    cd $INSTALL_DIR

    # Check if artisan can run
    if sudo -u www-data php artisan --version &>/dev/null; then
        ok "Artisan command works"
    else
        fail "Artisan command failed"
        return
    fi

    # Check environment
    APP_ENV=$(sudo -u www-data php artisan env 2>/dev/null || echo "unknown")
    info "Environment: $APP_ENV"

    # Check route caching
    if [[ -f "$INSTALL_DIR/bootstrap/cache/routes-v7.php" ]]; then
        ok "Routes are cached"
    else
        info "Routes are not cached"
    fi

    # Check config caching
    if [[ -f "$INSTALL_DIR/bootstrap/cache/config.php" ]]; then
        ok "Configuration is cached"
    else
        info "Configuration is not cached"
    fi
}

show_quick_fixes() {
    header "Quick Fix Commands"

    echo ""
    echo "  Clear all caches:"
    echo "    cd $INSTALL_DIR && sudo -u www-data php artisan optimize:clear"
    echo ""
    echo "  Restart all services:"
    echo "    systemctl restart php*-fpm nginx mysql redis-server asterisk supervisor"
    echo ""
    echo "  Restart queue workers:"
    echo "    supervisorctl restart all"
    echo ""
    echo "  View Laravel logs:"
    echo "    tail -f $INSTALL_DIR/storage/logs/laravel.log"
    echo ""
    echo "  View Asterisk logs:"
    echo "    tail -f /var/log/asterisk/messages"
    echo ""
    echo "  Test ODBC connection:"
    echo "    isql -v rswitch"
    echo ""
}

main() {
    print_banner

    check_services
    check_supervisor_processes
    check_database
    check_redis
    check_asterisk
    check_permissions
    check_ports
    check_disk_space
    check_logs
    run_artisan_check
    show_quick_fixes
}

main "$@"
