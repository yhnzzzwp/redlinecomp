#!/bin/bash
# Backup database harian — jalankan via cron
BACKUP_DIR="/var/www/storage/backups"
mkdir -p $BACKUP_DIR
FILENAME="redline-$(date +%Y%m%d-%H%M%S).sql.gz"
mysqldump -h${DB_HOST:-db} -u${DB_USERNAME:-redline} -p${DB_PASSWORD:-redline_secret} ${DB_DATABASE:-redline} | gzip > $BACKUP_DIR/$FILENAME
# Hapus backup lebih dari 30 hari
find $BACKUP_DIR -name '*.sql.gz' -mtime +30 -delete
echo "Backup selesai: $FILENAME"
