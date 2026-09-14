<?php
namespace App\Modules\Catalog\Application\UseCases\Brands;
use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class DeleteBrand {public function __construct(private readonly BrandRepositoryInterface $brands){} public function execute(int $id):void{$this->brands->findOrFail($id);if($this->brands->hasProducts($id))throw new BusinessRuleException('A brand containing products cannot be deleted.');$this->brands->delete($id);}}
