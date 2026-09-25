#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${APP_DIR:-$ROOT_DIR/laravel-api}"
INSTALL_REDIS="${INSTALL_REDIS:-true}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"
RUN_SEEDERS="${RUN_SEEDERS:-true}"
INSTALL_DEV="${INSTALL_DEV:-false}"
CHECK_ONLY="false"
APP_USER="${APP_USER:-${SUDO_USER:-$(id -un)}}"

log() { printf '\n[install] %s\n' "$*"; }
fatal() { printf '\n[install] ERROR: %s\n' "$*" >&2; exit 1; }
info() { printf '[install] %s\n' "$*"; }

usage() {
    cat <<'EOF'
Usage: ./install.sh [options]

Options:
  --check-only       Check PHP, Composer, extensions, and Redis without changes
  --app-dir=PATH     Laravel application directory
  --no-redis         Do not install or start Redis
  --no-migrations    Do not run database migrations
  --no-seeders       Do not run database seeders
  --dev              Install development Composer dependencies
  -h, --help         Show this help

Environment equivalents:
  APP_DIR, APP_USER, INSTALL_REDIS, RUN_MIGRATIONS, RUN_SEEDERS, INSTALL_DEV
EOF
}

for argument in "$@"; do
    case "$argument" in
        --check-only) CHECK_ONLY="true" ;;
        --app-dir=*) APP_DIR="${argument#*=}" ;;
        --no-redis) INSTALL_REDIS="false" ;;
        --no-migrations) RUN_MIGRATIONS="false" ;;
        --no-seeders) RUN_SEEDERS="false" ;;
        --dev) INSTALL_DEV="true" ;;
        -h|--help) usage; exit 0 ;;
        *) fatal "Unknown option: $argument. Use --help." ;;
    esac
done

[[ -d "$APP_DIR" ]] || fatal "Laravel application directory not found: $APP_DIR"

php_version_ok() {
    command -v php >/dev/null 2>&1 && php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'
}

required_extensions=(mbstring dom curl pdo_sqlite sqlite3 zip bcmath intl)
missing_extensions=()
if command -v php >/dev/null 2>&1; then
    for extension in "${required_extensions[@]}"; do
        php -m | grep -Eiq "^${extension}$" || missing_extensions+=("$extension")
    done
fi

log "Checking existing runtime"
if command -v php >/dev/null 2>&1; then
    info "PHP detected: $(php -r 'echo PHP_VERSION;')"
    if php_version_ok; then
        info "PHP version is compatible; PHP will not be reinstalled."
    else
        fatal "PHP 8.3 or newer is required. Detected: $(php -r 'echo PHP_VERSION;'). Upgrade PHP, then rerun this installer."
    fi
else
    info "PHP was not found; the installer will install it."
fi

if command -v composer >/dev/null 2>&1; then
    info "Composer detected: $(composer --version --no-ansi | head -1)"
else
    info "Composer was not found; the installer will install it."
fi

if ((${#missing_extensions[@]} > 0)); then
    info "Missing PHP extensions: ${missing_extensions[*]}"
else
    info "Required PHP extensions are available."
fi

if [[ "$INSTALL_REDIS" == "true" ]]; then
    if command -v redis-cli >/dev/null 2>&1; then
        info "Redis CLI detected."
    else
        info "Redis was not found; the installer will install it."
    fi
fi

if [[ "$CHECK_ONLY" == "true" ]]; then
    if ! command -v php >/dev/null 2>&1 || ! php_version_ok || ! command -v composer >/dev/null 2>&1 || ((${#missing_extensions[@]} > 0)); then
        exit 1
    fi
    if [[ "$INSTALL_REDIS" == "true" ]] && ! command -v redis-cli >/dev/null 2>&1; then
        exit 1
    fi
    info "Environment check passed. No changes were made."
    exit 0
fi

command -v sudo >/dev/null 2>&1 || fatal "sudo is required"
command -v apt-get >/dev/null 2>&1 || fatal "This installer supports Debian/Ubuntu systems only"
id "$APP_USER" >/dev/null 2>&1 || fatal "Application user does not exist: $APP_USER"

packages=(ca-certificates curl git unzip)
if ! command -v php >/dev/null 2>&1; then
    packages+=(php-cli php-mbstring php-xml php-curl php-sqlite3 php-zip php-bcmath php-intl)
elif ((${#missing_extensions[@]} > 0)); then
    for extension in "${missing_extensions[@]}"; do
        case "$extension" in
            mbstring) packages+=(php-mbstring) ;;
            dom) packages+=(php-xml) ;;
            curl) packages+=(php-curl) ;;
            pdo_sqlite|sqlite3) packages+=(php-sqlite3) ;;
            zip) packages+=(php-zip) ;;
            bcmath) packages+=(php-bcmath) ;;
            intl) packages+=(php-intl) ;;
        esac
    done
fi
command -v composer >/dev/null 2>&1 || packages+=(composer)
if [[ "$INSTALL_REDIS" == "true" ]] && ! command -v redis-cli >/dev/null 2>&1; then
    packages+=(redis-server)
fi

if ((${#packages[@]} > 0)); then
    log "Installing missing system packages: ${packages[*]}"
    sudo apt-get update
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y "${packages[@]}"
else
    log "All system packages are already available; skipping apt installation"
fi

command -v php >/dev/null 2>&1 || fatal "PHP installation failed"
command -v composer >/dev/null 2>&1 || fatal "Composer installation failed"
php_version_ok || fatal "PHP 8.3 or newer is required after installation"

if [[ "$INSTALL_REDIS" == "true" ]]; then
    if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files redis-server.service >/dev/null 2>&1; then
        log "Enabling Redis service"
        sudo systemctl enable --now redis-server
    fi
fi

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
