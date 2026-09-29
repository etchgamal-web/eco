# Outbox Pattern

يستخدم الـ API جدول `outbox_events` لتسجيل side effects داخل نفس transaction التي تغيّر الـ aggregate. بذلك لا يتم فقدان رسالة إلى مزود الدفع أو الشحن أو قنوات التواصل إذا حدث crash بعد commit مباشرة.

## البنية الحالية

- **Domain contracts/data:** `app/Shared/Domain/Contracts` و`app/Shared/Domain/Data`
- **Persistence/processing:** `app/Shared/Infrastructure/Outbox`
- **Business handlers:** داخل كل module في `app/Modules/*/Application/Outbox`
- **Queue job:** `App\\Shared\\Infrastructure\\Outbox\\Processing\\ProcessOutboxEvent`
- **Dispatcher command:** `php artisan outbox:dispatch`

طبقة `Application` تعتمد على عقود `Shared\\Domain` فقط، بينما يبقى Eloquent داخل `Shared\\Infrastructure\\Outbox\\Persistence`.

## إنشاء event

استخدم العقد المشترك و`OutboxMessage`، ولا تنشئ `OutboxEvent` مباشرة من use case أو repository:

```php
use App\\Shared\\Domain\\Data\\OutboxMessage;

$this->outbox->add(new OutboxMessage(
    eventType: 'payment.create.requested',
    aggregateType: 'payment',
    aggregateId: $payment->id,
    payload: ['payment_id' => $payment->id],
    deduplicationKey: 'payment:create:'.$idempotencyKey,
));
```

يضمن `deduplication_key` أن إعادة الطلب لا تضيف event ثانية. يجب استدعاء `add()` داخل نفس `DB::transaction` الخاصة بتغيير البيانات. مسؤولية transaction تقع على الـrepository أو use case الذي يغيّر الـaggregate، وليس على provider الخارجي.

في دورة الطلب، `Checkout` ينشئ الطلب والدفع فقط ولا ينشئ Shipment ولا يتواصل مع شركة الشحن. إنشاء الشحنة قرار إداري بعد تأكيد الطلب عبر `POST /api/v1/orders/{orderId}/shipments`، المحمي بصلاحية `shipments.create`. هذا المسار ينشئ سجل الشحنة محليًا ويسجل Outbox event؛ أما استدعاء شركة الشحن فيحدث لاحقًا داخل handler.

## دورة التشغيل

يسجل Laravel scheduler الأمر `outbox:dispatch` كل دقيقة. الأمر يطلب من `OutboxRepositoryInterface::claim($limit)` حجز الأحداث المستحقة بشكل ذري ثم يرسل لكل event job من نوع `ProcessOutboxEvent` إلى queue.

كل claim ينشئ `claim_token` عشوائيًا ويرسله مع الـQueue job. ولا يمكن للـworker إتمام أو إفشال الحدث إلا إذا تطابق token المرسل مع token الحالي في قاعدة البيانات.

الحجز يعتمد على `lease_until` و`claim_token`:

- `pending` مع `next_attempt_at` مستحق.
- `processing` مع lease منتهٍ يمكن استعادته.
- تحديث الحجز مشروط بالحالة الحالية لمنع تنفيذ event نفسها بالتوازي.
- `markProcessed(eventId, claimToken)` و`markFailed(eventId, claimToken, error)` يرفضان أي كتابة من worker قديم بعد انتهاء lease وإعادة claim.
- الـPaymentOperation والـShipmentOperation يستخدمان lease token مستقلًا؛ تحديث aggregate أو operation بعد استدعاء provider يتطلب بقاء ملكية الـclaim والـoperation.
- event بدون handler لا تُعتبر ناجحة؛ تتحول إلى retry/dead-letter عبر `markFailed` مع تسجيل `event_type` في `last_error`.

قيمة lease الافتراضية خمس دقائق ويمكن ضبطها عبر `OUTBOX_LEASE_MINUTES`. مهلة الـjob الافتراضية دقيقتان (`OUTBOX_JOB_TIMEOUT_SECONDS=120`) بينما نافذة إعادة تسليم database queue الافتراضية ثلاث دقائق (`DB_QUEUE_RETRY_AFTER=180`). يجب أن تظل نافذة queue أكبر من timeout، وأن تكون lease أكبر من أطول استدعاء خارجي متوقع.

بعد النجاح تصبح الحالة `dispatched`. عند الفشل تعود إلى `pending` مع `attempt_count` و`next_attempt_at` و`last_error`. الـQueue job لديه محاولة Laravel واحدة فقط؛ إعادة المحاولة تتم حصريًا بواسطة Outbox، حتى لا يتنافس Laravel retry مع إعادة enqueue من scheduler. بعد بلوغ `OUTBOX_MAX_ATTEMPTS` تتحول الحالة إلى `failed` وتظل قابلة للمراجعة من operational dashboard وPrometheus metrics.

## إضافة نوع event جديد

1. أنشئ event عبر `OutboxRepositoryInterface::add(new OutboxMessage(...))` داخل transaction.
2. أضف handler داخل module المناسب في `app/Modules/<Module>/Application/Outbox`.
3. سجّل handler في service provider باستخدام `OutboxEventHandlerInterface` من `Shared\\Domain\\Contracts`.
4. اجعل التنفيذ idempotent عبر provider idempotency key أو operation record قبل استدعاء مزود خارجي.
5. إذا انتهت محاولة خارجية دون حفظ النتيجة محليًا، يجب استدعاء `recover` المخصص للمزود فقط؛ لا يجوز إعادة `create` تلقائيًا. إذا لم يدعم المزود lookup موثقًا، تتوقف العملية وتحتاج reconciliation يدويًا.
6. أضف اختبارات للتسجيل مرة واحدة، والـretry، والـclaim، وعدم إعادة `create` دون recovery آمن.

لا ترسل side effect خارجيًا داخل transaction الأساسية؛ الـOutbox مسؤول عن الفصل بين commit المحلي والتنفيذ الخارجي.
