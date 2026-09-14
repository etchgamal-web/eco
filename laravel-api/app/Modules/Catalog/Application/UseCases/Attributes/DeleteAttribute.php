<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;
use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class DeleteAttribute {public function __construct(private readonly AttributeRepositoryInterface $attributes){} public function execute(int $id):void{$this->attributes->findOrFail($id);if($this->attributes->isUsed($id))throw new BusinessRuleException('An attribute used by variants cannot be deleted.');$this->attributes->delete($id);}}
