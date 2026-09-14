# أساس معماري للدفع والشحن والـ Checkout

## النتيجة التنفيذية

المشروع يتبع فصلًا مناسبًا بين **Controller → Form Request → Use Case → Domain Contract → Infrastructure → Domain Exception → Central Error Handler**. المشكلة الأساسية لم تكن في وجود الطبقات، بل في حدود المعاملة الزمنية بين النظام المحلي والمزوّد الخارجي.

أصبح الـ checkout الآن معاملة محلية قصيرة، ثم خطوة دفع قابلة للاسترداد. لا يتم استدعاء بوابة الدفع داخل معاملة قاعدة البيانات. كما أصبح مفتاح idempotency للدفع يميّز بين إعادة طلب مكتمل وطلب آخر ما زال قيد التنفيذ.

> قاعدة العمل: لا توجد معاملة موزعة حقيقية بين قاعدة بيانات المتجر وبوابة دفع أو شركة شحن. استخدم **Saga**، والحالات الصريحة، وOutbox/Webhooks، والمصالحة الدورية بدل محاولة `2PC` مع مزوّد خارجي.

## ما تم اكتشافه وإصلاحه

| المجال | الخطر | الحالة بعد التعديل |
|---|---|---|
| سباق إنشاء الدفع | كان الطلب الثاني يعيد سجلًا بحالة `initiating` وكأن العملية اكتملت | أصبح يعيد السجل فقط للحالات المكتملة، ويعيد `409` عند وجود عملية جارية |
| استدعاء gateway داخل transaction | فشل قاعدة البيانات بعد نجاح الشحن الخارجي قد يخفي عملية دفع حقيقية | تم نقل الدفع خارج معاملة الطلب المحلية |
| استثناءات idempotency | كان الخطأ يصنف كفشل دفع عام | أضيف `PaymentInProgressException` وتصنيف `409 Conflict` |
| مفاتيح الدفع | الاعتماد على الفحص ثم الإنشاء فقط غير كافٍ | بقي القيد `UNIQUE` وقفل الصف هما مصدر الحماية الذري |
| Webhooks | لم توجد ذاكرة دائمة لمنع تكرار الحدث | أضيف جدول `payment_webhook_events` بقيد `(provider,event_id)` |
| تكامل المزوّد | توجد واجهة عامة لكنها لا تزال تحتاج Adapter حقيقيًا | يمر التكامل القادم عبر `PaymentGatewayInterface` دون إدخال اسم المزوّد إلى Domain |
| فشل الشحن داخل checkout | ما زال محليًا وقابلًا للـ rollback قبل الخروج من المعاملة | هذا هو السلوك الصحيح؛ استدعاء شركة الشحن الخارجية يجب أن يكون Saga لاحقة مشابهة للدفع |

## الحدود الصحيحة للطبقات

### Domain

يحتوي على الحالات، الانتقالات، Value Objects، Exceptions، وContracts فقط. لا يعرف Laravel أو Eloquent أو Stripe أو Paymob أو Aramex أو غيرها.

### Application

ينفذ حالات الاستخدام. ينسق بين المستودعات والعقود. يقرر متى يثبت claim، ومتى يعيد المحاولة، ومتى يحتاج إلى reconciliation.

### Infrastructure

يحتوي على Eloquent repositories، HTTP clients، توقيع Webhook، Queue jobs، وAdapters لكل مزوّد. يجب ألا يتسرب اسم المزوّد أو payload الخاص به إلى Domain.

### Presentation

يحتوي على Form Requests وControllers وResource/JSON mapping. يجب أن يعيد `409` للتعارضات، و`422` لبيانات الأعمال غير الصحيحة، و`503` عند تعطل مزوّد خارجي بعد إنشاء عملية محلية قابلة للاسترداد.

## نموذج الحالة المقترح

### Payment

`initiating → pending → paid → refunded`

وتوجد حالات فشل تشغيلية منفصلة:

`initiating → failed`

`pending → failed`

