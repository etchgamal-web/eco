<?php
namespace App\Modules\Staff\Domain\Contracts;
use App\Modules\Staff\Domain\ValueObjects\StaffData;
interface StaffRepositoryInterface
{
 public function list(): mixed;
 public function create(StaffData $data): object;
 public function update(object $staff,StaffData $data): object;
 public function delete(object $staff): void;
 public function find(int $id): object;
 public function activeOwnerCount(): int;
}
