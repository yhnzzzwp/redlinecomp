#!/usr/bin/env bash
# ============================================================
# Backup harian MySQL SIRC — kewajiban SRS §3.5 (retensi ≥30 hari).
#
# Pasang di cron host (jam 02.00 setiap hari):
#   0 2 * * * /path/ke/redline/scripts/backup-db.sh >> /path/ke/redline/storage/logs/backup.log 2>&1
#
# Hasil: backups/redline-YYYYmmdd-HHMMSS.sql.gz
# ============================================================
set -euo pipefail
cd "$(dirname "$0")/.."

DIR="backups"
mkdir -p "$DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
FILE="$DIR/redline-$STAMP.sql.gz"

# Kredensial diambil dari environment container db sendiri — tidak ada
# password yang tertulis di script/cron.
docker compose exec -T db sh -c \
    'exec mysqldump --single-transaction --no-tablespaces --routines --triggers -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
    | gzip > "$FILE"

# Validasi hasil: harus berawalan header dump MySQL dan tidak kosong.
if ! gunzip -c "$FILE" | head -1 | grep -q "MySQL dump"; then
    echo "[$(date '+%F %T')] GAGAL: dump tidak valid, file dihapus." >&2
    rm -f "$FILE"
    exit 1
fi

echo "[$(date '+%F %T')] Backup OK: $FILE ($(du -h "$FILE" | cut -f1))"

# Retensi: buang backup lebih tua dari 30 hari.
find "$DIR" -name 'redline-*.sql.gz' -mtime +30 -print -delete | sed 's/^/[retensi] hapus: /'
