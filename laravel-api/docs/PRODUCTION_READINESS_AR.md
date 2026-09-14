# تقرير جاهزية المشروع للإنتاج

**تاريخ التدقيق:** 13 سبتمبر 2026

**آخر تحديث:** أضيفت بعد التدقيق حزمة production artifacts تشمل قالب environment آمن، سياسة CORS، إعداد Supervisor، cron للـscheduler، سكربت PostgreSQL backup، وCI لفحص الجودة والأمن. ما زالت البنود التي تتطلب بنية خارجية أو secrets أو تشغيلًا فعليًا في staging غير مغلقة تلقائيًا.

## الخلاصة التنفيذية

المشروع يملك أساسًا جيدًا من ناحية تقسيم الوحدات، اختبارات الصلاحيات، اتساق API v1، وتوثيق OpenAPI. لكنه **غير جاهز للإطلاق الإنتاجي المباشر** قبل إغلاق مجموعة من الموانع التشغيلية. أخطر الملاحظات ليست في منطق الـAPI، بل في التشغيل الآمن والمراقبة والاستمرارية.

أظهر التدقيق أن `php artisan about` يعمل ببيئة `PROD` مع تفعيل Debug، وأن ملف التشغيل الافتراضي لا يثبت سياسة إنتاج آمنة. كما لا توجد ضمن المستودع خطة نشر فعلية، أو إعداد workers، أو مراقبة خارجية، أو نسخ احتياطي واختبار استعادة. كذلك فشل فحص Artisan المرتبط بالـcache لأن ملف SQLite المحلي غير موجود، وهذا يكشف أن bootstrap المحلي ليس حتميًا عند استخدام الإعداد الافتراضي.

نجحت اختبارات المشروع الحالية وعددها **159 اختبارًا و5066 assertion**، كما نجح `composer audit` ولم تظهر ثغرات معلنة في تبعيات Composer أثناء التدقيق. هذه النتائج مهمة، لكنها لا تثبت جاهزية البنية التشغيلية أو التكاملات الخارجية للإنتاج.

## بوابات الإطلاق

| الأولوية | المانع | الدليل من المشروع | الحكم |
| --- | --- | --- | --- |
| P0 | تفعيل Debug في بيئة تظهر كـProduction | ناتج `php artisan about`: `Environment PROD` و`Debug Mode ENABLED` | يمنع الإطلاق حتى يصبح `APP_DEBUG=false` وتتم مراجعة secrets وconfig cache |
| P0 | لا يوجد backup/restore مُنفّذ ومختبر على بنية خارجية | أضيف أمر `backup:database` وسكربتات `sqlite` و`mysql` و`pgsql` وrunbook، لكن لا توجد storage أو schedule أو restore test فعلية داخل المستودع | ما زال خطر فقدان البيانات قائمًا حتى تنفيذ الاختبار |
| P0 | لا توجد مراقبة وتنبيهات إنتاجية | توجد logs و`X-Correlation-Id` فقط، دون APM أو metrics أو alerting | لا يمكن اكتشاف فشل الدفع أو queue أو webhook في الوقت المناسب |
| P0 | لا يوجد worker مُشغّل في بنية خارجية | أضيف `deploy/supervisor/ecommerce-worker.conf`، لكن لم يُثبت على host أو يُراقب بعد | الطلبات والأحداث المؤجلة قد تتراكم أو تتوقف بصمت |
| P0 | لا يوجد scheduler مُفعّل في بنية خارجية | أضيف `deploy/ecommerce-scheduler.cron`، لكن لم يُثبت في crontab production بعد | abandoned carts وoutbox وreconciliation لن تعمل تلقائيًا دون cron خارجي |
| P1 | readiness health محدود | Laravel `/up` هو health endpoint أساسي، ولا يوجد فحص DB/cache/queue/providers | قد يعلن التطبيق جاهزًا رغم تعطل dependency حرجة |
| P1 | CORS policy تحتاج origin حقيقي | أضيف `config/cors.php` و`CORS_ALLOWED_ORIGINS`، لكن القيمة الفعلية تعتمد على domain الواجهة | يجب ضبط origin الفعلي وعدم تركه فارغًا أو واسعًا |
| P1 | لا توجد سياسة reverse proxy/TLS موثقة | لا يوجد إعداد واضح لـtrusted proxies أو إجبار HTTPS أو secure cookies | خطر روابط غير آمنة أو cookies غير مناسبة خلف load balancer |
| P1 | CI لا ينفذ deploy أو security scan شامل | أضيف `quality.yml` مع composer validate/audit وPHP lint والاختبارات، لكن لا يوجد staging deploy أو secret scanning أو rollback workflow | ما زال الإصدار يحتاج release pipeline خارجية |
| P1 | تكاملات providers غير مثبتة في بيئة staging | الاختبارات الحالية لا توفر عقدة حقيقية أو sandbox verification آلية في CI | Paymob/Kashier/Bosta قد تختلف عن fakes المحلية |
| P1 | لا توجد load/performance tests | لا توجد اختبارات ضغط أو budget للـlatency والـthroughput | سلوك النظام تحت checkout concurrent traffic غير معروف |
| P2 | لا توجد سياسة retention وprivacy للـaudit/logs/webhook payloads | توجد `audit_logs` وتخزين metadata وpayloads، دون سياسة احتفاظ أو masking موثقة | احتمال تضخم البيانات أو الاحتفاظ ببيانات حساسة أكثر من اللازم |
| P2 | README ما زال قالب Laravel | لا يشرح install، production deploy، workers، scheduler، secrets، rollback، أو incident response | يرفع مخاطر التشغيل وتسليم المشروع للفريق |

