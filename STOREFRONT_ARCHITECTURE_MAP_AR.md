# خريطة Clean Architecture لواجهة متجر Eco

## الهدف

نقل واجهة المتجر من prototype صغير إلى بنية قابلة للتوسع، بحيث تكون كل ميزة مستقلة، وتكون المكوّنات العامة قابلة لإعادة الاستخدام. المثال المطلوب هو أن تكون بطاقة المنتج في ملف واحد داخل مساحة `shared`، ثم تستخدمها صفحات المنتجات والبحث والمنتجات المقترحة والسلة بدل تكرار تصميم البطاقة داخل كل صفحة.

هذه الخريطة تخص `storefront` فقط. الـLaravel API موجود أصلًا بتقسيم Modules قريب من Clean Architecture، لذلك يكون دور المتجر هو استهلاك عقود API واضحة دون وضع منطق Laravel داخل مكوّنات React.

## الوضع الحالي

المتجر يستخدم route group باسم `app/(store)`، والصفحة الرئيسية مركبة من `src/features/home/components/StorefrontHome.tsx` مع فصل التنقل والـHero والمزايا والفوتر وشريط الإعلان. تم تنفيذ صفحات قائمة المنتجات وتفاصيل المنتج والسلة وتسجيل الدخول والتسجيل. ما زالت طبقة `application` وبعض adapters القديمة تحت `src/lib/api` تحتاج نقلًا تدريجيًا، كما أن Checkout والحساب الكامل والاختبارات الشاملة لم تكتمل.

أهم النواقص الحالية هي أن السلة المربوطة بـLaravel تعتمد على token بعد المصادقة ولا توجد شاشة Checkout، وأن صفحة الحساب والطلبات والعناوين والمفضلة غير مكتملة. بطاقة المنتج ما زالت تستخدم الشكل الزخرفي كـfallback ولم يتم تحويلها إلى `Next Image`، كما أن فلاتر التصنيف والعلامة تستخرج خياراتها من المنتجات المحملة بدل endpoints مستقلة. الـHero يقرأ CMS عند توفر صفحة `home` منشورة مع fallback ثابت.

## التقسيم المستهدف

التقسيم التالي هو النسخة المعتمدة بعد مراجعة التقييم المرفق. الهدف ليس نسخ Clean Architecture الخاصة بالـLaravel حرفيًا، بل تطبيق فصل عملي يناسب Next.js ويمنع تضخم المكوّنات.

