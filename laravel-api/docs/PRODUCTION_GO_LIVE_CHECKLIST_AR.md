# قائمة تجهيز Backend للإنتاج — Ecommerce API

**آخر تدقيق:** 2026-09-25  
**آخر Commit تم التحقق منه:** `c693733`  
**الحالة الحالية:** جاهز وظيفيًا للـ Staging، مع إضافة readiness health واختبار smoke/preflight، وليس جاهزًا بعد لاستقبال طلبات ومدفوعات حقيقية قبل إغلاق بوابات التشغيل الخارجية أدناه.

## 1. النتيجة المختصرة

الـ API نفسه يعمل، ونظام الوحدات والصلاحيات والاختبارات جاهز بدرجة جيدة. النواقص المتبقية أغلبها **تشغيلية وأمنية خارج الكود** وليست نقصًا في CRUD أو منطق الأعمال.

| المجال | الحالة الحالية | الحكم |
|---|---|---|
| API v1 | 192 مسارًا مسجلًا | جاهز للـ Staging |
| Bearer Token | Laravel Sanctum يعمل مع login/register/logout | جاهز |
| الصلاحيات وRBAC | مغطاة بالاختبارات | جاهز وظيفيًا |
| الاختبارات | 246 اختبارًا و9671 assertion ناجحة | جيد |
| Composer security | لا توجد advisories | جيد |
| OpenAPI | JSON صالح واختبارات العقد ناجحة | جاهز |
| `/up` | Liveness أساسي من Laravel | موجود |
| `/ready` | يفحص Database وCache وStorage وQueue | موجود ومغطى بالاختبار |
| `/metrics` | Prometheus metrics محمية بـBearer Token | موجود، يحتاج Redis وPrometheus خارجي |
| Queue worker | ملف Supervisor موجود، لكنه غير مثبت على سيرفر | يحتاج تنفيذًا خارجيًا |
| Scheduler | ملف Cron موجود، لكنه غير مثبت على سيرفر | يحتاج تنفيذًا خارجيًا |
| Backup | أمر وسكربتات موجودة | يحتاج تخزينًا واختبار Restore حقيقي |
| Monitoring/Alerting | Sentry وPrometheus endpoint وAlert rules مضافة؛ الربط الخارجي غير مضبوط | مطلوب إعداد DSN وPrometheus/Grafana/Alertmanager |
| Staging deployment | غير موجود كـ workflow فعلي | مطلوب |
| Production deployment | لا يوجد Pipeline نشر آلي | مطلوب أو ينفذ يدويًا موثقًا |

---

# 2. ما هو موجود حاليًا في المستودع

## ملفات التشغيل الموجودة

| الملف | وظيفته | هل يكفي وحده؟ |
|---|---|---|
| `routes/web.php` | `/ready` لفحص DB/Cache/Storage | لا، يحتاج مراقبة وتشغيل فعلي |
| `deploy/README.md` | Runbook للنشر والنسخ الاحتياطي والرجوع | لا، هو تعليمات فقط |
| `deploy/supervisor/ecommerce-worker.conf` | تشغيل Queue Worker | يجب تثبيته على السيرفر وتعديل المسارات |
| `deploy/ecommerce-scheduler.cron` | تشغيل Scheduler كل دقيقة | يجب تثبيته في crontab |
| `config/sanctum.php` | Bearer Token ومدة الصلاحية | موجود |
| `database/migrations/*personal_access_tokens*` | تخزين Tokens | موجود ويجب تشغيل migration |
| `.env.example` | قالب إعدادات التطوير | ليس ملف Production |
| `.github/workflows/backend.yml` | اختبارات وLint وAudit | لا ينفذ نشرًا ولا Staging smoke test |
| `docs/API_V1_GUIDE.md` | دليل استخدام API وBearer Token | موجود |
| `docs/openapi.json` | العقد البرمجي للواجهة | موجود |

## التحقق الذي تم بنجاح

```text
246 tests passed
9671 assertions
Pint: PASS — 770 files
composer validate: PASS
composer audit: No security vulnerability advisories found
OpenAPI JSON: valid
personal_access_tokens migration: applied locally
Working tree: clean
```

---

# 3. النواقص البرمجية الحالية

## P0 — يجب تنفيذها قبل استقبال المستخدمين الحقيقيين

### 3.1 readiness أقوى

المسار الحالي `/ready` يفحص:

- Database
- Cache
- Storage writable

لكنه لا يفحص:

