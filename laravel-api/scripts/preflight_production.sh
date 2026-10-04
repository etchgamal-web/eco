#!/usr/bin/env bash
set -euo pipefail

failures=0
check() {
  local name="$1" value="$2"
  if [[ -z "$value" ]]; then
    printf 'FAIL %-24s missing\n' "$name"
    failures=$((failures + 1))
  else
    printf 'PASS %-24s configured\n' "$name"
  fi
}

[[ "${APP_ENV:-}" == "production" ]] || { printf 'FAIL APP_ENV                  must be production\n'; failures=$((failures + 1)); }
[[ "${APP_DEBUG:-}" == "false" || "${APP_DEBUG:-}" == "0" ]] || { printf 'FAIL APP_DEBUG                must be false\n'; failures=$((failures + 1)); }
[[ "${DB_CONNECTION:-}" != "sqlite" && -n "${DB_CONNECTION:-}" ]] || { printf 'FAIL DB_CONNECTION            must be a managed non-sqlite database\n'; failures=$((failures + 1)); }
[[ "${QUEUE_CONNECTION:-}" != "sync" && -n "${QUEUE_CONNECTION:-}" ]] || { printf 'FAIL QUEUE_CONNECTION         must use a persistent queue\n'; failures=$((failures + 1)); }
[[ "${APP_KEY:-}" != "" && "${APP_KEY:-}" != "base64:SomeRandomString" ]] || { printf 'FAIL APP_KEY                  missing or placeholder\n'; failures=$((failures + 1)); }

check APP_URL "${APP_URL:-}"
check CORS_ALLOWED_ORIGINS "${CORS_ALLOWED_ORIGINS:-}"
check SESSION_SECURE_COOKIE "${SESSION_SECURE_COOKIE:-}"
check SENTRY_LARAVEL_DSN "${SENTRY_LARAVEL_DSN:-}"
check METRICS_TOKEN "${METRICS_TOKEN:-}"

if (( failures > 0 )); then
  printf '\nProduction preflight failed: %d check(s). No deployment should proceed.\n' "$failures" >&2
  exit 1
fi
printf '\nProduction preflight passed. Continue with backup, migrations, workers, scheduler, and smoke tests.\n'
