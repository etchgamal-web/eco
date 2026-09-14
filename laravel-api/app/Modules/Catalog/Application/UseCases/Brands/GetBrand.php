<?php
namespace App\Modules\Catalog\Application\UseCases\Brands;

use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;

final class GetBrand
{
    public function __construct(private readonly BrandRepositoryInterface $brands) {}

    public function execute(int $id): object
    {
        return $this->brands->findOrFail($id);
    }
}
