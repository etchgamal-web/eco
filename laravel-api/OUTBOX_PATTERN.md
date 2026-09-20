# Outbox Pattern

يستخدم الـ API جدول `outbox_events` لتسجيل side effects داخل نفس transaction التي تغيّر الـ aggregate. بذلك لا يتم فقدان رسالة إلى مزود الدفع أو الشحن أو قنوات التواصل إذا حدث crash بعد commit مباشرة.

## إنشاء event

لا تنشئ `OutboxEvent` مباشرة من الـ use case أو repository. استخدم العقد المشترك:

```php
$this->outbox->record(
    aggregateType: 'payment',
    aggregateId: $payment->id,
    eventType: 'payment.create.requested',
    deduplicationKey: 'payment:create:' . $idempotencyKey,
    payload: ['payment_id' => $payment->id],
);
```

يضمن `deduplication_key` أن إعادة الطلب لا تضيف event ثانية، ويجب أن يتم استدعاء `record` داخل نفس `DB::transaction` الخاصة بتغيير البيانات.
في دورة الطلب، `Checkout` ينشئ الطلب والدفع فقط ولا ينشئ Shipment ولا يتواصل مع شركة الشحن. إنشاء الشحنة قرار إداري بعد تأكيد الطلب عبر `POST /api/v1/orders/{orderId}/shipments`، المحمي بصلاحية `shipments.create`. هذا المسار ينشئ سجل الشحنة محليًا ويسجل Outbox event؛ أما استدعاء شركة الشحن فيحدث لاحقًا داخل `ProcessOutboxEvent` فقط.

## دورة التشغيل

يشغّل Laravel scheduler الأمر `outbox:dispatch` كل دقيقة. الأمر يقرأ الأحداث `pending` المستحقة، أو الأحداث `processing` التي انتهت مدة lease الخاصة بها، ثم يستخدم تحديثًا ذريًا مشروطًا لحجز كل event قبل إرسال `ProcessOutboxEvent` إلى queue. لذلك يمكن تشغيل أكثر من scheduler/worker دون تنفيذ نفس event بالتوازي.

قيمة lease الافتراضية خمس دقائق ويمكن ضبطها عبر `OUTBOX_LEASE_MINUTES`. مهلة الـ job الافتراضية دقيقتان (`OUTBOX_JOB_TIMEOUT_SECONDS=120`) بينما نافذة إعادة تسليم database queue الافتراضية ثلاث دقائق (`DB_QUEUE_RETRY_AFTER=180`). يجب أن تظل نافذة queue أكبر من timeout حتى لا يعيد queue تسليم job ما زال يعمل، وأن تكون lease أكبر من أطول استدعاء خارجي متوقع.

بعد النجاح تصبح الحالة `dispatched`. عند الفشل تعود إلى `pending` مع `attempt_count` و`next_attempt_at` و`last_error`. الـ Queue job لديه محاولة Laravel واحدة فقط؛ إعادة المحاولة تتم حصريًا بواسطة الـ Dispatcher والـ Outbox، حتى لا يتنافس Laravel retry مع إعادة enqueue من scheduler. بعد بلوغ `OUTBOX_MAX_ATTEMPTS` تتحول الحالة إلى `failed` وتظل قابلة للمراجعة من operational dashboard.

## إضافة نوع event جديد

1. أنشئ event عبر `OutboxEventRepositoryInterface::record` داخل transaction. جدول `outbox_events` يفرض `UNIQUE` على `deduplication_key`، لذلك الحماية من race condition موجودة على مستوى قاعدة البيانات وليس التطبيق فقط.
2. أضف handler في `app/Modules/Shared/Application/Jobs/ProcessOutboxEvent.php` أو انقل المعالجة إلى handler مستقل إذا كبر النوع.
3. اجعل التنفيذ idempotent عبر provider idempotency key أو operation record قبل استدعاء مزود خارجي.
4. أضف اختبارًا يثبت التسجيل مرة واحدة، والـ retry، وأن claim الثاني لا ينجح.

لا ترسل side effect خارجيًا داخل transaction الأساسية؛ الـ Outbox مسؤول عن الفصل بين commit المحلي والتنفيذ الخارجي.
