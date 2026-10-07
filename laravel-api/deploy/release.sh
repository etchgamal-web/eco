#!/usr/bin/env bash
set -Eeuo pipefail

APP_ROOT="${APP_ROOT:-/var/www/ecommerce-platform}"
RELEASES_DIR="${RELEASES_DIR:-$APP_ROOT/releases}"
SHARED_DIR="${SHARED_DIR:-$APP_ROOT/shared}"
CURRENT_LINK="${CURRENT_LINK:-$APP_ROOT/current}"
RELEASE_DIR="${RELEASE_DIR:-}"
HEALTH_URL="${HEALTH_URL:-}"
SKIP_HEALTH_CHECK="${SKIP_HEALTH_CHECK:-0}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
ROLLBACK="${ROLLBACK:-0}"

log() { printf '[release] %s\n' "$*"; }
fail() { log "ERROR: $*" >&2; exit 1; }

[[ -d "$APP_ROOT" ]] || fail "APP_ROOT does not exist: $APP_ROOT"
mkdir -p "$RELEASES_DIR" "$SHARED_DIR"

ready_check() {
  [[ "$SKIP_HEALTH_CHECK" == "1" ]] && return 0
  [[ -n "$HEALTH_URL" ]] || { log 'HEALTH_URL is required unless SKIP_HEALTH_CHECK=1.' >&2; return 1; }
  for attempt in 1 2 3 4 5; do
    if curl --fail --silent --show-error --max-time 15 "$HEALTH_URL/ready" >/dev/null; then
      log 'Readiness check passed.'
      return 0
    fi
    [[ "$attempt" == 5 ]] && return 1
    sleep 5
  done
}

if [[ "$ROLLBACK" == "1" ]]; then
  current_target=''
  [[ -L "$CURRENT_LINK" ]] && current_target="$(readlink -f "$CURRENT_LINK")"

  previous="$(find "$RELEASES_DIR" -mindepth 3 -maxdepth 3 -type f -name artisan -printf '%T@ %h\n' \
    | sort -nr \
    | cut -d' ' -f2- \
    | while IFS= read -r candidate; do
        [[ "$candidate" != "$current_target" ]] && { printf '%s\n' "$candidate"; break; }
      done)"
  [[ -n "$previous" && -f "$previous/artisan" ]] || fail 'No previous Laravel release is available for rollback.'

  ln -sfn "$previous" "$CURRENT_LINK"
  (cd "$previous" && php artisan queue:restart || true)
  if ! ready_check; then
    [[ -n "$current_target" ]] && ln -sfn "$current_target" "$CURRENT_LINK"
    fail 'Rollback readiness check failed; restored the previous current symlink.'
  fi
  log "Rolled back to $previous"
  exit 0
fi

[[ -n "$RELEASE_DIR" && -d "$RELEASE_DIR" ]] || fail 'RELEASE_DIR must point to an uploaded release directory.'
[[ -f "$RELEASE_DIR/artisan" ]] || fail "Laravel artisan not found in $RELEASE_DIR"
[[ -f "$SHARED_DIR/.env" ]] || fail "Shared production environment is missing: $SHARED_DIR/.env"

previous=''
if [[ -L "$CURRENT_LINK" ]]; then
  previous="$(readlink -f "$CURRENT_LINK")"
fi

release_succeeded=0
rollback_on_exit() {
  status=$?
  if [[ "$status" != 0 && "$release_succeeded" != 1 && -n "$previous" && -f "$previous/artisan" ]]; then
    ln -sfn "$previous" "$CURRENT_LINK"
    (cd "$previous" && php artisan queue:restart || true)
    log "Release failed; restored $previous"
  fi
  exit "$status"
}
trap rollback_on_exit EXIT

mkdir -p "$SHARED_DIR/storage/app/public" "$SHARED_DIR/storage/framework/cache" \
  "$SHARED_DIR/storage/framework/sessions" "$SHARED_DIR/storage/framework/testing" \
  "$SHARED_DIR/storage/framework/views" "$SHARED_DIR/storage/logs"
rm -rf "$RELEASE_DIR/storage"
ln -s "$SHARED_DIR/storage" "$RELEASE_DIR/storage"
rm -f "$RELEASE_DIR/.env"
ln -s "$SHARED_DIR/.env" "$RELEASE_DIR/.env"

cd "$RELEASE_DIR"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize
php artisan about --only=environment,debug
ln -sfn "$RELEASE_DIR" "$CURRENT_LINK"
php artisan storage:link
php artisan queue:restart

ready_check || fail 'Readiness check failed after release.'

release_succeeded=1
trap - EXIT

find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' \
  | sort -nr \
  | tail -n +$((KEEP_RELEASES + 1)) \
  | cut -d' ' -f2- \
  | xargs -r rm -rf
log "Release activated: $RELEASE_DIR"
