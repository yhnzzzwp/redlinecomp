#!/bin/bash
# Entrypoint container app: pasang dependensi bila perlu, tunggu DB, migrasi, lalu jalankan FPM.
set -e

cd /var/www

# Pasang dependensi PHP jika vendor belum ada (mis. clone bersih)
if [ ! -d vendor ]; then
  echo "[entrypoint] composer install..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate APP_KEY bila belum ada
if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
  php artisan key:generate --force || true
fi

# Tunggu MySQL siap
echo "[entrypoint] menunggu database..."
until php -r "try{new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));echo 'ok';}catch(Exception \$e){exit(1);}" 2>/dev/null; do
  sleep 2
done
echo "[entrypoint] database siap."

# Migrasi + seed (aman diulang)
php artisan migrate --force || true

# Cache konfigurasi di lingkungan non-lokal
if [ "${APP_ENV:-local}" != "local" ]; then
  php artisan config:cache && php artisan route:cache && php artisan view:cache
fi

exec "$@"
