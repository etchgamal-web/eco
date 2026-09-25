#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${1:-${STAGING_BASE_URL:-}}"
if [[ -z "$BASE_URL" ]]; then
  echo "Usage: $0 https://staging-api.example.com" >&2
  exit 2
fi

BASE_URL="${BASE_URL%/}"
request() {
  local path="$1"
  local expected="$2"
  local status
  status="$(curl --silent --show-error --output /tmp/smoke-response.json --write-out '%{http_code}' \
    --header 'Accept: application/json' --max-time 20 "$BASE_URL$path")"
  if [[ "$status" != "$expected" ]]; then
    echo "Smoke test failed: $path returned HTTP $status (expected $expected)" >&2
    cat /tmp/smoke-response.json >&2 || true
    exit 1
  fi
  echo "OK $status $path"
}

request '/up' 200
request '/ready' 200
request '/api/v1/products' 200
