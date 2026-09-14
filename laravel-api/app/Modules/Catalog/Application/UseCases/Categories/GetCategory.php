<?php
namespace App\Modules\Catalog\Application\UseCases\Categories;

use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;

final class GetCategory
{
    public function __construct(private readonly CategoryRepositoryInterface $categories) {}

    public function execute(int $id): object
    {
        return $this->categories->findOrFail($id);
    }
}