```text
storefront/
├── app/
│   ├── (store)/
│   │   ├── page.tsx                         # الصفحة الرئيسية
│   │   ├── products/
│   │   │   ├── page.tsx                     # قائمة المنتجات
│   │   │   └── [slug]/page.tsx              # تفاصيل المنتج
│   │   ├── categories/[slug]/page.tsx       # منتجات التصنيف
│   │   ├── brands/[slug]/page.tsx           # منتجات العلامة
│   │   ├── cart/page.tsx                    # السلة
│   │   ├── checkout/page.tsx                # إتمام الطلب
│   │   ├── account/
│   │   │   ├── page.tsx                     # ملخص الحساب
│   │   │   ├── orders/page.tsx              # الطلبات
│   │   │   ├── addresses/page.tsx           # العناوين
│   │   │   └── wishlist/page.tsx            # المفضلة
│   │   └── landing/[slug]/page.tsx          # صفحات CMS المنشورة
│   ├── api/                                 # Route handlers عند الحاجة فقط
│   ├── layout.tsx                            # layout عام فقط
│   ├── loading.tsx                           # حالة تحميل عامة
│   ├── error.tsx                             # حد أخطاء عام
│   ├── not-found.tsx                         # 404 عامة
│   └── globals.css
│
├── src/
│   ├── core/
│   │   ├── config/
│   │   │   ├── env.ts                       # قراءة والتحقق من env
│   │   │   └── site.ts                      # إعدادات اسم المتجر والروابط
│   │   ├── http/
│   │   │   ├── client.ts                    # fetch موحد، الأخطاء، headers
│   │   │   └── errors.ts                    # ApiError موحد
│   │   ├── i18n/
│   │   │   ├── ar.ts                        # النصوص العربية
│   │   │   └── formatters.ts                # السعر، التاريخ، العملة
│   │   └── types/
│   │       └── common.ts                    # Pagination وResult وIDs
│   │
│   ├── application/
│   │   ├── catalog/
│   │   │   ├── catalog-repository.ts         # port لا يعرف Laravel
│   │   │   ├── list-products.ts              # use case خفيف
│   │   │   └── get-product.ts
│   │   ├── cart/
│   │   │   └── cart-repository.ts
│   │   └── checkout/
│   │       └── checkout-service.ts
│   │
│   ├── domain/
│   │   ├── catalog/
│   │   │   ├── product.ts                   # Product وProductVariant وProductMedia
│   │   │   ├── category.ts
│   │   │   └── brand.ts
│   │   ├── cart/
│   │   │   ├── cart.ts                      # Cart وCartItem وقواعد الكمية
│   │   │   └── pricing.ts                   # الإجماليات والخصومات
│   │   ├── customer/
│   │   │   ├── customer.ts
│   │   │   ├── address.ts
│   │   │   └── wishlist.ts
│   │   ├── order/
│   │   │   ├── order.ts
│   │   │   └── checkout.ts
│   │   └── content/
│   │       └── landing-page.ts              # Hero وCMS sections
│   │
│   ├── features/
│   │   ├── home/
│   │   │   ├── components/
│   │   │   │   ├── HomeHero.tsx
│   │   │   │   ├── BenefitsStrip.tsx
│   │   │   │   └── FeaturedProducts.tsx
│   │   │   ├── api.ts
│   │   │   └── useHomeContent.ts
│   │   ├── catalog/
│   │   │   ├── components/
│   │   │   │   ├── ProductGrid.tsx
│   │   │   │   ├── ProductFilters.tsx
│   │   │   │   ├── ProductSort.tsx
│   │   │   │   └── ProductDetails.tsx
│   │   │   ├── api.ts
│   │   │   └── queries.ts
│   │   ├── cart/
│   │   │   ├── components/
│   │   │   ├── store.ts
│   │   │   ├── api.ts
│   │   │   └── selectors.ts
│   │   ├── checkout/
│   │   │   ├── components/
│   │   │   ├── api.ts
│   │   │   └── validation.ts
│   │   ├── account/
│   │   │   ├── components/
│   │   │   └── api.ts
│   │   └── landing/
│   │       ├── components/SectionRenderer.tsx
│   │       ├── components/HeroSection.tsx
│   │       └── api.ts
│   │
│   ├── shared/
│   │   ├── components/
│   │   │   ├── ProductCard.tsx              # بطاقة المنتج المشتركة
│   │   │   ├── ProductPrice.tsx
│   │   │   ├── ProductImage.tsx
│   │   │   ├── QuantityStepper.tsx
│   │   │   ├── RatingStars.tsx
│   │   │   ├── Button.tsx
│   │   │   ├── Modal.tsx
│   │   │   ├── EmptyState.tsx
│   │   │   ├── ErrorState.tsx
│   │   │   ├── LoadingSkeleton.tsx
│   │   │   └── Pagination.tsx
│   │   ├── layout/
│   │   │   ├── SiteHeader.tsx
│   │   │   ├── SiteFooter.tsx
│   │   │   ├── AnnouncementBar.tsx
│   │   │   └── Container.tsx
│   │   ├── hooks/
│   │   │   ├── useDebounce.ts
│   │   │   └── useMediaQuery.ts
│   │   ├── lib/
│   │   │   ├── cn.ts
│   │   │   └── safe-storage.ts
│   │   ├── seo/
│   │   │   ├── metadata.ts
│   │   │   ├── product-schema.ts
│   │   │   ├── breadcrumb-schema.ts
│   │   │   ├── organization-schema.ts
│   │   │   └── canonical.ts
│   │   └── types/
│   │       └── ui.ts
│   │
│   └── infrastructure/
│       ├── api/
│       │   ├── client.ts                    # fetch موحد مع ApiError
│       │   ├── catalog-api.ts               # adapter لتنفيذ port الكتالوج
│       │   ├── landing-api.ts
│       │   ├── cart-api.ts
│       │   ├── checkout-api.ts
│       │   └── customer-api.ts
│       ├── storage/
│       │   └── cart-storage.ts
│       ├── repositories/
│       │   └── repository-factories.ts       # تركيب ports مع adapters
│       └── analytics/
│           └── storefront-events.ts
│
├── tests/
│   ├── unit/
│   ├── integration/
│   └── e2e/
└── docs/
    └── architecture.md
```

