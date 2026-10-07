# Storefront AEO/GEO Content Architecture

## الهدف

إنشاء طبقة محتوى قابلة للفهرسة والإجابة المباشرة، دون إضافة CMS أو ربط المحتوى بالـAPI قبل ثبات النموذج. كل صفحة محتوى يجب أن تنتج HTML دلاليًا من الخادم، مع metadata وstructured data متسقة مع الكيانات الموجودة في الكتالوج.

## حدود المرحلة الحالية

- لا يوجد CMS في هذه المرحلة.
- لا توجد مجلدات أو routes فارغة لمجرد استكمال شجرة مقترحة.
- مصدر المحتوى المؤقت سيكون TypeScript data modules ثابتة وقابلة للاستبدال لاحقًا بمستودع API/CMS.
- لا يُسمح لمحتوى خاص بالحساب أو السلة أو الدفع بالدخول إلى طبقة المحتوى العامة.

## التوزيع المقترح

```text
src/
├── features/
│   ├── catalog/                 # المنتجات والتصنيفات والعلامات الحالية
│   ├── search/                  # يُنشأ عند تنفيذ بحث المحتوى فعليًا
│   └── content/                 # يُنشأ مع أول صفحة محتوى فعلية
│       ├── guides/
│       ├── faqs/
│       ├── comparisons/
│       └── buying-guides/
├── shared/
│   ├── seo/                     # مكونات JSON-LD وmetadata الحالية
│   ├── structured-data/         # يُستخدم فقط عند وجود مجموعة structured data مشتركة فعلية
│   └── ui/                      # يُنشأ عند ظهور UI مشترك حقيقي
└── core/
    └── config/
```

> لا ننقل الملفات الحالية بين `src/lib` و`src/shared` في هذه المرحلة لتجنب refactor غير مطلوب. عند إضافة مكونات مشتركة جديدة، نستخدم `src/shared/seo` الحالية أو نضيف المجلد المطلوب فقط.

## عقود البيانات

### ContentEntity

```ts
export type ContentEntity = {
  slug: string
  title: string
  description: string
  excerpt?: string
  updatedAt?: string
  canonicalPath: string
  indexable: boolean
}
```

### Guide

```ts
export type Guide = ContentEntity & {
  kind: 'guide' | 'buying-guide'
  intro: string
  sections: Array<{
    heading: string
    paragraphs: string[]
    bullets?: string[]
  }>
  relatedCategorySlugs?: string[]
  relatedProductSlugs?: string[]
  faqs?: FAQItem[]
}
```

### FAQItem وFAQPage

```ts
export type FAQItem = {
  question: string
  answer: string
}

export type FAQPage = ContentEntity & {
  items: FAQItem[]
}
```

### Comparison

```ts
export type Comparison = ContentEntity & {
  left: {
    name: string
    summary: string
    strengths: string[]
    limitations: string[]
  }
  right: {
    name: string
    summary: string
    strengths: string[]
    limitations: string[]
  }
  recommendation: string
  relatedProductSlugs?: string[]
}
```

### روابط الكيانات

الربط يكون بالـslugs وليس بالأسماء المعروضة:

```text
Guide
  ├── relatedCategorySlugs[]  → /categories/[slug]
  └── relatedProductSlugs[]   → /products/[slug]

Comparison
  └── relatedProductSlugs[]   → /products/[slug]
```

عند غياب كيان مرتبط من الـAPI، لا نعرض رابطًا مكسورًا ولا نضيفه إلى structured data. المحتوى نفسه يظل قابلًا للعرض إذا كان `indexable` ومكتملًا.

## routes المخطط لها

تُنفذ بالترتيب التالي، route فعلي واحد في كل دورة:

1. `/guides`
2. `/guides/[slug]`
3. `/faq`
4. `/comparisons`
5. `/comparisons/[slug]`

كل route عام يحتاج:

- HTML دلاليًا يحتوي على `h1` ومحتوى مفيدًا.
- `title` و`description` خاصين بالمحتوى.
- canonical مطلق مبني من `NEXT_PUBLIC_SITE_URL`.
- Open Graph وTwitter metadata.
- Breadcrumb مرئي وBreadcrumbList JSON-LD.
- `noindex` عند عدم وجود المحتوى أو عدم صلاحيته للفهرسة.
- روابط داخلية إلى الكتالوج، وليس مجرد نصوص غير قابلة للنقر.

## مكونات AEO القابلة لإعادة الاستخدام

تُنشأ فقط عند تنفيذ أول صفحة تستخدمها:

- `FAQSection`: يعرض الأسئلة والأجوبة في HTML، ويمكنه إضافة FAQPage JSON-LD عند استيفاء الشروط.
- `ProductSpecifications`: جدول/قائمة دلالية لمواصفات المنتج، مع `dl/dt/dd` بدل نص مخفي.
- `KeyFacts`: نقاط مختصرة قابلة للاقتباس، مع `ul/li` وعناوين واضحة.
- `Breadcrumbs`: مسار تنقل مرئي، مصدره نفس البيانات المستخدمة في BreadcrumbList.
- `RelatedProducts`: روابط فعلية إلى صفحات المنتجات، ولا يعتمد على client-only loading.

المكونات لا تحمل بياناتها بنفسها؛ تستقبل عقودًا typed من route/content data وتعرضها بشكل صريح.

## قواعد GEO والكيانات

- الاسم المعروض للمنظمة: `siteConfig.name`.
- الرابط الأساسي: `env.siteUrl`.
- المنتج والتصنيف والعلامة: نفس `name` و`slug` القادمة من catalog API.
- المحتوى: اسم ثابت وslug ثابت، مع روابط canonical لا تتغير حسب query params.
- لا نكرر تعريفات متعارضة لنفس الكيان بين metadata وJSON-LD والنص المرئي.
- كل JSON-LD يجب أن يصف محتوى ظاهرًا فعلًا في HTML.

## سياسة المصدر المؤقت

يُفضّل وضع البيانات الثابتة في ملف typed واحد لكل نوع محتوى، مع واجهة repository صغيرة:

```ts
export interface ContentRepository {
  listGuides(): Promise<Guide[]>
  getGuide(slug: string): Promise<Guide | null>
  getFaqPage(): Promise<FAQPage>
  listComparisons(): Promise<Comparison[]>
  getComparison(slug: string): Promise<Comparison | null>
}
```

التنفيذ الأول يمكن أن يكون synchronous data module ملفوفًا في repository async، حتى لا تتغير routes عند نقل المصدر لاحقًا إلى API/CMS.

## معايير قبول أول صفحة فعلية

- route يعمل بدون JavaScript في HTML الأولي.
- صفحة صحيحة تعيد `200` وتحتوي على content-bearing HTML.
- slug غير موجود لا يدخل sitemap ولا يعرض محتوى نجاح.
- metadata وcanonical وOG/Twitter متسقة.
- JSON-LD صالح ومطابق للمحتوى المرئي.
- يوجد رابط داخلي واحد على الأقل إلى كيان كتالوج ذي صلة.
- `npm run lint` و`npm run build` ينجحان.
- يتم اختبار الاستجابة عبر HTTP قبل إضافة route التالية.

## الترتيب بعد اعتماد هذه المعمارية

1. تنفيذ repository وdata module لأول Guide واحد.
2. تنفيذ `/guides` و`/guides/[slug]`.
3. استخراج `Breadcrumbs` و`KeyFacts` عند أول استخدام حقيقي.
4. ربط الدليل بتصنيف ومنتجات موجودة فعليًا.
5. اختبار HTML وmetadata وJSON-LD والروابط.
6. تكرار النمط لـFAQ ثم Comparisons.
7. بعد ثبات المحتوى، مراجعة `no-store` و`revalidate` و`revalidateTag` للكتالوج والمحتوى.
