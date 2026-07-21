#!/bin/bash
# rSwitch Daily Backup Script — runs at 2 AM via cron
# Backs up: MySQL database, Asterisk configs, environment files
#
# HISTORY: this script silently produced 20-byte (empty) database dumps for
# weeks. mysqldump was invoked with NO credentials, so it failed with
# "Access denied for user 'root'@'localhost'", stderr was sent to /dev/null,
# and `gzip` of empty input exited 0 — leaving a valid-looking .sql.gz and a
# "Backup complete" line in the log. The result was no recoverable database
# backup at all, discovered only when one was needed.
#
# Hence the rules below: authenticate explicitly, never discard stderr, use
# pipefail so a failed mysqldump is not masked by a successful gzip, and
# VERIFY the artifact rather than trusting the exit code.
set -uo pipefail

BACKUP_DIR=/var/backups/rswitch
DATE=$(date +%Y-%m-%d_%H%M)
KEEP_DAYS=14
# Smallest plausible real dump. An empty gzip is ~20 bytes; a schema-only dump
# of this app is tens of KB. Anything under this means the dump did not happen.
MIN_DB_BYTES=100000

mkdir -p "$BACKUP_DIR"
rc=0

echo "[$(date)] Starting rSwitch backup..."

# ── MySQL database dump ────────────────────────────────────────────────────
# --defaults-file supplies credentials; without it cron's root has no MySQL
# grant on this host and the dump fails.
DB_FILE="${BACKUP_DIR}/db_${DATE}.sql.gz"
if mysqldump --defaults-file=/etc/mysql/debian.cnf \
       --single-transaction --routines --triggers rswitch | gzip > "$DB_FILE"; then
    size=$(stat -c%s "$DB_FILE" 2>/dev/null || echo 0)
    if [ "$size" -lt "$MIN_DB_BYTES" ]; then
        echo "  DB: FAILED — dump is only ${size} bytes (expected >= ${MIN_DB_BYTES})." >&2
        echo "      Treating as failure; keeping the file for inspection." >&2
        rc=1
    else
        echo "  DB: OK $(du -h "$DB_FILE" | awk '{print $1}')"
        # Prove it is a readable dump, not just a non-empty file.
        if ! gzip -t "$DB_FILE" 2>/dev/null; then
            echo "  DB: FAILED — archive is corrupt (gzip -t)." >&2
            rc=1
        fi
    fi
else
    echo "  DB: FAILED — mysqldump exited non-zero (see error above)." >&2
    rc=1
fi

# ── Asterisk configs ───────────────────────────────────────────────────────
if tar czf "${BACKUP_DIR}/asterisk_${DATE}.tar.gz" -C /etc asterisk/; then
    echo "  Asterisk configs: OK"
else
    echo "  Asterisk configs: FAILED" >&2
    rc=1
fi

# ── Environment files ──────────────────────────────────────────────────────
if tar czf "${BACKUP_DIR}/env_${DATE}.tar.gz" \
        /var/www/rswitch/.env \
        /var/www/rswitch/python-services/.env; then
    echo "  Environment files: OK"
else
    echo "  Environment files: FAILED" >&2
    rc=1
fi

# ── Cleanup old backups ────────────────────────────────────────────────────
# Only prune once we know today's database dump is good, so a run of failures
# can never age out the last usable backup.
if [ "$rc" -eq 0 ]; then
    find "$BACKUP_DIR" -name '*.gz' -mtime +${KEEP_DAYS} -delete
    echo "  Cleaned backups older than ${KEEP_DAYS} days"
else
    echo "  SKIPPING cleanup — this run failed, retaining all existing backups" >&2
fi

if [ "$rc" -eq 0 ]; then
    echo "[$(date)] Backup complete"
else
    echo "[$(date)] Backup FAILED — see errors above" >&2
    logger -p daemon.err -t rswitch-backup "rSwitch backup FAILED — no usable database dump"
fi
exit "$rc"
