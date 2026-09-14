<?php
namespace App\Modules\Staff\Infrastructure\Persistence;
use App\Models\AuditLog;use App\Models\User;use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;
final class EloquentAuditLogRepository implements AuditLogRepositoryInterface{public function record(object $actor,string $action,string $targetType,?int $targetId,array $metadata=[]):void{AuditLog::query()->create(['actor_id'=>$actor->id,'action'=>$action,'target_type'=>$targetType,'target_id'=>$targetId,'metadata'=>$metadata]);}}