لا تحذف payment عند الفشل. وجود السجل يسمح بإعادة المحاولة، وتتبع التدقيق، ومصالحة عملية ربما نجحت عند المزوّد ولم يصل ردها.

### Shipment

`pending → booked → picked_up → in_transit → out_for_delivery → delivered`

والإلغاء مسموح فقط قبل التسليم، مع منع الانتقال العكسي. يجب أن تكون عملية الحجز الخارجي للشحنة مثل الدفع: claim محلي أولًا، ثم provider call خارج transaction، ثم تحديث الحالة أو إدخالها إلى reconciliation.

### Order

حالات الطلب لا تعني حالة الدفع وحدها. يجب فصل:

| بُعد | أمثلة |
|---|---|
| Order lifecycle | `pending`, `confirmed`, `processing`, `completed`, `cancelled`, `refunded` |
| Payment lifecycle | `initiating`, `pending`, `paid`, `failed`, `refunded` |
| Fulfillment lifecycle | `pending`, `booked`, `in_transit`, `delivered`, `cancelled` |

لا تجعل `order.status = confirmed` دليلًا وحيدًا على أن المال وصل. القرار يجب أن يعتمد على payment aggregate أو projection موثقة.

## مصفوفة فشل الـ Checkout

| المرحلة | النتيجة المحلية | الإجراء | واجهة العميل |
|---|---|---|---|
| مفتاح checkout مستخدم لنفس الطلب | إعادة نفس الطلب | لا تنشئ Order جديدًا | `200/201` مع نفس المورد |
| مفتاح checkout مستخدم لطلب آخر | لا تغيير | ارفض لمنع تسريب/خلط البيانات | `409` |
| السلة فارغة | rollback | لا تنشئ طلبًا | `422` |
| منتج غير متاح أو سعر مفقود | rollback | لا تحجز المخزون | `422` |
| مخزون غير كافٍ | rollback | أعد الكمية المحجوزة ضمن transaction | `422` |
| عنوان غير صالح | rollback | لا تنشئ شحنة | `422` |
| طريقة شحن غير فعالة | rollback | لا تنشئ shipment | `422` |
| فشل إنشاء shipment محليًا | rollback | لا تنشئ order نهائيًا | `422/409` حسب السبب |
| فشل بوابة الدفع قبل claim | الطلب موجود والدفع غير موجود | أعد المحاولة بمفتاح جديد أو نفس العملية | `503` أو `422` |
| فشل gateway بعد claim | الطلب موجود وpayment=`failed` | reconciliation أو retry آمن | `503` |
| timeout مع احتمال نجاح المزوّد | payment=`initiating` أو `failed` مع reconciliation | لا تنشئ payment ثانية؛ استعلم أو انتظر webhook | `409/202` |
| Webhook مكرر | لا تغيير | unique provider/event_id | `200` idempotent |
| Webhook غير صالح التوقيع | لا تغيير | سجّل الحادثة دون تغيير payment | `401/400` |
| payment مدفوع مسبقًا | لا تعيد الشحن | أعد الحالة الحالية | `409` عند محاولة transition غير صالح |
| refund مكرر | لا تغيير | أعد الحالة أو ارفض transition | `409` |

## قاعدة idempotency

لكل عملية خارجية مفتاح مستقل ومحدد النطاق:

| العملية | المفتاح |
|---|---|
| إنشاء Order | `checkout_idempotency_key` |
| إنشاء Payment | `payment_idempotency_key` |
| حجز Shipment | `shipment_idempotency_key` |
| Refund | `refund_idempotency_key` |
| Webhook | `(provider, event_id)` |

المفتاح لا يُستخدم لإخفاء اختلاف payload. عند إعادة استخدامه يجب مقارنة `order_id`, `user_id`, `amount`, `currency`, و`method`. أي اختلاف يعيد `409`.

## مصفوفة التفويض

