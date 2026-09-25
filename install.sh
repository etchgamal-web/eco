#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${APP_DIR:-$ROOT_DIR/laravel-api}"
INSTALL_REDIS="${INSTALL_REDIS:-true}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"
RUN_SEEDERS="${RUN_SEEDERS:-true}"
INSTALL_DEV="${INSTALL_DEV:-false}"
APP_USER="${APP_USER:-${SUDO_USER:-$(id -un)}}"

log() { printf '\n[install] %s\n' "$*"; }
fatal() { printf '\n[install] ERROR: %s\n' "$*" >&2; exit 1; }

[[ -d "$APP_DIR" ]] || fatal "Laravel application directory not found: $APP_DIR"
command -v sudo >/dev/null 2>&1 || fatal "sudo is required"
command -v apt-get >/dev/null 2>&1 || fatal "This installer supports Debian/Ubuntu systems only"

if ! id "$APP_USER" >/dev/null 2>&1; then
    fatal "Application user does not exist: $APP_USER"
fi

log "Installing system packages"
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    ca-certificates curl git unzip \
    php-cli php-mbstring php-xml php-curl php-sqlite3 php-zip php-bcmath php-intl \
    composer

if [[ "$INSTALL_REDIS" == "true" ]]; then
    log "Installing and enabling Redis"
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y redis-server
    sudo systemctl enable --now redis-server
fi

command -v php >/dev/null || fatal "PHP installation failed"
command -v composer >/dev/null || fatal "Composer installation failed"
php -r 'version_compare(PHP_VERSION, "8.3.0", ">=") || exit(1);' || fatal "PHP 8.3 or newer is required"

cd "$APP_DIR"

if [[ ! -f .env ]]; then
    log "Creating .env from .env.example"
    cp .env.example .env
fi

log "Installing PHP dependencies"
if [[ "$INSTALL_DEV" == "true" ]]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
fi

if grep -q '^APP_KEY=$' .env; then
    log "Generating Laravel application key"
    php artisan key:generate --force
fi

log "Preparing Laravel runtime directories"
mkdir -p storage/app/private storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
sudo chown -R "$APP_USER":"$APP_USER" storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
php artisan storage:link >/dev/null 2>&1 || true

if [[ "$RUN_MIGRATIONS" == "true" ]]; then
    log "Running database migrations"
    php artisan migrate --force
fi

if [[ "$RUN_SEEDERS" == "true" ]]; then
    log "Running idempotent application seeders"
    php artisan db:seed --force
fi

log "Caching Laravel configuration"
php artisan optimize

log "Installation completed"
printf '\nApplication: %s\n' "$APP_DIR"
printf 'PHP:         %s\n' "$(php -r 'echo PHP_VERSION;')"
printf 'Composer:    %s\n' "$(composer --version --no-ansi | head -1)"
printf '\nNext steps:\n'
printf '1. Review and secure %s/.env\n' "$APP_DIR"
printf '2. Set APP_ENV, APP_DEBUG=false, database, CORS, Sentry, and metrics secrets.\n'
printf '3. Configure Nginx/Apache with HTTPS.\n'
printf '4. Install the Supervisor worker and scheduler from deploy/.\n'
printf '5. Run scripts/smoke-test.sh against the deployed HTTPS URL.\n'
