<?php
namespace App\Modules\Order\Domain\Contracts;
interface ReturnRepositoryInterface
{
    public function createForCustomer(int $userId, int $orderId, array $data): object;
    public function listForCustomer(int $userId): iterable;
    public function listAll(): iterable;
    public function approve(int $returnId): object;
    public function reject(int $returnId, string $reason): object;
}
