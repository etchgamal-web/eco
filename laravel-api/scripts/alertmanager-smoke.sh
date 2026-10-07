#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${1:-${ALERTMANAGER_URL:-}}"
if [[ -z "$BASE_URL" ]]; then
  echo "Usage: $0 https://alertmanager.example.com" >&2
  exit 2
fi

BASE_URL="${BASE_URL%/}"
ALERT_NAME="${ALERT_NAME:-EcommerceAlertmanagerSmokeTest}"
starts_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
ends_at="$(date -u -d '+2 minutes' +%Y-%m-%dT%H:%M:%SZ)"

payload="$(cat <<JSON
[{"labels":{"alertname":"$ALERT_NAME","severity":"warning","job":"ecommerce-api","instance":"smoke-test"},"annotations":{"summary":"Ecommerce Alertmanager smoke test","description":"Verify that the configured receiver delivered this test notification."},"startsAt":"$starts_at","endsAt":"$ends_at","generatorURL":"https://github.com/etchgamal-web/eco/actions"}]
JSON
)"

status="$(curl --fail --silent --show-error --output /tmp/alertmanager-smoke-response.json --write-out '%{http_code}' \
  --header 'Content-Type: application/json' --max-time 20 \
  --data "$payload" "$BASE_URL/api/v1/alerts")"

[[ "$status" == "200" ]] || { cat /tmp/alertmanager-smoke-response.json >&2 || true; exit 1; }
echo "Alertmanager accepted $ALERT_NAME (HTTP $status). Verify delivery in the configured receiver and delete/expire the test alert afterward."
