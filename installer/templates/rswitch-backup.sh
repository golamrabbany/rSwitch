#!/bin/bash
# rSwitch Daily Backup Script — runs at 2 AM via cron
# Backs up: MySQL database, Asterisk configs, environment files

BACKUP_DIR=/var/backups/rswitch
DATE=$(date +%Y-%m-%d_%H%M)
KEEP_DAYS=14

mkdir -p $BACKUP_DIR

echo "[$(date)] Starting rSwitch backup..."

# MySQL database dump
mysqldump --single-transaction --routines --triggers rswitch 2>/dev/null | gzip > ${BACKUP_DIR}/db_${DATE}.sql.gz
echo "  DB: $(du -h ${BACKUP_DIR}/db_${DATE}.sql.gz | awk '{print $1}')"

# Asterisk configs
tar czf ${BACKUP_DIR}/asterisk_${DATE}.tar.gz -C /etc asterisk/ 2>/dev/null
echo "  Asterisk configs backed up"

# Environment files
tar czf ${BACKUP_DIR}/env_${DATE}.tar.gz \
    /var/www/rswitch/.env \
    /var/www/rswitch/python-services/.env \
    2>/dev/null
echo "  Environment files backed up"

# Cleanup old backups
find ${BACKUP_DIR} -name '*.gz' -mtime +${KEEP_DAYS} -delete
echo "  Cleaned backups older than ${KEEP_DAYS} days"

echo "[$(date)] Backup complete"
