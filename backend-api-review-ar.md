# مراجعة Backend API ومقارنته بالمقترحات

**تاريخ المراجعة:** 2026-09-24  
**الريبو:** `etchgamal-web/eco`  
**النطاق:** `laravel-api` فقط  
**الفرع/النسخة:** `main` عند commit `eceea5b`  
**حالة Git عند المراجعة:** نظيفة، دون تعديلات محلية.

## الخلاصة التنفيذية

المقترحات المرفقة لا تعكس بالكامل الحالة الحالية للفرع؛ فقد أضيفت بالفعل قائمة التسويات وتقارير التسويات المالية في commits حديثة، وعلى رأسها `557b8ee` و`eceea5b`. لذلك لا ينبغي إعادة تنفيذ هذه البنود كما لو كانت مفقودة.

في المقابل، توجد فجوات حقيقية في دورة المرتجعات والاسترداد، وإعادة تعيين كلمة المرور، والتتبع العام الآمن للشحنات، والعمليات الجماعية للتنبيهات، وتصدير البيانات، وCI/CD ومراقبة الإنتاج. كما أن توحيد pagination وcorrelation IDs ما زال جزئيًا.

تعذر تشغيل الاختبارات في هذه البيئة؛ فـ`vendor/` غير موجود و`composer` غير مثبت، ولذلك لا يمكن اعتماد ادعاء `44 اختبارًا ناجحًا / 7014 assertion` من الملف المرفق كدليل مستقل على النسخة الحالية.

## المقارنة التفصيلية

