#!/usr/bin/env bash
# ============================================================
# Pulihkan backup SIRC ke database.
#
# Pemakaian:
#   scripts/restore-db.sh backups/redline-XXXX.sql.gz            # -> db utama (minta konfirmasi)
#   scripts/restore-db.sh backups/redline-XXXX.sql.gz redline_uji # -> db lain (uji restore)
#   scripts/restore-db.sh -y backups/redline-XXXX.sql.gz          # tanpa konfirmasi
# ============================================================
set -euo pipefail
cd "$(dirname "$0")/.."

YES=0
if [ "${1:-}" = "-y" ]; then YES=1; shift; fi

FILE="${1:?Pemakaian: $0 [-y] <backups/file.sql.gz> [nama_db]}"
TARGET="${2:-redline}"

[ -f "$FILE" ] || { echo "File tidak ditemukan: $FILE" >&2; exit 1; }

if [ "$TARGET" = "redline" ] && [ "$YES" != "1" ]; then
    printf 'PERINGATAN: ini MENIMPA database utama "redline". Lanjutkan? [ketik: ya] '
    read -r JAWAB
    [ "$JAWAB" = "ya" ] || { echo "Dibatalkan."; exit 1; }
fi

docker compose exec -T db sh -c \
    'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`'"$TARGET"'\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"'

gunzip -c "$FILE" | docker compose exec -T db sh -c \
    'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "'"$TARGET"'"'

echo "Restore selesai: $FILE -> database '$TARGET'."
