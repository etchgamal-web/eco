# تثبيت بيئة Ecommerce API

يوجد سكربت تثبيت موحد في جذر المشروع:

```bash
chmod +x install.sh
./install.sh
```

يوجد أيضًا معالج تفاعلي خطوة بخطوة:

```bash
chmod +x install-wizard.sh
./install-wizard.sh
```

المعالج يسأل عن مسار التطبيق، Redis، migrations، seeders، وdevelopment dependencies قبل بدء التثبيت.

السكربت مصمم ليكون **Idempotent**؛ يمكن تشغيله أكثر من مرة دون إعادة إنشاء المفتاح إذا كان موجودًا أو حذف ملف `.env` الحالي.

## فحص البيئة قبل التثبيت

السكربت يفحص أولًا PHP وComposer والـextensions الموجودة. إذا كان PHP إصدار `8.3` أو أحدث وComposer موجودين، فلن يعيد تثبيتهما. كما أنه يثبت فقط الـextensions أو الأدوات الناقصة.

للفحص بدون أي تعديل:

```bash
./install.sh --check-only
```

إذا كان إصدار PHP أقل من 8.3، سيتوقف السكربت برسالة واضحة بدل استبدال PHP الموجود تلقائيًا.

## ما الذي يثبته؟

- PHP 8.3 أو أحدث.
- Composer.
- PHP extensions المطلوبة:
  - `mbstring`
  - `xml`
  - `curl`
  - `sqlite3`
  - `zip`
  - `bcmath`
  - `intl`
- Git وCurl وUnzip.
- Redis Server اختياريًا ومفعّل افتراضيًا.
- Composer dependencies.
- Laravel application key إذا لم يكن موجودًا.
- Storage link ومجلدات Laravel runtime.
- Database migrations.
- Seeders الخاصة بـRBAC وSettings.
- Laravel optimization/cache.

## المتطلبات

- Ubuntu أو Debian.
- مستخدم لديه `sudo`.
- PHP 8.3 أو أحدث متاح من مستودعات النظام.
- إعداد قاعدة البيانات قبل تشغيل migrations.

## إعدادات التشغيل

يمكن التحكم في سلوك السكربت عبر متغيرات البيئة:

```bash
INSTALL_REDIS=true \
RUN_MIGRATIONS=true \
RUN_SEEDERS=true \
INSTALL_DEV=false \
./install.sh
```

### تثبيت dependencies الخاصة بالتطوير

```bash
INSTALL_DEV=true ./install.sh
```

### تثبيت البيئة بدون تشغيل migrations أو seeders

```bash
RUN_MIGRATIONS=false RUN_SEEDERS=false ./install.sh
```

هذا مفيد عندما تريد مراجعة `.env` أو تنفيذ migrations بعد أخذ Backup.

### تحديد مسار مختلف للتطبيق

```bash
APP_DIR=/var/www/eco/laravel-api ./install.sh
```

### تحديد مستخدم التطبيق

```bash
APP_USER=www-data ./install.sh
```

### استخدام الخيارات المباشرة

```bash
./install.sh --help
./install.sh --no-redis
./install.sh --no-migrations --no-seeders
./install.sh --dev
./install.sh --app-dir=/var/www/eco/laravel-api
```

## الترتيب الصحيح على Production

قبل التشغيل:

1. انسخ `.env.example` إلى `.env` أو اترك السكربت ينشئه.
2. اضبط:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
APP_KEY=base64:...
DB_CONNECTION=pgsql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

3. اضبط CORS على frontend الحقيقي فقط.
4. أضف مفاتيح Sentry وPrometheus:

```env
SENTRY_LARAVEL_DSN=https://...
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=git-sha
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_SEND_DEFAULT_PII=false

METRICS_ENABLED=true
METRICS_TOKEN=secret-long-random-value
METRICS_REDIS_CONNECTION=default
```

5. خذ Backup قبل migrations:

```bash
php artisan backup:database --force
```

6. شغل التثبيت:

```bash
RUN_MIGRATIONS=true RUN_SEEDERS=true ./install.sh
```

## بعد التثبيت

### اختبار التطبيق

```bash
php artisan about
php artisan route:list --path=api/v1
php artisan test --no-coverage
```

### تشغيل الـQueue

استخدم Supervisor الموجود في:

```text
deploy/supervisor/ecommerce-worker.conf
```

### تشغيل Scheduler

استخدم:

```text
deploy/ecommerce-scheduler.cron
```

### Smoke test

```bash
bash laravel-api/scripts/smoke-test.sh https://api.example.com
```

### اختبار Readiness

```bash
curl -i https://api.example.com/up
curl -i https://api.example.com/ready
```

### اختبار Metrics

```bash
curl -i \
  -H "Authorization: Bearer $METRICS_TOKEN" \
  https://api.example.com/metrics
```

لا تفتح `/metrics` للعامة ولا تضع `METRICS_TOKEN` في Git أو داخل Workflow logs.

## ملاحظات أمنية

- السكربت لا يكتب secrets داخل Git.
- لا يستبدل `.env` الموجود.
- لا يعيد توليد `APP_KEY` إذا كانت موجودة.
- لا يغير `APP_DEBUG` تلقائيًا؛ يجب مراجعته يدويًا قبل Production.
- لا يقوم بتثبيت Nginx أو إعداد TLS تلقائيًا لأن ذلك يعتمد على بنية السيرفر.
- لا ينفذ Restore تلقائيًا؛ Restore يجب أن يتم في قاعدة بيانات معزولة.
- لا تستخدم SQLite للإنتاج الحقيقي إذا كان المتجر يحتاج Queue أو traffic متعدد العمال.

## استكشاف الأخطاء

### فشل PHP version

تأكد من أن إصدار PHP:

```bash
php -v
```

هو 8.3 أو أحدث.

### فشل الاتصال بقاعدة البيانات

راجع `.env` ثم نفذ:

```bash
php artisan config:clear
php artisan migrate:status
```

### فشل Redis

تحقق من الخدمة:

```bash
sudo systemctl status redis-server
redis-cli ping
```

والنتيجة الصحيحة:

```text
PONG
```

### فشل الصلاحيات

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```