## الملاحظات التفصيلية

### 1. Configuration وSecrets

يجب إنشاء environment production حقيقي خارج Git، وعدم استخدام `.env.example` كملف تشغيل مباشر. يجب ضبط `APP_ENV=production` و`APP_DEBUG=false` و`APP_URL` الحقيقي، وتوليد `APP_KEY` مرة واحدة وتخزينه في secret manager. يجب ضبط `LOG_LEVEL` الإنتاجي، و`SESSION_SECURE_COOKIE=true`، و`SESSION_SAME_SITE` بما يناسب الواجهة، و`SESSION_DOMAIN` عند الحاجة فقط.

يجب عدم وضع مفاتيح Paymob أو Kashier أو Bosta في ملفات المشروع أو CI logs. يجب تحديد سياسة تدوير secrets، وتوثيق من يملك صلاحية قراءتها، وتوفير قيم مختلفة للتطوير وstaging وproduction. يجب التأكد من أن webhook URLs النهائية تستخدم `/api/v1` وHTTPS.

### 2. Database وmigrations

يجب استخدام PostgreSQL أو MySQL مُدار في الإنتاج بدل SQLite المحلي، مع connection pooling وTLS وcredentials منفصلة. يجب تشغيل migrations في خطوة release محمية، مع backup قبل migration، وخطة rollback أو forward-fix لكل migration غير قابلة للعكس.

الـbootstrap الحالي يعتمد على SQLite عند عدم تغيير البيئة. وقد فشلت أوامر `config:cache` و`schedule:list` في بيئة التدقيق لأن `database/database.sqlite` غير موجود. هذا لا يثبت فشل الإنتاج، لكنه يثبت أن مسار التشغيل الافتراضي لا يقدم فحصًا مبكرًا واضحًا للـdatabase dependency.

### 3. Queue وOutbox

النظام يحتوي على `jobs` و`failed_jobs` وأمر `outbox:dispatch`، لكنه لا يحتوي على طريقة تشغيل production worker أو سياسة retry/backoff/timeout موحدة على مستوى النشر. يجب تحديد عدد workers، queue names، `retry_after`، `--tries`، `--timeout`، dead-letter handling، alerts على `failed_jobs`، وآلية graceful restart بعد release.

يجب تحديد ما إذا كان Redis + Horizon هو الخيار المعتمد، أو database queue مع Supervisor. لا ينبغي ترك هذا القرار ضمنيًا في `QUEUE_CONNECTION=database`.

### 4. Scheduler وعمليات المصالحة

يعرّف التطبيق مهامًا لـabandoned carts وoutbox وpayments reconciliation وshipments reconciliation. يجب إضافة cron إنتاجي واحد يشغل:

```bash
php artisan schedule:run
```

كل دقيقة، مع ضمان أن process manager يحافظ على worker دائمًا. يجب مراقبة آخر تشغيل ناجح لكل مهمة، وقياس عمر أقدم outbox event، وعدد failed jobs، وعدد payments أو shipments العالقة.

### 5. Health وobservability

المسار `/up` ليس كافيًا كـreadiness check لمتجر يعتمد على database وcache وqueue وproviders. يجب إضافة readiness داخلي محمي أو غير كاشف للمعلومات الحساسة يفحص database connection، cache read/write، queue backend، ومساحة التخزين، مع فصل liveness عن readiness.

يجب إرسال logs إلى نظام مركزي، وإضافة alerting على HTTP 5xx، authentication failures، authorization spikes، webhook signature failures، payment failures، outbox backlog، queue failures، وslow queries. يجب الحفاظ على `X-Correlation-Id` في logs وresponses دون تسجيل tokens أو card data أو secrets.

### 6. Webhooks وPayments

منطق signature verification وidempotency موجود ومختبر على مستوى التطبيق، لكن يلزم اختبار sandbox حقيقي من مزودي الدفع والشحن، مع fixtures versioned لكل provider، وتوثيق تدوير secrets، وإعادة إرسال event، وreplay protection، ووقت انتهاء event deduplication.