| العملية | Customer | Support | Order Manager | Manager | Owner/Admin |
|---|---:|---:|---:|---:|---:|
| عرض طلباته | نعم | نعم حسب السياسة | نعم | نعم | نعم |
| إنشاء checkout لنفسه | نعم | لا | لا | لا | لا |
| عرض كل الطلبات | لا | نعم | نعم | نعم | نعم |
| تأكيد الطلب | لا | لا | نعم | نعم | نعم |
| إلغاء طلب | طلبه قبل الشحن فقط | لا | نعم وفق السياسة | نعم | نعم |
| إنشاء/تعديل طريقة شحن | لا | لا | لا | حسب الصلاحية | نعم |
| تغيير حالة shipment يدويًا | لا | لا | نعم | نعم | نعم |
| عرض payments | Payments الخاصة به | قراءة دعم | نعم | نعم | نعم |
| confirm payment | لا | لا | لا افتراضيًا | حسب سياسة مالية | نعم |
| refund payment | لا | لا | لا افتراضيًا | حسب سياسة مالية | نعم |
| تغيير gateway credentials | لا | لا | لا | لا | Admin فقط |
| معالجة webhook | ليس endpoint للمستخدم | ليس endpoint للمستخدم | ليس endpoint للمستخدم | ليس endpoint للمستخدم | System signature فقط |

يجب تطبيق التفويض مرتين: في Form Request للطلبات العادية، وفي Use Case أو Policy على المورد نفسه. لا تعتمد على الدور وحده دون فحص ownership وحالة المورد.

## تصميم Adapter لمزوّد فعلي

يجب إنشاء Adapter مستقل لكل مزوّد، مثل:

```text
PaymentGatewayInterface
├── CashOnDeliveryGateway
├── ProviderAGateway
└── ProviderBGateway
```

الـ Adapter مسؤول عن تحويل:

```text
ProviderRequest → ProviderResponse → GatewayResult
```

ولا يحق له إعادة payload الخام إلى Domain. يجب أن يحتوي `GatewayResult` على `status`, `provider_reference`, `provider_event_id`, و`metadata` المسموح بها فقط.

التكامل الفعلي يحتاج تحديد مزوّد واحد، وبيئة sandbox، وعملة الحساب، وسياسة 3-D Secure، وwebhook secret. لا ينبغي اختيار مزوّد أو افتراض API من دون هذا القرار لأنه يغير شكل redirect، capture، refund، وwebhook.

## التشغيل الإنتاجي

يجب تشغيل Queue للـ payment/shipping retries مع backoff، وحد أقصى للمحاولات، وdead-letter أو جدول reconciliation. يجب تسجيل `correlation_id`, `order_id`, `payment_id`, `provider`, و`provider_reference` في كل log. يجب منع تسجيل PAN أو CVV أو secrets.

يجب توفير لوحات مراقبة لـ:

| المؤشر | التنبيه المقترح |
|---|---|
| payments في `initiating` أقدم من حد زمني | تنبيه عالي |
| payments `paid` بلا Order confirmed | تنبيه مالي |
| orders confirmed بلا payment paid | تنبيه مالي |
| shipments booked بلا tracking | تنبيه تشغيلي |
| webhook signature failures | تنبيه أمني |
| idempotency conflicts | تنبيه احتيال/خلل عميل |
| provider timeout/error rate | تنبيه موثوقية |

قبل الإنتاج يجب تطبيق rate limiting على checkout وpayment وwebhook، والتحقق من توقيع webhook قبل parsing، وتدوير الأسرار، وفصل مفاتيح sandbox عن production، وإجراء health checks لا تعتمد على نجاح عملية دفع حقيقية.

## الاختبارات المطلوبة

يجب إضافة اختبارات متوازية أو integration tests تغطي إنشاء طلبين بنفس المفتاح، ومفتاح واحد لطلبين مختلفين، وإعادة الطلب بعد timeout، ووصول webhook مرتين، ووصول webhook قبل رد create، ونجاح gateway ثم فشل تحديث قاعدة البيانات، وrefund المتزامن، ومحاولات الوصول إلى payment أو shipment لمستخدم آخر.

