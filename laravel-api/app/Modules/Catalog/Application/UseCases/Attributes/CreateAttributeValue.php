<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;
use App\Modules\Catalog\Domain\ValueObjects\AttributeValueData;use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class CreateAttributeValue {public function __construct(private readonly AttributeRepositoryInterface $attributes,private readonly AttributeValueRepositoryInterface $values){} public function execute(AttributeValueData $d): object{$this->attributes->findOrFail($d->attributeId);if($this->values->valueExists($d->attributeId,$d->value))throw new BusinessRuleException('The attribute value already exists.');return $this->values->create($d);}}