## قرارات إضافية معتمدة

### إزالة `app/components`

سيكون `app/` مسؤولًا عن routing وlayouts وloading وerror وnot-found وmetadata وتركيب الصفحة فقط. المكوّنات القابلة لإعادة الاستخدام تنتقل إلى `src/shared/components`، والمكوّنات الخاصة بميزة إلى `src/features/<feature>/components`. لا ننشئ مجلدًا عامًا ثالثًا باسم `app/components` حتى لا تتكرر أماكن المكوّنات.

### طبقة application خفيفة

سنضيف ports وuse cases فقط عندما يكون هناك فائدة حقيقية، مثل عزل catalog repository أو توحيد cart وcheckout. لن نضيف interfaces لكل دالة عرض أو نكرر DTOs بلا حاجة. `domain` لا يعرف React أو Next.js أو fetch، و`application` ينسق القواعد، بينما `infrastructure` ينفذ الاتصال الفعلي بـLaravel.

### تنظيم API داخل features

عند زيادة حجم الكتالوج، تكون الاستدعاءات منظمة كـ`features/catalog/api/list-products.ts` و`get-product.ts` و`get-category.ts`، وتكون hooks أو queries منفصلة عن API. حاليًا يمكن نقل الملفات تدريجيًا بدل إعادة تسميتها دفعة واحدة.

### استراتيجية السلة

القرار المعتمد هو **Hybrid cart**: الزائر يستخدم local cart، وبعد تسجيل الدخول يتم merge مع `customer/cart`، وعند Checkout يصبح Laravel هو المصدر النهائي للحقيقة. لا يعتمد حساب الإجماليات النهائية أو توفر المخزون على local state فقط.

### SEO

تنقل أدوات SEO الحالية إلى `src/shared/seo`، مع helpers مشتركة للـmetadata وcanonical وorganization وbreadcrumb وproduct schema. أما البيانات الخاصة بمنتج معين فتظل تحت `features/catalog/seo` إذا احتاجت منطقًا خاصًا.

## قواعد الاعتماد بين الطبقات

| الطبقة | مسؤوليتها | ما يمكنها استيراده |
|---|---|---|
| `app` | تعريف المسارات وتجميع الصفحة | `features`, `shared`, `domain` عبر واجهات واضحة |
| `application` | use cases وports الخفيفة | `domain` فقط، بلا React أو Laravel |
| `features` | حالات استخدام واجهة المتجر وعملياتها | `application`, `domain`, `shared`, وواجهات البنية التحتية |
| `domain` | الأنواع والقواعد التجارية الخفيفة | لا يستورد React ولا `fetch` ولا Next.js |
| `shared` | مكوّنات وأدوات عامة قابلة لإعادة الاستخدام | أنواع عامة فقط، ولا يعتمد على feature بعينه |
| `infrastructure` | Laravel API وlocal storage وanalytics | `domain` و`core` |
| `core` | HTTP وenv وformatters الأساسية | لا يعتمد على صفحات أو features |

قاعدة عملية مهمة: `ProductCard` يمكنه استقبال `Product` وcallbacks مثل `onAddToCart`، لكنه لا يجلب البيانات بنفسه ولا يعرف صفحة المنتجات أو صفحة البحث. جلب البيانات يتم في feature، وتمريرها للبطاقة يتم من الصفحة أو `ProductGrid`.

## مثال فصل بطاقة المنتج

بدل الوضع الحالي داخل `StorefrontHome.tsx`:

```tsx
products.map((product) => (
  <article className="product-card">...</article>
))
```

يصبح الاستخدام:

```tsx
<ProductGrid products={products} />
```

ثم:

```tsx
// src/features/catalog/components/ProductGrid.tsx
<ProductCard
  product={product}
  href={`/products/${product.slug ?? product.id}`}
  onAddToCart={onAddToCart}
/>
```

وتبقى كل تفاصيل البطاقة في:

```text
src/shared/components/ProductCard.tsx
```

وبذلك يمكن استخدام نفس البطاقة في الصفحة الرئيسية، قائمة المنتجات، نتائج البحث، صفحات التصنيفات، وصفحة العلامة التجارية.

## خطة النقل المرحلية

### المرحلة 0 — تثبيت الحدود

