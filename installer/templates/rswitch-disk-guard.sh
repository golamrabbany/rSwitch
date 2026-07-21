#!/usr/bin/env bash
# rswitch-disk-guard — autonomous last line of defence against a full root disk.
# Added 2026-07-21 after the disk-full outage (Redis MISCONF -> site 500).
# Deliberately conservative: it only ever reclaims LOG space. It never touches
# MySQL data, binlogs, or application files — those are judgement calls for a human.
set -uo pipefail

LOG=/var/log/rswitch-disk-guard.log
WARN=80   # percent used
CRIT=90

# Buffer log lines in memory and flush at exit. This script force-rotates every
# logrotate config, which would otherwise truncate THIS log mid-run and destroy
# the audit trail of what it just did. Self-trimmed, so no logrotate config.
_buf=""
log() { _buf+="$(date '+%F %T') $*"$'\n'; }
flush() {
  [ -n "$_buf" ] || return 0
  printf '%s' "$_buf" >>"$LOG"
  # keep the last 2000 lines; never managed by logrotate (see above)
  if [ "$(wc -l <"$LOG" 2>/dev/null || echo 0)" -gt 2000 ]; then
    tail -n 2000 "$LOG" >"$LOG.tmp" && mv "$LOG.tmp" "$LOG"
  fi
}
trap flush EXIT

used=$(df --output=pcent / | tail -1 | tr -dc '0-9')
avail=$(df -h --output=avail / | tail -1 | tr -d ' ')

[ "$used" -lt "$WARN" ] && exit 0

log "WARNING: root disk ${used}% used (${avail} free) — reclaiming log space"

# --- tier 1: safe reclaim, log space only ---------------------------------
before=$(df --output=avail / | tail -1)

journalctl --vacuum-size=200M >/dev/null 2>&1 && log "  journald vacuumed to 200M"
logrotate -f /etc/logrotate.d/asterisk >/dev/null 2>&1 && log "  asterisk logs force-rotated"
logrotate -f /etc/logrotate.conf        >/dev/null 2>&1 && log "  all logrotate configs forced"

# compress anything already rotated but left uncompressed
find /var/log -type f -regextype posix-extended \
     -regex '.*\.[0-9]+$' -size +50M -exec gzip -f {} \; 2>/dev/null \
     && log "  compressed large rotated logs"

after=$(df --output=avail / | tail -1)
log "  tier-1 reclaimed $(( (after - before) / 1024 )) MB"

used=$(df --output=pcent / | tail -1 | tr -dc '0-9')

# --- tier 2: still critical — truncate runaway individual logs -------------
if [ "$used" -ge "$CRIT" ]; then
  log "CRITICAL: still ${used}% used — truncating individual logs over 1GB"
  while IFS= read -r f; do
    sz=$(du -h "$f" | cut -f1)
    : >"$f" && log "  TRUNCATED $f (was $sz)"
  done < <(find /var/log {{INSTALL_DIR}}/storage/logs -maxdepth 2 -type f -size +1G 2>/dev/null)

  # Redis refuses all writes once a bgsave fails; nudge it once space exists.
  redis-cli bgsave >/dev/null 2>&1 && log "  redis bgsave triggered (clears MISCONF)"
fi

final=$(df --output=pcent / | tail -1 | tr -dc '0-9')
log "done — root disk now ${final}% used"

# Loud marker so this is visible in journald/monitoring even with no alerting.
if [ "$final" -ge "$CRIT" ]; then
  logger -p daemon.crit -t rswitch-disk-guard \
    "ROOT DISK STILL ${final}% FULL AFTER AUTOMATIC LOG CLEANUP — MANUAL ACTION REQUIRED"
fi
