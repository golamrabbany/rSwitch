#!/bin/bash
set -e

# Detect architecture for ODBC driver path
ARCH=$(dpkg --print-architecture 2>/dev/null || uname -m)
case "$ARCH" in
    amd64|x86_64) LIB_DIR="/usr/lib/x86_64-linux-gnu" ;;
    arm64|aarch64) LIB_DIR="/usr/lib/aarch64-linux-gnu" ;;
    *) LIB_DIR="/usr/lib" ;;
esac

# Configure ODBC for MySQL connection
cat > /etc/odbcinst.ini <<ODBC_DRIVERS
[MariaDB]
Description = MariaDB ODBC Connector
Driver = ${LIB_DIR}/odbc/libmaodbc.so
Setup = ${LIB_DIR}/odbc/libodbcmyS.so
FileUsage = 1
ODBC_DRIVERS

cat > /etc/odbc.ini <<ODBC_DSN
[asterisk-connector]
Description = MySQL connection for Asterisk
Driver = MariaDB
Server = ${ODBC_HOST:-mysql}
Port = ${ODBC_PORT:-3306}
Database = ${ODBC_DATABASE:-rswitch}
User = ${ODBC_USERNAME:-sail}
Password = ${ODBC_PASSWORD:-password}
Option = 3
Socket =
ODBC_DSN

# Substitute env vars in Asterisk config files (can't use sed -i on bind mounts)
if [ -f /etc/asterisk/res_odbc.conf ]; then
    sed "s|\${ODBC_USERNAME}|${ODBC_USERNAME:-sail}|g; s|\${ODBC_PASSWORD}|${ODBC_PASSWORD:-password}|g" \
        /etc/asterisk/res_odbc.conf > /tmp/res_odbc.conf
    cp /tmp/res_odbc.conf /etc/asterisk/res_odbc.conf
    rm /tmp/res_odbc.conf
fi

# Ensure proper ownership
chown -R asterisk:asterisk /var/lib/asterisk /var/spool/asterisk /var/log/asterisk /var/run/asterisk /etc/asterisk

echo "=== Asterisk starting ==="
echo "ODBC Host: ${ODBC_HOST:-mysql}"
echo "ODBC Database: ${ODBC_DATABASE:-rswitch}"
echo "ODBC Driver: ${LIB_DIR}/odbc/libmaodbc.so"

exec "$@"
