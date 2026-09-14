<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;
use App\Modules\Catalog\Domain\ValueObjects\AttributeValueData;use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class UpdateAttributeValue {public function __construct(private readonly AttributeValueRepositoryInterface $values){} public function execute(int $attributeId,int $id,AttributeValueData $d):object{$this->values->findOrFail($id,$attributeId);if($this->values->valueExists($attributeId,$d->value,$id))throw new BusinessRuleException('The attribute value already exists.');return $this->values->update($id,$d);}}
