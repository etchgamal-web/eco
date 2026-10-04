# قائمة إصدار Eco — Laravel API ولوحة التحكم

**تاريخ المراجعة:** 4 أكتوبر 2026  
**النطاق:** `laravel-api` و`admin-dashboard` فقط

## القرار الحالي

**الحالة: Staging-ready / Not production-ready.** الكود والاختبارات جاهزة للمراجعة، لكن لا يجوز اعتبار Sandbox أو ملفات `.env` المحلية بيئة إنتاج.

## نتائج التحقق البرمجي

- [x] `APP_ENV=testing php artisan test` — **295 اختبارًا ناجحًا و10,551 assertion**.
- [x] اختبارات Layer/Strict Architecture ناجحة.
- [x] `npm run verify` في لوحة التحكم ناجح.
- [x] `git diff --check` ناجح.
- [x] `docs/openapi.json` صالح بصيغة JSON.
- [x] مسارات الإدارة الجديدة مطابقة للتوثيق.
- [x] فحص Composer dependencies وPHP syntax مكتملان ضمن المراجعات السابقة.
- [x] إضافة فحص آلي لإعدادات Laravel الإنتاجية دون طباعة الأسرار.
- [x] إضافة فحص آلي لعنوان API الإنتاجي في لوحة التحكم.

## موانع الإنتاج الحالية

| البند | الحالة الحالية | الإجراء المطلوب قبل الإنتاج |
|---|---|---|
| `APP_ENV` | البيئة المحلية تحتوي `local`، وshell الحالي يفرض `PROD` | إنشاء environment production صريح خارج Git |
| `APP_DEBUG` | مفعّل محليًا | ضبط `APP_DEBUG=false` والتحقق بعد `config:cache` |
| قاعدة البيانات | SQLite محلي | PostgreSQL/MySQL مُدار مع TLS وbackup |
| Queue | database queue | تشغيل worker دائم عبر Supervisor أو Horizon |
| Scheduler | يحتاج cron خارجي | تثبيت `php artisan schedule:run` كل دقيقة |
| Secrets | قيم فعلية خارج المستند | نقلها إلى secret manager وتدويرها قبل الإطلاق |
| CORS | يعتمد على origin النشر | ضبط `CORS_ALLOWED_ORIGINS` على domain لوحة التحكم فقط |
| HTTPS | غير مثبت محليًا | TLS وtrusted proxies وsecure cookies |
| Backup/Restore | runbook موجود، اختبار خارجي مطلوب | تنفيذ backup واستعادة فعلية على staging |
| Providers | اختبارات محلية فقط | اختبار sandbox للدفع والشحن والـwebhooks |
| Monitoring | يحتاج ربطًا خارجيًا | ربط Sentry/Prometheus والتنبيه التجريبي |

## أوامر التحقق قبل النشر

```bash
# API
cd laravel-api
./scripts/preflight_production.sh
APP_ENV=testing php artisan test
php artisan route:list --path=api/v1
php artisan config:cache
php artisan migrate --force
php artisan queue:restart

# Dashboard
cd ../admin-dashboard
npm ci
npm run verify
VITE_API_URL=https://admin.example.com/api npm run verify:production
```

لا تُشغّل `config:cache` أو `migrate --force` على الإنتاج قبل أخذ backup ومراجعة القيم السرية.

## Smoke test بعد النشر

1. `GET /up` للتحقق من liveness.
2. فحص readiness الداخلي وقاعدة البيانات والـcache والـqueue.
3. تسجيل دخول حساب staff تجريبي محدود الصلاحيات.
4. فتح لوحة الإدارة والتحقق من `GET /api/v1/roles` و`GET /api/v1/staff`.
5. فتح سجل تدقيق موظف ودور مع pagination والفلاتر.
6. تنفيذ تغيير RBAC على دور تجريبي والتحقق من ظهور audit event.
7. تجربة webhook sandbox بتوقيع صحيح وتوقيع غير صحيح.
8. مراقبة logs وmetrics وfailed jobs لمدة لا تقل عن 15 دقيقة.

## Rollback

- الاحتفاظ بآخر release artifact صالح.
- عدم حذف migrations أو تعديل بيانات يدويًا أثناء rollback.
- استخدام forward-fix عند migration غير قابلة للعكس.
- إعادة worker إلى artifact السابق ثم `php artisan queue:restart`.
- توثيق وقت الإصدار، الإصدار السابق، سبب الرجوع، ونتيجة smoke test.

## مراجع

- [توثيق عقود API](laravel-api/docs/API_ADMIN_CONTRACTS_AR.md)
- [OpenAPI](laravel-api/docs/openapi.json)
- [تقرير الجاهزية الإنتاجية](laravel-api/docs/PRODUCTION_READINESS_AR.md)
- [خارطة التحسينات](API_ADMIN_IMPROVEMENT_ROADMAP.md)
