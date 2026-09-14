<?php
namespace App\Modules\Catalog\Application\UseCases\Brands;

use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;
use Illuminate\Support\Collection;

final class ListBrands
{
    public function __construct(private readonly BrandRepositoryInterface $brands) {}

    public function execute(): Collection
    {
        return $this->brands->all();
    }
}
