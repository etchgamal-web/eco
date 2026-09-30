# Returns/Refund Follow-up

## الحالة

طبقة Returns وRefund Integration جاهزة وظيفيًا بعد ربطها بـ Outbox وPaymentOperation وreconciliation.

## الملاحظة التقنية غير الحاجزة

في `ReturnOutboxHandler` يُفضّل لاحقًا جعل:

```text
PaymentOperation.confirmed_amount
```

هو **المصدر الأساسي والوحيد للحقيقة** عند تسجيل:

```text
Return.actual_customer_refund
```

ويظل:

```text
Payment.metadata.refund_confirmed_amount
```

مجرد snapshot مساعد للعرض أو القراءة السريعة، وليس مصدرًا محاسبيًا مستقلًا.

## التحسين المقترح

عند إكمال Refund ناجح مباشرة داخل `ReturnOutboxHandler`:

1. قراءة `confirmed_amount` من سجل `PaymentOperation` الخاص بعملية `refund`.
2. استخدام القيمة نفسها في `Return.actual_customer_refund`.
3. استخدام metadata فقط كـ fallback توافق للبيانات القديمة، مع تسجيل warning عند استخدامه.
4. إضافة اختبار يثبت أن اختلاف metadata عن `PaymentOperation.confirmed_amount` لا يغيّر القيمة المحاسبية المعتمدة.

## سبب المتابعة

هذا يمنع وجود مصدرين مختلفين لمبلغ Refund ويضمن اتساق Returns وPayments وSettlement عند وجود Refund جزئي أو reconciliation متأخر.

**الأولوية:** تحسين معماري لاحق، وليس blocker لإغلاق Returns/Refund Integration.
