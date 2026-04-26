#!/bin/bash
#
# rSwitch Engine Server Installer
# Installs: MySQL + Redis + Asterisk + Python Billing + ODBC
# This server handles all VoIP + billing — no web UI
#
# Supports: Ubuntu 22.04+ LTS, Debian 12+, CentOS 9+, AlmaLinux 9+
#
set -e

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; CYAN='\033[0;36m'; NC='\033[0m'

INSTALLER_VERSION="2.0.0"
ASTERISK_VERSION="20.11.1"
INSTALL_DIR="/var/www/rswitch"
DB_NAME="rswitch"
DB_USER="rswitch"
DB_PASS=""
APP_SERVER_IP=""
INSTALL_MYSQL="yes"
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
    echo "║       ENGINE SERVER Installer v${INSTALLER_VERSION}                          ║"
    echo "║   MySQL + Asterisk + Python Billing + Redis                     ║"
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
# Configuration
# =============================================================================

gather_configuration() {
    log_step "Engine Server Configuration"

    read -p "App Server IP (Laravel web server): " APP_SERVER_IP
    while [[ -z "$APP_SERVER_IP" ]]; do log_warning "App Server IP is required"; read -p "App Server IP: " APP_SERVER_IP; done

    echo ""
    echo -e "${YELLOW}Database Configuration${NC}"
    read -p "Install MySQL on this server? (Y/n): " -n 1 -r; echo
    [[ $REPLY =~ ^[Nn]$ ]] && INSTALL_MYSQL="no" || INSTALL_MYSQL="yes"

    if [[ "$INSTALL_MYSQL" == "no" ]]; then
        read -p "Remote Database Server IP: " DB_HOST
        while [[ -z "$DB_HOST" ]]; do log_warning "DB Server IP required"; read -p "DB Server IP: " DB_HOST; done
    else
        DB_HOST="127.0.0.1"
    fi

    DB_PASS=$(generate_password)
    log_info "Generated database password"

    read -p "Database name [$DB_NAME]: " input_db; DB_NAME=${input_db:-$DB_NAME}
    read -p "Database username [$DB_USER]: " input_user; DB_USER=${input_user:-$DB_USER}
    read -p "Installation directory [$INSTALL_DIR]: " input_dir; INSTALL_DIR=${input_dir:-$INSTALL_DIR}

    # Summary
    echo ""
    log_info "Installation Summary:"
    echo "  App Server:     $APP_SERVER_IP"
    echo "  Install MySQL:  $INSTALL_MYSQL"
    echo "  DB Host:        $DB_HOST"
    echo "  Database:       $DB_NAME"
    echo "  Install Dir:    $INSTALL_DIR"
    echo "  Asterisk:       $ASTERISK_VERSION"
    echo ""

    read -p "Proceed with installation? (Y/n): " -n 1 -r; echo
    if [[ $REPLY =~ ^[Nn]$ ]]; then exit 0; fi
}

# =============================================================================
# Installation Functions
# =============================================================================

install_system_dependencies() {
    log_step "Installing System Dependencies"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf update -y -q; dnf install -y -q epel-release
        dnf install -y -q ca-certificates curl wget gnupg2 git unzip zip acl supervisor cronie logrotate firewalld fail2ban htop vim nano python3 python3-pip ffmpeg
    else
        export DEBIAN_FRONTEND=noninteractive
        apt-get update -qq
        apt-get install -y -qq software-properties-common ca-certificates curl wget gnupg lsb-release git unzip zip acl supervisor cron logrotate ufw fail2ban htop vim nano python3-venv python3-pip ffmpeg
    fi
    log_success "System dependencies installed"
}

