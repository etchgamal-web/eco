# جاهزية Paymob وKashier Sandbox

**تاريخ المراجعة:** 2026-10-06

## الحالة المختصرة

- اختبارات Checkout وواجهات الدفع المحلية ناجحة: **314 اختبارًا، 10,768 assertion**.
- اختبارات Adapter الجديدة تستخدم HTTP fakes؛ لذلك **لم تُجرَ معاملة فعلية** على Paymob أو Kashier Sandbox.
- تشغيل Sandbox الحقيقي متوقف حاليًا لعدم وجود مفاتيح/معرّفات اختبار لكلا المزوّدين في البيئة.
- `APP_URL` الحالي هو `http://localhost:8000`؛ لذلك رابط webhook الحالي محلي وغير قابل للوصول من المزوّد عبر الإنترنت.
- اكتشفت المراجعة أن قالب البيئة يضبط العودة إلى `/payment/return`، بينما صفحة النجاح الموجودة في Storefront هي `/checkout/success` وتتوقع `orderId`. محولا الدفع يرسلان رابط العودة المضبوط دون إضافة رقم الطلب؛ لذلك **مسار العودة إلى Storefront غير مكتمل بعد**.
- قبل Sandbox حقيقي يلزم: بيانات Test للمزوّد، عنوان HTTPS عام للـAPI/Storefront، وربط redirect الخاص بكل provider بصفحة `/checkout/success?orderId=<local-order-id>` بإضافة رقم الطلب وقت إنشاء الجلسة؛ ثم اختبار ذلك في Adapter tests.

## تصحيحات التكامل التي نُفذت

### Paymob

- الأسعار والمبالغ في النظام مخزنة ومعروضة بوحدة العملة الكاملة؛ مثال `1,500` يعني **1,500 EGP**.
- Paymob Intention API يتوقع `amount` بوحدة القروش/الوحدة الصغرى. أصبح التحويل ×100 مطبقًا على إجمالي الطلب، وعناصره، والشحن، ومبلغ الاسترداد.
- يرفض النظام نتيجة webhook أو reconciliation إذا لم تتطابق قيمة `amount_cents` أو `currency` مع الدفع المحلي.
- أُصلح توقيع تنفيذ verifier ليتوافق مع العقد، إذ كان اختلاف القيمة الافتراضية للوسيط يمنع تحميل معالج Paymob أصلًا.

### Kashier

- تستمر جلسة الدفع بإرسال القيمة بوحدة الجنيه وبصيغة عشرية، مثل `2500.00 EGP`؛ لا تُحوّل إلى قروش.
- تستخدم المصالحة الآن مسار Payment Session الرسمي: `GET /v3/payment/sessions/{sessionId}/payment`، وتتحقق من المبلغ والعملة قبل قبول الحالة.
- أصبح webhook يقرأ بنية `event` و`data` الحديثة، ويأخذ التوقيع من `x-kashier-signature`، ويعيد حساب HMAC-SHA256 من `signatureKeys` المرتبة وقيمها URL-encoded.
- تُعالج حالات `SUCCESS` و`FAILURE` و`PENDING` منفصلة؛ والحدث المكرر يرجع `409` كما توصي Kashier، من دون إنشاء أثر دفع ثانٍ.

### فصل Payment عن Order

نجاح الدفع يحدّث **حالة الدفع** فقط. يبقى الطلب `pending` إلى أن يجتاز مراجعة الطلب المعتادة؛ فـworkflow الطلبات لا يسمح بالانتقال المباشر من `pending` إلى `confirmed`. أزيلت محاولات الدفع والـreconciliation التي كانت تتجاوز هذا المسار وتؤدي إلى `409` وتراجع معاملة webhook.

## متطلبات التشغيل الحقيقي

لا تُرسل المفاتيح السرية في المحادثة. اضبطها في `.env` محليًا أو في مخزن إعدادات آمن، ولا ترفعها إلى Git.

### Paymob

المتغيرات اللازمة:

```dotenv
PAYMOB_ENABLED=true
PAYMOB_SECRET_KEY=
PAYMOB_PUBLIC_KEY=
PAYMOB_HMAC_SECRET=
PAYMOB_INTEGRATION_IDS=
PAYMOB_NOTIFICATION_URL=https://<public-api>/api/v1/webhooks/paymob
PAYMOB_REDIRECTION_URL=https://<public-store>/checkout/success
```