- إنشاء aliases في `tsconfig` مثل `@/core`, `@/application`, `@/domain`, `@/features`, `@/shared`, `@/infrastructure`.
- جعل `app/` يقتصر على routing وlayouts وحالات Next.js العامة، ومنع إضافة ملفات UI جديدة إلى `app/components`.
- إنشاء `core/http/client.ts` و`infrastructure/api/client.ts` أو اختيار اسم واحد واضح بدل تكرار `fetch`.
- تعريف `ApiError` ونتيجة pagination موحدة.
- نقل أدوات SEO الحالية إلى `shared/seo` مع الحفاظ على exports المتوافقة.
- عدم تغيير سلوك المتجر في هذه المرحلة.

### المرحلة 1 — تفكيك الصفحة الحالية

- نقل `ProductCard` إلى `src/shared/components/ProductCard.tsx`.
- نقل `ProductGrid` وحالات التحميل والخطأ إلى `src/features/catalog/components`.
- نقل الـHeader والـFooter وشريط الإعلان إلى `src/shared/layout`.
- نقل الـHero إلى `src/features/home/components/HomeHero.tsx`.
- جعل `app/(store)/page.tsx` مسؤولًا عن تركيب الصفحة فقط، ونقل الملف الحالي من `app/components`.

**الحالة:** مكتملة. تم نقل منطق الصفحة إلى `src/features/home/components/StorefrontHome.tsx`، وإنشاء مكونات `AnnouncementBar` و`SiteHeader` و`SiteFooter` و`HomeHero` و`BenefitsStrip`. أصبح `app/(store)/page.tsx` ملف تركيب فقط، وتم حذف `app/components`.

### المرحلة 2 — تأسيس الكتالوج

- إنشاء صفحة `/products`.
- إضافة صفحة `/products/[slug]`.
- استخدام endpoint المنتج المفرد والـvariants عبر `getProduct`.
- دعم وسائط المنتج (`media` أو `images`) مع fallback بصري عند عدم توفرها.
- إضافة التصنيف والعلامة والبحث والترتيب والتصفية والت pagination.

**الحالة:** مكتملة جزئيًا. تم تنفيذ `/products` عبر `ProductListing` مع البحث، وفلاتر التصنيف والعلامة، والترتيب، وحالات التحميل والخطأ وإعادة المحاولة، و`Pagination` مشتركة مرتبطة ببيانات `meta` القادمة من Laravel. تم تنفيذ `/products/[slug]` مع السعر والوصف والوسائط والـvariants والكمية. معاملات الفلترة تستخدم `category_id` و`brand_id` و`sort` الفعلية في Laravel. المتبقي: endpoints مستقلة للتصنيفات والعلامات، صور `Next Image`، وتحسينات SEO الخاصة بالمنتج.

### المرحلة 3 — بناء السلة والحساب

- اعتماد `local-first` للزائر عبر `features/cart/store.ts` و`persistence.ts`.
- مزامنة وmerge السلة مع `customer/cart` بعد المصادقة.
- اعتبار Laravel مصدر الحقيقة عند Checkout وإعادة التحقق من الأسعار والمخزون.
- إضافة تسجيل الدخول والتسجيل وحالة العميل.
- إضافة الحساب والعناوين والطلبات والمفضلة.

**الحالة:** مكتملة جزئيًا. تم تنفيذ local-first عبر `src/domain/cart/cart.ts` و`src/features/cart/store.tsx`، وربط `CartProvider` بالـlayout، وعدّاد السلة بالرأس، وزر الإضافة، وصفحة `/cart` مع تعديل الكميات والحذف والتفريغ. تم إنشاء Adapter لعقود `customer/cart` في `src/infrastructure/api/cart-api.ts`، وإضافة مصادقة العميل عبر `src/features/auth/auth-context.tsx` وصفحتي `/account/login` و`/account/register`. بعد تسجيل الدخول يتم دمج السلة المحلية ثم تفريغها بعد النجاح فقط. تم تنفيذ `/account` و`/account/orders` و`/account/orders/[id]` و`/account/addresses` و`/account/profile` و`/account/wishlist` و`/account/notifications`، مع عرض الطلبات وتفاصيلها وإلغاء الطلب في الحالات المسموحة وإدارة العناوين وتعديل بيانات العميل وإزالة عناصر المفضلة وتعليم الإشعارات كمقروءة عبر `customer-api.ts`. المتبقي: جلب السلة البعيدة إلى الواجهة، دمج العناصر المتعارضة بسياسة واضحة، وربط المفضلة مباشرة ببطاقات المنتجات.

