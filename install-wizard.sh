#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALLER="$ROOT_DIR/install.sh"

ask_yes_no() {
    local prompt="$1"
    local default="$2"
    local answer
    while true; do
        if [[ "$default" == "yes" ]]; then
            read -r -p "$prompt [Y/n]: " answer
            answer="${answer:-Y}"
        else
            read -r -p "$prompt [y/N]: " answer
            answer="${answer:-N}"
        fi
        case "${answer,,}" in
            y|yes) return 0 ;;
            n|no) return 1 ;;
            *) echo "اكتب y أو n من فضلك." ;;
        esac
    done
}

clear 2>/dev/null || true
cat <<'EOF'
============================================
 Ecommerce API - Installation Wizard
============================================
هذا المعالج سيفحص البيئة أولاً، ثم يثبت الناقص فقط.
لن يعيد إنشاء APP_KEY أو يستبدل ملف .env الموجود.
EOF

read -r -p "مسار Laravel API [/var/www/eco/laravel-api]: " APP_DIR
APP_DIR="${APP_DIR:-$ROOT_DIR/laravel-api}"

if [[ ! -d "$APP_DIR" ]]; then
    echo "المسار غير موجود: $APP_DIR" >&2
    exit 1
fi

INSTALL_REDIS="false"
RUN_MIGRATIONS="false"
RUN_SEEDERS="false"
INSTALL_DEV="false"

if ask_yes_no "هل تريد تثبيت وتشغيل Redis؟" yes; then INSTALL_REDIS="true"; fi
if ask_yes_no "هل تريد تشغيل database migrations الآن؟" yes; then RUN_MIGRATIONS="true"; fi
if ask_yes_no "هل تريد تشغيل seeders الخاصة بالصلاحيات والإعدادات؟" yes; then RUN_SEEDERS="true"; fi
if ask_yes_no "هل تريد تثبيت Composer development dependencies؟" no; then INSTALL_DEV="true"; fi

cat <<EOF

ملخص التثبيت:
- المسار: $APP_DIR
- Redis: $INSTALL_REDIS
- Migrations: $RUN_MIGRATIONS
- Seeders: $RUN_SEEDERS
- Development dependencies: $INSTALL_DEV
EOF

ask_yes_no "هل تريد بدء التثبيت؟" yes || { echo "تم الإلغاء."; exit 0; }

APP_DIR="$APP_DIR" \
INSTALL_REDIS="$INSTALL_REDIS" \
RUN_MIGRATIONS="$RUN_MIGRATIONS" \
RUN_SEEDERS="$RUN_SEEDERS" \
INSTALL_DEV="$INSTALL_DEV" \
"$INSTALLER"
