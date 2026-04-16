#!/bin/bash
set -e
 
# ------------------------------------------------------------------
# Medibook – PHP-FPM container entrypoint
# Runs automatically on every `docker compose up`.
# ------------------------------------------------------------------
 
APP_DIR="/var/www/html"
JWT_DIR="${APP_DIR}/config/jwt"
APP_ENV="${APP_ENV:-dev}"
 
echo "[entrypoint] APP_ENV=${APP_ENV}"
 
# 1. Wait for MySQL — parse DATABASE_URL and open a PDO connection
echo "[entrypoint] Waiting for database…"
until php -r "
  \$url = getenv('DATABASE_URL') ?: '';
  preg_match('#mysql://([^:]+):([^@]*)@([^:/]+)(?::(\d+))?/([^?]+)#', \$url, \$m);
  \$dsn = 'mysql:host=' . (\$m[3] ?? 'database') . ';port=' . (\$m[4] ?? 3306) . ';dbname=' . (\$m[5] ?? 'medirdv');
  new PDO(\$dsn, \$m[1] ?? 'medirdv', \$m[2] ?? 'password');
" 2>/dev/null; do
    sleep 2
done
echo "[entrypoint] Database is up."
 
# 2. Run pending migrations (idempotent — safe to run on every restart)
echo "[entrypoint] Running migrations…"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
echo "[entrypoint] Migrations done."
 
# 3. Seed data in dev if the database is empty (no rows in the user table)
if [ "${APP_ENV}" = "dev" ]; then
    USER_COUNT=$(php -r "
      \$url = getenv('DATABASE_URL') ?: '';
      preg_match('#mysql://([^:]+):([^@]*)@([^:/]+)(?::(\d+))?/([^?]+)#', \$url, \$m);
      \$dsn = 'mysql:host=' . (\$m[3] ?? 'database') . ';port=' . (\$m[4] ?? 3306) . ';dbname=' . (\$m[5] ?? 'medirdv');
      \$pdo = new PDO(\$dsn, \$m[1] ?? 'medirdv', \$m[2] ?? 'password');
      echo \$pdo->query('SELECT COUNT(*) FROM \`user\`')->fetchColumn();
    " 2>/dev/null || echo "0")
 
    if [ "${USER_COUNT:-0}" = "0" ]; then
        if php bin/console list 2>/dev/null | grep -q 'doctrine:fixtures:load'; then
            echo "[entrypoint] Empty database — loading fixtures…"
            php bin/console doctrine:fixtures:load --no-interaction
            echo "[entrypoint] Fixtures loaded."
        else
            echo "[entrypoint] DoctrineFixturesBundle not installed (run composer install without --no-dev). Skipping fixtures."
        fi
    else
        echo "[entrypoint] Database already seeded (${USER_COUNT} users) — skipping fixtures."
        echo "[entrypoint] To force a reseed: docker compose exec app php bin/console doctrine:fixtures:load --no-interaction"
    fi
fi
 
# 4. Generate JWT key pair if not already present
if [ ! -f "${JWT_DIR}/private.pem" ] || [ ! -f "${JWT_DIR}/public.pem" ]; then
    echo "[entrypoint] Generating JWT key pair…"
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
    chmod 600 "${JWT_DIR}/private.pem"
    chmod 644 "${JWT_DIR}/public.pem"
    echo "[entrypoint] JWT keys created."
else
    echo "[entrypoint] JWT keys already present, skipping."
fi
 
echo "[entrypoint] Setup complete. Starting php-fpm…"
exec "$@"
 
 