### المرحلة 4 — Checkout

- نموذج العنوان والشحن.
- قراءة طرق الشحن النشطة من API.
- قراءة طرق الدفع المتاحة من إعدادات عامة آمنة.
- إنشاء الطلب بطريقة idempotent.
- redirect للدفع ومتابعة حالة العملية.
- صفحات نجاح وفشل وإعادة المحاولة.

**الحالة:** بدأت فعليًا. تم إنشاء `/checkout` وربطه بصفحة `/cart`، وتحميل عناوين العميل وطرق الشحن من Laravel، واختيار طريقة الدفع، وإرسال `address_id` و`payment_method` ومفاتيح idempotency إلى `customer/checkout`. تم إنشاء `/checkout/success` و`/checkout/failure` مع إعادة المحاولة، وتعديل Hybrid Cart بحيث تبقى العناصر المحلية بعد merge حتى نجاح Checkout، ثم تُفرغ بعد إنشاء الطلب. المتبقي: عرض رسوم الشحن المختارة داخل إجمالي الخادم، وتدفق متابعة حالة بوابات Paymob/Kashier بعد webhook.

### المرحلة 5 — الجودة والإطلاق

- اختبارات unit للدوال والقواعد.
- اختبارات integration لعملاء API.
- اختبارات E2E للتصفح والبحث والسلة والـCheckout.
- تحسين الصور وmetadata وstructured data.
- مراقبة الأخطاء والـanalytics دون إرسال بيانات حساسة.

## النقاط الناقصة في المتجر

| الأولوية | النقطة | الوضع الحالي | المطلوب |
|---|---|---|---|
| P0 | بنية الملفات | تم فصل route group وfeatures وshared وdomain وinfrastructure، وما زالت `application` غير مكتملة | نقل ما تبقى من `src/lib/api` وإضافة ports/use cases عند الحاجة |
| P0 | بطاقة المنتج | موجودة في `shared/components/ProductCard.tsx` وتدعم رابط المنتج، مع fallback زخرفي للصور | ربط صور Laravel عبر `Next Image` وإضافة إجراءات السلة عند الحاجة |
| P0 | صفحة تفاصيل المنتج | منفذة في `/products/[slug]` مع الوصف والسعر والوسائط والـvariants والكمية | تقييمات، structured data فعلي، والتحقق النهائي من المخزون قبل checkout |
| P0 | السلة | local-first منفذة في `/cart` مع Adapter لـ`customer/cart` ومزامنة بعد login | جلب السلة البعيدة، merge conflicts، والتحقق من السعر والمخزون في checkout |
| P0 | Checkout | `/checkout` ونتائج النجاح والفشل منفذة، مع العنوان وطرق الشحن وخيارات الدفع وidempotency وربط `customer/checkout`، وتفريغ السلة بعد النجاح فقط | عرض الإجمالي النهائي من الخادم، حالات الدفع الخارجية، وتفاصيل الشحن المختار |
| P1 | طبقة HTTP | client موحد في `src/core/http/client.ts` مع `ApiError` وBearer token وcredentials | timeouts، retry policy، ونقل API القديم إلى adapters المنظمة |
| P1 | صور المنتجات | تفاصيل المنتج تدعم `media/images`، والبطاقة تستخدم fallback CSS | `ProductImage` و`Next Image` وتحسين التحميل |
| P1 | التصنيفات والعلامات | فلاتر `category_id` و`brand_id` مرتبطة بـLaravel، والخيارات من المنتجات المحملة | endpoints عامة وخيارات مستقلة وصفحات التصنيف والعلامة |
| P1 | variants | اختيار الـvariant والسعر والمخزون الجزئي في تفاصيل المنتج | ربط كل variant بوسائطه والتحقق الخادمي الكامل |
| P1 | إعدادات المتجر العامة | إعدادات لوحة التحكم داخلية | public configuration آمن للعملة والشحن والدفع، مع عدم كشف secrets |
| P1 | المصادقة | login/register/logout وAuthProvider وBearer token منفذة | حماية الصفحات، refresh/session strategy، ورسائل validation التفصيلية |
| P1 | الحساب | مسارات الحساب والطلبات والعناوين والملف والمفضلة والإشعارات منفذة، مع القراءة والإضافة والحذف وتحديث الملف وتعليم الإشعار كمقروء وإلغاء الطلب في الحالات المسموحة | تعديل العناوين، تحسين حماية واجهات الحساب، وربط إجراءات إضافية حسب سياسة المنتج |
| P1 | البحث | submit search وpagination وempty state والفلاتر منفذة | debounce، حفظ query في URL، وفرز/فلترة أوسع |
| P2 | صفحات CMS | Hero `home` فقط | renderer للأقسام مثل banner وfeatured وbenefits وFAQ |
| P2 | التقييمات | غير موجودة في المتجر | عرض التقييمات وإرسالها للعميل الموثق |
| P2 | SEO | أدوات SEO موجودة في `src/lib/seo` وغير منظمة ضمن الخريطة | نقلها إلى `shared/seo` وربط metadata وProduct structured data فعليًا بكل route |
| P2 | sitemap/robots | ملفات موجودة وتحتاج ربطًا بالمصادر | توليد URLs فعلية للمنتجات والتصنيفات والصفحات المنشورة |
| P2 | analytics | غير مربوط بتفاعلات المتجر | events للعرض والبحث والإضافة للسلة والتحويل |
| P2 | accessibility | أساسيات موجودة | keyboard navigation وfocus states وlabels واختبار آلي |
| P2 | responsive UX | CSS أولي | اختبار الهاتف، السلة، checkout، والجداول الطويلة |
| P3 | الاختبارات | لا توجد suite للمتجر | unit/integration/e2e وcontract tests |
| P3 | الأداء | `cache: no-store` للكتالوج والـCMS | cache/revalidation حسب نوع البيانات وقياس Core Web Vitals |
| P3 | الحماية | endpoint العام مستهلك مباشرة من browser | CORS وrate limits ومراقبة أخطاء وتسريب معلومات API |

