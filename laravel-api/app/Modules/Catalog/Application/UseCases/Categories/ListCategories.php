<?php
namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

final class ListCategories
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    public function execute(): Collection
    {
        return $this->categories->all();
    }
}