يجب التأكد من أن جميع providers تعيد response سريعًا قبل أي عملية طويلة، وأن المعالجة الثقيلة تذهب إلى queue عندما يتطلب provider ذلك. يجب وضع alert عند ارتفاع invalid signatures أو تزايد events غير المعالجة.

### 7. Authorization وaccount security

تغطية Authorization قوية حاليًا، لكن الإنتاج يحتاج أيضًا إلى تأكيدات تشغيلية: تعطيل الحساب، تدوير tokens، password reset production flow، email/phone verification، حماية brute force على كل identifier، وإجبار MFA لحسابات owner/staff إن كانت لوحة الإدارة متاحة عبر الإنترنت.

يجب مراجعة سياسة staff permissions دوريًا، وتسجيل تغييرات الأدوار والصلاحيات، وتحديد مدة الجلسات، وإبطال الجلسات عند تغيير كلمة المرور أو تعطيل الحساب.

### 8. CI/CD وrelease management

يجب توسيع CI الحالي ليشمل `composer validate`، `composer audit`، PHP lint، Pint أو coding standard، OpenAPI contract test، migration test من قاعدة فارغة، migration test من snapshot قريب من الإنتاج، وفحص secrets أو dependency vulnerabilities.

يجب إضافة staging deployment، smoke tests بعد النشر، health verification، release tagging، rollback instructions، وقيود branch protection. لا يكفي نجاح PHPUnit وحده لقبول release إنتاجي.

### 9. الاختبارات

اختبارات authorization وschema وAPI موجودة بشكل جيد، لكن التغطية الإنتاجية تحتاج إلى اختبارات إضافية لـprovider HTTP clients، timeouts، retries، circuit breaker، webhook replay، queue failures، failed outbox dispatch، concurrent checkout، deadlocks، وrestore من backup.

يجب قياس coverage مع حد أدنى قابل للتبرير، وإضافة contract tests مع sandbox providers أو mock servers ثابتة. كما يجب تنفيذ load test قبل الإطلاق على checkout وpayment وwebhook endpoints.

### 10. Data governance

يجب تعريف البيانات الحساسة التي لا يجوز تسجيلها، ومدة الاحتفاظ بـaudit logs وwebhook payloads، وسياسة حذف حساب العميل، وطلبات تصحيح البيانات، وتصدير البيانات، وقيود الوصول إلى customer PII. يجب أيضًا تعريف retention للـfailed jobs والـsessions والـcache.

## خطة التنفيذ المقترحة

| المرحلة | الأعمال المطلوبة | معيار الإغلاق |
| --- | --- | --- |
| Release blocker | إيقاف Debug، إعداد production secrets، database مُدارة، backup وrestore، worker process، scheduler cron، HTTPS | smoke test وrestore test ناجحان في staging |
| Reliability | readiness checks، centralized logs، metrics، alerts، queue/outbox monitoring | تنبيه تجريبي يصل للفريق لكل failure class |
| Security | CORS، trusted proxies، secure cookies، MFA للـstaff، token/password lifecycle، secret rotation | security review وpenetration checklist مكتملان |
| Delivery | CI audit/lint/migrations، staging deployment، smoke tests، rollback، branch protection | release تجريبي قابل للنشر والرجوع |
| Scale | load tests، DB indexes review، Redis/Horizon أو worker topology، provider sandbox tests | budgets موثقة ومقبولة للـcheckout والـwebhooks |
| Governance | privacy/retention/masking، incident response، on-call runbook، ownership | runbook مجرب ومراجع من الفريق |

## قرار الجاهزية

**الحالة الحالية: Not production-ready.** يمكن استخدام المشروع في development أو staging، ويمكن بدء hardening production، لكن لا ينبغي استقبال طلبات أو مدفوعات حقيقية قبل إغلاق عناصر P0 على الأقل. نجاح الاختبارات الحالية و`composer audit` يقللان مخاطر regression البرمجية، لكنهما لا يعالجان مخاطر فقدان البيانات، توقف workers، غياب التنبيهات، أو تفعيل Debug.

## المراجع

[1]: https://laravel.com/framework/docs/12.x/deployment "Laravel Deployment Documentation"
[2]: https://laravel.com/framework/docs/configuration "Laravel Configuration Documentation"
[3]: https://laravel.com/framework/docs/queues "Laravel Queue Documentation"
[4]: https://laravel.com/framework/docs/12.x/scheduling "Laravel Task Scheduling Documentation"
[5]: https://github.com/heshamGamal/ecommerce-platform/blob/main/docs/openapi.json "E-commerce Platform OpenAPI Contract"
[6]: https://github.com/heshamGamal/ecommerce-platform/blob/main/docs/API_V1_GUIDE.md "E-commerce Platform API v1 Integration Guide"
