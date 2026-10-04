# عقود API للوحة التحكم

هذا المستند يوضح العقود التي تعتمد عليها `admin-dashboard` في Laravel API v1.

## قواعد الاستجابة

- كل الاستجابات الناجحة تُغلف داخل المفتاح `data`.
- القوائم التقليدية تعيد مصفوفة داخل `data`.
- القوائم التي تستخدم pagination تعيد:

```json
{
  "data": {
    "items": [],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 0
    }
  }
}
```

- أخطاء التحقق تعيد HTTP `422`، وأخطاء الصلاحيات تعيد HTTP `403`.
- كل المعرفات الرقمية تستخدم `integer`، والتواريخ ISO 8601 أو `YYYY-MM-DD` حسب الحقل.

## إدارة الموظفين

| الطريقة | المسار | الصلاحية | الاستخدام |
|---|---|---|---|
| GET | `/api/v1/staff` | `assistants.view` | قائمة الموظفين |
| POST | `/api/v1/staff` | `assistants.create` | إنشاء موظف |
| PATCH/PUT | `/api/v1/staff/{staffId}` | `assistants.update` | تعديل موظف |
| DELETE | `/api/v1/staff/{staffId}` | `assistants.delete` | حذف موظف مع حماية آخر مالك |
| GET | `/api/v1/staff/{staffId}/audit` | `assistants.view` | سجل تدقيق الموظف |

### فلاتر سجل التدقيق

- `page`: رقم الصفحة، يبدأ من 1.
- `per_page`: من 1 إلى 50، والقيمة الافتراضية 20.
- `action`: بحث جزئي في اسم العملية، مثل `staff.updated`.
- `actor_id`: معرف المستخدم الذي نفذ العملية.
- `from` و`to`: نطاق تاريخي شامل حسب تاريخ إنشاء الحدث.

## الأدوار والصلاحيات

| الطريقة | المسار | الصلاحية | الاستخدام |
|---|---|---|---|
| GET | `/api/v1/roles` | `roles.view` | مصفوفة الأدوار والصلاحيات |
| PATCH/PUT | `/api/v1/roles/{roleId}/permissions` | `permissions.manage` | تحديث صلاحيات دور مخصص |
| PATCH | `/api/v1/roles/{roleId}/status` | `permissions.manage` | تفعيل أو تعطيل دور مخصص |
| GET | `/api/v1/roles/{roleId}/audit` | `roles.view` | سجل تدقيق الدور |

### تغيير حالة الدور

```json
{
  "is_active": false
}
```

لا يمكن تعطيل الأدوار النظامية أو المالك أو دور ما زال مرتبطًا بموظفين.

## المراقبة التشغيلية

| الطريقة | المسار | pagination |
|---|---|---|
| GET | `/api/v1/operational-alerts` | `items` و`meta` |
| GET | `/api/v1/orders/delayed` | `items` و`meta` |
| GET | `/api/v1/operational-alerts/{id}` | تفاصيل تنبيه |
| PATCH | `/api/v1/operational-alerts/{id}/acknowledge` | عنصر واحد |
| PATCH | `/api/v1/operational-alerts/{id}/resolve` | عنصر واحد |
| PATCH | `/api/v1/operational-alerts/bulk-acknowledge` | مصفوفة نتائج |
| PATCH | `/api/v1/operational-alerts/bulk-resolve` | مصفوفة نتائج |

إجراءات bulk تستخدم:

```json
{
  "alert_ids": [12, 13],
  "reason": "تمت مراجعة التنبيهات ومعالجة السبب"
}
```

## سجل التدقيق

كل حدث يتضمن عادة:

```json
{
  "id": 10,
  "actor_id": 2,
  "action": "role.permissions_updated",
  "target_type": "App\\Modules\\Auth\\Infrastructure\\Models\\Role",
  "target_id": 5,
  "metadata": {
    "before": [],
    "after": ["reports.view"]
  },
  "created_at": "2026-10-04T20:00:00Z",
  "actor": {
    "id": 2,
    "name": "مدير النظام"
  }
}
```

## OpenAPI

العقد الآلي موجود في [`openapi.json`](./openapi.json)، ويشمل مسارات سجل التدقيق، تغيير حالة الدور، ومخططات `AuditLogPage` و`PaginationMeta`.
