#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="${APP_ROOT:-/var/www/ecommerce-platform}"
RELEASES_DIR="${RELEASES_DIR:-$APP_ROOT/releases}"
CURRENT_LINK="${CURRENT_LINK:-$APP_ROOT/current}"
RELEASE_DIR="${RELEASE_DIR:-}"
HEALTH_URL="${HEALTH_URL:-}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
ROLLBACK="${ROLLBACK:-0}"

log() { printf '[release] %s\n' "$*"; }
fail() { log "ERROR: $*" >&2; exit 1; }

[[ -d "$APP_ROOT" ]] || fail "APP_ROOT does not exist: $APP_ROOT"
[[ -d "$RELEASES_DIR" ]] || mkdir -p "$RELEASES_DIR"

if [[ "$ROLLBACK" == "1" ]]; then
  previous="$(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -nr | sed -n '2p' | cut -d' ' -f2-)"
  [[ -n "$previous" && -d "$previous" ]] || fail 'No previous release is available for rollback.'
  ln -sfn "$previous" "$CURRENT_LINK"
  if [[ -x "$previous/artisan" ]]; then
    (cd "$previous" && php artisan queue:restart || true)
  fi
  log "Rolled back to $previous"
  exit 0
fi

[[ -n "$RELEASE_DIR" && -d "$RELEASE_DIR" ]] || fail 'RELEASE_DIR must point to an uploaded release directory.'
[[ -f "$RELEASE_DIR/artisan" ]] || fail "Laravel artisan not found in $RELEASE_DIR"

previous=''
if [[ -L "$CURRENT_LINK" ]]; then
  previous="$(readlink -f "$CURRENT_LINK")"
fi

release_succeeded=0
rollback_on_exit() {
  status=$?
  if [[ "$status" != 0 && "$release_succeeded" != 1 && -n "$previous" && -d "$previous" ]]; then
    ln -sfn "$previous" "$CURRENT_LINK"
    (cd "$previous" && php artisan queue:restart || true)
    log "Release failed; restored $previous"
  fi
  exit "$status"
}
trap rollback_on_exit EXIT

cd "$RELEASE_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan storage:link || true
php artisan migrate --force
php artisan optimize
php artisan about --only=environment,debug
ln -sfn "$RELEASE_DIR" "$CURRENT_LINK"
php artisan queue:restart

if [[ -n "$HEALTH_URL" ]]; then
  for attempt in 1 2 3 4 5; do
    if curl --fail --silent --show-error --max-time 15 "$HEALTH_URL/ready" >/dev/null; then
      log 'Readiness check passed.'
      break
    fi
    [[ "$attempt" == 5 ]] && fail 'Readiness check failed after release.'
    sleep 5
done
fi

release_succeeded=1
trap - EXIT

find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -nr | tail -n +$((KEEP_RELEASES + 1)) | cut -d' ' -f2- | xargs -r rm -rf
log "Release activated: $RELEASE_DIR"
