#!/usr/bin/env bash
set -Eeuo pipefail

BACKUP_FILE="${1:-${BACKUP_FILE:-}}"
if [[ -z "$BACKUP_FILE" || ! -f "$BACKUP_FILE" ]]; then
  echo "Usage: $0 /secure/backups/database.dump" >&2
  exit 2
fi

if [[ -f "$BACKUP_FILE.sha256" ]]; then
  (cd "$(dirname "$BACKUP_FILE")" && sha256sum --check "$(basename "$BACKUP_FILE.sha256")")
else
  echo "Warning: checksum file is missing; integrity verification is incomplete" >&2
  exit 1
fi

case "$BACKUP_FILE" in
  *.sql.gz) gzip --test "$BACKUP_FILE" ;;
  *.dump) command -v pg_restore >/dev/null && pg_restore --list "$BACKUP_FILE" >/dev/null ;;
  *.sqlite) sqlite3 "$BACKUP_FILE" 'PRAGMA integrity_check;' | grep -qx 'ok' ;;
  *) echo "Unsupported backup extension: $BACKUP_FILE" >&2; exit 1 ;;
esac

echo "Backup integrity preflight passed: $BACKUP_FILE"
echo "A full restore into an isolated database is still required before production approval."
