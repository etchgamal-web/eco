<?php

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Domain\Contracts\ProductImportReaderInterface;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Exceptions\ProductImportException;
use App\Modules\Catalog\Domain\ValueObjects\ProductData;
use App\Modules\Shared\Domain\Contracts\TransactionManagerInterface;
use Illuminate\Support\Str;

final class ImportProducts
{
    private const REQUIRED_COLUMNS = ['name', 'type', 'status'];

    private const MAX_ROWS = 5000;

    public function __construct(
        private readonly ProductImportReaderInterface $reader,
        private readonly ProductRepositoryInterface $products,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    /**
     * @return array{created: int, rows: int}
     */
    public function execute(string $path, ?string $extension = null): array
    {
        return $this->transactions->run(function () use ($path, $extension): array {
            $created = 0;
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

                $this->createProduct($row, $rows);
                $created++;
            }

            if (! $headersChecked) {
                throw ProductImportException::invalidHeaders(self::REQUIRED_COLUMNS);
            }

            return ['created' => $created, 'rows' => $rows];
        });
    }

    private function createProduct(array $row, int $rowNumber): void
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

        foreach (['brand_id', 'category_id'] as $foreignKey) {
            if (($row[$foreignKey] ?? '') !== '' && filter_var($row[$foreignKey], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                throw ProductImportException::invalidRow($rowNumber, "{$foreignKey} must be a positive integer");
            }
        }

        $productData = new ProductData(
            name: $name,
            slug: $slug !== '' ? $slug : null,
            description: trim((string) ($row['description'] ?? '')) ?: null,
            type: $type,
            status: $status,
            brandId: $this->nullableInteger($row['brand_id'] ?? null),
            categoryId: $this->nullableInteger($row['category_id'] ?? null),
        );
        $normalizedSlug = Str::slug($productData->slug ?: $productData->name);
        if ($productData->slug === null) {
            $normalizedSlug = $this->uniqueSlug($normalizedSlug ?: 'product');
        } elseif ($normalizedSlug === '' || $this->products->slugExists($normalizedSlug)) {
            throw ProductImportException::invalidRow($rowNumber, "slug [{$normalizedSlug}] is empty or already in use");
        }

        $this->products->create($productData, $normalizedSlug);
    }

    private function uniqueSlug(string $base): string
    {
        $candidate = $base;
        $suffix = 2;
        while ($this->products->slugExists($candidate)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
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
        return array_combine(
            array_map(static fn ($key): string => strtolower(trim((string) $key)), array_keys($row)),
            array_values($row),
        ) ?: [];
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null || trim((string) $value) === '' ? null : (int) $value;
    }
}
