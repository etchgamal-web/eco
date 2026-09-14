<?php
namespace App\Modules\Staff;
use App\Modules\Staff\Domain\Contracts\AuditLogRepositoryInterface;use App\Modules\Staff\Domain\Contracts\StaffRepositoryInterface;use App\Modules\Staff\Infrastructure\Persistence\EloquentAuditLogRepository;use App\Modules\Staff\Infrastructure\Persistence\EloquentStaffRepository;use Illuminate\Support\ServiceProvider;
final class StaffServiceProvider extends ServiceProvider{public array $bindings=[StaffRepositoryInterface::class=>EloquentStaffRepository::class,AuditLogRepositoryInterface::class=>EloquentAuditLogRepository::class];}