يجب أن تستخدم اختبارات بوابة الدفع Fake Adapter يسجل عدد الاستدعاءات. اختبار idempotency الناجح يجب أن يثبت أن استدعاء المزود حدث مرة واحدة فقط، وليس مجرد أن عدد الصفوف يساوي واحدًا.

## التغييرات المنفذة في هذه الدفعة

تمت إضافة `PaymentInProgressException`، وتحديث `CreatePayment` لمنع إعادة استخدام payment بحالة `initiating`، وتحديث الـ Central Error Handler لإرجاع `409`، وتعديل `Checkout` بحيث تنتهي المعاملة المحلية قبل استدعاء gateway، وإضافة جدول `payment_webhook_events` مع unique deduplication key.

## القيود الحالية

لا توجد في بيئة التنفيذ PHP أو `vendor`، لذلك تعذر تشغيل PHPUnit وLaravel migrations في هذه الجلسة. يجب تشغيل `composer install` ثم `php artisan test` و`php artisan migrate:fresh --seed` في CI أو بيئة التطوير. تم تنفيذ Adapter Paymob وWebhook، لكن تفعيل الإنتاج يتطلب إدخال مفاتيح Sandbox الصحيحة، وتسجيل عنوان callback في لوحة Paymob، ثم اختبار العمليات الفعلية قبل التحويل إلى Production.

## References

[1]: https://microservices.io/patterns/data/transactional-outbox.html "Transactional Outbox Pattern"

[2]: https://docs.stripe.com/api/idempotent_requests "Stripe Idempotent Requests"

[3]: https://martinfowler.com/articles/patterns-of-distributed-systems/saga.html "Saga Pattern"


## تكامل Paymob المضاف

تمت إضافة `PaymobGateway` خلف `PaymentGatewayInterface` باستخدام مسار Hosted Checkout الحديث الخاص بـ Paymob:

```text
CreatePayment
    ↓
PaymentGatewayRouter
    ↓
PaymobGateway
    ↓
POST /v1/intention/
    ↓
client_secret + unifiedcheckout URL
```

تمت إضافة endpoint عام:

```text
POST /api/v1/webhooks/paymob
```

ويتحقق من HMAC-SHA512 قبل تحديث payment. كما تم دعم deduplication للحدث باستخدام `provider + event_id`، وربط callback أولًا بـ `merchant_order_id`، ثم بـ provider reference عند الحاجة.

أضف الإعدادات التالية إلى `.env` باستخدام بيانات Sandbox من Paymob:

```env
PAYMOB_ENABLED=true
PAYMOB_BASE_URL=https://accept.paymob.com
PAYMOB_SECRET_KEY=...
PAYMOB_PUBLIC_KEY=...
PAYMOB_HMAC_SECRET=...
PAYMOB_INTEGRATION_IDS=...
PAYMOB_NOTIFICATION_URL=https://your-domain.example/api/v1/webhooks/paymob
PAYMOB_REDIRECTION_URL=https://your-domain.example/payment/return
```

مسار العميل هو إنشاء payment باستخدام `method=paymob`، ثم فتح `data.metadata.checkout_url`. لا يعتمد تأكيد النجاح على عودة المتصفح؛ الـ callback هو مصدر الحقيقة، بينما redirect يستخدم لتجربة المستخدم فقط.

تم اعتماد واجهة Paymob الرسمية الحالية الخاصة بإنشاء Payment Intention، وواجهة Unified Checkout، وTransaction Callback/HMAC، وRefund endpoint. يجب اختبار أسماء الحقول وتفعيل webhook من لوحة Paymob في Sandbox قبل الإنتاج لأن إعدادات التكامل تختلف باختلاف البلد والحساب ونوع المنتج.

[4]: https://developers.paymob.com/paymob-docs/intention-apis/create-intention "Paymob Create Intention API"

[5]: https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac/hmac/hmac-transaction-callback "Paymob Transaction Callback HMAC"