install_mysql() {
    [[ "$INSTALL_MYSQL" != "yes" ]] && { log_info "Skipping MySQL (using remote DB at ${DB_HOST})"; return; }

    log_step "Installing MySQL 8.0"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q mysql-server mysql; systemctl start mysqld; systemctl enable mysqld
    else
        export DEBIAN_FRONTEND=noninteractive
        apt-get install -y -qq mysql-server mysql-client; systemctl start mysql; systemctl enable mysql
    fi

    # Determine root auth: passwordless socket → debian.cnf → set new password.
    MYSQL_ROOT_AUTH=""
    if mysql -u root -e "SELECT 1" &>/dev/null; then
        mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_PASS}_root';"
        MYSQL_ROOT_AUTH="-u root -p${DB_PASS}_root"
    elif [[ -f /etc/mysql/debian.cnf ]] && mysql --defaults-file=/etc/mysql/debian.cnf -e "SELECT 1" &>/dev/null; then
        log_info "Using existing MySQL via /etc/mysql/debian.cnf"
        MYSQL_ROOT_AUTH="--defaults-file=/etc/mysql/debian.cnf"
    else
        log_warning "MySQL root credentials not auto-detected; skipping DB/user creation."
        log_warning "Manually create database '${DB_NAME}', user '${DB_USER}'@'localhost' and '${DB_USER}'@'${APP_SERVER_IP}'."
        return 0
    fi

    mysql ${MYSQL_ROOT_AUTH} -e "DELETE FROM mysql.user WHERE User='';" 2>/dev/null || true
    mysql ${MYSQL_ROOT_AUTH} -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');" 2>/dev/null || true

    # Create database
    mysql ${MYSQL_ROOT_AUTH} -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    # Create users: local + remote (app server)
    mysql ${MYSQL_ROOT_AUTH} -e "
        CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
        GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
        CREATE USER IF NOT EXISTS '${DB_USER}'@'${APP_SERVER_IP}' IDENTIFIED BY '${DB_PASS}';
        GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'${APP_SERVER_IP}';
        FLUSH PRIVILEGES;
    "

    # Allow remote connections
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        MYSQL_CNF="/etc/my.cnf.d/rswitch-remote.cnf"
    else
        MYSQL_CNF="/etc/mysql/mysql.conf.d/rswitch-remote.cnf"
    fi
    cat > ${MYSQL_CNF} << 'EOF'
[mysqld]
bind-address = 0.0.0.0
EOF

    # MySQL tuning
    TOTAL_RAM_MB=$(free -m | awk '/^Mem:/{print $2}')
    BUFFER_POOL_MB=$((TOTAL_RAM_MB * 65 / 100))
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then MYSQL_CONF_DIR="/etc/my.cnf.d"; else MYSQL_CONF_DIR="/etc/mysql/mysql.conf.d"; fi
    if [[ -f "${SCRIPT_DIR}/templates/mysql-tuning.cnf.template" ]]; then
        sed "s/__BUFFER_POOL_SIZE__/${BUFFER_POOL_MB}M/" "${SCRIPT_DIR}/templates/mysql-tuning.cnf.template" > "${MYSQL_CONF_DIR}/rswitch-tuning.cnf"
    fi

    systemctl restart mysql 2>/dev/null || systemctl restart mysqld 2>/dev/null
    log_info "innodb_buffer_pool_size set to ${BUFFER_POOL_MB}M"

    mkdir -p /var/backups/rswitch/cdr

    log_success "MySQL installed with remote access for ${APP_SERVER_IP}"
}

install_redis() {
    log_step "Installing Redis (billing cache, Celery broker)"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf install -y -q redis; systemctl restart redis; systemctl enable redis
    else
        apt-get install -y -qq redis-server; systemctl restart redis-server; systemctl enable redis-server
    fi
    REDIS_CONF="/etc/redis/redis.conf"
    sed -i "s/^supervised .*/supervised systemd/" $REDIS_CONF
    sed -i "s/^# maxmemory .*/maxmemory 512mb/" $REDIS_CONF
    sed -i "s/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/" $REDIS_CONF
    log_success "Redis installed (512MB, billing cache)"
}

