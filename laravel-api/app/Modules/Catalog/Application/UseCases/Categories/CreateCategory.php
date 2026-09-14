<?php
namespace App\Modules\Catalog\Application\UseCases\Categories;
use App\Modules\Catalog\Domain\ValueObjects\CategoryData;use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\DuplicateSlugException;use Illuminate\Support\Str;
final class CreateCategory {public function __construct(private readonly CategoryRepositoryInterface $categories){} public function execute(CategoryData $d): object{if($d->parentId!==null)$this->categories->findOrFail($d->parentId);$s=Str::slug($d->slug?:$d->name);if($this->categories->slugExists($s))throw new DuplicateSlugException($s);return $this->categories->create($d,$s);}}