| البند | الحالة في الريبو الحالي | الدليل/الملاحظة | الحكم |
|---|---|---|---|
| قائمة التسويات `GET /api/v1/shipping/settlements` | موجودة | `routes/api/settlements.php`، و`SettlementController::index`، و`SettlementListRequest` | **مكتمل** |
| فلترة قائمة التسويات | موجودة | provider، status، from/to، search، has_discrepancy، page، per_page، sort، direction | **مكتمل وظيفيًا** |
| تقارير التسويات المجمعة | موجودة | `GET /api/v1/reports/settlements/summary` و`GET /api/v1/reports/settlements/providers` | **مكتمل** |
| تصدير تقرير شركات الشحن | موجود | `GET /api/v1/reports/settlements/providers/export` ويستخدم `streamDownload` | **مكتمل جزئيًا**؛ لكنه ليس بديلًا عن تصدير كل طلبات أو ملف تسوية منفرد |
| دورة إنشاء المرتجع | موجودة | customer endpoint لإنشاء المرتجع، مع reason/items والتحقق من الطلب والكمية | **موجود** |
| دورة فحص واستلام المرتجع | غير ظاهرة | لا توجد مسارات أو حالات واضحة للاستلام، الفحص، القبول/الرفض بعد الفحص، أو رفع الصور/المستندات | **فجوة عالية** |
| Refund API | موجود للاسترداد الكامل | `POST /api/v1/payments/{paymentId}/refund`، انتقال حالة وحماية من التكرار وoutbox/audit | **جزئي** |
| Refund جزئي وسبب refund وربطه بالمرتجع/التسوية | غير مكتمل | `RefundPayment` يسترد payment كاملًا؛ لا يظهر amount جزئي أو refund resource مستقل أو settlement linkage | **فجوة عالية** |
| Idempotency في refund | موجود داخليًا جزئيًا | operation key وlease وpayment operation protection موجودة | **جيد لكن يحتاج عقد API صريحًا** |
| Webhooks لتأكيد refund | جزئي | توجد webhooks لـPaymob/Kashier وتحديث حالات الدفع؛ لا يظهر مسار refund مستقل متكامل مع reconciliation | **يحتاج اختبار تكاملي** |
| التتبع العام للشحنات | غير موجود | `routes/api/shipping.php` محمية بـ`auth` ولا يوجد public tracking token endpoint | **فجوة عالية** |
| التنبيهات التشغيلية الفردية | موجودة | list/show/acknowledge/resolve مع حالات التشغيل | **مكتمل** |
| التنبيهات الجماعية | غير موجودة | لا توجد bulk-acknowledge أو bulk-resolve في routes | **فجوة متوسطة** |
| تقارير العمليات والتأخير | جزئية | يوجد `GET /api/v1/operations/dashboard` ومنطق delayed orders، لكن لا يظهر عقد موحد مطابق لـ`operations/summary` و`operations/delays` مع كل المؤشرات المقترحة | **جزئي** |
| تصدير الطلبات | غير ظاهر | لا يوجد endpoint `GET /api/v1/orders/export` في المسارات التي تمت مراجعتها | **فجوة متوسطة** |
| تصدير ملف تسوية منفرد | غير ظاهر | يوجد provider report export، وليس export واضحًا لـ`settlements/{id}/export` | **فجوة متوسطة** |
| Password reset | غير موجود | Auth routes تحتوي register/login/me/logout/change password فقط؛ لا توجد forgot/reset token flow | **فجوة عالية** |
| توحيد pagination | جزئي | Settlement list يدعم page/per_page، بينما قوائم أخرى تعيد `data` مباشرة أو تستخدم عقودًا مختلفة؛ كما أن المقترح يستخدم `sort_dir` والريبو يستخدم `direction` في التسويات | **فجوة متوسطة** |
| CI/CD | غير موجود في الريبو | لا توجد ملفات `.github/workflows` ظاهرة | **فجوة إنتاجية عالية** |
| أوامر backup وverify | موجودة | توجد `BackupDatabase` و`VerifyBackup` و`config/backup.php` | **موجود جزئيًا**؛ وجود الأمر لا يثبت restore معزولًا وsmoke tests وقياس RPO/RTO |
| مراقبة jobs/webhooks/5xx والـqueue | غير مكتملة | توجد queue/failed_jobs وأوامر reconciliation، لكن لا تظهر منظومة مراقبة وتنبيهات production كاملة | **فجوة إنتاجية عالية** |
| المال باستخدام minor units | جيد إلى حد كبير | عدة migrations تستخدم `unsignedBigInteger` للمبالغ الأساسية مثل order/payment/settlement | **متحقق غالبًا**؛ يلزم تدقيق شامل لكل الحقول والحسابات |
| Audit logs | موجودة | توجد سجلات للعمليات الحساسة مثل refund وreturn وsettlement | **موجود جزئيًا** |
| Correlation/request IDs | غير موحد | توجد provider references وidempotency وaudit metadata في مجالات مختلفة، ولا يظهر middleware/contract موحد يربط request ID بكل العملية | **فجوة متوسطة** |
| OpenAPI والاختبارات المعمارية والصلاحيات | موجودة | `docs/openapi.json` واختبارات OpenAPI/RBAC/architecture موجودة | **موجود** |

## ملاحظات مهمة على البنود التي تم تنفيذها بالفعل

### التسويات والتقارير

الـBackend الحالي يحتوي على:

```text
GET   /api/v1/shipping/settlements
POST  /api/v1/shipping/settlements/import
GET   /api/v1/shipping/settlements/{id}
GET   /api/v1/shipping/settlements/{id}/items
PATCH /api/v1/shipping/settlements/{id}/finalize

GET   /api/v1/reports/settlements/summary
GET   /api/v1/reports/settlements/providers
GET   /api/v1/reports/settlements/providers/export
```

وتدعم تقارير شركات الشحن قيمًا متوقعة وفعلية وفروقات للتحصيل، وقيمة الطلب، وتكلفة الشحن، ورسوم المرتجعات، ورد العملاء. لذلك فإن البندين الأول والثاني في المقترح منفذان بالفعل بدرجة جيدة.

### المرتجعات

يوجد إنشاء مرتجع من جهة العميل، وقائمة إدارية، وapprove/reject، مع منع تكرار طلب الإرجاع للمنتج/الطلب في الاختبارات الحالية. لكن هذا لا يساوي دورة reverse logistics كاملة؛ لا توجد طبقة واضحة لحالة الاستلام والفحص وربط الصور والمستندات وrefund الفعلي المرتبط بسجل المرتجع.