- Queue backend
- وجود `jobs` و`failed_jobs`
- اتصال Redis إذا تم اختياره
- صحة مساحة التخزين والحد الأدنى المتاح
- جاهزية مزودي الدفع والشحن بطريقة آمنة
- backlog للـOutbox

**المطلوب:** إضافة فحوصات داخلية آمنة، بحيث لا تعرض secrets أو تفاصيل الاتصال، وتعيد `503` عند فشل dependency حرجة.

**الملف الحالي:**

```text
laravel-api/routes/web.php
```

**التعديل المقترح:** نقل منطق الفحص إلى Service/Use Case بدل وضعه بالكامل في Route Closure، مثل:

```text
app/Modules/Shared/Application/Health/ReadinessChecker.php
app/Modules/Shared/Domain/Contracts/HealthCheckInterface.php
app/Modules/Shared/Infrastructure/Health/DatabaseHealthCheck.php
app/Modules/Shared/Infrastructure/Health/CacheHealthCheck.php
app/Modules/Shared/Infrastructure/Health/QueueHealthCheck.php
```

هذا التحسين ليس ضروريًا لتجربة الـStaging، لكنه مطلوب لجعل `/ready` موثوقًا في Load Balancer.

### 3.2 Monitoring وAlerting

تمت إضافة طبقة Monitoring داخلية تشمل Sentry وPrometheus-compatible metrics وAlert rules. ما يحتاج إعدادًا خارجيًا هو:

- Sentry أو APM
- Prometheus أو Metrics backend
- Centralized logs
- تنبيه عند `5xx`
- تنبيه عند فشل Queue jobs
- تنبيه عند فشل Payment/Webhook
- تنبيه عند تراكم Outbox

**المطلوب تشغيليًا:** ربط الخدمات التالية في بيئة الإنتاج:

```text
Sentry + CloudWatch/Grafana/Better Stack
```

ثم إضافة:

```text
config/services.php
app/Support/Observability/...
```

مع التأكد من عدم تسجيل:

- Bearer Tokens
- APP_KEY
- مفاتيح الدفع
- كلمات المرور
- بيانات البطاقات
- Payloads حساسة دون masking

### 3.3 Smoke Tests بعد النشر

الـCI الحالي يشغل الاختبارات، لكنه لا ينشر إلى Staging ولا يشغل smoke test بعد النشر.

**الملفات المطلوبة المقترحة:**

```text
.github/workflows/staging.yml
scripts/smoke-test.sh
```

ويجب أن يفحص السكربت:

```text
GET /up                 => 200
GET /ready              => 200
GET /api/v1/products    => 200
POST /api/v1/auth/login => 200 أو 401 حسب بيانات الاختبار
```

ويجب عدم استخدام بيانات Production الحقيقية في smoke test.

### 3.4 اختبار Backup وRestore

يوجد أمر Backup وRunbook، لكن وجود الكود لا يثبت إمكانية الاستعادة.

**المطلوب:**

1. Backup إلى Storage منفصل.
2. تشفير النسخ الاحتياطية.
3. اختبار Restore دوري على قاعدة منفصلة.
4. تسجيل RPO وRTO.
5. تنبيه عند فشل Backup.

**الملفات الموجودة ذات الصلة:**

```text
app/Console/Commands/BackupDatabase.php
app/Console/Commands/VerifyBackup.php
docs/BACKUP_RUNBOOK_AR.md
deploy/README.md
```

**الملف المطلوب إضافته اختياريًا:**

```text
scripts/restore-smoke-test.sh
```

لكن تشغيله يحتاج سيرفر أو قاعدة بيانات منفصلة، ولا يمكن إغلاق هذه النقطة من داخل Git فقط.

---

## P1 — مطلوب قبل التوسع أو فتح النظام للعامة

### 3.5 Staging وDeployment Pipeline

الـCI الحالي يختبر فقط. لا يوجد:

- نشر Staging تلقائي
- Environment protection
- Secret manager integration
- Smoke test بعد النشر
- Rollback آلي أو موثق داخل Pipeline
- Release tag

**الملفات المقترحة:**

```text
.github/workflows/staging.yml
.github/workflows/production.yml
.github/workflows/release.yml
```

لا يجب أن يحتوي أي ملف Workflow على secrets مباشرة.

### 3.6 Proxy وHTTPS

يجب إعداد السيرفر أو Laravel ليعرف أنه يعمل خلف Reverse Proxy/Load Balancer.

**المطلوب:**

- HTTPS إجباريًا.
- `APP_URL` يبدأ بـ `https://`.
- Trusted Proxies مضبوطة حسب مزود الاستضافة.
- Secure Cookies مفعلة.
- HSTS من Reverse Proxy بعد الاختبار.