install_asterisk() {
    log_step "Installing Asterisk $ASTERISK_VERSION"

    log_info "Installing Asterisk build dependencies..."
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        dnf groupinstall -y -q "Development Tools"
        dnf install -y -q ncurses-devel jansson-devel libxml2-devel sqlite-devel libuuid-devel openssl-devel libedit-devel libsrtp-devel spandsp-devel libcurl-devel newt-devel libogg-devel libvorbis-devel speex-devel unbound-devel unixODBC unixODBC-devel mariadb-connector-odbc mariadb-devel opus-devel
    else
        apt-get install -y -qq build-essential libncurses5-dev libjansson-dev libxml2-dev libsqlite3-dev uuid-dev libssl-dev libedit-dev libsrtp2-dev libspandsp-dev libcurl4-openssl-dev libnewt-dev libogg-dev libvorbis-dev libspeex-dev libspeexdsp-dev libunbound-dev unixodbc unixodbc-dev odbc-mariadb libmariadb-dev libmariadb-dev-compat libopus-dev subversion
    fi

    cd /usr/src
    [[ ! -f asterisk-${ASTERISK_VERSION}.tar.gz ]] && wget -q https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${ASTERISK_VERSION}.tar.gz
    [[ ! -d asterisk-${ASTERISK_VERSION} ]] && tar -xzf asterisk-${ASTERISK_VERSION}.tar.gz
    cd asterisk-${ASTERISK_VERSION}

    contrib/scripts/get_mp3_source.sh || true
    export DEBIAN_FRONTEND=noninteractive
    yes | contrib/scripts/install_prereq install || true

    ./configure --with-pjproject-bundled --with-jansson-bundled

    make menuselect.makeopts
    menuselect/menuselect --enable chan_pjsip --enable res_pjsip --enable res_pjsip_session --enable res_pjsip_transport_websocket --enable res_http_websocket --enable res_agi --enable res_odbc --enable res_config_odbc --enable cdr_odbc --enable cdr_custom --enable func_odbc --enable res_realtime --enable CORE-SOUNDS-EN-WAV --enable CORE-SOUNDS-EN-ULAW --enable CORE-SOUNDS-EN-ALAW --enable MOH-OPSOUND-WAV --enable MOH-OPSOUND-ULAW --enable MOH-OPSOUND-ALAW --enable EXTRA-SOUNDS-EN-WAV --enable format_mp3 menuselect.makeopts

    log_info "Compiling Asterisk (this may take a while)..."
    make -j$(nproc); make install; make samples; make config

    useradd -r -d /var/lib/asterisk -s /bin/false asterisk 2>/dev/null || true
    usermod -aG audio,dialout asterisk

    chown -R asterisk:asterisk /var/lib/asterisk /var/log/asterisk /var/spool/asterisk /var/run/asterisk /etc/asterisk

    sed -i 's/^;runuser = .*/runuser = asterisk/' /etc/asterisk/asterisk.conf
    sed -i 's/^;rungroup = .*/rungroup = asterisk/' /etc/asterisk/asterisk.conf

    systemctl enable asterisk 2>/dev/null || true
    cd /usr/src; rm -rf asterisk-${ASTERISK_VERSION}.tar.gz

    log_success "Asterisk $ASTERISK_VERSION installed"
}

configure_odbc() {
    log_step "Configuring ODBC for Asterisk Realtime"

    # Determine ODBC password — if MySQL is local use local user, otherwise use DB_PASS
    ODBC_USER="${DB_USER}"
    ODBC_PASS="${DB_PASS}"
    ODBC_HOST="${DB_HOST}"

    cat > /etc/odbcinst.ini << 'EOF'
[MariaDB]
Description = MariaDB Connector/ODBC
Driver = /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so
Driver64 = /usr/lib/x86_64-linux-gnu/odbc/libmaodbc.so
Setup = /usr/lib/x86_64-linux-gnu/odbc/libodbcmyS.so
UsageCount = 1
EOF

    # Remove Socket line if DB is remote
    if [[ "$DB_HOST" == "127.0.0.1" || "$DB_HOST" == "localhost" ]]; then
        SOCKET_LINE="Socket = /var/run/mysqld/mysqld.sock"
    else
        SOCKET_LINE=""
    fi

    cat > /etc/odbc.ini << EOF
[rswitch]
Description = rSwitch Database
Driver = MariaDB
Server = ${ODBC_HOST}
Database = ${DB_NAME}
User = ${ODBC_USER}
Password = ${ODBC_PASS}
Port = 3306
${SOCKET_LINE}
Option = 3
EOF

    cat > /etc/asterisk/res_odbc.conf << EOF
[rswitch]
enabled => yes
dsn => rswitch
username => ${ODBC_USER}
password => ${ODBC_PASS}
pre-connect => yes
sanitysql => select 1
max_connections => 100
connect_timeout => 10
logging => no
EOF

    log_success "ODBC configured (DB: ${ODBC_HOST})"
}

