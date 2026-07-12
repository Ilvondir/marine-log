#!/bin/bash
set -e

# Ensure storage directories exist with correct permissions
echo "[entrypoint] Ensuring storage directories..."
mkdir -p /var/www/html/storage/app/public/observations
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Create storage symlink if missing
if [ ! -L /var/www/html/public/storage ]; then
    echo "[entrypoint] Creating storage symlink..."
    php /var/www/html/artisan storage:link
fi

# Run migrations and seed if enabled (default: true)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php /var/www/html/artisan migrate --force

    echo "[entrypoint] Running seeders..."
    php /var/www/html/artisan db:seed --force
fi

# Hand off to Apache
exec apache2-foreground