إذا كان الـWeb App على دومين مختلف، يجب ضبط:

```env
CORS_ALLOWED_ORIGINS=https://app.example.com
```

ولا يجب تركها فارغة أو `*` في الإنتاج.

### 3.7 Queue Worker

الملف الحالي:

```text
deploy/supervisor/ecommerce-worker.conf
```

يستخدم Redis:

```text
queue:work redis
```

لكن `.env.example` يستخدم:

```env
QUEUE_CONNECTION=database
```

**يجب اتخاذ قرار واضح:**

#### اختيار A — Database Queue

```env
QUEUE_CONNECTION=database
```

وتعديل Supervisor إلى:

```bash
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90 --max-time=3600
```

#### اختيار B — Redis Queue — مفضل للأحمال الأعلى

```env
QUEUE_CONNECTION=redis
REDIS_HOST=...
REDIS_PASSWORD=...
REDIS_PORT=6379
```

ثم إبقاء Supervisor على Redis.

لا يجوز أن يبقى الاختلاف بين `.env.example` وSupervisor دون قرار، لأن الـJobs قد تتراكم في Queue غير التي يراقبها Supervisor.

### 3.8 Scheduler

الملف موجود:

```text
deploy/ecommerce-scheduler.cron
```

لكن يجب تثبيته على السيرفر:

```bash
crontab -e
```

ويجب التأكد من تنفيذ:

- `cart:mark-abandoned`
- `outbox:dispatch`
- `payments:reconcile`
- `shipments:reconcile`
- `orders:detect-delays`
- `backup:database` عند تفعيله

### 3.9 Logging مركزي

الإعداد الحالي يكتب غالبًا إلى:

```text
storage/logs/laravel.log
```

في الإنتاج يفضل استخدام `stderr` أو قناة مركزية، مثل:

```env
LOG_CHANNEL=stderr
LOG_LEVEL=warning
```

أو قناة Daily مع تدوير مضبوط، مع إرسال الأخطاء إلى Sentry/Cloud logging.

### 3.10 Rate Limiting موزع

الـRate Limiter الحالي يعتمد على Cache. إذا كان هناك أكثر من Application instance، يجب استخدام Redis أو Cache مركزي حتى لا تختلف الحدود بين السيرفرات.

**المطلوب:**

```env
CACHE_STORE=redis
```

أو استخدام Redis managed مع التأكد من TLS والـcredentials.

---

# 4. إعدادات Production المطلوبة

يجب إنشاء ملف `.env` خاص بالإنتاج خارج Git أو باستخدام Secret Manager.

## إعدادات أساسية

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_ONCE_AND_STORE_SECURELY
APP_URL=https://api.example.com

LOG_CHANNEL=stderr
LOG_LEVEL=warning

AUTH_GUARD=sanctum
SANCTUM_TOKEN_EXPIRATION=43200
SANCTUM_TOKEN_PREFIX=eco_
SANCTUM_STATEFUL_DOMAINS=
```

## قاعدة البيانات

يفضل PostgreSQL أو MySQL مُدار:

```env
DB_CONNECTION=pgsql
DB_HOST=managed-db-host
DB_PORT=5432
DB_DATABASE=ecommerce
DB_USERNAME=ecommerce_app
DB_PASSWORD=FROM_SECRET_MANAGER
```

لا تستخدم SQLite للإنتاج إذا كان النظام سيستقبل طلبات متزامنة أو مدفوعات.

## الجلسات والـCookies

حتى مع استخدام Bearer Token، إعدادات الجلسة مطلوبة لبعض أجزاء Laravel وSanctum:

```env
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

إذا كان هناك SPA يعتمد على Cookies بدل Bearer Token، يجب تحديد `SANCTUM_STATEFUL_DOMAINS` وCORS وCSRF بما يناسب الدومين الفعلي. أما إذا كان التطبيق Bearer-only، فلا تعتمد على Session Cookies في الواجهة.

## CORS

```env
CORS_ALLOWED_ORIGINS=https://app.example.com
```

إذا كان هناك أكثر من origin:

```env
CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
```

## Queue وCache

خيار Redis:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_HOST=managed-redis-host
REDIS_PASSWORD=FROM_SECRET_MANAGER
REDIS_PORT=6379
```

أو خيار Database Queue، بشرط تعديل Supervisor ليستخدم `database` بدل `redis`.

## البريد

لا تستخدم:

```env
MAIL_MAILER=log
```

في الإنتاج. استخدم SMTP أو مزود Email حقيقي:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=FROM_SECRET_MANAGER
MAIL_PASSWORD=FROM_SECRET_MANAGER
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Ecommerce"
```

