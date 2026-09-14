#!/usr/bin/env bash
set -Eeuo pipefail

: "${BACKUP_DIR:?BACKUP_DIR must point to encrypted or access-controlled backup storage}"
: "${DB_HOST:?DB_HOST is required}"
: "${DB_PORT:=5432}"
: "${DB_DATABASE:?DB_DATABASE is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"
: "${BACKUP_RETENTION_DAYS:=14}"

if ! [[ "$BACKUP_RETENTION_DAYS" =~ ^[1-9][0-9]*$ ]]; then
  printf 'BACKUP_RETENTION_DAYS must be a positive integer\n' >&2
  exit 1
fi

umask 077
mkdir -p "$BACKUP_DIR"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
output="$BACKUP_DIR/${DB_DATABASE}_${timestamp}.dump"

PGPASSWORD="${DB_PASSWORD:-}" pg_dump \
  --format=custom \
  --no-owner \
  --no-privileges \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --username="$DB_USERNAME" \
  "$DB_DATABASE" > "$output"

sha256sum "$output" > "$output.sha256"
find "$BACKUP_DIR" -type f -name '*.dump' -mtime +"$BACKUP_RETENTION_DAYS" -delete
find "$BACKUP_DIR" -type f -name '*.sha256' -mtime +"$BACKUP_RETENTION_DAYS" -delete
printf 'Created backup: %s\n' "$output"
