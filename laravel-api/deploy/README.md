# Production deployment runbook

هذا المجلد يصف الحد الأدنى لتشغيل Laravel في production. يجب وضع secrets في secret manager أو environment provider، وليس في Git.

## Release sequence

1. أنشئ release directory جديدًا، ثم ثبّت dependencies باستخدام `composer install --no-dev --prefer-dist --optimize-autoloader`.
2. اربط ملف `.env` الإنتاجي، وتأكد من أن `APP_DEBUG=false` و`APP_ENV=production` وأن قاعدة البيانات managed وليست SQLite.
3. شغّل `php artisan migrate --force` بعد أخذ backup والتحقق من خطة migration.
4. شغّل `php artisan storage:link` عند استخدام local public storage.
5. شغّل `php artisan optimize`، ثم أعد تشغيل workers باستخدام `php artisan queue:restart`.
6. فعّل Supervisor من `supervisor/ecommerce-worker.conf` بعملية أو أكثر حسب حجم الحمل.
7. ثبّت `ecommerce-scheduler.cron` في crontab لمستخدم التطبيق.
8. نفّذ smoke tests على `/up` و`/api/v1/products` وعمليات authentication، ثم تحقق من queue وwebhook logs.
9. احتفظ بالrelease السابق حتى ينجح smoke test، ولا تحذف آخر release قابل للرجوع.

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

## Health expectations

`/up` هو liveness check أساسي، أما `/ready` فيفحص database وcache وstorage ويعيد `503` عند عدم الجاهزية. يجب أن يراقب مشغل البنية التحتية `/ready` قبل توجيه traffic، إضافة إلى `5xx` وqueue failures وpayment/webhook failures.
