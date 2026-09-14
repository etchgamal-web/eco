<?php
namespace App\Modules\Catalog\Application\UseCases\Attributes;
use App\Modules\Catalog\Domain\ValueObjects\AttributeData;use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\BusinessRuleException;
final class UpdateAttribute {public function __construct(private readonly AttributeRepositoryInterface $attributes){} public function execute(int $id,AttributeData $d):object{$this->attributes->findOrFail($id);if($this->attributes->nameExists($d->name,$id))throw new BusinessRuleException('The attribute name is already in use.');return $this->attributes->update($id,$d);}}
