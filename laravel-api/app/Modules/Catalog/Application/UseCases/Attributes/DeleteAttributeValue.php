<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;
use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class DeleteAttributeValue {public function __construct(private readonly AttributeValueRepositoryInterface $values){} public function execute(int $attributeId,int $id):void{$this->values->findOrFail($id,$attributeId);if($this->values->isUsed($id))throw new BusinessRuleException('An attribute value used by variants cannot be deleted.');$this->values->delete($id);}}
