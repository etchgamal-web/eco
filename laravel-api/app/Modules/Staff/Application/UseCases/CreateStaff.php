<?php
namespace App\Modules\Staff\Application\UseCases;
use App\Modules\Staff\Domain\ValueObjects\StaffData;use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;use App\Modules\Staff\Domain\Contracts\StaffRepositoryInterface;
final class CreateStaff{public function __construct(private readonly StaffRepositoryInterface $staff,private readonly AuditLogRepositoryInterface $audit){}public function execute(StaffData $data,object $actor){$created=$this->staff->create($data);$this->audit->record($actor,'staff.created',get_class($actor),$created->id,['roles'=>$data->roleSlugs]);return $created;}}