## علاقة نقاط لوحة التحكم بالمتجر

| شاشة لوحة التحكم | ما يجب أن يظهر في المتجر |
|---|---|
| الكتالوج والمنتجات | قائمة المنتجات، التفاصيل، الصور، variants، المخزون المتاح |
| التصنيفات والعلامات | navigation، صفحات التصنيف، الفلاتر، breadcrumb |
| إعدادات اللغة والعملة | locale والعملة وتنسيق السعر |
| إعدادات الشحن | طرق الشحن، الرسوم، المدة، شروط الشحن المجاني |
| بوابات الدفع | طرق الدفع العامة المتاحة فقط، دون مفاتيح سرية |
| Checkout | اشتراط الدخول، العنوان، الشحن، حالة الطلب |
| الصفحات الترويجية | Hero وbanner والأقسام المنشورة |
| العملاء والتقييمات | الحساب، التقييمات، المراجعة، الإشعارات |
| الطلبات والمرتجعات | تاريخ الطلب، التتبع، الإلغاء، طلب الإرجاع |
| التقارير والتحليلات | أحداث المتجر ومؤشرات التحويل، لا تعرض تقارير الإدارة للزائر |

## قرار التنفيذ

لا يُنصح الآن بإعادة تسمية كل الملفات دفعة واحدة. التنفيذ الآمن يبدأ بتقسيم الصفحة الرئيسية إلى مكوّنات مع الحفاظ على نفس الـAPI ونفس التصميم، ثم إضافة الاختبارات، وبعد ذلك بناء كل route جديد داخل feature مستقلة. أي مكوّن يستخدم في أكثر من feature ينتقل إلى `shared`، وأي مكوّن خاص بصفحة أو مجال يبقى داخل `features`.

## تقييم النقاط المرفقة

التقييم المرفق صحيح بدرجة **9/10**. تم اعتماد النقاط الخمس مع تعديلات عملية: إزالة `app/components`، إضافة `application` وports بشكل انتقائي، تنظيم API حسب العمليات عند توسع الميزة، اعتماد Hybrid Cart، ونقل SEO إلى `shared/seo`. التحفظ الوحيد هو عدم تطبيق abstraction صارمة على كل جزء من الواجهة؛ لأن ذلك سيضيف تعقيدًا قبل وجود حاجة فعلية.

## حالة التنفيذ

