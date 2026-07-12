#!/bin/bash
set -e

# Run migrations and seed if enabled (default: true)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php /var/www/html/artisan migrate --force

    echo "[entrypoint] Running seeders..."
    php /var/www/html/artisan db:seed --force
fi

# Hand off to Apache
exec apache2-foreground
