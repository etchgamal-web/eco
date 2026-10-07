# Production deployment runbook

هذا المجلد يصف الحد الأدنى لتشغيل Laravel في production. يجب وضع secrets في secret manager أو environment provider، وليس في Git.

للتثبيت الأولي على Ubuntu أو Debian استخدم السكربت الموحد من جذر المستودع:

```bash
chmod +x install.sh
./install.sh
```

الدليل الكامل بالعربية موجود في `docs/INSTALLATION_AR.md`.

## Release sequence

1. أنشئ release directory جديدًا، ثم ثبّت dependencies باستخدام `composer install --no-dev --prefer-dist --optimize-autoloader`.
2. اربط ملف `.env` الإنتاجي، وتأكد من أن `APP_DEBUG=false` و`APP_ENV=production` وأن قاعدة البيانات managed وليست SQLite.
3. شغّل `php artisan migrate --force` بعد أخذ backup والتحقق من خطة migration.
4. شغّل `php artisan storage:link` عند استخدام local public storage.
5. شغّل `php artisan optimize`، ثم أعد تشغيل workers باستخدام `php artisan queue:restart`.
6. فعّل Supervisor من `supervisor/ecommerce-worker.conf` بعملية أو أكثر حسب حجم الحمل.
7. ثبّت `ecommerce-scheduler.cron` في crontab لمستخدم التطبيق.
8. نفّذ smoke tests على `/up` و`/ready` و`/api/v1/products` وعمليات authentication، ثم تحقق من queue وwebhook logs.
9. احتفظ بالrelease السابق حتى ينجح smoke test، ولا تحذف آخر release قابل للرجوع.

## Automated deployment

يوجد workflow يدوي في `.github/workflows/deploy.yml` ويستخدم GitHub Environment باسم `staging` أو `production`. لا يعمل workflow إلا بعد ضبط secrets التالية داخل البيئة المطلوبة:

- `DEPLOY_SSH_HOST`
- `DEPLOY_SSH_PORT`
- `DEPLOY_SSH_KNOWN_HOSTS`
- `DEPLOY_SSH_USER`
- `DEPLOY_SSH_PRIVATE_KEY`
- `DEPLOY_APP_ROOT`
- `DEPLOY_HEALTH_URL`

يستخدم الـworkflow `release.sh` لإنشاء release منفصل، تثبيت dependencies، تشغيل migrations، بناء config cache، إعادة تشغيل workers، ثم فحص `/ready`. عند فشل الفحص لا ينبغي تحويل traffic إلى الإصدار الجديد. خيار `rollback` يعيد symlink إلى آخر release سابق ويعيد تشغيل queue workers.

شغّل النشر من GitHub Actions فقط بعد تفعيل environment protection والمراجعة المطلوبة للإنتاج.

## Queue and scheduler checks

يجب مراقبة `failed_jobs`، وعمر أقدم outbox event، ونجاح `cart:mark-abandoned` و`outbox:dispatch` و`payments:reconcile` و`shipments:reconcile`. عند تغيير الكود، نفّذ `php artisan queue:restart` بعد نشر الملفات.

## Backup

استخدم الأمر الموحّد `php artisan backup:database`؛ يختار تلقائيًا سكربت `sqlite` أو `mysql` أو `pgsql` حسب `DB_CONNECTION`. يجب أن يكون `BACKUP_DIR` على storage منفصل ومقيد الوصول، مع تشفير storage والتحقق من ملف `.sha256` وتجربة restore دورية في بيئة معزولة. مثال PostgreSQL:

```bash
BACKUP_DIR=/secure/backups/ecommerce \
DB_HOST=db.internal \
DB_PORT=5432 \
DB_DATABASE=ecommerce \
DB_USERNAME=ecommerce_backup \
DB_PASSWORD='provided-by-secret-manager' \
php artisan backup:database --force
```

لا يعتبر إنشاء backup ناجحًا دليلًا على قابلية الاستعادة. معيار الإغلاق هو restore test ناجح مع قياس RPO وRTO وتوثيق النتيجة.

## Security checklist

يجب إنهاء TLS عند reverse proxy موثوق، وتقييد `CORS_ALLOWED_ORIGINS` إلى origins الفعلية، وحماية مفاتيح providers، وتفعيل secure cookies، ومنع عرض logs أو debug traces للمستخدم، وتدوير webhook credentials عند الاشتباه في تسريبها.

## Rollback and incident response

عند فشل release، أوقف استقبال traffic أو فعّل maintenance mode، احتفظ بالـlogs و`X-Correlation-Id`، أعد توجيه traffic إلى آخر release سليم، ولا تعمل `migrate:rollback` تلقائيًا على production إلا بعد مراجعة أثر migration. استخدم forward-fix عندما تكون migration قد غيّرت بيانات لا يمكن عكسها.

للتراجع الآلي من الخادم:

```bash
APP_ROOT=/var/www/ecommerce-platform \
  ROLLBACK=1 bash "$APP_ROOT/current/deploy/release.sh"
```

## Health expectations

`/up` هو liveness check أساسي، أما `/ready` فيفحص database وcache وstorage وqueue ويعيد `503` عند عدم الجاهزية. يجب أن يراقب مشغل البنية التحتية `/ready` قبل توجيه traffic، إضافة إلى `/metrics` المحمي و`5xx` وqueue failures وpayment/webhook failures.

## Automated preflight

قبل أي release production، شغّل الفحص من نفس environment provider الذي سيشغّل التطبيق. السكربت لا يطبع الأسرار، ويفشل إذا كانت البيئة غير آمنة:

```bash
./scripts/preflight_production.sh
```

وللواجهة:

```bash
cd ../admin-dashboard
VITE_API_URL=https://admin.example.com/api npm run verify:production
```
