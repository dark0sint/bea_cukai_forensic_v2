#!/bin/bash
set -e

cd /var/www/html

if [ ! -f .env ]; then
    echo "[entrypoint] .env tidak ditemukan, menyalin dari .env.example"
    cp .env.example .env
fi

echo "[entrypoint] Menunggu database MySQL siap..."
until mysqladmin ping -h "${DB_HOST:-mysql}" -u"${DB_USERNAME:-forensic_user}" -p"${DB_PASSWORD}" --silent 2>/dev/null; do
    sleep 2
done
echo "[entrypoint] Database siap."

if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force
fi

echo "[entrypoint] Menjalankan migrasi database..."
php artisan migrate --force

echo "[entrypoint] Seeding data awal (admin default)..."
php artisan db:seed --force || true

echo "[entrypoint] Membersihkan & meng-cache konfigurasi..."
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

echo "[entrypoint] Membuat storage symlink..."
php artisan storage:link || true

exec "$@"
