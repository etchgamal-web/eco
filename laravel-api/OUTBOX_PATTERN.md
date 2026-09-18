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

## دورة التشغيل

يشغّل Laravel scheduler الأمر `outbox:dispatch` كل دقيقة. الأمر يقرأ الأحداث `pending` المستحقة، أو الأحداث `processing` التي انتهت مدة lease الخاصة بها، ثم يستخدم تحديثًا ذريًا مشروطًا لحجز كل event قبل إرسال `ProcessOutboxEvent` إلى queue. لذلك يمكن تشغيل أكثر من scheduler/worker دون تنفيذ نفس event بالتوازي.

بعد النجاح تصبح الحالة `dispatched`. عند الفشل تعود إلى `pending` مع `attempt_count` و`next_attempt_at` و`last_error`. وبعد استنفاد محاولات Laravel تتحول إلى `failed` وتظل قابلة للمراجعة من operational dashboard.

## إضافة نوع event جديد

1. أنشئ event عبر `OutboxEventRepositoryInterface::record` داخل transaction.
2. أضف handler في `app/Modules/Shared/Application/Jobs/ProcessOutboxEvent.php` أو انقل المعالجة إلى handler مستقل إذا كبر النوع.
3. اجعل التنفيذ idempotent عبر provider idempotency key أو operation record قبل استدعاء مزود خارجي.
4. أضف اختبارًا يثبت التسجيل مرة واحدة، والـ retry، وأن claim الثاني لا ينجح.

لا ترسل side effect خارجيًا داخل transaction الأساسية؛ الـ Outbox مسؤول عن الفصل بين commit المحلي والتنفيذ الخارجي.
