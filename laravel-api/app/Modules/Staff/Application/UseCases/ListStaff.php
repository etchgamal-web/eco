<?php
namespace App\Modules\Staff\Application\UseCases;
use App\Modules\Staff\Domain\Contracts\StaffRepositoryInterface;
final class ListStaff{public function __construct(private readonly StaffRepositoryInterface $staff){}public function execute():mixed{return $this->staff->list();}}
