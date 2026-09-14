<?php
namespace App\Modules\Catalog\Application\UseCases\Categories;
use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class DeleteCategory {public function __construct(private readonly CategoryRepositoryInterface $categories){} public function execute(int $id):void{$this->categories->findOrFail($id);if($this->categories->hasProductsOrChildren($id))throw new BusinessRuleException('A category containing products or child categories cannot be deleted.');$this->categories->delete($id);}}