[6]: https://github.com/PaymobAccept/API-Postman-Collections "Paymob Official API Postman Collections"


## تكامل Kashier المضاف

تمت إضافة `KashierGateway` خلف نفس `PaymentGatewayRouter`، لذلك لا يتغير الـ Domain عند اختيار Kashier:

```text
CreatePayment
    ↓
PaymentGatewayRouter
    ↓
KashierGateway
    ↓
POST /v3/payment/sessions
    ↓
sessionUrl
```

استخدام Kashier يتم عبر `method=kashier`. يعيد الـ Gateway رابط `session_url` داخل metadata، ويجب تحويل العميل إليه. تم تنفيذ HMAC-SHA256 الخاص بإنشاء الجلسة باستخدام **Payment API Key**، كما تم تنفيذ Refund عبر `PUT /v3/orders/{orderId}` باستخدام **Secret Key**.

تمت إضافة callback:

```text
POST /api/v1/webhooks/kashier
```

ويتم التحقق من توقيع الاستجابة باستخدام ترتيب الحقول الثابت الموثق من Kashier، ثم تخزين الحدث في `payment_webhook_events` ومنع تكراره وتحديث Payment وOrder داخل transaction محلية.

الإعدادات المطلوبة:

```env
KASHIER_ENABLED=true
KASHIER_API_BASE_URL=https://test-api.kashier.io
KASHIER_FEP_BASE_URL=https://test-fep.kashier.io
KASHIER_CHECKOUT_BASE_URL=https://payments.kashier.io
KASHIER_MERCHANT_ID=...
KASHIER_SECRET_KEY=...
KASHIER_PAYMENT_API_KEY=...
KASHIER_WEBHOOK_URL=https://your-domain.example/api/v1/webhooks/kashier
KASHIER_REDIRECT_URL=https://your-domain.example/payment/return
```

عند الانتقال إلى الإنتاج يجب تغيير API host إلى `https://api.kashier.io` وFEP host إلى `https://fep.kashier.io`، واستبدال المفاتيح بمفاتيح Live. يجب إنشاء Webhook مستقل لوضع Live، والتأكد من أن Payment API Key المستخدم في التوقيع يخص نفس الوضع.

[7]: https://developers.kashier.io/docs/api-reference/payment-sessions/createPaymentSession "Kashier Create Payment Session"

[8]: https://developers.kashier.io/docs/direct-api/hashing "Kashier Request Hashing and Signatures"

[9]: https://developers.kashier.io/docs/api-reference/order-operations/updateOrder "Kashier Refund Order Operation"

[10]: https://developers.kashier.io/docs/get-started/going-live "Kashier Going Live Checklist"


## إدارة بوابات الدفع من الإعدادات

تم نقل اختيار وتكوين بوابات الدفع إلى مجموعة الإعدادات:

```text
Settings API
    ↓
payment_gateways
    ↓
PaymentGatewaySettings
    ↓
PaymentGatewayRouter
    ↓
Paymob / Kashier / Cash on Delivery
```

المفاتيح الحساسة تحمل `is_secret=true`، وتظهر في API كقيمة `********` فقط. إذا أعاد مدير النظام القيمة المقنّعة نفسها فلن يتم استبدال السر المخزن. مفاتيح `.env` ما زالت fallback للترحيل، بينما الإعدادات غير الفارغة هي المصدر التشغيلي الأساسي.

بعد تشغيل migrations وseed يمكن إدارة الإعدادات عبر:

```text
GET /api/settings/groups/payment_gateways
PUT /api/settings/payment_gateways.paymob.enabled
PUT /api/settings/payment_gateways.kashier.enabled
```

يجب أن يكون المستخدم حاصلًا على `settings.view` للقراءة و`settings.update` للتعديل. يُنصح بقصر صلاحية `settings.update` على Owner/Admin وعدم منحها لموظفي الدعم.


## حماية API Keys والبيانات الحساسة