### الاسترداد

يوجد full refund على مستوى payment، مع state protection وoperation lease وoutbox وaudit. إلا أن التنفيذ الحالي ليس Refund API كاملًا بالمواصفات المقترحة: لا يظهر partial refund، ولا سبب مرسل من العميل/الإدارة، ولا كيان refund مستقل، ولا ربط صريح بين refund وreturn وsettlement.

## ملاحظات جودة واختبار

1. **الاختبارات غير قابلة للتشغيل في البيئة الحالية:** `vendor/bin/phpunit` غير موجود، و`composer` نفسه غير مثبت. لذلك تم فحص الاختبارات والمصدر static inspection فقط.
2. **لا يوجد CI workflow داخل الريبو:** نجاح الاختبارات محليًا، إن تحقق في بيئة أخرى، لا يضمن منع regression على GitHub.
3. **OpenAPI موجود:** وجود `docs/openapi.json` واختبار `OpenApiContractTest` نقطة إيجابية، لكن يجب التأكد من أن كل endpoint جديد وحقول الاستجابة محدثة في نفس commit.
4. **الاستجابات ليست موحدة بالكامل:** بعض القوائم تستخدم pagination، وبعضها يعيد collection مباشرة؛ يفضل اعتماد Resource/Response contract مشترك.
5. **الحسابات المالية مبنية في مواضع كثيرة على أعداد صحيحة، وهو مناسب لـminor units، لكن يلزم تدقيق جميع التحويلات والمدخلات الخارجية حتى لا يحدث خلط بين الوحدات الصغرى وقيمة العرض.

## ترتيب التنفيذ المقترح بعد مراجعة النسخة الحالية

### P0 — قبل اعتبار الـBackend مكتملًا تجاريًا

1. إكمال refund/returns: نموذج refund مستقل أو عقد واضح، partial refund، reason، idempotency header/key، ربط return/payment/order/settlement، وتأكيد webhook.
2. إضافة password reset كامل مع token hash، expiry، single-use، rate limiting، وعدم كشف وجود البريد.
3. إضافة public shipment tracking باستخدام token غير قابل للتخمين أو order number مع verification، مع whitelist للبيانات.

### P1 — تحسين العمليات والإدارة

4. إضافة bulk acknowledge/resolve للتنبيهات مع actor وreason وaudit.
5. توحيد pagination/filter/sort وresponse meta لكل endpoints القوائم.
6. إضافة orders export وsettlement detail export، وترك التصدير الكبير لـstreaming أو queue.
7. توحيد operational reports بعقد ثابت يغطي delay counts، average delay، provider breakdown، open alerts، ونطاق التاريخ.

### P2 — جاهزية الإنتاج

8. إضافة GitHub Actions تشمل `composer validate` وPint والاختبارات المعمارية وPHPUnit وOpenAPI وdependency/secrets checks.
9. إضافة monitoring قابل للتشغيل للفشل في jobs وwebhooks وproviders وsettlement imports والـ5xx والـqueue lag.
10. تنفيذ restore drill دوري: backup، checksum، restore في قاعدة معزولة، smoke tests، وتسجيل RPO/RTO.
11. إضافة middleware/standard fields لـrequest/correlation ID وربطها بالـaudit وprovider reference وidempotency وorder/settlement IDs.

## الحكم النهائي

العبارة الأدق عن الريبو الحالي هي:

> **الـBackend قوي في التسويات، reconciliation، delayed-order monitoring، operational alerts، الصلاحيات، والتوثيق الأساسي؛ لكنه ليس مكتملًا بعد كمنظومة returns/refunds وproduction operations.**

وأكبر تصحيح للمقترح المرفق هو أن **قائمة التسويات والتقارير المالية المجمعة تم تنفيذها بالفعل** في النسخة الحالية، بينما ينبغي نقل التركيز الآن إلى دورة refund/returns، الأمن التشغيلي، password reset، public tracking، وتوحيد عقود الـAPI.

