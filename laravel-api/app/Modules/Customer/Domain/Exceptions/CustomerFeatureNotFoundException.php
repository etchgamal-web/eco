<?php
namespace App\Modules\Customer\Domain\Exceptions;
use RuntimeException;
final class CustomerFeatureNotFoundException extends RuntimeException { public function __construct(string $feature,int $id){ parent::__construct("{$feature} {$id} was not found."); } }
