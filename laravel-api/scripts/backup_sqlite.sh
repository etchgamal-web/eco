#!/usr/bin/env bash
set -Eeuo pipefail

: "${BACKUP_DIR:?BACKUP_DIR must point to encrypted or access-controlled backup storage}"
: "${DB_DATABASE:?DB_DATABASE must point to the SQLite database file}"
: "${BACKUP_RETENTION_DAYS:=14}"

if ! [[ "$BACKUP_RETENTION_DAYS" =~ ^[1-9][0-9]*$ ]]; then
  printf 'BACKUP_RETENTION_DAYS must be a positive integer\n' >&2
  exit 1
fi
if [ ! -f "$DB_DATABASE" ]; then
  printf 'SQLite database file does not exist: %s\n' "$DB_DATABASE" >&2
  exit 1
fi

umask 077
mkdir -p "$BACKUP_DIR"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
output="$BACKUP_DIR/$(basename "$DB_DATABASE")_${timestamp}.sqlite"

if command -v sqlite3 >/dev/null 2>&1; then
  sqlite3 "$DB_DATABASE" ".backup '$output'"
else
  cp --reflink=auto "$DB_DATABASE" "$output"
fi

sha256sum "$output" > "$output.sha256"
find "$BACKUP_DIR" -type f -name '*.sqlite' -mtime +"$BACKUP_RETENTION_DAYS" -delete
find "$BACKUP_DIR" -type f -name '*.sha256' -mtime +"$BACKUP_RETENTION_DAYS" -delete
printf 'Created SQLite backup: %s\n' "$output"
