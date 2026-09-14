<?php
namespace App\Modules\Staff\Domain\Contracts;
interface AuditLogRepositoryInterface{public function record(object $actor,string $action,string $targetType,?int $targetId,array $metadata=[]):void;}