## مزودو الدفع والشحن

يجب وضع القيم من Secret Manager:

```env
PAYMOB_ENABLED=true
PAYMOB_SECRET_KEY=...
PAYMOB_PUBLIC_KEY=...
PAYMOB_HMAC_SECRET=...

KASHIER_ENABLED=true
KASHIER_SECRET_KEY=...
KASHIER_PAYMENT_API_KEY=...

BOSTA_ENABLED=true
BOSTA_API_KEY=...
```

يجب التأكد أن URLs كلها HTTPS ومطابقة لمسارات `/api/v1` في الإنتاج.

---

# 5. خطوات النشر الفعلية

## قبل النشر

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan storage:link
php artisan migrate --force
php artisan optimize
```

يجب أخذ Backup قبل `migrate --force`.

## بعد النشر

```bash
php artisan queue:restart
php artisan optimize:clear
php artisan optimize
curl -fsS https://api.example.com/up
curl -fsS https://api.example.com/ready
curl -fsS https://api.example.com/api/v1/products
```

ثم اختبار:

1. Login ناجح.
2. Token يصل في الاستجابة.
3. طلب محمي باستخدام `Authorization: Bearer ...`.
4. Permission denial يعيد `403`.
5. Token غير صالح يعيد `401`.
6. Logout يلغي Token الحالي.
7. Upload Excel يعمل بحدود الحجم والصلاحية.
8. Webhook signature verification تعمل.
9. Queue job يصل للـWorker.
10. Backup جديد ظهر في التخزين المنفصل.

## Rollback

يجب الاحتفاظ بآخر Release سليم وعدم حذفِه مباشرة. عند فشل النشر:

1. أوقف توجيه Traffic للإصدار الجديد.
2. راجع Correlation ID والـlogs.
3. أعد Traffic إلى آخر Release سليم.
4. لا تنفذ `migrate:rollback` تلقائيًا إذا كانت Migration غيّرت بيانات.
5. استخدم Forward Fix بعد مراجعة الأثر.

---

# 6. قائمة الملفات التي يُنصح بإضافتها

هذه الملفات ليست كلها ضرورية لإغلاق الـAPI، لكنها تجعل التشغيل الاحترافي أسهل:

```text
.github/workflows/staging.yml
.github/workflows/production.yml
scripts/smoke-test.sh
scripts/restore-smoke-test.sh
app/Modules/Shared/Application/Health/ReadinessChecker.php
app/Modules/Shared/Domain/Contracts/HealthCheckInterface.php
app/Modules/Shared/Infrastructure/Health/QueueHealthCheck.php
tests/Feature/ReadinessHealthTest.php
docs/INCIDENT_RESPONSE_AR.md
docs/OBSERVABILITY_RUNBOOK_AR.md
docs/RELEASE_RUNBOOK_AR.md
```

## الملفات التي ليست مطلوبة حاليًا

لا نحتاج إلى إعادة بناء الـModules أو إضافة Models جديدة من أجل الإنتاج. كذلك لا نحتاج إلى تغيير نظام Bearer Token الحالي؛ فهو يعمل ومغطى بالاختبارات.

---

# 7. قرار الإطلاق

## يمكن الإطلاق إلى Staging الآن إذا تم توفير:

- Database Staging.
- `.env` Staging.
- APP_KEY خاص بالـStaging.
- مزودات دفع Sandbox أو تعطيلها.
- Worker وScheduler للتجربة.
- Domain وHTTPS أو بيئة داخلية آمنة.

## لا يتم فتح Production الحقيقي قبل إغلاق هذه البنود

- `APP_DEBUG=false`.
- Database managed مع Backup.
- Restore test ناجح.
- Worker مثبت ويعمل.
- Scheduler مثبت ويعمل.
- CORS مضبوط على domains حقيقية.
- HTTPS وSecure Cookies مفعلة.
- Monitoring وAlerting فعالان.
- Payment/Webhook sandbox verification ناجحة.
- Smoke test بعد النشر ناجح.
- Rollback معروف ومجرب.

**الخلاصة:** لا يوجد نقص يمنع بناء الواجهة الأمامية الآن. طبقة Monitoring البرمجية مضافة، ويبقى تجهيز DSN وخدمات Prometheus/Grafana/Alertmanager والتنبيهات الفعلية على البنية التشغيلية قبل الإنتاج الحقيقي.