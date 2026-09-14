<?php
namespace App\Modules\Staff\Application\UseCases;
use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;
final class RecordStaffAudit{public function __construct(private readonly AuditLogRepositoryInterface $audit){}public function execute(object $actor,string $action,?int $targetId,array $metadata=[]):void{$this->audit->record($actor,$action,get_class($actor),$targetId,$metadata);}}
