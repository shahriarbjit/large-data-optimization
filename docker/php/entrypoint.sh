#!/bin/sh
# Starts the app container:
#   1. creates an APP_KEY once (kept in the storage volume) if none is given
#   2. waits for MySQL
#   3. runs migrations
#   4. seeds the database, but only when the users table is empty
#   5. caches config, routes and events, then starts PHP-FPM
set -e
cd /var/www/html

log() { echo "[entrypoint] $*"; }

# Storage is a volume: make sure its folders exist.
mkdir -p storage/framework/cache/data storage/framework/views storage/framework/sessions storage/logs storage/app

# 1. APP_KEY
if [ -z "${APP_KEY:-}" ]; then
    KEY_FILE=storage/app/.docker-app-key
    if [ ! -s "$KEY_FILE" ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$KEY_FILE"
        log "generated APP_KEY (stored in the storage volume)"
    fi
    APP_KEY="$(cat "$KEY_FILE")"
    export APP_KEY
fi

# 2. Wait for MySQL (up to 120 s).
log "waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
php -r '
    for ($i = 0; $i < 60; $i++) {
        try {
            new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
            exit(0);
        } catch (Throwable $e) {
            sleep(2);
        }
    }
    fwrite(STDERR, "MySQL is not reachable: ".$e->getMessage()."\n");
    exit(1);
'

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    # 3. Migrations
    log "running migrations"
    php artisan migrate --force --no-interaction

    # 4. Seed only an empty database (first start).
    USERS=$(php -r '
        $pdo = new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
        echo (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    ')
    if [ "$USERS" = "0" ] && [ "${SEED_ON_FIRST_RUN:-true}" = "true" ]; then
        log "empty database: seeding (SEED_BULK_USERS=${SEED_BULK_USERS:-1000000}); this takes a minute or two"
        php -d memory_limit=1G artisan db:seed --force --no-interaction
    else
        log "database already has ${USERS} users: skipping seed"
    fi
fi

# 5. Cache config, routes and events for speed (env is read here, at start).
#    Not `artisan optimize`: it also caches Blade views, and this API has none.
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan event:cache --no-interaction

chown -R www-data:www-data storage bootstrap/cache

log "ready"
exec "$@"
