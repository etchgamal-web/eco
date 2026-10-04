# واجهة متجر Eco

واجهة المتجر مبنية باستخدام Next.js 16 وReact، وتستخدم Laravel API v1 للكتالوج.

## التشغيل المحلي

```bash
npm ci
cp .env.example .env.local
npm run dev
```

المتغيرات:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_SITE_URL=http://localhost:3000
```

## التحقق

```bash
npm run build
npm run lint
```

## المرحلة الحالية

تم تنفيذ الصفحة الرئيسية الأولى بواجهة عربية RTL تشمل:

- هوية Eco ومقدمة المتجر.
- كتالوج متصل بـ `GET /api/v1/products`.
- البحث في المنتجات.
- حالات التحميل والخطأ وعدم وجود نتائج.
- بطاقات المنتجات والتصميم المتجاوب.
- metadata وOpen Graph باللغة العربية.

السلة، صفحة تفاصيل المنتج، تسجيل الدخول، وCheckout ستُنفذ على مراحل لاحقة بعد تثبيت تدفقات الكتالوج والـAPI في بيئة Staging.