تمت إضافة `is_encrypted` إلى جدول الإعدادات. بيانات Paymob وKashier الحساسة تُخزّن باستخدام Laravel `Crypt` المعتمد على `APP_KEY`، ولا تظهر في API إلا كـ `********`. يتم فك التشفير داخل Infrastructure فقط عند إنشاء طلب إلى المزوّد أو التحقق من Webhook.

لا يتم استخدام hash أحادي الاتجاه لتخزين مفاتيح API؛ لأن البوابة تحتاج القيمة الأصلية عند كل طلب. يتم استخدام HMAC/hash فقط لإنشاء توقيعات الطلب والتحقق من Webhooks، حيث لا نحتاج لاسترجاع القيمة الأصلية.

| نوع البيانات | الحماية |
|---|---|
| Payment API Key وSecret Key وHMAC Secret | تشفير at rest عبر `Crypt` |
| Webhook signature | HMAC-SHA256 أو HMAC-SHA512 حسب المزوّد |
| Passwords | Hash أحادي الاتجاه عبر Laravel hashing |
| API response | Masking بالقيمة `********` |

يتطلب ذلك وجود `APP_KEY` ثابت وسري. تغييره يجعل القيم المشفرة القديمة غير قابلة للفك، لذلك يجب تدوير المفاتيح عبر خطة migration مخصصة وليس بتغييرها مباشرة.


## قاعدة شركات الشحن وإضافة Bosta

تم إنشاء عقد عام لشركات الشحن:

```text
ShippingProviderInterface
    ├── create
    ├── track
    ├── cancel
    └── supports
```

ويستخدم النظام `ShippingProviderRouter` لاختيار الـ Adapter حسب `shipping_method.carrier`، لذلك يبقى الـ Domain غير مرتبط بـ Bosta أو أي شركة لاحقة.

تمت إضافة `BostaShippingProvider` لتنفيذ:

- إنشاء Delivery عبر `POST /api/v2/deliveries?apiVersion=1`.
- إرسال بيانات المستلم والعنوان والـ COD وبيانات الطرد.
- حفظ Bosta delivery ID ورقم التتبع محليًا.
- التتبع عبر `POST /api/v2/deliveries/search`.
- الإلغاء عبر terminate endpoint.
- إعادة المحاولة بأمان باستخدام `idempotency_key` المحلي و`provider_reference`.

تمت إضافة Webhook:

```text
POST /api/v1/webhooks/bosta
```

ويتم حمايته عبر Custom Header وقيمة سرية قابلة للتعديل من Settings، ثم تحويل حالات Bosta إلى الحالات المحلية (`picked_up`, `in_transit`, `out_for_delivery`, `delivered`, `cancelled`).

إعدادات Bosta موجودة في مجموعة:

```text
shipping_providers
```

وأهمها:

```text
shipping_providers.bosta.enabled
shipping_providers.bosta.api_key
shipping_providers.bosta.base_url
shipping_providers.bosta.webhook_url
shipping_providers.bosta.webhook_auth_header
shipping_providers.bosta.webhook_auth_value
shipping_providers.bosta.delivery_type
shipping_providers.bosta.package_type
```

يجب إنشاء Shipping Method بقيمة `carrier=bosta`، ثم تفعيل `shipping_providers.bosta.enabled`. مفاتيح API وWebhook السرية مشفرة في قاعدة البيانات عند استخدام Seeder والإعدادات الجديدة.

المراجع:

[11]: https://docs.bosta.co/docs/how-to/create-your-first-delivery/ "Bosta Create Delivery"

[12]: https://docs.bosta.co/docs/how-to/get-your-api-key/ "Bosta API Key"

[13]: https://docs.bosta.co/docs/how-to/get-delivery-status-via-webhook/ "Bosta Delivery Status Webhook"


## External API وDB Transaction: التصميم الموزع

لا يتم وضع استدعاء Paymob أو Kashier أو Bosta داخل Database Transaction. العملية أصبحت مقسمة إلى:

