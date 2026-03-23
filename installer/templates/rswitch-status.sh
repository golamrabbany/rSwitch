#!/bin/bash
# rSwitch Server Status Dashboard

echo '╔══════════════════════════════════════════════╗'
echo '║           rSwitch Server Status              ║'
echo '╚══════════════════════════════════════════════╝'
echo ''
echo '── System ──'
printf '  CPU:    %s%%\n' $(top -bn1 | grep 'Cpu(s)' | awk '{print int($2 + $4)}')
printf '  RAM:    %s/%s (%s%% used)\n' $(free -h | awk '/Mem/{print $3, $2}') $(free | awk '/Mem/{print int($3/$2*100)}')
printf '  Disk:   %s/%s (%s used)\n' $(df -h / | tail -1 | awk '{print $3, $2, $5}')
echo ''
echo '── Services ──'
for svc in nginx php8.3-fpm mysql redis-server asterisk fail2ban; do
    STATUS=$(systemctl is-active $svc 2>/dev/null)
    if [ "$STATUS" = "active" ]; then
        printf '  %-16s OK\n' $svc
    else
        printf '  %-16s %s\n' $svc $STATUS
    fi
done
echo ''
echo '── Supervisor ──'
supervisorctl status 2>/dev/null | while read line; do
    echo "  $line"
done
echo ''
echo '── Asterisk ──'
asterisk -rx 'core show channels count' 2>/dev/null | head -3 | while read line; do
    echo "  $line"
done
echo ''
echo '── Fail2Ban ──'
printf '  Jails: %s\n' $(fail2ban-client status 2>/dev/null | grep 'Number of jail' | awk '{print $NF}')
echo ''
echo '── Python API ──'
curl -s http://127.0.0.1:8001/api/health 2>/dev/null || echo '  Not responding'
echo ''
echo ''
echo '── Last Monitor Log ──'
tail -3 /var/log/rswitch-monitor.log 2>/dev/null
