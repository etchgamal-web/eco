#!/usr/bin/env bash
set -Eeuo pipefail

: "${BACKUP_DIR:?BACKUP_DIR must point to encrypted or access-controlled backup storage}"
: "${DB_HOST:?DB_HOST is required}"
: "${DB_PORT:=3306}"
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
output="$BACKUP_DIR/${DB_DATABASE}_${timestamp}.sql.gz"

MYSQL_PWD="${DB_PASSWORD:-}" mysqldump \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USERNAME" \
  "$DB_DATABASE" | gzip -c > "$output"

sha256sum "$output" > "$output.sha256"
find "$BACKUP_DIR" -type f -name '*.sql.gz' -mtime +"$BACKUP_RETENTION_DAYS" -delete
find "$BACKUP_DIR" -type f -name '*.sha256' -mtime +"$BACKUP_RETENTION_DAYS" -delete
printf 'Created MySQL backup: %s\n' "$output"
