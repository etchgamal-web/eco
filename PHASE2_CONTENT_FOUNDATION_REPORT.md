# Phase 2 — Public Catalog Hardening + Content Foundation

## النطاق

تم تنفيذ **Public Catalog hardening** و**Content domain/API foundation** فقط. لم يتم تعديل `LandingPage`، ولم تتم إضافة Storefront routes/components أو Admin UI أو caching أو sitemap أو أي جزء من checkout/cart/payment/auth.

## الملفات الجديدة

### Content module

- `laravel-api/app/Modules/Content/ContentServiceProvider.php`
- `laravel-api/app/Modules/Content/Domain/Contracts/ContentRepositoryInterface.php`
- `laravel-api/app/Modules/Content/Domain/ValueObjects/ContentData.php`
- `laravel-api/app/Modules/Content/Application/UseCases/ListPublicContent.php`
- `laravel-api/app/Modules/Content/Application/UseCases/GetPublicContent.php`
- `laravel-api/app/Modules/Content/Application/UseCases/ManageContent.php`
- `laravel-api/app/Modules/Content/Infrastructure/Models/ContentItem.php`
- `laravel-api/app/Modules/Content/Infrastructure/Persistence/EloquentContentRepository.php`
- `laravel-api/app/Modules/Content/Presentation/Http/Controllers/ContentController.php`
- `laravel-api/app/Modules/Content/Presentation/Http/Requests/{PublicContentRequest,ContentReadRequest,ContentWriteRequest}.php`
- `laravel-api/routes/api/content.php`
- `laravel-api/database/migrations/2026_10_07_000001_create_content_tables.php`

### Public catalog hardening

- `laravel-api/app/Modules/Catalog/Application/UseCases/Products/{ListPublicProducts,GetPublicProduct,ListPublicProductVariants,GetPublicProductVariant}.php`

## Migration والعلاقات

Migration `content_items` تدعم:

- `type`: `article`, `guide`, `faq`, `comparison`
- `title`, `slug`, `excerpt`, `body`, `status`, `published_at`, `seo_title`, `seo_description`, `canonical_url`, `featured_image`
- `author_id` إلى `users`
- `parent_id` إلى `content_items` لعلاقة **FAQ → Content** دون polymorphic system

Pivot tables:

- `content_product`: Content → Products وComparison → Products
- `content_category`: Content → Categories

الاستعلام العام يستخدم `status = published` و`published_at IS NOT NULL`. والعلاقات العامة تعرض المنتجات `active` والتصنيفات `is_active` فقط.

## API endpoints الجديدة

كل المسارات تحت `/api/v1`:

### Public

- `GET /content?type=article|guide|faq|comparison`
- `GET /content/{slug}`

### Admin — يتطلب Sanctum

- `GET /admin/content` — `cms.view`
- `GET /admin/content/{id}` — `cms.view`
- `GET /admin/content/{id}/preview` — `cms.view`
- `POST /admin/content` — `cms.manage`
- `PUT/PATCH /admin/content/{id}` — `cms.manage`
- `POST /admin/content/{id}/publish` — `cms.manage`
- `POST /admin/content/{id}/unpublish` — `cms.manage`
- `DELETE /admin/content/{id}` — `cms.manage`

## Hardening للكتالوج العام

تم فصل public reads عن reads/operations الإدارية في `ProductRepositoryInterface` و`EloquentProductRepository`، وربط endpoints العامة بـ:

- `allPublic()` و`searchPublic()` — المنتجات `active` فقط
- `findPublicOrFail()` — يمنع تفاصيل المنتجات غير النشطة
- `publicVariants()` و`findPublicVariantOrFail()` — المنتج والـvariant يجب أن يكونا `active`

وبذلك تظل مسارات Storefront الحالية كما هي، لكن لا تعيد المنتجات غير النشطة.

## الصلاحيات

تمت إعادة استخدام صلاحيات CMS الموجودة مسبقًا: `cms.view` و`cms.manage`. لم تتم إضافة صلاحيات جديدة أو تغيير Rbac roles الحالية.

## الاختبارات المضافة

- `laravel-api/tests/Feature/PublicCatalogFilteringTest.php`
  - قائمة المنتجات العامة لا تعيد draft/inactive.
  - تفاصيل المنتج غير النشط 404.
  - قائمة variants العامة لا تعيد variant غير النشط.
- `laravel-api/tests/Feature/ContentFeatureTest.php`
  - المحتوى draft غير ظاهر في public API.
  - إنشاء Comparison وربطه بمنتج ثم نشره.
  - منع مستخدم بلا `cms.view` من Admin API.

## التحقق

- `git diff --check`: ناجح.
- تم فحص اتساق أسماء أصناف PSR-4 وعدم وجود imports للملفات المؤقتة.
- تعذر تشغيل `php -l` وLaravel/PHPUnit في sandbox الحالي لأن PHP و`laravel-api/vendor` غير متوفرين (`php: command not found`, `vendor-missing`).
- لم يتم تنفيذ commit أو push.
