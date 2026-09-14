<?php
namespace App\Modules\Promotion\Application\UseCases;
use App\Modules\Promotion\Domain\Contracts\CouponManagementRepositoryInterface;
final class ManageCoupons
{
    public function __construct(private readonly CouponManagementRepositoryInterface $coupons) {}
    public function list(): iterable { return $this->coupons->list(); }
    public function show(int $id): object { return $this->coupons->find($id); }
    public function store(array $data): object { return $this->coupons->create($data); }
    public function update(int $id, array $data): object { return $this->coupons->update($id, $data); }
    public function remove(int $id): void { $this->coupons->delete($id); }
}