configure_asterisk() {
    log_step "Configuring Asterisk for rSwitch"

    # AMI Secret
    AMI_SECRET=$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 24)

    # PJSIP
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

#include pjsip_trunks.conf
EOF
    touch /etc/asterisk/pjsip_trunks.conf; chown asterisk:asterisk /etc/asterisk/pjsip_trunks.conf

    # Realtime
    cat > /etc/asterisk/extconfig.conf << 'EOF'
[settings]
ps_endpoints => odbc,rswitch,ps_endpoints
ps_auths => odbc,rswitch,ps_auths
ps_aors => odbc,rswitch,ps_aors
ps_contacts => odbc,rswitch,ps_contacts
ps_endpoint_id_ips => odbc,rswitch,ps_endpoint_id_ips
EOF

    cat > /etc/asterisk/sorcery.conf << 'EOF'
[res_pjsip]
endpoint=realtime,ps_endpoints
auth=realtime,ps_auths
aor=realtime,ps_aors
contact=realtime,ps_contacts

[res_pjsip_endpoint_identifier_ip]
identify=realtime,ps_endpoint_id_ips
EOF

    # RTP
    cat > /etc/asterisk/rtp.conf << 'EOF'
[general]
rtpstart=10000
rtpend=30000
strictrtp=yes
icesupport=no
EOF

    # Extensions (copy from app or generate)
    if [[ -f "${INSTALL_DIR}/docker/asterisk/conf/extensions.conf" ]]; then
        cp "${INSTALL_DIR}/docker/asterisk/conf/extensions.conf" /etc/asterisk/extensions.conf
    else
        cat > /etc/asterisk/extensions.conf << 'EOF'
[general]
static=yes
writeprotect=yes

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
 same => n,Dial(${ROUTE_DIAL_STRING},${ROUTE_DIAL_TIMEOUT},gT)
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
exten => _X.,1,NoOp(Inbound from ${CALLERID(all)} to DID ${EXTEN})
 same => n,Set(CHANNEL(language)=en)
 same => n,Set(TRUNK_ENDPOINT=${CHANNEL(endpoint)})
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

    # Modules — hardened: only load what rSwitch needs (243 vs 319 default)
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

; ARI REST API (security risk — not used)
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

    # Manager — allow App Server IP + localhost
    cat > /etc/asterisk/manager.conf << EOF
[general]
enabled = yes
port = 5038
bindaddr = 0.0.0.0
webenabled = no

[rswitch]
secret = ${AMI_SECRET}
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.255
permit = ${APP_SERVER_IP}/255.255.255.255
read = command,system,call,cdr
write = command,system,call
EOF

    # Logger (with full log for debugging)
    cat > /etc/asterisk/logger.conf << 'EOF'
[general]
dateformat=%F %T.%3q
[logfiles]
console => notice,warning,error,verbose
messages => notice,warning,error
full => notice,warning,error,debug,verbose
security => security
EOF

    # Detect actual module path
    AST_MOD_DIR="/usr/lib/asterisk/modules"
    [[ -d "/usr/lib/x86_64-linux-gnu/asterisk/modules" ]] && AST_MOD_DIR="/usr/lib/x86_64-linux-gnu/asterisk/modules"
    [[ -d "/usr/lib64/asterisk/modules" ]] && AST_MOD_DIR="/usr/lib64/asterisk/modules"

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
transmit_silence = yes
EOF

    # Directories
    mkdir -p /var/spool/asterisk/{voicebroadcast,outgoing,recording}
    chown asterisk:asterisk /var/spool/asterisk/{voicebroadcast,outgoing,recording}
    chmod 775 /var/spool/asterisk/{voicebroadcast,outgoing}

    # File limits
    cat > /etc/security/limits.d/asterisk.conf << 'EOF'
asterisk soft nofile 131072
asterisk hard nofile 131072
EOF

    # Proper systemd service (auto-restart, correct startup)
    cat > /etc/systemd/system/asterisk.service << 'EOF'
