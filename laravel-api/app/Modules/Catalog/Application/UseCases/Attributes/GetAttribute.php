<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;

use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;

final class GetAttribute
{
    public function __construct(private readonly AttributeRepositoryInterface $attributes) {}

    public function execute(int $id): object
    {
        return $this->attributes->findOrFail($id);
    }
}
