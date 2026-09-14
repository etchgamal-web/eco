<?php

namespace App\Modules\Staff\Application\UseCases;

use App\Modules\Staff\Domain\Contracts\StaffRepositoryInterface;

final class GetStaff
{
    public function __construct(private readonly StaffRepositoryInterface $staff)
    {
    }

    public function execute(int $id): object
    {
        return $this->staff->find($id);
    }
}
