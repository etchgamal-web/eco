# Product Excel Import

## Endpoint

```http
POST /api/v1/products/import
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

The authenticated user must have the `products.create` permission.

## Supported files

- `.xlsx` Excel workbooks (the first worksheet is read)
- `.csv` or `.txt` files opened/saved by Excel
- Maximum file size: **20 MB**
- Maximum rows: **5,000 products**

The import is **create-only**. If a provided slug already exists, the whole import is rolled back; no partial products are kept. When `slug` is empty, it is generated from `name` and made unique automatically (`phone`, `phone-2`, `phone-3`, ...).

## Required columns

| Column | Required | Description |
|---|---:|---|
| `name` | Yes | Product name, maximum 255 characters |
| `type` | Yes | `simple` or `variable` |
| `status` | Yes | Product status, maximum 50 characters |
| `slug` | No | URL slug; generated from `name` and made unique when empty |
| `description` | No | Product description |
| `brand_id` | No | Existing brand ID, positive integer |
| `category_id` | No | Existing category ID, positive integer |

Extra columns are ignored. The first row must contain the column names. Column names are normalized, so `Brand ID` is accepted as `brand_id`.

> **SKU note:** SKU belongs to `ProductVariant` in the current domain model, not to `Product`. This endpoint imports product records only. Variant/SKU import requires defining how Excel rows map to variants and their attribute values; it is intentionally not silently stored on the product.

## Example CSV

```csv
name,type,status,slug,description,brand_id,category_id
Phone,simple,active,phone,Mobile phone,1,2
T-Shirt,variable,draft,t-shirt,Cotton shirt,1,3
```

## Example request

```bash
curl -X POST https://api.example.com/api/v1/products/import \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@products.xlsx"
```

## Successful response

```json
{
  "data": {
    "created": 2,
    "rows": 2
  }
}
```
