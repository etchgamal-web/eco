# دليل Backup لقاعدة البيانات

## المبدأ

يتم تشغيل النسخ الاحتياطي من خلال أمر Laravel مستقل، ويختار تلقائيًا أداة النسخ بناءً على `DB_CONNECTION`. المحركات المدعومة هي `sqlite` و`mysql` و`pgsql`. تبقى بيانات الاتصال بقاعدة البيانات ومسار التخزين في متغيرات البيئة. لا تُحفظ كلمات مرور قواعد البيانات داخل جدول الإعدادات.

## الإعداد

يمكن اختيار محرك قاعدة البيانات من خلال `DB_CONNECTION`:

```env
DB_CONNECTION=sqlite # أو mysql أو pgsql
```

في الإنتاج يفضل استخدام MySQL أو PostgreSQL مُدار، مع تحديد مخزن خارجي مشفّر أو محمي بالصلاحيات:

```env
# مثال PostgreSQL؛ غيّر القيم حسب المحرك المختار
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecommerce
DB_USERNAME=ecommerce_backup
DB_PASSWORD=ضع_القيمة_في_مدير_الأسرار

BACKUP_DIR=/var/backups/ecommerce-platform
BACKUP_ENABLED=true
BACKUP_SCHEDULE=02:00
BACKUP_RETENTION_DAYS=14
```

بالنسبة إلى SQLite، يجب أن يشير `DB_DATABASE` إلى ملف قاعدة البيانات، مثل:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/ecommerce-platform/database/database.sqlite
```

يجب أن يكون `BACKUP_DIR` خارج مجلد المشروع، مع صلاحيات وصول لحساب التطبيق فقط، ويفضل أن يكون على تخزين خارجي مشفّر. بعد تحديث البيئة يجب تنفيذ `php artisan config:cache`.

## التحكم من Settings

يمكن للمسؤول ضبط القيم التالية من واجهة إعدادات النظام أو API الإعدادات:

| المفتاح | النوع | الوظيفة | القيمة الافتراضية |
|---|---|---|---|
| `backup.enabled` | boolean | تفعيل النسخ المجدول | `false` |
| `backup.schedule` | string | وقت النسخ بتوقيت الخادم بصيغة `HH:MM` | `02:00` |
| `backup.retention_days` | integer | مدة الاحتفاظ بالأيام | `14` |

حتى عند استخدام Settings، تظل قيم الاتصال (`DB_*`) و`BACKUP_DIR` في البيئة أو مدير الأسرار، ولا تُعرض عبر API الإعدادات.

## التشغيل

التشغيل اليدوي مع احترام حالة التفعيل:

```bash
php artisan backup:database
```

تشغيل إجباري لاختبار النسخ حتى لو كان معطّلًا في الإعدادات:

```bash
php artisan backup:database --force
```

فحص الإعدادات دون إنشاء ملف:

```bash
php artisan backup:database --force --dry-run
```

ينتج الأمر ملفًا مناسبًا للمحرك (`.dump` لـ PostgreSQL، أو `.sql.gz` لـ MySQL، أو `.sqlite` لـ SQLite) وملف `.sha256` مطابقًا له. يتم حذف الملفات الأقدم من مدة الاحتفاظ المحددة.

للتحقق من وجود نسخة حديثة وسلامة checksum:

```bash
php artisan backup:verify --max-age=48
```

يفشل الأمر إذا لم توجد نسخة، أو غاب checksum، أو فشل التحقق، أو تجاوز عمر أحدث نسخة الحد المحدد.

## الجدولة

يقوم Laravel Scheduler بتشغيل النسخ يوميًا في الوقت المحدد عندما تكون `backup.enabled=true`. يجب تثبيت cron التالي مرة واحدة على خادم الإنتاج:

```cron
* * * * * cd /var/www/ecommerce-platform && /usr/bin/php artisan schedule:run --no-ansi >> /var/log/ecommerce-scheduler.log 2>&1
```

بعد ذلك يمكن التحقق من الجدولة عبر:

```bash
php artisan schedule:list
```

ينبغي تشغيل `backup:verify` من نظام المراقبة أو cron مستقل، وإرسال تنبيه عند رمز خروج غير صفري.

## الاستعادة والاختبار

إنشاء Backup لا يثبت قابلية الاستعادة. يجب تنفيذ Restore تجريبي دوريًا على قاعدة منفصلة:

```bash
createdb ecommerce_restore_test
pg_restore --clean --if-exists --no-owner --no-privileges \
  --dbname=ecommerce_restore_test /var/backups/ecommerce-platform/ecommerce_YYYYMMDDTHHMMSSZ.dump
sha256sum --check /var/backups/ecommerce-platform/ecommerce_YYYYMMDDTHHMMSSZ.dump.sha256
```

يجب التحقق بعد الاستعادة من migrations والمنتجات والطلبات والمستخدمين والملفات المرتبطة، ثم توثيق تاريخ الاختبار ونتيجته. لا تُستبدل نسخة الإنتاج بعملية Restore قبل اختبارها على بيئة منفصلة.
