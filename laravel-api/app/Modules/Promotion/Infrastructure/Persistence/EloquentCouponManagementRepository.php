<?php
namespace App\Modules\Promotion\Infrastructure\Persistence;
use App\Models\Coupon;
use App\Modules\Promotion\Domain\Contracts\CouponManagementRepositoryInterface;
use App\Modules\Promotion\Domain\Exceptions\CouponInvalidException;
final class EloquentCouponManagementRepository implements CouponManagementRepositoryInterface
{
    public function list(): iterable { return Coupon::query()->withCount('usages')->latest()->get(); }
    public function find(int $id): object { $coupon = Coupon::query()->withCount('usages')->find($id); if ($coupon === null) throw CouponInvalidException::forCode((string) $id); return $coupon; }
    public function create(array $data): object { return Coupon::query()->create(array_merge($data, ['code' => strtoupper($data['code'])])); }
    public function update(int $id, array $data): object { $coupon = $this->find($id); $coupon->update(array_merge($data, isset($data['code']) ? ['code' => strtoupper($data['code'])] : [])); return $coupon->fresh(); }
    public function delete(int $id): void { $this->find($id)->delete(); }
}
