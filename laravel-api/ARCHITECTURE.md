# القاعدة المعمارية الإلزامية

كل مسار HTTP في هذا المشروع يجب أن يسير بالترتيب التالي دون تجاوز أي طبقة:

```text
Controller
    ↓
Form Request
    ↓
Use Case
    ↓
Domain Contract
    ↓
Infrastructure
    ↓
Domain Exception
    ↓
Central Error Handler
```

## مسؤولية الطبقات

| الطبقة | المسؤولية | الممنوعات |
|---|---|---|
| `Presentation/Http/Controllers` | استقبال الطلب، استخدام Form Request، تحويل المدخلات إلى DTO أو Value Object، استدعاء Use Case، وإرجاع JSON | قواعد العمل، Eloquent، `DB`، Repository، Contract، أو معالجة الاستثناءات |
| `Presentation/Http/Requests` | التحقق من مدخلات HTTP وتفويض الطلب | الوصول إلى قاعدة البيانات لتنفيذ عملية، أو تنفيذ قاعدة عمل |
| `Application/UseCases` | تنسيق حالة الاستخدام وتنفيذ العملية عبر عقد Domain | استدعاء Infrastructure أو Controllers أو Form Requests أو Facades وقاعدة البيانات مباشرة |
| `Domain/Contracts` | تعريف الواجهات التي يحتاجها التطبيق | تنفيذ قاعدة البيانات أو معرفة تفاصيل Laravel |
| `Infrastructure` | تنفيذ العقود والوصول إلى Eloquent وقاعدة البيانات والخدمات الخارجية | استدعاء Use Cases أو Controllers أو HTTP |
| `Domain/Exceptions` | تمثيل فشل قاعدة العمل أو عدم العثور على كيان | بناء استجابة HTTP أو استدعاء `abort()` |
| `bootstrap/app.php` | ترجمة Domain Exceptions وValidation وHTTP failures إلى استجابات API موحدة | نقل قواعد العمل إلى معالج الأخطاء |

## قواعد التنفيذ الصارمة

1. كل Action عام في Controller يجب أن يستقبل كائنًا منتهيًا بـ `Request`، حتى عمليات القراءة والحذف.
2. كل Controller يجب أن يعتمد على Use Case، وليس على Contract أو Repository.
3. لا يجوز لأي ملف في Application أو Domain استيراد `App\Models` أو `Infrastructure` أو Facades قاعدة البيانات.
4. لا يجوز لأي ملف Infrastructure استيراد Application أو Presentation.
5. لا يجوز إنشاء استجابة HTTP من Domain Exception.
6. كل فشل متوقع في قاعدة العمل يجب أن يخرج من Domain Exception.
7. الترجمة إلى HTTP status وJSON تتم مركزيًا من خلال `withExceptions` في `bootstrap/app.php`.
8. أي إضافة أو تعديل يكسر هذه القواعد يجب أن يفشل اختبار `StrictArchitectureTest`.

## التحقق

يحتوي `tests/Unit/StrictArchitectureTest.php` على حارس معماري يغطي جميع الوحدات الحالية، وليس قائمة ثابتة من الوحدات. يجب تشغيل الاختبار مع كل Pull Request، ولا يعد التغيير مكتملًا إذا فشل هذا الاختبار.
