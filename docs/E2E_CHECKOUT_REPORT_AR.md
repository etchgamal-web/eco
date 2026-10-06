# تقرير اختبار E2E لـ Checkout

> التاريخ: 2026-10-06
>
> البيئة: Laravel API محلي + Storefront Next.js عبر متصفح Sandbox

## نطاق الاختبار

تم اختبار الرحلة الفعلية التالية:

1. تحميل قائمة المنتجات من Laravel عبر Server Component.
2. فتح صفحة المنتج باستخدام `slug`.
3. تسجيل العميل واستعادة السلة الموثقة.
4. إضافة المنتج إلى السلة ومزامنته مع Laravel.
5. مراجعة السلة والانتقال إلى Checkout.
6. تحميل عنوان العميل وطريقة الشحن.
7. إرسال Checkout بالدفع عند الاستلام ومفتاح idempotency.
8. تفريغ السلة بعد نجاح إنشاء الطلب.
9. عرض صفحة النجاح وحالة الدفع `pending`.
10. قراءة تفاصيل الطلب من API ومقارنة الإجمالي الخادمي.

## النتائج

| السيناريو | النتيجة |
|---|---|
| Server-first catalog | ناجح بعد دعم Laravel paginator (`data.data`) |
| Product detail by slug | ناجح بعد إضافة lookup عام بالـslug |
| Authenticated cart sync | ناجح |
| Shipping method selection | ناجح |
| Server-side subtotal | ناجح؛ تم حفظ `subtotal_amount` من أسعار Laravel |
| Server-side shipping | ناجح؛ تم حفظ رسم الشحن `500 EGP` |
| Server-side tax | ناجح؛ تم احتساب `14%` خادميًا (`630 EGP` للعنصر الاختباري) |
| Create order | ناجح |
| Cart clear after order | ناجح؛ العداد أصبح صفرًا |
| Payment state | ناجح؛ الحالة الظاهرة `pending` |
| Idempotency replay | ناجح؛ طلبان بنفس المفتاح أعادا نفس `order_id` ولم ينشئا طلبًا ثانيًا |

## ملاحظة التسعير المهمة

في الرحلة الفعلية كان إجمالي الواجهة التقديري قبل الضريبة `9,500 EGP`، بينما أعاد الخادم الإجمالي النهائي `10,760 EGP` بعد إضافة ضريبة `1,260 EGP` إلى طلب كميته 2.

تم تعديل نص الواجهة ليكون صريحًا:

> الإجمالي التقديري قبل الضريبة — سيعيد الخادم احتساب الضريبة والإجمالي النهائي عند تأكيد الطلب.

وهذا يحافظ على مبدأ أن المتصفح **ليس مصدر الحقيقة**. المتبقي كتحسين لاحق هو إضافة endpoint لـ **Checkout Preview** يعيد subtotal/discount/tax/shipping/total قبل الإنشاء، بحيث يرى العميل الإجمالي النهائي المتوقع قبل الضغط على التأكيد.

## إصلاحات كشفتها E2E

- عميل الكتالوج كان يدعم `data.items` فقط، بينما Laravel يعيد paginator في `data.data`; تم دعم الصيغتين.
- صفحة المنتج تستخدم slug، بينما API كان يقبل رقمًا فقط داخل Use Case; تم إضافة `findBySlugOrFail` مع الحفاظ على دعم المعرف الرقمي.
- متصفح Sandbox يحتاج `allowedDevOrigins` في Next dev حتى يعمل hydration والتفاعل عبر عنوان الخدمة العام.

## التحقق النهائي

- Laravel: **299 اختبارًا ناجحًا، 10,648 assertion**.
- Storefront: `npm run lint` ناجح.
- Storefront: `npm run build` ناجح.
- اختبار API مستقل لـ idempotency: نجح، وأعاد الطلب نفسه في المحاولة الثانية.

## الخطوة التالية

1. إضافة Checkout Preview خادمي قبل إنشاء الطلب.
2. اختبار Paymob/Kashier sandbox مع webhook وreconciliation.
3. بعد ذلك بدء SEO الديناميكي: metadata، canonical، Product schema، sitemap وrobots.