[Unit]
Description=Asterisk PBX
After=network.target mysql.service

[Service]
Type=simple
ExecStartPre=/bin/mkdir -p /var/run/asterisk
ExecStart=/usr/sbin/asterisk -f
ExecReload=/usr/sbin/asterisk -rx "core reload"
ExecStop=/usr/sbin/asterisk -rx "core stop now"
Restart=always
RestartSec=5
LimitNOFILE=131072
Nice=-10

[Install]
WantedBy=multi-user.target
EOF
    systemctl daemon-reload
    systemctl enable asterisk

    # Kernel tuning
    cat > /etc/sysctl.d/99-rswitch-asterisk.conf << 'EOF'
net.core.rmem_max = 26214400
net.core.wmem_max = 26214400
net.core.somaxconn = 4096
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15
net.ipv4.udp_mem = 65536 131072 262144
fs.file-max = 262144
EOF
    sysctl -p /etc/sysctl.d/99-rswitch-asterisk.conf 2>/dev/null || true

    # Remove sample AEL/Lua configs (prevent startup errors)
    rm -f /etc/asterisk/extensions.ael /etc/asterisk/extensions.lua 2>/dev/null

    # Protect config files from accidental overwrite
    chown -R asterisk:asterisk /etc/asterisk
    chmod 444 /etc/asterisk/extensions.conf /etc/asterisk/modules.conf /etc/asterisk/pjsip.conf \
              /etc/asterisk/manager.conf /etc/asterisk/res_odbc.conf /etc/asterisk/extconfig.conf \
              /etc/asterisk/sorcery.conf /etc/asterisk/rtp.conf /etc/asterisk/logger.conf \
              /etc/asterisk/asterisk.conf 2>/dev/null

    # Note: pjsip_trunks.conf is NOT protected — trunks are DB-based (ODBC realtime)

    log_success "Asterisk configured (AMI permits: localhost + ${APP_SERVER_IP})"
}

