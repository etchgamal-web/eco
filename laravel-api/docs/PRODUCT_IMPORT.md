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
| `sku` | No | Variant SKU; generated automatically for a variant when empty |
| `price` | No | Required when variant columns are supplied; non-negative integer |
| `compare_at_price` | No | Optional variant comparison price |
| `weight` | No | Optional non-negative variant weight |
| `variant_status` | No | Variant status; defaults to `active` |
| `attribute_value_ids` | No | Comma-separated attribute value IDs for a variable product variant |

Extra columns are ignored. The first row must contain the column names. Column names are normalized, so `Brand ID` is accepted as `brand_id`.

> **SKU note:** SKU belongs to `ProductVariant` in the domain model, not to `Product`. When variant columns are supplied for a `variable` product, a variant is created. If `sku` is empty, it is generated uniquely from the product name (for example `SKU-T-SHIRT`, then `SKU-T-SHIRT-2`).

## Example CSV

```csv
name,type,status,slug,description,brand_id,category_id,sku,price,attribute_value_ids
Phone,simple,active,phone,Mobile phone,1,2,,,
T-Shirt,variable,draft,t-shirt,Cotton shirt,1,3,,1500,12|18
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
    "variants": 1,
    "rows": 2
  }
}
```