## الملفات المرجعية الأساسية

- `laravel-api/routes/api/settlements.php`
- `laravel-api/app/Modules/Settlement/Presentation/Http/Controllers/SettlementController.php`
- `laravel-api/app/Modules/Settlement/Presentation/Http/Controllers/SettlementReportController.php`
- `laravel-api/app/Modules/Payment/Application/UseCases/RefundPayment.php`
- `laravel-api/routes/api/auth.php`
- `laravel-api/routes/api/shipping.php`
- `laravel-api/routes/api/monitoring.php`
- `laravel-api/tests/Feature/SettlementImportApiTest.php`
- `laravel-api/tests/Feature/ReturnsApiTest.php`
- `laravel-api/tests/Feature/PaymentApiTest.php`
- `laravel-api/docs/openapi.json`


## تحديث التنفيذ بتاريخ 2026-09-24

بعد المراجعة تم تنفيذ دفعة إغلاق أولى داخل `laravel-api`:

- إضافة `POST /api/v1/auth/password/forgot` و`POST /api/v1/auth/password/reset` باستخدام Laravel Password Broker، مع rate limiting ورسالة عامة لا تكشف وجود البريد.
- إضافة `GET /api/v1/public/shipments/{tracking_token}` مع token عشوائي بطول 48 حرفًا، وإرجاع حالة الشحنة وأحداثها الآمنة فقط.
- إضافة migration لتوليد tokens للشحنات الموجودة، وتوليد token جديد عند إنشاء الشحنات.
- توسيع دورة المرتجع بعد الموافقة إلى `received` ثم `inspected_accepted` أو `inspected_rejected`، مع timestamps وnotes وaudit/outbox.
- تحديث OpenAPI واختبارات Feature مبدئية للمسارات الجديدة.
- إضافة GitHub Actions لتشغيل Composer validation، Pint، PHPUnit، فحص OpenAPI، و`composer audit`.

ما يزال **partial refund المرتبط فعليًا ببوابات الدفع والتسوية** يحتاج طبقة Refund مستقلة وتكاملًا صريحًا مع كل provider؛ لم يتم الادعاء بتنفيذه بمجرد تسجيل حالة `refunded`. كما بقيت عمليات bulk alerts وتصدير الطلبات/ملف التسوية المنفرد ضمن الدفعة التالية.


## نتيجة الدفعة الثانية والتحقق النهائي

اكتملت الدفعة الثانية بإضافة عمليات جماعية للتنبيهات عبر `bulk-acknowledge` و`bulk-resolve` مع تسجيل Audit، وإضافة تصدير CSV للطلبات وتفاصيل التسويات. كما تم نقل منطق Password Reset والتتبع العام إلى Use Cases وواجهات Domain/Repositories، بما يحافظ على قواعد الطبقات المعمارية للريبو.

تم تحديث OpenAPI ليطابق المسارات الفعلية بدون تكرار `api/v1` داخل الوثيقة، وإضافة metadata الخاصة بالصلاحيات ومحددات المعدل وأمثلة الطلبات. كما تم تحديث اختبارات versioning وauthorization لتشمل المسارات الجديدة.

التحقق النهائي ناجح: **230 اختبارًا مرّ بنجاح مع 7,358 assertion**، وشمل ذلك اختبارات OpenAPI، الصلاحيات، architecture، Password Reset، Public Tracking، ومسارات التصدير والعمليات الجديدة. كما نجح فحص syntax لجميع ملفات PHP المعدلة.

يبقى partial refund المرتبط بمبلغ جزئي وتكامل provider-specific خارج هذه الدفعة؛ مسار refund الحالي في الريبو مصمم للاسترداد الكامل فقط، وإضافة partial refund بشكل صحيح تتطلب عقدًا جديدًا للبوابات وledger مستقلًا للمبالغ المستردة.
