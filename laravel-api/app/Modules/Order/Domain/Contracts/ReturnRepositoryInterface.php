<?php

namespace App\Modules\Order\Domain\Contracts;

interface ReturnRepositoryInterface
{
    public function createForCustomer(int $userId, int $orderId, array $data): object;

    public function listForCustomer(int $userId): iterable;

    public function listAll(): iterable;

    public function findForAdmin(int $id): object;

    public function approve(int $returnId): object;

    public function receive(int $returnId): object;

    public function inspect(int $returnId, bool $accepted, ?string $notes = null): object;

    public function reject(int $returnId, string $reason): object;

    public function findForWorkflow(int $returnId): object;

    public function markWorkflowAttempt(int $returnId): object;

    public function markRestocked(int $returnId): object;

    public function markRefundRequested(int $returnId): object;

    public function markCompleted(int $returnId, int $actualRefund): object;

    public function markWorkflowFailed(int $returnId, string $error): object;

    public function completeRefundForPayment(int $paymentId, int $actualRefund, ?int $returnId = null): void;
}