```text
pending
    ↓
processing
    ↓
provider_created
    ↓
confirmed
```

وعند فشل مؤكد من المزوّد تصبح:

```text
failed
```

أما timeout أو network failure أو فشل تحديث محلي بعد نجاح خارجي، فيبقى السجل في `processing` مع `reconciliation_required` بدل ادعاء أن العملية فشلت. هذا يمنع تحصيل المال مرتين ويترك العملية قابلة للإعادة بنفس `idempotency_key`.

تمت إضافة جداول durable:

```text
payment_operations
shipment_operations
```

وتسجل كل واحدة:

- عدد المحاولات.
- حالة العملية.
- provider reference.
- response payload.
- آخر خطأ.
- وقت retry التالي.
- مفتاح idempotency.

### Webhook Recovery

إذا نجح Paymob أو Kashier أو Bosta ثم فشل تحديث قاعدة البيانات، لا يتم اعتبار Webhook منتهيًا بمجرد استلامه. يبقى الحدث `received`، وتقوم إعادة Webhook بمعالجة الحدث مرة أخرى. لا يتم تجاهل الحدث إلا بعد تسجيله `processed` داخل نفس المسار المحلي.

### Retry

إعادة طلب إنشاء Payment أو Shipment بنفس `idempotency_key` لا تنشئ عملية جديدة. Payment الموجود في `processing` يعيد المحاولة، أما `provider_created` أو `confirmed` فيُعاد كما هو. وبالنسبة للشحن، تتم إعادة محاولة dispatch فقط عندما لا يوجد `provider_reference`.

تم تجهيز `next_retry_at` في جداول العمليات لتوصيلها لاحقًا بـ Queue Job أو Outbox Worker دون تغيير الـ Domain contracts. المرحلة التالية للإنتاج هي إضافة Outbox Transactional Event وQueue Worker يعتمد على هذه السجلات، مع reconciliation polling للمزوّدين الذين لا يرسلون Webhook.


## الإصلاحات الحرجة المضافة

تمت إضافة `outbox_events` كـ Transactional Outbox، ويتم إنشاء intent الدفع أو الشحن داخل نفس معاملة Claim/Creation المحلية. تمت إضافة `outbox:dispatch` لإعادة إرسال الأحداث إلى Queue، مع `ProcessOutboxEvent` وBackoff وعدد محاولات وحد زمني لاستعادة الأحداث التي بقيت في `processing` بعد توقف Worker.

تمت إضافة Reconciliation دورية:

```text
payments:reconcile   كل 5 دقائق
shipments:reconcile  كل 10 دقائق
outbox:dispatch       كل دقيقة
```

وتستخدم Paymob Transaction Inquiry وKashier Order Inquiry وBosta Tracking API لتصحيح الحالات التي لم يصل Webhook الخاص بها.

تمت إضافة `PaymentStateMachine` و`ShipmentStateMachine` لمنع الانتقالات غير الصحيحة مثل عودة `confirmed` إلى `processing` أو عودة الشحنة من `delivered` إلى `in_transit`.

تمت إضافة `shipping_webhook_events`، ويستخدم Bosta hash حتميًا للـ payload حتى لا تتم معالجة نفس الحدث مرتين. يبقى الحدث `received` إذا فشل التحديث المحلي، ولا يتحول إلى `processed` إلا بعد اكتمال الإسقاط المحلي.

تمت حماية Refund بنفس نموذج العملية الموزعة؛ فإذا نجح Refund خارجيًا وفشل تحديث الدفع محليًا، تعيد المحاولة استخدام نتيجة Refund المحفوظة دون إرسال Refund ثانٍ.

لتشغيل التنفيذ في الإنتاج يجب تشغيل Queue Worker وجدولة Laravel:

```bash
php artisan queue:work --tries=5
php artisan schedule:work
```

ويجب أن يكون `QUEUE_CONNECTION=database` أو Redis مضبوطًا، مع مراقبة `failed_jobs` و`outbox_events`.
