<?php
namespace App\Modules\Order\Application\UseCases;
use App\Modules\Order\Domain\Contracts\ReturnRepositoryInterface;
final class ManageReturns
{
    public function __construct(private readonly ReturnRepositoryInterface $returns) {}
    public function submit(int $userId, int $orderId, array $data): object { return $this->returns->createForCustomer($userId, $orderId, $data); }
    public function customerList(int $userId): iterable { return $this->returns->listForCustomer($userId); }
    public function adminList(): iterable { return $this->returns->listAll(); }
    public function approve(int $id): object { return $this->returns->approve($id); }
    public function reject(int $id, string $reason): object { return $this->returns->reject($id, $reason); }
}