- [x] إضافة aliases للطبقات الأساسية في `tsconfig.json`.
- [x] إنشاء عميل HTTP موحد في `src/core/http/client.ts` مع `ApiError`.
- [x] نقل تنسيق السعر إلى `src/core/i18n/formatters.ts`.
- [x] إنشاء نموذج `Product` مستقل في `src/domain/catalog/product.ts`.
- [x] استخراج `ProductCard` إلى `src/shared/components/ProductCard.tsx`.
- [x] استخراج `ProductGrid` وحالات التحميل والخطأ والفراغ إلى `src/features/catalog/components/ProductGrid.tsx`.
- [x] ترحيل استدعاءات الكتالوج والـCMS إلى عميل HTTP المشترك.
- [x] نجاح `npm run build` و`npm run lint` بعد التغيير.
- [x] نقل `StorefrontHome` إلى route group `app/(store)` وإزالة `app/components` بالكامل.
- [x] نقل الـHeader والـFooter والـHero إلى `shared/layout` و`features/home`.
- [x] إنشاء `/products` و`/products/[slug]` مع البحث والفلاتر والترتيب وpagination.
- [x] إضافة `ProductDetails` مع الوسائط والـvariants والكمية.
- [x] إنشاء local-first cart وصفحة `/cart` وربط العداد والإضافة.
- [x] إنشاء Laravel cart adapter ومزامنة سلة الزائر بعد المصادقة.
- [x] إنشاء login/register/logout وAuthProvider وBearer token.
- [x] إنشاء `/account` وAdapter بيانات العميل والطلبات والعناوين.
- [x] إنشاء `/account/orders` لعرض طلبات العميل.
- [x] إنشاء `/account/addresses` لإضافة وحذف عناوين الشحن.
- [x] إنشاء `/account/orders/[id]` لعرض تفاصيل الطلب.
- [x] إنشاء `/account/profile` لتعديل بيانات العميل وتحديث جلسة المصادقة.
- [x] إنشاء `/account/wishlist` و`/account/notifications` وربطهما بعقود Laravel.
- [x] إنشاء `/checkout` وربطه بالسلة والعناوين وطرق الشحن و`customer/checkout`.
- [x] إنشاء صفحات نجاح وفشل Checkout وإعادة المحاولة، وضبط تفريغ السلة بعد نجاح الطلب فقط.
- [x] إضافة إلغاء الطلب للحالات `pending` و`reviewing` و`confirmed` مع تأكيد المستخدم وتحديث الحالة.
- [x] إضافة `Pagination` و`ProductListing` كمكونات قابلة لإعادة الاستخدام.
- [x] إضافة `src/app/robots.ts` و`src/app/sitemap.ts` وأدوات SEO الحالية ضمن الملفات الموجودة.
- [ ] إضافة اختبارات للمكونات وعميل API.
- [x] إضافة `loading.tsx` و`error.tsx` و`not-found.tsx` كحالات عامة لمسارات المتجر.
- [x] إضافة `ProductImage` مع `Next Image` وfallback آمن للصور المفقودة أو المعطوبة.
- [x] فصل الكتالوج إلى طبقتي `application` و`infrastructure` مع repository contract وfactories.
- [x] إضافة `src/core/config/env.ts` و`src/core/config/site.ts` كمصدر موحد لإعدادات البيئة والموقع.
- [x] فتح قراءات عامة آمنة للتصنيفات والعلامات مع إبقاء عمليات الإدارة محمية.
- [x] إنشاء صفحات `/categories/[slug]` و`/brands/[slug]` وربطها بفلترة المنتجات.
- [x] تنفيذ سياسة Hybrid Cart: دمج كميات الزائر، جلب السلة البعيدة، واعتماد Laravel كمصدر للحقيقة.
- [x] إضافة اختبارات API للصلاحيات ودورة حياة السلة، مع نجاح lint وbuild وaudit.

الحالة الحالية: **المرحلتان 0 و1 مكتملتان، والمرحلة 2 مكتملة وظيفيًا، والمرحلة 3 مكتملة وظيفيًا في الأساس، والمرحلة 4 منفذة جزئيًا، ومرحلة الجودة والإطلاق بدأت**. آخر commit مرفوع هو `c5584a3` بتاريخ 2026-10-06. تم الحفاظ على دعم RTL والواجهة العربية واتصال Laravel، ونجح `npm run lint` و`npm run build` و`npm audit` واختبارات API الأساسية. المتبقي ذو الأولوية هو الإجمالي النهائي والتحقق الخادمي في Checkout، تكامل بوابات الدفع، معالجة أخطاء مزامنة السلة، إعدادات المتجر العامة، SEO الديناميكي، اختبارات الواجهة وE2E.
