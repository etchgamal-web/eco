#!/usr/bin/env bash
set -Eeuo pipefail

if [[ "$#" -eq 0 ]]; then
  echo "Usage: $0 path/to/new-migration.php [...]" >&2
  exit 2
fi

failed=0
for file in "$@"; do
  [[ -f "$file" ]] || { echo "Migration not found: $file" >&2; failed=1; continue; }
  case "$file" in
    *.php) ;;
    *) continue ;;
  esac

  if grep -nE 'drop(Column|Columns|Table|IfExists)|rename(Column|Index)|->change\(|nullable\(false\).*->change\(' "$file"; then
    echo "Unsafe migration pattern detected in $file" >&2
    echo 'Use expand -> migrate/backfill -> contract in separate releases; do not deploy this migration with an automatic code rollback.' >&2
    failed=1
  fi
done

if [[ "$failed" -ne 0 ]]; then
  exit 1
fi

echo 'Migration safety check passed: no destructive schema operation was found.'
