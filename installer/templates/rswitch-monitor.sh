#!/bin/bash
# rSwitch Server Monitor — runs every 5 minutes via cron
# Checks CPU, RAM, Disk, Services, Asterisk, MySQL, Python API
# Auto-restarts failed services

LOG=/var/log/rswitch-monitor.log
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
ALERTS=""

# Thresholds
CPU_WARN=80
RAM_WARN=85
DISK_WARN=85

# ── CPU ──
CPU_USAGE=$(top -bn1 | grep 'Cpu(s)' | awk '{print int($2 + $4)}')
if [ "$CPU_USAGE" -gt "$CPU_WARN" ]; then
    ALERTS+="[CPU] Usage at ${CPU_USAGE}% (threshold: ${CPU_WARN}%)\n"
fi

# ── RAM ──
RAM_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
if [ "$RAM_USAGE" -gt "$RAM_WARN" ]; then
    ALERTS+="[RAM] Usage at ${RAM_USAGE}% (threshold: ${RAM_WARN}%)\n"
fi

# ── Disk ──
DISK_USAGE=$(df / | tail -1 | awk '{print int($5)}')
if [ "$DISK_USAGE" -gt "$DISK_WARN" ]; then
    ALERTS+="[DISK] Usage at ${DISK_USAGE}% (threshold: ${DISK_WARN}%)\n"
fi

# ── Services ──
for SVC in nginx php8.3-fpm mysql redis-server asterisk; do
    if ! systemctl is-active --quiet $SVC 2>/dev/null; then
        ALERTS+="[SERVICE] ${SVC} is DOWN — restarting...\n"
        systemctl restart $SVC 2>/dev/null
        if systemctl is-active --quiet $SVC 2>/dev/null; then
            ALERTS+="[SERVICE] ${SVC} restarted OK\n"
        fi
    fi
done

# ── Supervisor ──
SUPERVISOR_DOWN=$(supervisorctl status 2>/dev/null | grep -v RUNNING | grep -v '^$' | wc -l)
if [ "$SUPERVISOR_DOWN" -gt 0 ]; then
    ALERTS+="[SUPERVISOR] ${SUPERVISOR_DOWN} process(es) not running\n"
    supervisorctl restart all 2>/dev/null
fi

# ── Asterisk ──
ACTIVE_CALLS=0
if systemctl is-active --quiet asterisk; then
    ACTIVE_CALLS=$(asterisk -rx 'core show channels count' 2>/dev/null | grep 'active call' | awk '{print $1}')
fi

# ── Python API ──
HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8001/api/health 2>/dev/null)
if [ "$HTTP_CODE" != "200" ]; then
    ALERTS+="[PYTHON-API] Health check HTTP ${HTTP_CODE}\n"
fi

# Log
echo "[$TIMESTAMP] CPU:${CPU_USAGE}% RAM:${RAM_USAGE}% Disk:${DISK_USAGE}% Calls:${ACTIVE_CALLS:-0}" >> $LOG

# Alert
if [ -n "$ALERTS" ]; then
    echo "[$TIMESTAMP] ALERTS:" >> $LOG
    echo -e "$ALERTS" >> $LOG
    echo "=== rSwitch Alert [$TIMESTAMP] ==="
    echo -e "$ALERTS"
fi

# Keep log manageable
tail -10000 $LOG > ${LOG}.tmp && mv ${LOG}.tmp $LOG 2>/dev/null