استخدم بيانات **TEST** فقط، وتأكد أن Integration ID وSecret Key من نمط البيئة نفسه.

### Kashier

```dotenv
KASHIER_ENABLED=true
KASHIER_API_BASE_URL=https://test-api.kashier.io
KASHIER_FEP_BASE_URL=https://test-fep.kashier.io
KASHIER_MERCHANT_ID=
KASHIER_SECRET_KEY=
KASHIER_PAYMENT_API_KEY=
KASHIER_WEBHOOK_URL=https://<public-api>/api/v1/webhooks/kashier
KASHIER_REDIRECT_URL=https://<public-store>/checkout/success
```

استخدم مفاتيح وMID الخاصة ببيئة **Test**، واجعل روابط الـwebhook والعودة HTTPS عامة وقابلة للوصول من المزوّد.

## ترتيب اختبار Sandbox المقترح

1. توفير عنوان عام لخدمة API والواجهة، وضبط `APP_URL` وروابط العودة والـwebhook عليه.
2. قبل اختبار Paymob، حدّث adapter لإلحاق `orderId` المحلي برابط العودة؛ ثم اختبر: Preview → Confirm → إنشاء Order/Payment → Intention → Redirect إلى `/checkout/success?orderId=...` → نتيجة مزوّد الاختبار → webhook أو transaction inquiry → مراجعة حالة Payment في Laravel والواجهة.
3. التحقق من أن مبلغًا داخليًا مقداره `1,500 EGP` يُرسل إلى Paymob كـ`150,000` قرش، وأن webhook/reconciliation بالمبلغ أو العملة الخطأ لا يؤكد الدفع.
4. اختبر Kashier بالتسلسل نفسه، مع إلحاق `orderId` برابط `merchantRedirect`؛ ثم أعد إرسال webhook للتحقق من idempotency واستجابة `409` للحدث المكرر.
5. عدم اعتبار العودة من صفحة المزوّد أو إشارة المتصفح إثباتًا للدفع؛ المرجع الحاسم webhook موثّق أو قراءة حالة server-to-server.
6. بعد اكتمال المزوّدين، مراجعة حالة الدفع في Storefront مع الحفاظ على مراجعة الطلب المستقلة.

تذكر وثائق Kashier الرسمية بطاقة اختبار Mastercard `5123450000000008` مع تاريخ الانتهاء `06/25` وCVV `100` لنتيجة `APPROVED` في وضع الاختبار؛ تاريخ الانتهاء هنا trigger اختباري حرفي وليس تاريخ بطاقة حقيقيًا.

## التحقق المحلي

- `PaymentGatewayAdapterTest`: **7 اختبارات ناجحة** تغطي تحويل الوحدات، جلسة Kashier، المصالحة، توقيع webhook، التكرار، وحالات الدفع.
- مجموعة Laravel كاملة: **314 passed (10,768 assertions)**.
- `git diff --check`: ناجح.
- Storefront lint وproduction build: نجحا سابقًا بعد تحديث تدفق stale Preview.

## مصادر المزوّدين الرسمية

- [Paymob — Create Intention](https://developers.paymob.com/paymob-docs/intention-apis/create-intention): يحدد أن قيمة `amount` بوحدة cents.
- [Paymob — By Transaction ID](https://developers.paymob.com/paymob-docs/developers/transaction-inquiry-apis/transaction-inquiry/by-transaction-id): حقول `amount_cents` و`currency` و`success` و`pending` لقراءة النتيجة.
- [Kashier — Quick start](https://developers.kashier.io/docs/get-started/quickstart): إنشاء جلسة اختبار وبطاقة الاختبار.
- [Kashier — Create payment session](https://developers.kashier.io/docs/api-reference/payment-sessions/createPaymentSession): حقول الجلسة والاستجابة التي تتضمن `sessionUrl` و`_id`.
- [Kashier — Get payment session](https://developers.kashier.io/docs/api-reference/payment-sessions/getPaymentSession): قراءة الحالة والمبلغ والعملة server-to-server.
- [Kashier — Webhooks](https://developers.kashier.io/docs/webhooks) و[Webhook payloads](https://developers.kashier.io/docs/webhooks/payloads): بنية `data`، توقيع `signatureKeys`، رأس `x-kashier-signature`، وإرشادات idempotency.
- [Kashier — Test cards and testing](https://developers.kashier.io/docs/get-started/testing): بطاقات الاختبار ومشغلات النتائج.