install_python_services() {
    log_step "Installing Python Billing + Call Control"

    [[ "$OS" == "centos" || "$OS" == "almalinux" ]] && WEB_USER="root" || WEB_USER="www-data"

    # Copy python-services from app source
    mkdir -p "${INSTALL_DIR}/python-services"
    if [[ -d "$SCRIPT_DIR/../python-services" ]]; then
        cp -r $SCRIPT_DIR/../python-services/* "${INSTALL_DIR}/python-services/"
    else
        log_error "python-services/ not found. Run installer from rSwitch directory."
        exit 1
    fi

    cd "${INSTALL_DIR}/python-services"
    python3 -m venv venv
    source venv/bin/activate
    pip install --upgrade pip --quiet
    pip install -r requirements.txt --quiet
    pip install cryptography --quiet
    deactivate

    # Python DB user (GRANT ALL to avoid partition table permission issues)
    PYTHON_DB_PASS=$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 16)

    if [[ "$INSTALL_MYSQL" == "yes" ]]; then
        # Reuse the auth determined in install_mysql() when possible.
        if [[ -z "${MYSQL_ROOT_AUTH:-}" ]]; then
            if [[ -f /etc/mysql/debian.cnf ]] && mysql --defaults-file=/etc/mysql/debian.cnf -e "SELECT 1" &>/dev/null; then
                MYSQL_ROOT_AUTH="--defaults-file=/etc/mysql/debian.cnf"
            else
                MYSQL_ROOT_AUTH="-u root -p${DB_PASS}_root"
            fi
        fi
        mysql ${MYSQL_ROOT_AUTH} -e "
            CREATE USER IF NOT EXISTS 'python_svc'@'localhost' IDENTIFIED BY '${PYTHON_DB_PASS}';
            GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO 'python_svc'@'localhost';
            FLUSH PRIVILEGES;
        " 2>/dev/null || true
        PYTHON_DB_HOST="127.0.0.1"
    else
        PYTHON_DB_HOST="${DB_HOST}"
        PYTHON_DB_PASS="${DB_PASS}"  # Use same pass as app for remote DB
    fi

    AMI_SECRET_VALUE=$(grep '^secret' /etc/asterisk/manager.conf 2>/dev/null | head -1 | awk -F'= ' '{print $2}' | tr -d ' ')

    cat > .env << EOF
DATABASE_URL=mysql+pymysql://python_svc:${PYTHON_DB_PASS}@${PYTHON_DB_HOST}:3306/${DB_NAME}
ASYNC_DATABASE_URL=mysql+aiomysql://python_svc:${PYTHON_DB_PASS}@${PYTHON_DB_HOST}:3306/${DB_NAME}
REDIS_URL=redis://127.0.0.1:6379/0
ASTERISK_AMI_HOST=127.0.0.1
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USER=rswitch
ASTERISK_AMI_SECRET=${AMI_SECRET_VALUE}
DEBUG=false
LOG_LEVEL=info
EOF

    log_success "Python billing services installed"
}

configure_supervisor() {
    log_step "Configuring Supervisor (Python services only)"

    [[ "$OS" == "centos" || "$OS" == "almalinux" ]] && { WEB_USER="root"; SUPERVISOR_CONF_DIR="/etc/supervisord.d"; } || { WEB_USER="www-data"; SUPERVISOR_CONF_DIR="/etc/supervisor/conf.d"; }
    mkdir -p $SUPERVISOR_CONF_DIR

    cat > ${SUPERVISOR_CONF_DIR}/rswitch-engine.conf << EOF
[program:rswitch-api]
command=${INSTALL_DIR}/python-services/venv/bin/uvicorn main:app --host 0.0.0.0 --port 8001 --workers 1
directory=${INSTALL_DIR}/python-services
environment=PYTHONPATH="${INSTALL_DIR}/python-services"
user=${WEB_USER}
autostart=true
autorestart=true
stderr_logfile=/var/log/rswitch-python-api.err.log
stdout_logfile=/var/log/rswitch-python-api.out.log
stopwaitsecs=10

[program:rswitch-celery]
command=${INSTALL_DIR}/python-services/venv/bin/celery -A celery_app worker -l info -Q billing,monitoring,broadcast -c 4
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

    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then systemctl restart supervisord; else supervisorctl reread; supervisorctl update; supervisorctl start all; fi
    log_success "Supervisor configured (Python API + Celery + Beat)"
}

configure_firewall() {
    log_step "Configuring Firewall"
    if [[ "$OS" == "centos" || "$OS" == "almalinux" ]]; then
        systemctl start firewalld; systemctl enable firewalld
        firewall-cmd --permanent --add-service=ssh
        firewall-cmd --permanent --add-port=5060/udp; firewall-cmd --permanent --add-port=5060/tcp; firewall-cmd --permanent --add-port=5061/tcp
        firewall-cmd --permanent --add-port=10000-30000/udp
        firewall-cmd --permanent --add-rich-rule="rule family='ipv4' source address='${APP_SERVER_IP}' port port='8001' protocol='tcp' accept"
        firewall-cmd --permanent --add-rich-rule="rule family='ipv4' source address='${APP_SERVER_IP}' port port='5038' protocol='tcp' accept"
        [[ "$INSTALL_MYSQL" == "yes" ]] && firewall-cmd --permanent --add-rich-rule="rule family='ipv4' source address='${APP_SERVER_IP}' port port='3306' protocol='tcp' accept"
        firewall-cmd --reload
    else
        ufw --force enable; ufw allow ssh
        ufw allow 5060/udp; ufw allow 5060/tcp; ufw allow 5061/tcp
        ufw allow 10000:30000/udp
        ufw allow from ${APP_SERVER_IP} to any port 8001
        ufw allow from ${APP_SERVER_IP} to any port 5038
        [[ "$INSTALL_MYSQL" == "yes" ]] && ufw allow from ${APP_SERVER_IP} to any port 3306
        ufw reload
    fi
    log_success "Firewall configured (SIP + RTP + API/AMI from ${APP_SERVER_IP})"
}

configure_fail2ban() {
    log_step "Configuring Fail2Ban"
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

    cat > /etc/fail2ban/filter.d/asterisk.conf << 'EOF'
[INCLUDES]
before = common.conf
[Definition]
failregex = NOTICE.* .*: Registration from '.*' failed for '<HOST>:.*' - Wrong password
            NOTICE.* .*: Registration from '.*' failed for '<HOST>:.*' - No matching peer found
            NOTICE.* <HOST> failed to authenticate as '.*'$
            NOTICE.* .*: Failed to authenticate device .*@<HOST>.*
ignoreregex =
EOF

    systemctl restart fail2ban
    log_success "Fail2Ban configured (SIP protection)"
}

save_credentials() {
    AMI_SECRET_VALUE=$(grep '^secret' /etc/asterisk/manager.conf 2>/dev/null | head -1 | awk -F'= ' '{print $2}' | tr -d ' ')

    cat > /root/rswitch-engine-credentials.txt << EOF
╔══════════════════════════════════════════════════════════════════╗
║            rSwitch Engine Server Credentials                     ║
╚══════════════════════════════════════════════════════════════════╝

Installation Date: $(date)
Server Type:       Engine Server (DB + Asterisk + Billing)

App Server:        ${APP_SERVER_IP}

Database:
  Host:            ${DB_HOST}
  Database:        ${DB_NAME}
  App Username:    ${DB_USER}
  App Password:    ${DB_PASS}
  Root Password:   ${DB_PASS}_root

Asterisk:
  AMI User:        rswitch
  AMI Secret:      ${AMI_SECRET_VALUE}
  AMI Port:        5038
  SIP Port:        5060

Python API:
  URL:             http://0.0.0.0:8001
  Access From:     ${APP_SERVER_IP}

Firewall:
  SIP:             5060/5061 (open)
  RTP:             10000-30000 (open)
  API (8001):      ${APP_SERVER_IP} only
  AMI (5038):      ${APP_SERVER_IP} only
  MySQL (3306):    ${APP_SERVER_IP} only

Commands:
  Asterisk CLI:    asterisk -rvvv
  Restart billing: supervisorctl restart all
  View AGI logs:   tail -f /var/log/rswitch-python-api.err.log
  View Celery:     tail -f /var/log/rswitch-celery.err.log

╔══════════════════════════════════════════════════════════════════╗
║  PROVIDE AMI SECRET TO APP SERVER INSTALLER!                     ║
║  AMI Secret: ${AMI_SECRET_VALUE}
║  DB Password: ${DB_PASS}
╚══════════════════════════════════════════════════════════════════╝
EOF
    chmod 600 /root/rswitch-engine-credentials.txt
    log_success "Credentials saved to /root/rswitch-engine-credentials.txt"
}

print_completion() {
    AMI_SECRET_VALUE=$(grep '^secret' /etc/asterisk/manager.conf 2>/dev/null | head -1 | awk -F'= ' '{print $2}' | tr -d ' ')

    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║     rSwitch Engine Server Installation Complete!                ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${CYAN}App Server IP:${NC}   ${APP_SERVER_IP}"
    echo -e "  ${CYAN}DB Host:${NC}         ${DB_HOST}"
    echo -e "  ${CYAN}DB Password:${NC}     ${DB_PASS}"
    echo -e "  ${CYAN}AMI Secret:${NC}      ${AMI_SECRET_VALUE}"
    echo ""
    echo -e "  ${YELLOW}Next: Run install-app.sh on ${APP_SERVER_IP}${NC}"
    echo -e "  ${YELLOW}  - DB Server IP: $(hostname -I | awk '{print $1}')${NC}"
    echo -e "  ${YELLOW}  - Engine Server IP: $(hostname -I | awk '{print $1}')${NC}"
    echo -e "  ${YELLOW}  - DB Password: ${DB_PASS}${NC}"
    echo -e "  ${YELLOW}  - AMI Secret: ${AMI_SECRET_VALUE}${NC}"
    echo ""
    echo -e "  ${YELLOW}Credentials:${NC} /root/rswitch-engine-credentials.txt"
    echo ""
}

# =============================================================================
main() {
    print_banner; check_root; check_os; gather_configuration
    install_system_dependencies; install_mysql; install_redis
    install_asterisk; configure_odbc; configure_asterisk
    install_python_services; configure_supervisor
    configure_firewall; configure_fail2ban
    save_credentials; print_completion
}
main "$@"
