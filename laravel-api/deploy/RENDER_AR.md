# تجهيز Laravel API للنشر على Render

تم تجهيز الـBackend ليعمل كخدمة Docker على Render مع PostgreSQL مُدار. ملفات الإعداد موجودة في `Dockerfile` و`deploy/render/`، بينما يوجد Blueprint كامل في جذر المستودع باسم `render.yaml`.

## طريقة الاستخدام

1. ادفع التغييرات إلى GitHub.
2. من Render اختر **New > Blueprint** واربط مستودع `eco`.
3. سيقرأ Render ملف `render.yaml` وينشئ:
   - خدمة `souqi-api` للـHTTP API.
   - عامل `souqi-worker` لمعالجة طوابير Laravel.
   - مهمة `souqi-scheduler` لتشغيل Scheduler كل دقيقة.
   - قاعدة PostgreSQL باسم `souqi-db`.
4. عند ظهور المتغيرات التي تحتوي `sync: false` أدخل القيم يدويًا.

## المتغيرات المطلوبة يدويًا

- `APP_KEY`: أنشئها محليًا بالأمر:

  ```bash
  php artisan key:generate --show
  ```

- `APP_URL`: رابط خدمة Render النهائي، مثل `https://souqi-api.onrender.com`.
- `CORS_ALLOWED_ORIGINS`: رابط الواجهة المنشورة فقط، مثل `https://souqi-admin.onrender.com`. لا تستخدم `*` مع تسجيل الدخول.

تُمرر قاعدة البيانات تلقائيًا عبر `DATABASE_URL` و`DB_URL` من قاعدة Render الداخلية، مع `DB_CONNECTION=pgsql` و`DB_SSLMODE=require`. لا تضع كلمات المرور أو مفاتيح الدفع في Git.

## الفحص بعد النشر

- `GET /up` لفحص الحياة الأساسي.
- `GET /ready` لفحص قاعدة البيانات والـcache والتخزين والـqueue.
- `POST /api/v1/auth/login` لاختبار تسجيل الدخول.

قبل استخدام بيانات حقيقية، راجع logs والـmigrations والـworker والـscheduler، ثم نفّذ اختبار smoke على بيئة staging. خدمة Render تحتاج أن تستمع على `0.0.0.0:10000`، وهو ما يفعله إعداد Nginx المرفق.

## ملاحظات مهمة

- خدمة الـAPI وقاعدة البيانات مضبوطة على `free` للتجربة، بينما الـworker والـscheduler مضبوطان على `starter` لأن Render لا يتيح عادةً تشغيلهما كخدمات مجانية. إذا لم تكن بحاجة للمهام المؤجلة مؤقتًا، احذف الخدمتين من Blueprint بدل ترقية الخطة.
- `FILESYSTEM_DISK=local` مناسب للمرحلة الأولى فقط. الصور والملفات الدائمة يجب نقلها إلى S3 أو object storage.
- لا تستخدم SQLite في Render للإنتاج.
- لا يتم تنفيذ النشر من هذا التغيير؛ الملفات فقط أصبحت جاهزة للربط من حساب Render.

## مراجع Render

- [نشر Laravel باستخدام Docker](https://render.com/docs/deploy-php-laravel-docker)
- [خدمات الويب والمنافذ وفحص الصحة](https://render.com/docs/web-services)
- [إنشاء والاتصال بـRender PostgreSQL](https://render.com/docs/postgresql-creating-connecting)
