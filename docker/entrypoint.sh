#!/bin/sh
# Container entrypoint.
#
# With the default command (supervisord) it waits for MySQL, caches the
# configuration, runs migrations and base seeders when enabled and then
# starts nginx, PHP-FPM, the queue worker and the scheduler.
# Any other command (for example `php artisan key:generate --show`) runs
# directly without these steps.
#
# Environment:
#   RUN_MIGRATIONS   true|false  run `php artisan migrate --force` (default true)
#   RUN_SEEDERS      true|false  seed provinces, cities, vehicle types and the
#                                admin from ADMIN_MOBILE / ADMIN_PASSWORD
#                                (default false, idempotent)
#   DB_WAIT_TIMEOUT  seconds to wait for the database (default 60)

set -eu

cd /var/www/html

log() {
    printf '[entrypoint] %s\n' "$*"
}

die() {
    printf '[entrypoint] ERROR: %s\n' "$*" >&2
    exit 1
}

if [ "$#" -eq 0 ]; then
    set -- supervisord -c /etc/supervisord.conf
fi

if [ "$1" != "supervisord" ]; then
    exec "$@"
fi

if [ -z "${APP_KEY:-}" ] && ! grep -Eq '^APP_KEY=.+' .env 2>/dev/null; then
    die "APP_KEY is not set. Generate one with: docker compose run --rm --no-deps app php artisan key:generate --show"
fi

for dir in storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private bootstrap/cache; do
    mkdir -p "$dir" 2>/dev/null || true
    [ -w "$dir" ] || die "$dir is not writable by $(id -un). Fix the ownership of the storage volume."
done

wait_for_database() {
    case "${DB_CONNECTION:-mysql}" in
        mysql | mariadb) ;;
        *) return 0 ;;
    esac

    timeout="${DB_WAIT_TIMEOUT:-60}"
    elapsed=0
    log "Waiting for the database at ${DB_HOST:-127.0.0.1}:${DB_PORT:-3306} ..."
    # shellcheck disable=SC2016 # PHP code, not shell expansions.
    until php -r '
        try {
            new PDO(
                sprintf("mysql:host=%s;port=%s", getenv("DB_HOST") ?: "127.0.0.1", getenv("DB_PORT") ?: "3306"),
                getenv("DB_USERNAME") ?: "root",
                getenv("DB_PASSWORD") ?: "",
                [PDO::ATTR_TIMEOUT => 3],
            );
        } catch (Throwable $e) {
            exit(1);
        }
    ' >/dev/null 2>&1; do
        if [ "$elapsed" -ge "$timeout" ]; then
            die "The database did not become reachable within ${timeout}s."
        fi
        sleep 2
        elapsed=$((elapsed + 2))
    done
    log "Database is reachable."
}

wait_for_database

# Caches config, events, routes, views and Filament components.
php artisan optimize

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force --class='Database\Seeders\ProvinceCitySeeder'
    php artisan db:seed --force --class='Database\Seeders\VehicleTypeSeeder'
    php artisan db:seed --force --class='Database\Seeders\AdminUserSeeder'
fi

log "Starting nginx, PHP-FPM, queue worker and scheduler."
exec "$@"
