<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;
use App\Modules\Catalog\Domain\ValueObjects\AttributeData;use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class CreateAttribute {public function __construct(private readonly AttributeRepositoryInterface $attributes){} public function execute(AttributeData $d): object{if($this->attributes->nameExists($d->name))throw new BusinessRuleException('The attribute name is already in use.');return $this->attributes->create($d);}}
