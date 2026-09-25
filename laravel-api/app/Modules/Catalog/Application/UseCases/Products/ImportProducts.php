<?php

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\ProductImportReaderInterface;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\ProductImportException;
use App\Modules\Catalog\Domain\ValueObjects\ProductData;
use App\Modules\Catalog\Domain\ValueObjects\ProductVariantData;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;
use Illuminate\Support\Str;
use Throwable;

final class ImportProducts
{
    private const REQUIRED_COLUMNS = ['name', 'type', 'status'];

    private const MAX_ROWS = 5000;

    public function __construct(
        private readonly ProductImportReaderInterface $reader,
        private readonly ProductRepositoryInterface $products,
        private readonly BrandRepositoryInterface $brands,
        private readonly CategoryRepositoryInterface $categories,
        private readonly CreateProductVariant $createVariant,
        private readonly AttributeValueRepositoryInterface $attributeValues,
        private readonly AuditLogRepositoryInterface $audit,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    /**
     * @return array{created: int, variants: int, rows: int}
     */
    public function execute(string $path, ?string $extension = null, ?object $actor = null): array
    {
        $result = $this->transactions->run(function () use ($path, $extension): array {
            $created = 0;
            $variants = 0;
            $rows = 0;
            $headersChecked = false;

            foreach ($this->reader->rows($path, $extension) as $row) {
                $rows++;
                if ($rows > self::MAX_ROWS) {
                    throw ProductImportException::invalidRow($rows, 'maximum of '.self::MAX_ROWS.' rows exceeded');
                }
                $row = $this->normalizeRow($row);
                if (! $headersChecked) {
                    $this->validateHeaders($row);
                    $headersChecked = true;
                }
                $variants += $this->createProduct($row, $rows);
                $created++;
            }

            if (! $headersChecked) {
                throw ProductImportException::invalidHeaders(self::REQUIRED_COLUMNS);
            }

            return ['created' => $created, 'variants' => $variants, 'rows' => $rows];
        });

        $this->audit->record($actor, 'catalog.products_imported', 'product_import', null, $result);

        return $result;
    }

    private function createProduct(array $row, int $rowNumber): int
    {
        $name = trim((string) ($row['name'] ?? ''));
        $type = strtolower(trim((string) ($row['type'] ?? '')));
        $status = trim((string) ($row['status'] ?? ''));
        $slug = trim((string) ($row['slug'] ?? ''));

        if ($name === '' || mb_strlen($name) > 255) {
            throw ProductImportException::invalidRow($rowNumber, 'name is required and must be at most 255 characters');
        }
        if (! in_array($type, ['simple', 'variable'], true)) {
            throw ProductImportException::invalidRow($rowNumber, 'type must be simple or variable');
        }
        if ($status === '' || mb_strlen($status) > 50) {
            throw ProductImportException::invalidRow($rowNumber, 'status is required and must be at most 50 characters');
        }
        if ($slug !== '' && mb_strlen($slug) > 191) {
            throw ProductImportException::invalidRow($rowNumber, 'slug must be at most 191 characters');
        }

        $brandId = $this->nullableInteger($row['brand_id'] ?? null);
        $categoryId = $this->nullableInteger($row['category_id'] ?? null);
        $this->validateRelations($brandId, $categoryId, $rowNumber);

        $productData = new ProductData(
            name: $name,
            slug: $slug !== '' ? $slug : null,
            description: trim((string) ($row['description'] ?? '')) ?: null,
            type: $type,
            status: $status,
            brandId: $brandId,
            categoryId: $categoryId,
        );
        $normalizedSlug = Str::slug($productData->slug ?: $productData->name);
        if ($productData->slug === null) {
            $normalizedSlug = $this->uniqueSlug($normalizedSlug ?: 'product');
        } elseif ($normalizedSlug === '' || $this->products->slugExists($normalizedSlug)) {
            throw ProductImportException::invalidRow($rowNumber, "slug [{$normalizedSlug}] is empty or already in use");
        }

        $product = $this->products->create($productData, $normalizedSlug);

        return $this->createVariantIfRequested($product, $row, $type, $name, $rowNumber);
    }

    private function createVariantIfRequested(object $product, array $row, string $type, string $name, int $rowNumber): int
    {
        $variantColumns = ['sku', 'price', 'compare_at_price', 'weight', 'variant_status', 'attribute_value_ids', 'variant_data'];
        if (count(array_filter($variantColumns, fn (string $column): bool => ($row[$column] ?? '') !== '')) === 0) {
            return 0;
        }
        if ($type !== 'variable') {
            throw ProductImportException::invalidRow($rowNumber, 'variant columns require a variable product');
        }

        $price = filter_var($row['price'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($price === false) {
            throw ProductImportException::invalidRow($rowNumber, 'price is required for an imported variant and must be a non-negative integer');
        }
        $sku = trim((string) ($row['sku'] ?? ''));
        $sku = $sku !== '' ? $sku : $this->uniqueSku($name);
        if ($this->products->skuExists($sku)) {
            throw ProductImportException::invalidRow($rowNumber, "sku [{$sku}] is already in use");
        }
        $attributeValueIds = $this->attributeValueIds($row['attribute_value_ids'] ?? null, $rowNumber);

        try {
            $this->createVariant->execute($product->id, new ProductVariantData(
                sku: $sku,
                price: $price,
                compareAtPrice: $this->nullableInteger($row['compare_at_price'] ?? null),
                weight: $this->nullableFloat($row['weight'] ?? null, $rowNumber),
                status: trim((string) ($row['variant_status'] ?? 'active')) ?: 'active',
                variantData: is_array($row['variant_data'] ?? null) ? $row['variant_data'] : null,
                attributeValueIds: $attributeValueIds,
            ));
        } catch (Throwable $exception) {
            throw ProductImportException::invalidRow($rowNumber, $exception->getMessage());
        }

        return 1;
    }

    private function validateRelations(?int $brandId, ?int $categoryId, int $rowNumber): void
    {
        try {
            if ($brandId !== null) {
                $this->brands->findOrFail($brandId);
            }
            if ($categoryId !== null) {
                $this->categories->findOrFail($categoryId);
            }
        } catch (Throwable $exception) {
            throw ProductImportException::invalidRow($rowNumber, $exception->getMessage());
        }
    }

    private function uniqueSlug(string $base): string
    {
        $candidate = $base;
        $suffix = 2;
        while ($this->products->slugExists($candidate)) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    private function uniqueSku(string $name): string
    {
        $base = 'SKU-'.Str::upper(Str::slug($name, '-'));
        $base = $base === 'SKU-' ? 'SKU-PRODUCT' : $base;
        $candidate = $base;
        $suffix = 2;
        while ($this->products->skuExists($candidate)) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    private function attributeValueIds(mixed $value, int $rowNumber): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', preg_split('/[,|]/', (string) $value) ?: []))));
        if (count($ids) !== count(array_filter(preg_split('/[,|]/', (string) $value) ?: [], static fn ($id): bool => filter_var(trim($id), FILTER_VALIDATE_INT) !== false))) {
            throw ProductImportException::invalidRow($rowNumber, 'attribute_value_ids must be a comma-separated list of positive integers');
        }
        try {
            if (count($ids) !== count($this->attributeValues->findMany($ids))) {
                throw ProductImportException::invalidRow($rowNumber, 'one or more attribute_value_ids do not exist');
            }
        } catch (ProductImportException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ProductImportException::invalidRow($rowNumber, 'one or more attribute_value_ids do not exist');
        }

        return $ids;
    }

    private function validateHeaders(array $row): void
    {
        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, array_keys($row)));
        if ($missing !== []) {
            throw ProductImportException::invalidHeaders($missing);
        }
    }

    private function normalizeRow(array $row): array
    {
        return array_combine(array_map(static fn ($key): string => strtolower(trim((string) $key)), array_keys($row)), array_values($row)) ?: [];
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null || trim((string) $value) === '' ? null : (int) $value;
    }

    private function nullableFloat(mixed $value, int $rowNumber): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        if (! is_numeric($value) || (float) $value < 0) {
            throw ProductImportException::invalidRow($rowNumber, 'weight must be a non-negative number');
        }

        return (float) $value;
    }
}
